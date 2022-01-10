<?php

class Database extends PDO {

	public function __construct() {
	  require 'dbconfig.php';
	  parent::__construct("mysql:host={$db_host};dbname={$db_name}",$db_user,$db_pass);
	  
	}	
}

?>