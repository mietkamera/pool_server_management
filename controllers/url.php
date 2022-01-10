<?php

  class Url extends Controller {

    function __construct() {
      parent::__construct();
    }
  
    public function connection_status($urlPart='') {
      if(isset($_POST)) {
        $url    = $_POST['url'];
        $secret = $_POST['secret'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $secret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
        $response = curl_exec($ch);
        if(curl_errno($ch)) {
          //throw new Exception(curl_error($ch));
          $this->view->errorcode        = "400";
          $this->view->msg              = "Beim Abruf ist ein Fehler aufgetreten";
          $this->view->payload[] = array ( "url" => $url); 
          $this->view->payload[] = array ( "type"=>"curl"); 
          $this->view->payload[] = array ( "number"=>curl_errno($ch)); 
          $this->view->payload[] = array ( "message"=>curl_error($ch));
        } else {
          libxml_use_internal_errors(true);
          $dom = new DOMDocument();
          $dom->loadHTML($response);
          $provider_name   = $dom->getElementById('3gProv')?$dom->getElementById('3gProv')->nodeValue:'unbekannt';
          $connection_type = $dom->getElementById('3gCType')?$dom->getElementById('3gCType')->nodeValue:'unbekannt';
          $signal_strength = $dom->getElementById('3gStr')?filter_var($dom->getElementById('3gStr')->nodeValue, FILTER_SANITIZE_NUMBER_INT):'-200'; 
          $this->view->errorcode = "200";
          $this->view->msg       = "Abruf erfolgreich";
          $this->view->payload[] = array( 'provider' => $provider_name);
          $this->view->payload[] = array( 'connection_type' => $connection_type);
          $this->view->payload[] = array( 'signal_strength' => $signal_strength);
        }
        curl_close($ch);    
      } else {
        $this->view->errorcode        = "400";
        $this->view->msg              = "Keine POST-Daten vorhanden";
        $this->view->payload[] = array ( "url"     => $url ); 
        $this->view->payload[] = array ( "type"    => "aufruf" );
        $this->view->payload[] = array ( "number"  => "1");
        $this->view->payload[] = array ( "message" => "Keine POST-Daten vorhanden" );
      }
      $this->view->render('shorttag/index');
    }
}

?>