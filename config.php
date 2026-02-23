<?php
// User configurable variables smarterp
//---------------------------------------------------

// Error Handling & Logging Configuration
define('DEBUG_MODE', false); // Set to true in development environment only
error_reporting(E_ALL);
ini_set('display_errors', DEBUG_MODE ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/logs/php_errors.log');

// Initialize error logging system
require_once dirname(__FILE__) . '/includes/ErrorLogger.php';
require_once dirname(__FILE__) . '/includes/DatabaseErrorHandler.php';

//DefaultLanguage to use for the login screen and the setup of new users.
$DefaultLanguage = 'en_GB.utf8';
// Whether to display the demo login and password or not on the login screen
$AllowDemoMode = FALSE;
// Connection information for the database
// $host is the computer ip address or name where the database is located
// assuming that the webserver is also the sql server
// MySQL server connection settings
$host = 'localhost'; // Change to your MySQL server host if different
$DBType = 'mysqli';
$DBUser = 'root'; // Change to your MySQL username
$DBPassword = ''; // Change to your MySQL password
// The timezone of the business - this allows the possibility of having;
date_default_timezone_set('Africa/Nairobi');
putenv('TZ=Africa/Nairobi');
$AllowCompanySelectionBox = 'ShowSelectionBox';
//The system administrator name use the user input mail;
$SysAdminEmail = '';
$DefaultDatabase = 'mozillaerpv2'; // Change to your MySQL database name if different
$SessionLifeTime = 144000;
$MaximumExecutionTime = 1000;
$CryptFunction = 'sha1';
$DefaultClock = 12;
$RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'));

if (isset($DirectoryLevelsDeep)){
   for ($i=0;$i<$DirectoryLevelsDeep;$i++){
		$RootPath = mb_substr($RootPath,0, strrpos($RootPath,'/'));
	}
}

if ($RootPath == '/' OR $RootPath == '\\') {
    $RootPath = '';
}

//Installed companies 

$CompanyList[] = array('database'=>'mozillaerpv2' ,'company'=>'QBPL Company' );
//End Installed companies-do not change this line
/* Make sure there is nothing - not even spaces after this last ?> */
?>