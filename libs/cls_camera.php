<?php

  class Camera {
  	
    var $name = "";             // Kurze Beschreibung fuer die Anzeige im Menue
    var $description = "";      // Eine ausfuehrlichere Beschreibung
    var $mac = "";              // MAC-Hardwareadresse ohne Trennzeichen in Kleinbuchstaben
                                // bildet gleichzeitig ein Unterverzeichnis im Projektverzeichnis
    var $shorttag = "";          // Shorttag zum einfachen Aufruf via URL
    var $fip = "";              // Zur MAC-Adresse passende Werks-IP
    var $ip = "";               // Ueber diese IP oder diesen DNS-Namen ist die Kamera erreichbar
    var $port = "80";
    var $router_port = ''    ;  // 
    var $secret = '';           // kennung:passwort-Paar fuer den Abruf von Bildern/Erreignisvideos
    var $api_operator_secret = '';
    var $api_user_secret = '';
    var $router_secret = ''  ;  // 
    var $proxy_url = "";        // Komplette URL beim Zugriff ueber einen Proxy
    var $monitor = false;       // Test Funktionalitaet der Kamera
    var $monitor_active = true; // Aktives Monitoring oder passives (wenn nicht von aussen erreichbar)
    var $longitude = 0;         // Geografische Laenge des Kamerastandortes als double-Wert
    var $latitude = 0;          // Geografische Breite des Kamerastandortes als double-Wert
    var $visible = true;        // Anzeige im Menue des Programm true/false
    var $umts_callid = "";      // Rufnummer der UMTS-SIM-Karte
    var $active = true;         // Kamera ist aktiv im Einsatz true/false
    var $calendar = true;
    var $series = array();      // Bildserien, die die Kamera aufzeichnet
    var $httpapi_type = 'm12d';

    /* ********************************************************************************************** */
    // Dieses Array speichert den Wertebereich fuer die Anzahl der gleichzeitig benutzten Bildsensoren.
    // Manche Kameramodelle nehmen gleichzeitig mit beiden Bildsensoren ein Bild auf und speichern es
    // auch so ab (z.Bsp. D12D-IT 180°-Panorama).
    // Die Anzahl der gleichzeitg genutzen Bildsensoren hat Einfluss auf die Geometrie der Darstellung
    // von Slideshow und Ereignisvideos.
    // Beispiel fuer einen HTTP-Request:
    //   http://192.168.18.71/control/control?section=imagecontrol&camera=both

    var $arr_active_sensors = array( 'right'=>'Rechts (color)',
                                     'left'=>'Links (s/w)',
                                     'auto'=>'Rechts oder Links (auto)',
                                     'both'=>'Beide gleichzeitig');
    var $active_sensors = "auto";

    // Dieses Array speichert den Wertebereich fuer die moegliche Darstellungsgroesse der Slideshow.
    var $arr_zoom_slideshow = array('1'=>array('320','240'),
                                    '2'=>array('640','480'),
                                    '3'=>array('960','720'),
                                    '4'=>array('1280','960')
                                   );
    // Dieses Array speichert den Wertebereich fuer die moegliche Darstellungsgroesse der Eventvideos.
    var $arr_zoom_eventvideo = array('1'=>array('320','240'),
                                     '2'=>array('640','480'),
                                     '3'=>array('960','720')
                                    );
    // Dieses Array speichert den Wertebereich fuer die moegliche Darstellungsgroesse des animierten
    // MJPEG-Livebildes. 
    var $arr_zoom_livestream = array('1'=>array('160','120'),
                                     '2'=>array('320','240'),
                                     '3'=>array('640','480')
                                    );

       function login_required() {
      return $this->api_operator_secret!='' || $this->api_user_secret!='';
    }

    function live_img_url($auth='') {
      $url = 'https://'.($auth!=''?$auth.'@':'').$this->ip.($this->port=='80'?'':':'.$this->port);
      switch ($this->httpapi_type) {
        case APIType::MOBOTIX:
          $url .= $this->cgi_jpg.'?size=640x480';
          break;
        case APITYPE::AXIS:
          $url .= '/axis-cgi/jpg/image.cgi?resolution=vga';
          break;
      }
      return $url;
    }

    function preview_img_url() {
      $url = 'https://'.$this->ip.($this->port=='80'?'':':'.$this->port);
      return $url.APIType::get_preview_url($this->httpapi_type);
    }

    function proxy_img_url() {
      $url = 'http://'.$this->proxy_url.
             $this->cgi_jpg.'?size='.$this->arr_stream_format[$this->stream_format][0].'x'.
             $this->arr_stream_format[$this->stream_format][1];

      return $url;
    }

    function firstserie() {
      return (is_array($this->series) && count($this->series)>0)?$this->series[0]:NULL;
    }

    function lastserie() {
      return (is_array($this->series) && count($this->series)>0)?
                $this->series[count($this->series)-1]:NULL;
    }

    
  }
?>