<?php
  /* ****************************************************************************************************************
     ImageProfile

     Klasse zur Verwaltung der Image-Profile der Kamera
     Diese Klasse muss nicht instantiiert werden, da sie aus statischen Methoden und Variablen besteht
  **************************************************************************************************************** */
  class ImageProfile {
    public static $profiles = array( APIType::MOBOTIX => array( 'QXGA' => array('2048x1536','live'),
                                                                'MEGA' => array('1280x960','live'),
                                                                'XGA' => array('1024x768','live'),
                                                                'VGA' => array('640x480','live'),
                                                                'CIF' => array('320x240','live'),
                                                                '3D' => array('1280x960','both')
                                                              ),
                                     APIType::MOBOTIX2M => array ( '6MP' => array('3072x2048','live'),
                                                                   '5MP' => array('2592x1944','live'),
                                                                   'QXGA' => array('2048x1536','live'),
                                                                   'MEGA' => array('1280x960','live'),
                                                                   'XGA' => array('1024x768','live'),
                                                                   'VGA' => array('640x480','live'),
                                                                   'CIF' => array('320x240','live')
                                                                 ),
                                     APIType::MOBOTIX2D => array ( '6MP' => array('3072x2048','live'),
                                                                   '5MP' => array('2592x1944','live'),
                                                                   'QXGA' => array('2048x1536','live'),
                                                                   'MEGA' => array('1280x960','live'),
                                                                   'XGA' => array('1024x768','live'),
                                                                   'VGA' => array('640x480','live'),
                                                                   'CIF' => array('320x240','live')
                                                                 ),
                                     APIType::AXIS => array ( '4K' => array('3840x2160','live'),
                                                              'HD' => array('1920x1080','live'),
                                                              'HD ready' => array('1280x720','live'),
                                                              'XGA' => array('1024x768','live'),
                                                              'SVGA' => array('800x600','live'),
                                                              'VGA' => array('640x480','live'),
                                                              'CIF' => array('320x240','live')
                                                             )
                                   );

    public static function size($key,$type=APIType::MOBOTIX) {
      
      return self::$profiles[$type][$key][0];
    }

    public static function objective($key) {
      return self::$profiles[$type][$key][1];
    }

  }

?>