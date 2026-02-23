<?php
/* ConnectDB_mysqli.inc - MySQLi compatibility layer for migration with error logging */
global $db, $errorLogger, $dbErrorHandler;

$database = isset($_SESSION['DatabaseName']) ? $_SESSION['DatabaseName'] : $DefaultDatabase;

// Suppress warnings during connection
$db = @mysqli_connect($host, $DBUser, $DBPassword, $database);

if (!$db || mysqli_connect_errno()) {
    $connectError = mysqli_connect_error();
    
    // Log connection error
    if ($GLOBALS['errorLogger'] ?? null) {
        $GLOBALS['errorLogger']->logDatabaseError(
            'Database connection failed',
            'database_connection',
            "Host: $host, User: $DBUser, Database: $database",
            mysqli_connect_errno(),
            true
        );
    }
    
    testconnect($connectError);
}

require_once ($PathPrefix .'includes/MiscFunctions.php');

function testconnect($errorMsg = ''){
    global $RootPath, $PathPrefix;
    if (!isset($RootPath)){
        $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
        if ($RootPath == '/' OR $RootPath == "\\") {
            $RootPath = '';
        }
    }
    
    if (!$errorMsg) {
        $errorMsg = mysqli_connect_error();
    }
    
    echo '<html><head><link href="'.$RootPath.'/javascripts/bootstrap.min.css" rel="stylesheet" type="text/css" /><link href="'. $RootPath .'/css/homepage.css" rel="stylesheet" type="text/css"/></head>';
    echo '<body><div class="container">';
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading">Database Connection Error</h4>';
    echo '<p>Connect failed: ' . htmlspecialchars($errorMsg) . '</p>';
    echo '<p>Contact your system administrator if the problem persists.</p>';
    echo '<hr>';
    echo '<a href="index.php" class="btn btn-primary">Click here to try logging in again</a>';
    echo '</div>';
    echo '</div></body></html>';
    session_unset();
    session_destroy();
    exit();
}

function DB_query($SQL,$Conn,$ErrorMessage='',$DebugMessage= '',$Transaction=false,$TrapErrors=true){
    global $debug, $PathPrefix, $errorLogger;
    
    // Basic SQL Server -> MySQL compatibility fixes
    $sql_work = $SQL;
    // replace square brackets with backticks
    $sql_work = str_replace(array('[',']'), array('`','`'), $sql_work);
    // SELECT TOP N -> use LIMIT
    if (preg_match('/SELECT\s+TOP\s+(\d+)\s+/i',$sql_work,$matches)){
        $top = (int)$matches[1];
        $sql_work = preg_replace('/SELECT\s+TOP\s+\d+/i','SELECT',$sql_work,1);
        // append LIMIT if not present
        if (!preg_match('/LIMIT\s+\d+/i',$sql_work)){
            // put LIMIT before trailing semicolon if any
            $sql_work = rtrim($sql_work);
            $sql_work = preg_replace('/;\s*$/','',$sql_work);
            $sql_work .= ' LIMIT ' . $top;
        }
    }
    // getdate() -> now()
    $sql_work = preg_replace('/GETDATE\s*\(\s*\)/i','NOW()',$sql_work);
    // isnull(x,y) -> IFNULL(x,y)
    $sql_work = preg_replace('/ISNULL\s*\(/i','IFNULL(',$sql_work);

    $result = mysqli_query($Conn,$sql_work);

    if ($DebugMessage == '') {
        $DebugMessage = _('The SQL that failed was');
    }

    // Handle query errors - log silently by default, popup only if TrapErrors=true
    if (DB_error_no($Conn) != 0){
        $errorCode = DB_error_no($Conn);
        $errorMsg = DB_error_msg($Conn);
        
        // Log the error to JSON file (silent)
        if ($errorLogger) {
            $errorLogger->logDatabaseError(
                $errorMsg,
                'database_query',
                substr($SQL, 0, 500),
                $errorCode,
                $TrapErrors  // Mark as fatal if TrapErrors is true
            );
        }
        
        // Display error popup/page only if TrapErrors is true
        if ($TrapErrors){
            if ($TrapErrors){
                require_once($PathPrefix . 'includes/header.inc');
            }
            prnMsg($ErrorMessage . '<br />' . $errorMsg,'error', _('Database Error'). ' ' .$errorCode);
            if ($debug==1){
                prnMsg($DebugMessage. '<br />' . $SQL . '<br />','error',_('Database SQL Failure'));
            }
            if ($Transaction){
                $SQL = 'rollback';
                $Result = DB_query($SQL,$Conn);
                if (DB_error_no($Conn) !=0){
                    prnMsg(_('Error Rolling Back Transaction'), 'error', _('Database Rollback Error'). ' ' .DB_error_no($Conn) );
                }else{
                    prnMsg(_('Rolling Back Transaction OK'), 'error', _('Database Rollback Due to Error Above'));
                }
            }
            if ($TrapErrors){
                include($PathPrefix . 'includes/footer.inc');
                exit;
            }
        }
    }

    return $result;
}

function DB_System($SQL,$Conn,$ErrorMessage='',$DebugMessage= '',$Transaction=false,$TrapErrors=true){
    return DB_query($SQL,$Conn,$ErrorMessage,$DebugMessage,$Transaction,$TrapErrors);
}

function DB_escape_string($String){
    global $db;
    if (is_null($String)) return null;
    return mysqli_real_escape_string($db, $String);
}

function DB_backup_string($String){
    return addslashes(htmlspecialchars($String, ENT_COMPAT,'utf-8', false));
}

function Table_fetch_row (&$ResultIndex) {
    $row = mysqli_fetch_row($ResultIndex);
    if ($row !== null && $row !== false) {
        $ResultIndex->_last_table_row = $row;
        return $row;
    }
    return false;
}

function Table_name($ResultIndex) {
    if (isset($ResultIndex->_last_table_row) && is_array($ResultIndex->_last_table_row)){
        return $ResultIndex->_last_table_row[0];
    }
    return null;
}

function DB_show_fields($TableName, $Conn){
   return mysqli_query($Conn, "SHOW COLUMNS FROM `". $TableName ."`");
}

function DB_show_tables($Conn){
   return mysqli_query($Conn, "SHOW TABLES");
}

function DB_fetch_row($ResultIndex) {
   return mysqli_fetch_row($ResultIndex);
}

function DB_fetch_assoc ($ResultIndex) {
    return mysqli_fetch_assoc($ResultIndex);
}

function DB_fetch_array ($ResultIndex) {
  return mysqli_fetch_array($ResultIndex, MYSQLI_ASSOC);
}

function DB_data_seek ($ResultIndex,$Record) {
    return mysqli_data_seek($ResultIndex,$Record);
}

function DB_free_result ($ResultIndex){
    if ($ResultIndex instanceof mysqli_result) {
        mysqli_free_result($ResultIndex);
    }
}

function DB_num_rows ($ResultIndex){
   return mysqli_num_rows($ResultIndex);
}

function DB_affected_rows($ResultIndex){
    return mysqli_affected_rows($GLOBALS['db']);
}

function DB_error_no ($Conn){
   return mysqli_errno($Conn);
}

function DB_error_msg($Conn){
   return mysqli_error($Conn);
}

function DB_Last_Insert_ID($Conn, $Table, $FieldName){
    return mysqli_insert_id($Conn);
}

function DB_Maintenance($Conn){
   prnMsg(_('The system has just run the regular database administration and optimisation routine.'),'info');
   $Result = DB_query("UPDATE config SET confvalue='" . Date('Y-m-d') . "' WHERE confname='DB_Maintenance_LastRun'", $Conn);
}

function DB_Txn_Begin($Conn){
     mysqli_begin_transaction($Conn);
}

function DB_Txn_Commit($Conn){
   mysqli_commit($Conn);
}

function DB_Txn_Rollback($Conn){
    mysqli_rollback($Conn);
}

function DB_IgnoreForeignKeys($Conn){
   DB_query('SET FOREIGN_KEY_CHECKS = 0',$Conn);
}

function DB_ReinstateForeignKeys($Conn){
    DB_query('SET FOREIGN_KEY_CHECKS = 1',$Conn);
}

function fetch_array($id_res){
  return mysqli_fetch_assoc($id_res);
}

?>
