<?php

  class ResponseError extends Controller {
  	
  	function __construct() {
  	  parent::__construct();
  	}
  	
  	function not_found() {
  	  $this->view->errorcode = "404";
	  $this->view->msg = "Seite existiert nicht";
  	  $this->view->render('error/index');
  	}
  	
  	function not_valid() {
  	  $this->view->errorcode = "403";
	  $this->view->msg = "Zugriff f&uuml;r '".$_SERVER['REMOTE_ADDR']."' nicht gestattet";
  	  $this->view->render('error/index');
  	}
  	
  }
?>