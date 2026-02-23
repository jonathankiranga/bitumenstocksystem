<?php
/**
 * ErrorLogger.php - Database & Application Error Logging System
 * Logs all errors to JSON file with categorization (fatal vs non-fatal)
 * Supports performance optimization and error analytics
 */

class ErrorLogger {
    private $logFile;
    private $maxFileSize;
    private $logDir;
    private $isFatal = false;
    private $errorCategories = [
        'database_connection' => 'Database Connection',
        'database_query' => 'Database Query',
        'file_operation' => 'File Operation',
        'permission' => 'Permission Denied',
        'validation' => 'Validation Error',
        'system' => 'System Error',
        'fatal' => 'Fatal Error'
    ];
    
    public function __construct($logDir = null) {
        if ($logDir === null) {
            $logDir = dirname(__FILE__) . '/../logs';
        }
        
        $this->logDir = $logDir;
        $this->logFile = $this->logDir . '/errors_' . date('Y-m-d') . '.json';
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB per day
        
        // Create logs directory if not exists
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Log a database error
     * @param string $message Error message
     * @param string $category Error category (database_connection, database_query, etc.)
     * @param string $sql SQL query that failed (optional)
     * @param int $errorCode MySQL error code
     * @param bool $isFatal Whether this is a fatal error
     */
    public function logDatabaseError($message, $category = 'database_query', $sql = '', $errorCode = 0, $isFatal = false) {
        $this->isFatal = $isFatal;
        
        $error = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'database',
            'category' => $category,
            'severity' => $isFatal ? 'fatal' : 'warning',
            'message' => $message,
            'error_code' => $errorCode,
            'sql' => $this->redactSensitiveData($sql),
            'user_id' => isset($_SESSION['UserID']) ? $_SESSION['UserID'] : 'anonymous',
            'session_id' => session_id(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'remote_ip' => $this->getClientIP(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? ''
        ];
        
        return $this->writeLog($error, $isFatal);
    }
    
    /**
     * Log a system/PHP error
     * @param string $message Error message
     * @param string $file File where error occurred
     * @param int $line Line number
     * @param int $errorCode PHP error code
     * @param bool $isFatal Whether this is a fatal error
     */
    public function logSystemError($message, $file = '', $line = 0, $errorCode = 0, $isFatal = false) {
        $this->isFatal = $isFatal;
        
        $error = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'system',
            'category' => $this->categorizeErrorCode($errorCode),
            'severity' => $isFatal ? 'fatal' : 'warning',
            'message' => $message,
            'error_code' => $errorCode,
            'file' => $file,
            'line' => $line,
            'user_id' => isset($_SESSION['UserID']) ? $_SESSION['UserID'] : 'anonymous',
            'session_id' => session_id(),
            'remote_ip' => $this->getClientIP(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        return $this->writeLog($error, $isFatal);
    }
    
    /**
     * Log an application error
     * @param string $message Error message
     * @param string $context Context (e.g., 'invoice_creation', 'user_login')
     * @param array $details Additional details
     * @param bool $isFatal Whether this is a fatal error
     */
    public function logApplicationError($message, $context = '', $details = [], $isFatal = false) {
        $this->isFatal = $isFatal;
        
        $error = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'application',
            'category' => 'system',
            'severity' => $isFatal ? 'fatal' : 'warning',
            'context' => $context,
            'message' => $message,
            'details' => $details,
            'user_id' => isset($_SESSION['UserID']) ? $_SESSION['UserID'] : 'anonymous',
            'session_id' => session_id(),
            'remote_ip' => $this->getClientIP()
        ];
        
        return $this->writeLog($error, $isFatal);
    }
    
    /**
     * Write error to JSON log file
     */
    private function writeLog($error, $isFatal = false) {
        try {
            // Read existing errors
            $errors = [];
            if (file_exists($this->logFile)) {
                $content = @file_get_contents($this->logFile);
                if ($content) {
                    $errors = json_decode($content, true) ?: [];
                }
            }
            
            // Add new error
            $errors[] = $error;
            
            // Limit entries per day (keep last 10000)
            if (count($errors) > 10000) {
                $errors = array_slice($errors, -10000);
            }
            
            // Write to file
            $jsonContent = json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            @file_put_contents($this->logFile, $jsonContent);
            
            // Archive if file is too large
            if (filesize($this->logFile) > $this->maxFileSize) {
                $archiveFile = $this->logDir . '/errors_' . date('Y-m-d_H-i-s') . '.archive.json';
                @copy($this->logFile, $archiveFile);
                @file_put_contents($this->logFile, json_encode([$error], JSON_PRETTY_PRINT));
            }
            
            return true;
        } catch (Exception $e) {
            // Silently fail if we can't write log
            error_log('ErrorLogger write failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all errors for a specific date
     */
    public function getErrors($date = null, $severity = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $file = $this->logDir . '/errors_' . $date . '.json';
        
        if (!file_exists($file)) {
            return [];
        }
        
        $content = @file_get_contents($file);
        $errors = json_decode($content, true) ?: [];
        
        // Filter by severity if specified
        if ($severity !== null) {
            $errors = array_filter($errors, function($e) use ($severity) {
                return $e['severity'] === $severity;
            });
        }
        
        return array_values($errors);
    }
    
    /**
     * Get fatal errors
     */
    public function getFatalErrors($date = null) {
        return $this->getErrors($date, 'fatal');
    }
    
    /**
     * Get error statistics for a date
     */
    public function getStatistics($date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $errors = $this->getErrors($date);
        
        $stats = [
            'total_errors' => count($errors),
            'fatal_errors' => 0,
            'warning_errors' => 0,
            'by_category' => [],
            'by_type' => [],
            'by_hour' => [],
            'top_errors' => []
        ];
        
        $errorCounts = [];
        
        foreach ($errors as $error) {
            // Count by severity
            if ($error['severity'] === 'fatal') {
                $stats['fatal_errors']++;
            } else {
                $stats['warning_errors']++;
            }
            
            // Count by category
            $category = $error['category'] ?? 'unknown';
            $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + 1;
            
            // Count by type
            $type = $error['type'] ?? 'unknown';
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
            
            // Count by hour
            $hour = substr($error['timestamp'], 0, 13);
            $stats['by_hour'][$hour] = ($stats['by_hour'][$hour] ?? 0) + 1;
            
            // Track error messages for top errors
            $msg = $error['message'] ?? 'unknown';
            $errorCounts[$msg] = ($errorCounts[$msg] ?? 0) + 1;
        }
        
        // Sort and get top 10 errors
        arsort($errorCounts);
        $stats['top_errors'] = array_slice($errorCounts, 0, 10);
        
        return $stats;
    }
    
    /**
     * Is this a fatal error that should stop execution?
     */
    public function isFatal() {
        return $this->isFatal;
    }
    
    /**
     * Check if an error code is considered fatal
     */
    private function categorizeErrorCode($code) {
        switch ($code) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                return 'fatal';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return 'warning';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'notice';
            default:
                return 'unknown';
        }
    }
    
    /**
     * Redact sensitive data from SQL queries
     */
    private function redactSensitiveData($sql) {
        // Remove password values
        $sql = preg_replace('/password[\'\"]\s*[=:,]\s*[\'"]([^\'"]*)[\'\"]/i', 'password=***REDACTED***', $sql);
        // Remove email values
        $sql = preg_replace('/(\w+)[\'\"]\s*[=:,]\s*[\'"]([^\'"]*@[^\'"]*)[\'\"]/i', '${1}=***REDACTED***', $sql);
        // Limit length
        if (strlen($sql) > 500) {
            $sql = substr($sql, 0, 500) . '... (truncated)';
        }
        return $sql;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        return trim($ip);
    }
    
    /**
     * Clear old log files (older than N days)
     */
    public function cleanup($daysToKeep = 30) {
        $files = @glob($this->logDir . '/errors_*.json');
        if (!$files) return;
        
        $cutoffTime = strtotime("-$daysToKeep days");
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                @unlink($file);
            }
        }
    }
}

// Initialize global error logger
if (!isset($GLOBALS['errorLogger'])) {
    $GLOBALS['errorLogger'] = new ErrorLogger();
}
?>
