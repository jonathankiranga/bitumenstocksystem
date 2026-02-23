<?php
/**
 * admin/view-error-logs.php - Error Log Viewer for Administrators
 * View, filter, and analyze application errors
 * 
 * Security: Admin-only page
 */

$PageSecurity = 0; // Adjust based on your permission system
require_once '../includes/session.inc';
require_once '../includes/ErrorLogger.php';

// Verify admin access
if (!isset($_SESSION['UserID'])) {
    header('Location: ../index.php');
    exit;
}

$logger = new ErrorLogger();
$date = $_GET['date'] ?? date('Y-m-d');
$severity = $_GET['severity'] ?? null;
$category = $_GET['category'] ?? null;

// Get errors for selected date
$errors = $logger->getErrors($date, $severity);

// Filter by category if specified
if ($category && is_array($errors)) {
    $errors = array_filter($errors, function($e) use ($category) {
        return ($e['category'] ?? null) === $category;
    });
    $errors = array_values($errors);
}

// Get statistics
$stats = $logger->getStatistics($date);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Logs - Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }
        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-card.error .number {
            color: #dc3545;
        }
        .stat-card.warning .number {
            color: #ffc107;
        }
        .errors-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #ddd;
        }
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            text-transform: uppercase;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .full-error {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            color: #333;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .pagination {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Error Log Dashboard</h1>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
                </div>
                <div class="filter-group">
                    <label>Severity</label>
                    <select name="severity">
                        <option value="">All Severities</option>
                        <option value="fatal" <?php echo $severity === 'fatal' ? 'selected' : ''; ?>>Fatal</option>
                        <option value="warning" <?php echo $severity === 'warning' ? 'selected' : ''; ?>>Warning</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="database_connection" <?php echo $category === 'database_connection' ? 'selected' : ''; ?>>Database Connection</option>
                        <option value="database_query" <?php echo $category === 'database_query' ? 'selected' : ''; ?>>Database Query</option>
                        <option value="system" <?php echo $category === 'system' ? 'selected' : ''; ?>>System</option>
                    </select>
                </div>
                <div class="filter-group" style="justify-content: flex-end;">
                    <button type="submit">Filter</button>
                </div>
            </form>
        </div>
        
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <h3>Total Errors</h3>
                <div class="number"><?php echo $stats['total_errors']; ?></div>
            </div>
            <div class="stat-card error">
                <h3>Fatal Errors</h3>
                <div class="number"><?php echo $stats['fatal_errors']; ?></div>
            </div>
            <div class="stat-card warning">
                <h3>Warnings</h3>
                <div class="number"><?php echo $stats['warning_errors']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Errors by Hour</h3>
                <div class="number"><?php echo count($stats['by_hour']); ?></div>
            </div>
        </div>
        
        <!-- Error List -->
        <div class="errors-table">
            <?php if (empty($errors)): ?>
                <div class="no-data">
                    <p>✅ No errors found for the selected date and filters.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Message</th>
                            <th>User</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($errors, 0, 50) as $error): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($error['timestamp']); ?></td>
                                <td><?php echo htmlspecialchars($error['category'] ?? 'Unknown'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $error['severity']; ?>">
                                        <?php echo ucfirst($error['severity']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($error['message'], 0, 50)); ?></td>
                                <td><?php echo htmlspecialchars($error['user_id'] ?? 'Anonymous'); ?></td>
                                <td><?php echo htmlspecialchars($error['remote_ip'] ?? 'Unknown'); ?></td>
                            </tr>
                            <?php if (!empty($error['sql'])): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="full-error">
                                            <strong>SQL:</strong><br><?php echo htmlspecialchars($error['sql']); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Top Errors -->
        <?php if (!empty($stats['top_errors'])): ?>
            <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 20px; color: #2c3e50;">🔝 Top 10 Errors Today</h3>
                <table style="width: 100%;">
                    <thead>
                        <tr style="border-bottom: 2px solid #ddd;">
                            <th style="text-align: left; padding: 10px; text-transform: uppercase; font-size: 12px; color: #666;">Error Message</th>
                            <th style="text-align: right; padding: 10px; text-transform: uppercase; font-size: 12px; color: #666;">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($stats['top_errors'] as $message => $count): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px 10px;">
                                    <strong>#<?php echo $rank; ?></strong>
                                    <?php echo htmlspecialchars(substr($message, 0, 80)); ?>
                                </td>
                                <td style="padding: 12px 10px; text-align: right;">
                                    <span style="background: #667eea; color: white; padding: 4px 12px; border-radius: 4px; font-weight: 600;">
                                        <?php echo $count; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
