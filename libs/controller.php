<?php

  class Controller {
  	
  	function __construct() {
  	  // echo 'main controller..';
  	  $this->view = new View();
  	  if (isset($_SERVER["SERVER_NAME"]))
  	    $this->httpRoot = $_SERVER["SERVER_NAME"]._URL_PORT_.
  	              substr($_SERVER["PHP_SELF"],0,strlen($_SERVER["PHP_SELF"])-10);
  	}
  	
  	public function loadModule($name) {
  	  if (file_exists('models/'.$name.'_model.php')) {
  	  	$modelName = $name.'_Model';
  	  	require 'models/'.$name.'_model.php';
  	  	$this->model = new $modelName;
  	  }
  	}
  	
 /* 	public function encode_string($string) {
  	  # Einige Zeichen, die eventuell in der Shell verwendet werden könnten, müssen
  	  # entfernt werden - das ist sicherer
  	  $item = str_replace('"','',$string);
  	  $item = str_replace("'",'',$item);
  	  $item = str_replace('/','',$item);
  	  $item = str_replace('\\','',$item);
  	  
  	  return trim(htmlentities($item, ENT_QUOTES | ENT_HTML5, "UTF-8"));
  	}
 */ 	
  	public function strip_quotes($string) {
  	  $item = str_replace('"','',$string);
  	  $item = str_replace("'",'',$item);
  	  $item = str_replace('/','',$item);
  	  $item = str_replace('\\','',$item);
  	  
  	  return $item;
  	}
  	
  	public function encrypt_decrypt($string, $action = 'encrypt') {
      $encrypt_method = "AES-256-CBC";
      $secret_key = _SECRET_KEY_;                     // user define private key
      $secret_iv = _SECRET_INITIALIZATION_VECTOR_;    // user define initialization vector
      $key = hash('sha256', $secret_key);
      $iv = substr(hash('sha256', $secret_iv), 0, 16); // sha256 is hash_hmac_algo
      if ($action == 'encrypt') {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
      } else if ($action == 'decrypt') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
      }
      return $output;
    }

  	public function httpGet($url,$secret='') {
  	  $ch = curl_init($url);
  	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLINFO_HEADER_OUT, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      if ($secret!='') {
        curl_setopt($ch, CURLOPT_USERPWD, $secret);
      }
      $result = curl_exec($ch);
      if ($result === false) {
        $json = '{ "returncode": "501", "message": "'.curl_error($ch).'" }';
      } else {
        if( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) == '200' ) 
          $json = $result;
         else
          $json = '{ "returncode": "500", "message": "URL not found" }';
      }
      curl_close($ch);
      return json_decode($json, true);
  	}
  	
  	public function httpPost($url,$data,$secret='') {
  	  //error_log('httpPost: url ->"'.$url.'" '."\n");
  	  //error_log('httpPost: data ->'.print_r($data,true));
  	  $payload = http_build_query($data);
  	  error_log('httpPost: data ->'.$payload."\n");
  	  $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLINFO_HEADER_OUT, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      if ($secret!='') {
        curl_setopt($ch, CURLOPT_USERPWD, $secret);
      }
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
      $result = curl_exec($ch);
      if ($result === false) {
        $json = '{ "returncode": "501", "message": "'.curl_error($ch).'" }';
      } else {
        $info = curl_getinfo($ch);
        //error_log('httpPost: info ->'.print_r($info,true));
        if( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) == 200 )
          $json = $result;
         else
          $json = '{ "returncode": "'.$info['http_code'].'", "message": "Die Website antwortet mit einem HTTP-Fehlercode" }';
      }
      curl_close($ch);
      return json_decode($json, true);
  	}
  	
  }
?>