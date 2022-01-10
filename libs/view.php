<?php

  class View {
  	
  	function __construct() {
  	  if (isset($_SERVER["SERVER_NAME"]))
  	    $this->httpRoot = 'https://'.$_SERVER["SERVER_NAME"]._URL_PORT_.
  	         substr($_SERVER["PHP_SELF"],0,strlen($_SERVER["PHP_SELF"])-10).
  	         '/';
  	  if (isset($_SERVER["DOCUMENT_ROOT"]))
  	    $this->srcRoot = $_SERVER["DOCUMENT_ROOT"].
  	                   substr($_SERVER["PHP_SELF"],0,strlen($_SERVER["PHP_SELF"])-10).'/';
  	}
  	
  	public function render($name) {
  	  require($this->srcRoot.'views/header.php');
  	  require($this->srcRoot.'views/'.$name.'.php');
  	  require($this->srcRoot.'views/footer.php');
  	}
  	
  }
  
?>