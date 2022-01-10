<?php
  class MovieType {
    const MO2FR = 31;
    const MO2SA = 63;
    const MO2SO = 127;
    
  	public static $days = array ( 
  		                    self::MO2FR  => 'Montag - Freitag',
                            self::MO2SA  => 'Montag - Sonnabend',
                            self::MO2SO  => 'Montag - Sonntag'
                          );
                          
    const CROP_BOTTOM = 0;
    const CROP_BOTTOM_AND_TOP = 192;
    const CROP_TOP = 384;
    
    public static $crop_area = array (
                                 self::CROP_BOTTOM         => 'unten wegschneiden',
                                 self::CROP_BOTTOM_AND_TOP => 'oben und unten wegschneiden',
                                 self::CROP_TOP            => 'oben wegschneiden'
                               );

    const LOW = 0;
    const MEDIUM = 1;
    const HIGHT = 2;
    const LOSSLESS = 3;

    public static $mode = array ( 
    	                       self::LOW      => array('Geringe Qualit&auml;t','-b:v 2000k','-b:v 1000k'),
                               self::MEDIUM   => array('Mittlere Qualit&auml;t','-b:v 4000k','-b:v 2000k'),
                               self::HIGHT    => array('Hohe Qualit&auml;t','-preset slow','-b:v 6000k'),
                               self::LOSSLESS => array('Nahezu Verlustfrei','-preset veryslow','-b:v 10000k')
                             );

    public static function ffmpeg_quality_parameter($quality,$codec='h264') {
      return $codec=='h264'?self::$mode[$quality][1]:self::$mode[$quality][2];
    }

    const WEEK     = 1;
    const MONTH    = 2;
    const YEAR     = 4;
    const COMPLETE = 8;

    public static function movie_subdir($typ) {
      switch ($typ) {
        case self::WEEK :     $dir = 'movies/week'; break;
        case self::MONTH :    $dir = 'movies/month'; break;
        case self::YEAR :     $dir = 'movies/year'; break;
        case self::COMPLETE : $dir = 'movies'; break;
      }
      return $dir;
    }

    public static function create_movie_script_name($typ) {
      switch ($typ) {
        case self::WEEK :     $dir = 'create_movie_week.sh'; break;
        case self::MONTH :    $dir = 'create_movie_month.sh'; break;
        case self::YEAR :     $dir = 'create_movie_year.sh'; break;
        case self::COMPLETE : $dir = 'create_movie.sh'; break;
      }
      return $dir;
    }

    public static function movie_cron_name($typ) {
      switch ($typ) {
        case self::WEEK :     $dir = 'etc/movie_week.cron'; break;
        case self::MONTH :    $dir = 'etc/movie_month.cron'; break;
        case self::YEAR :     $dir = 'etc/movie_year.cron'; break;
        case self::COMPLETE : $dir = 'etc/movie.cron'; break;
      }
      return $dir;
    }

  }

?>