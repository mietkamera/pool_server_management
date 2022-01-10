<?php
 
 if(!defined('_GLOBALS_')) {
 	
	 // Global Definitions
	define('_SUCCESS_', 0);
	define('_ERROR_', 1);
	define('_INFO_', 2);
	
	define('_PRINT_DEBUG_LOG_', false);
	define('_ALLOW_ALL_IPS_', false);
	
	// In welchem Unterverzeichnis des Servers ist die 
	// anwendung installiert ?
	define('_URL_STUB_','');
	define('_URL_PORT_',':8443');
	define('_ALARM_MAIL_','support@mietkamera.de');
	
	define('_SHORT_DIR_','/var/www/short');
	define('_MRTG_DIR_','/var/www/mrtg');
	define('_TRASH_DIR_','/var/www/trash');
 
	define('_GLOBALS_', 0);
 } 
 
?>
