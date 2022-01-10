<?php
  class Bootstrap {
  	
    function __construct() {
      session_start();
      $this->db = new Database;
      
      $valid = false;
      $iprecord = $this->db->query('SELECT * FROM valid_ips WHERE ip="'.$_SERVER['REMOTE_ADDR'].'"');
      if (!is_null($iprecord)) {
      	foreach($iprecord as $record) {
      	  if (filter_var($record['ip'], FILTER_VALIDATE_IP)) {
      	    $valid = true;
      	    break;
      	  }
      	}
      }
      
      $url = explode('/',rtrim((isset($_GET['url'])?$_GET['url']:null),'/'));
      if ($valid || _ALLOW_ALL_IPS_) {
        $file = 'controllers/'.$url[0].'.php';

        if (empty($url[0]) || !file_exists($file)) {
           require 'controllers/error.php';
           $controller = new ResponseError();
           $controller->not_found();
           return false;
        } else {
          require $file;
          $controller = new $url[0];
          if (isset($url[1]) && method_exists($controller,$url[1])) {
            $controller->loadModule($url[0]);
            if (isset($url[2])) {
              //error_log($url[0].'->'.$url[1].'('.$url[2].')'."\n");
              $controller->{$url[1]}($url[2]);
            } else {
              $controller->{$url[1]}();
            }
          } else {
            require 'controllers/error.php';
            $err_controller = new ResponseError();
            $err_controller->not_found();
            return false;
          }
        }
      } else {
        require 'controllers/error.php';
        $controller = new ResponseError();
        $controller->not_valid();
        return false;
      }

    }
      
  }
?>