<?php
  /* ****************************************************************************************************************
     ImageIteration

     Frequenz der Einzelbildaufnahmen und dazu passende Multiplikatoren fuer die Filmerstellung
     Diese Klasse muss nicht instantiiert werden, da sie aus statischen Methoden und Variablen besteht
  **************************************************************************************************************** */
  class ImageIteration {
    public static $iterations = array ( '-'    => array(1,'alle 20 Sekunden','',18),
                                        '+'    => array(1,'alle 30 Sekunden','',28),
                                        '*'    => array(1,'jede Minute','',58),
                                        '*/2'  => array(2,'alle 2 Minuten','',90),
                                        '*/3'  => array(3,'alle 3 Minuten','',180),
                                        '*/4'  => array(4,'alle 4 Minuten','',220),
                                        '*/5'  => array(5,'alle 5 Minuten','',280),
                                        '*/10' => array(10,'alle 10 Minuten',' -filter:v "setpts=2.0*PTS"',580),
                                        '*/15' => array(15,'alle 15 Minuten',' -filter:v "setpts=3.0*PTS"',580),
                                        '*/20' => array(20,'alle 20 Minuten',' -filter:v "setpts=4.0*PTS"',580),
                                        '*/30' => array(30,'alle 30 Minuten',' -filter:v "setpts=6.0*PTS"',580),
                                        '0'    => array(60,'st&uuml;ndlich',' -filter:v "setpts=12.0*PTS"',580)
                                      );

    public static function filter($key) {
      return self::$iterations[$key][2];
    }

    public static function minute($key) {
      return self::$iterations[$key][0];
    }

    public static function description($key) {
      return self::$iterations[$key][1];
    }

    public static function max_time_out($key) {
      return self::$iterations[$key][3];
    }

    public static function connect_time_out($key) {
      return round(self::$iterations[$key][3]/2);
    }

  }

?>