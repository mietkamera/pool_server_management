<?php
  /* ****************************************************************************************************************
     Bildnachbearbeitung

     Welche Operation soll nach dem Abholen der Bilder ausgeführt werden
  **************************************************************************************************************** */
  class PreProcessScript {
    public static $mode = array ( ''   => array('keine Nachbearbeitung','mv'),
                                  'cropQXGA'  => array('QXGA aus 6MP ausschneiden','convert -crop 2048x1536+512+100 +repage'),
                                  'scale6MPtoUHD'  => array('6MP zu UHD skalieren','convert -crop 3072x1728+0+0 -resize 3840x2160'),
                                  'resizeQXGA'  => array('QXGA aus 5MP skalieren','convert -resize 2048x1536')
                                );

    public static function command_to_execute($key) {
      return self::$mode[$key][1];
    }

  }

?>