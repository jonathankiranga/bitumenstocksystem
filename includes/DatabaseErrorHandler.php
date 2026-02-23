<?php
/**
 * DatabaseErrorHandler.php - Wrapper for database errors
 * Handles silent/fatal error logic and logging integration
 * Must be included after ErrorLogger.php
 */

class DatabaseErrorHandler {
    private $logger;
    private $showTechnicalDetails = false; // Set to true only in development
    private $errorDisplay = 'silent'; // 'silent', 'popup', 'log_only'
    
    public function __construct($logger = null) {
        $this->logger = $logger ?: $GLOBALS['errorLogger'] ?? null;
        
        // Detect development mode from config
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->showTechnicalDetails = true;
            $this->errorDisplay = 'popup';
        }
    }
    
    /**
     * Handle database connection errors
     * @param string $host Database host
     * @param string $user Database user
     * @param string $error mysqli_connect_error()
     * @return bool Whether error is fatal
     */
    public function handleConnectionError($host, $user, $error) {
        // Log the error
        if ($this->logger) {
            $this->logger->logDatabaseError(
                'Database connection failed',
                'database_connection',
                "Host: $host, User: $user",
                0,
                true  // This is fatal
            );
        }
        
        // Log to standard PHP error log as well
        error_log("DATABASE CONNECTION ERROR: $error | Host: $host | User: $user");
        
        // Return JSON response if AJAX
        if ($this->isAjax()) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed',
                'technical_error' => $this->showTechnicalDetails ? $error : null
            ]);
            exit;
        }
        
        // Return true = fatal error
        return true;
    }
    
    /**
     * Handle database query errors
     * @param string $query SQL query that failed
     * @param string $error mysqli_error()
     * @param int $error_code mysqli_errno()
     * @param bool $fatal Whether this is a fatal error
     * @return bool Whether error is fatal
     */
    public function handleQueryError($query, $error, $error_code = 0, $fatal = false) {
        // Log the error
        if ($this->logger) {
            $this->logger->logDatabaseError(
                $error,
                'database_query',
                $query,
                $error_code,
                $fatal
            );
        }
        
        // Log to PHP error log
        error_log("DATABASE QUERY ERROR [$error_code]: $error | Query: " . substr($query, 0, 200));
        
        // For AJAX requests, always return JSON
        if ($this->isAjax()) {
            http_response_code($fatal ? 500 : 400);
            
            $response = [
                'success' => false,
                'error' => $fatal ? 'A database error occurred. Please try again.' : 'Database query failed',
                'type' => 'database_error'
            ];
            
            if ($this->showTechnicalDetails) {
                $response['technical_error'] = $error;
                $response['error_code'] = $error_code;
            }
            
            echo json_encode($response);
            exit;
        }
        
        return $fatal;
    }
    
    /**
     * Wrap a database query with error handling
     * @param callable $queryCallback Function that executes the query
     * @param string $errorMessage Friendly error message to show user
     * @param bool $isFatal Whether query failure is fatal
     * @return mixed Query result or false/null on error
     */
    public function executeQuery($queryCallback, $errorMessage = 'Database operation failed', $isFatal = true) {
        try {
            return call_user_func($queryCallback);
        } catch (Exception $e) {
            $this->handleQueryError(
                $e->getMessage(),
                $e->getMessage(),
                0,
                $isFatal
            );
            
            if ($isFatal) {
                throw $e;
            }
            
            return null;
        }
    }
    
    /**
     * Check if this is an AJAX request
     */
    private function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get display HTML for error popup
     */
    public function getErrorScreenHTML($title = 'Database Error', $message = 'An error occurred', $technicalDetails = '') {
        $details = $this->showTechnicalDetails ? htmlspecialchars($technicalDetails) : '';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #dc3545;
            font-size: 28px;
            margin-bottom: 15px;
        }
        p {
            color: #555;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .technical-details {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            border-radius: 4px;
            color: #666;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            max-height: 200px;
            overflow-y: auto;
        }
        .button-group {
            margin-top: 30px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.2s;
        }
        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon">⚠️</div>
        <h1>$title</h1>
        <p>$message</p>
        {$this->getDetailsHtml($details)}
        <div class="button-group">
            <button onclick="location.href='index.php'">Go to Login</button>
            <button onclick="window.history.back()">Go Back</button>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Get details HTML for error screen
     */
    private function getDetailsHtml($details) {
        return $details ? "<div class='technical-details'><strong>Details:</strong><br>$details</div>" : '';
    }
    
    /**
     * Get error as JSON
     */
    public function getErrorJSON($message, $technicalDetails = '', $code = 500) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'error' => $message,
            'type' => 'database_error',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($this->showTechnicalDetails && $technicalDetails) {
            $response['technical_error'] = $technicalDetails;
        }
        
        return json_encode($response);
    }
}

// Initialize global handler
if (!isset($GLOBALS['dbErrorHandler'])) {
    $GLOBALS['dbErrorHandler'] = new DatabaseErrorHandler();
}
?>
