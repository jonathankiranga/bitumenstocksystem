# Developer Quick Start - Error Logging & Custom Alerts

## 30-Second Overview

Your system now has:
1. **Automatic error logging to JSON files** (`logs/errors_YYYY-MM-DD.json`)
2. **Silent error handling** (errors logged without scaring users)
3. **Professional custom alerts** (replaces ugly native alerts)
4. **Admin dashboard** for viewing and analyzing errors

---

## Common Tasks

### ✅ Task 1: Make Query Errors Silent (Default)

```php
// Don't trap errors - they'll be logged silently
$result = DB_query("SELECT * FROM customers", $db);

if (!$result) {
    echo "Unable to retrieve customers"; // User-friendly only
}
```

**Result:**
- ✅ Error logged to JSON file automatically
- ✅ User sees friendly message only
- ✅ No scary technical details shown

---

### ✅ Task 2: Show Error Popup if Query Fails

```php
// Trap errors - they'll show popup and exit
$result = DB_query(
    "SELECT * FROM customers", 
    $db,
    "Unable to retrieve customers",
    "The SQL that failed was",
    false,
    true  // This =  true means show popup on error
);
```

**Result:**
- ✅ If query fails, popup appears
- ✅ Error logged to JSON
- ✅ Script exits after error

---

### ✅ Task 3: Show Success Popup (JavaScript)

```javascript
// Anywhere in your JavaScript code
CustomAlert.success("Invoice saved successfully!");

// With a callback function
CustomAlert.success("Data saved!", function() {
    // This runs after user clicks OK
    location.reload();
});
```

**Variations:**
```javascript
CustomAlert.error("Something went wrong");
CustomAlert.warning("Are you sure about this?");
CustomAlert.info("Here's some information");
```

---

### ✅ Task 4: Show Confirmation Dialog

```javascript
CustomAlert.confirm(
    "Delete this customer? This cannot be undone.",
    function() {
        // User clicked Confirm
        deleteCustomer();
    },
    function() {
        // User clicked Cancel
        cancelDelete();
    }
);
```

---

### ✅ Task 5: Show Database Error (Admin Debugging)

```php
if ($error) {
    $error_logged = $GLOBALS['errorLogger']->logDatabaseError(
        "Failed to save invoice",
        'database_query',
        $sql_query,
        mysqli_errno($db)
    );
}
```

**In JavaScript:**
```javascript
// This is shown if DEBUG_MODE = true
CustomAlert.databaseError(
    "Failed to save invoice",
    "Error code 1054: Unknown column 'invoice_id' in field list"
);
```

---

### ✅ Task 6: Access Admin Error Dashboard

**URL:** `http://yoursite/MYSQLERP/admin/view-error-logs.php`

**Features:**
- Filter by date
- Filter by severity (fatal/warning)
- View top 10 errors
- See statistics

---

## Configuration

### Development Mode (Show Technical Details)

Edit `config.php`:
```php
define('DEBUG_MODE', true);  // ← Change this
```

**Effect:**
- ✅ Technical error messages displayed
- ✅ SQL queries visible (for debugging)
- ✅ Stack traces shown
- ✅ Sensitive data NOT redacted (for debugging)

### Production Mode (Hide Technical Details)

```php
define('DEBUG_MODE', false);  // ← Default
```

**Effect:**
- ✅ Friendly error messages only
- ✅ Technical details hidden
- ✅ Sensitive data redacted
- ✅ Errors still logged for admins
- ✅ Better performance

---

## File Locations

```
Your App Root/
├── config.php                          ← Configuration
├── includes/
│   ├── ErrorLogger.php                 ← Core logging engine
│   ├── DatabaseErrorHandler.php        ← DB error handling
│   └── ConnectDB_mysqli.inc            ← Modified for logging
├── javascripts/
│   └── custom-alert.js                 ← Custom alerts (auto-loaded)
├── css/
│   └── custom-alert.css                ← Alert styling (auto-loaded)
├── logs/                               ← Error logs directory
│   └── errors_2026-02-23.json          ← Today's errors
└── admin/
    └── view-error-logs.php             ← Admin dashboard
```

---

## Error Log Format

**File:** `logs/errors_2026-02-23.json`

```json
[
  {
    "timestamp": "2026-02-23 14:30:45",
    "type": "database",
    "category": "database_query",
    "severity": "warning",
    "message": "Unknown column 'typo_field' in field list",
    "error_code": 1054,
    "sql": "SELECT * FROM `customers` WHERE typo_field = 'value'",
    "user_id": "admin",
    "remote_ip": "192.168.1.100",
    "request_uri": "/MYSQLERP/Customer.php"
  }
]
```

**Open in:**
- Any text editor
- VS Code
- Online JSON viewer

---

## Common Patterns

### Pattern 1: Try/Catch Style

```php
$result = DB_query($sql, $db);

if (!$result) {
    // Log already done automatically
    prnMsg("Unable to process request. Please try again.", "error");
    // Continue or die() depending on context
}
```

### Pattern 2: Validation + Save

```php
if (!validate_data($_POST)) {
    CustomAlert.error("Please fill all required fields");
    // JavaScript stops here, doesn't proceed
} else {
    // Save data
    DB_query($save_sql, $db);
    CustomAlert.success("Data saved successfully!");
}
```

### Pattern 3: Confirm Before Delete

```javascript
function deleteItem(id) {
    CustomAlert.confirm(
        "Delete item " + id + "? This cannot be undone.",
        function() {
            // Send delete request
            $.post('delete.php', {id: id}, function(data) {
                if (data.success) {
                    CustomAlert.success("Item deleted");
                    location.reload();
                } else {
                    CustomAlert.error(data.error);
                }
            });
        }
    );
}
```

---

## Troubleshooting

### ❌ "Logs not being created"

**Check:**
1. Does `/logs/` directory exist? 
   - If not: `mkdir logs/`
   - Set permissions: `chmod 755 logs/`

2. Is ErrorLogger included in config.php?
   ```php
   require_once dirname(__FILE__) . '/includes/ErrorLogger.php';
   ```

3. Can PHP write to logs directory?
   - Check file permissions
   - Check PHP user (www-data, apache, etc.)

---

### ❌ "Custom alerts not showing"

**Check:**
1. Is jQuery loaded?
   ```html
   <script src="jquery.min.js"></script>
   ```

2. Is custom-alert.js loaded after jQuery?
   ```html
   <script src="custom-alert.js"></script>
   ```

3. Is custom-alert.css linked?
   ```html
   <link rel="stylesheet" href="custom-alert.css" />
   ```

4. Check browser console (F12) for errors

---

### ❌ "Debug mode not working"

**Check:**
1. Is DEBUG_MODE set in config.php?
   ```php
   define('DEBUG_MODE', true);
   ```

2. Did you clear browser cache?
   - Ctrl+Shift+Delete (Windows)
   - Cmd+Shift+Delete (Mac)

3. Is php.ini configured?
   ```ini
   error_reporting = E_ALL
   display_errors = 1
   ```

---

## Examples by Use Case

### 🛒 E-commerce: Save Order

```php
$sql = "INSERT INTO orders VALUES (...)";
$result = DB_query($sql, $db, 'Failed to save order', '', false, false);

if ($result) {
    // Success - log to JSON, show success popup
    $order_id = DB_Last_Insert_ID($db, 'orders', 'orderid');
    // Send confirmation email, etc.
} else {
    // Error - already logged to JSON
    prnMsg('Unable to save order. Please try again.', 'error');
}
```

### 👥 CRM: Delete Customer

```javascript
function deleteCustomer(customerId) {
    CustomAlert.confirm(
        'Delete customer "' + customerName + '"? This cannot be undone.',
        function() {
            // Confirmed - send delete request
            $.ajax({
                url: 'delete_customer.php',
                method: 'POST',
                data: {id: customerId},
                success: function(data) {
                    CustomAlert.success('Customer deleted successfully');
                    // Refresh table or redirect
                    loadCustomers();
                },
                error: function() {
                    CustomAlert.error('Failed to delete customer');
                }
            });
        }
    );
}
```

### 📊 Reports: Generate Report

```php
// Start report generation
set_time_limit(300); // 5 minutes for processing
$filename = 'report_' . date('Y-m-d_His') . '.xlsx';

$result = DB_query("SELECT * FROM sales WHERE date >= ...", $db);

if (!$result) {
    // Error already logged to JSON
    echo '<script>CustomAlert.error("Unable to generate report");</script>';
} else {
    // Generate and download
    $export = new PHPExcel();
    // ... export code ...
    CustomAlert.success('Report downloaded');
}
```

---

## Best Practices

✅ **DO:**
- Use CustomAlert for all user-facing messages
- Log errors even if you handle them gracefully
- Check DEBUG_MODE before showing technical details
- Use $TrapErrors=true only for critical operations
- Clean up old logs monthly

❌ **DON'T:**
- Show raw SQL errors to end-users
- Store sensitive data in error logs
- Display error details in production
- Ignore database connection errors
- Log password values

---

## Advanced Features

### Custom Error Statistics

```php
require_once 'includes/ErrorLogger.php';
$logger = new ErrorLogger();

// Get all errors for a date
$errors = $logger->getErrors('2026-02-23');

// Get only fatal errors
$fatal = $logger->getFatalErrors('2026-02-23');

// Get statistics
$stats = $logger->getStatistics('2026-02-23');
echo "Total: " . $stats['total_errors'];
echo "Fatal: " . $stats['fatal_errors'];
echo "By Category: " . print_r($stats['by_category']);
```

### Custom Alert Styling Override

```css
/* Add to your CSS to customize */
.custom-alert-modal {
    max-width: 600px; /* Larger modal */
}

.custom-alert-btn {
    background: #your-color;
}

/* Change animation speed */
@keyframes slideUp {
    from { transform: translate(-50%, -30%); }
    to { transform: translate(-50%, -50%); }
}
```

---

## Support

- **Documentation**: `ERROR_LOGGING_GUIDE.md`
- **Admin View**: `/admin/view-error-logs.php`
- **Error Logs**: `/logs/errors_YYYY-MM-DD.json`
- **Configuration**: `config.php`

---

**Last Updated**: February 23, 2026
**Version**: 1.0
**Status**: ✅ Production Ready

