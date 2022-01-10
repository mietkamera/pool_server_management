  <?php
  
    class APIType {
      const MOBOTIX = 'm12d';
      const MOBOTIX2M = 'm26m';
      const MOBOTIX2D = 'm15d';
      const AXIS = 'axis';

      public static $type = array ( self::MOBOTIX =>   array('Mobotix Dual M12D',
                                                             '/cgi-bin/image.jpg?imgprof=',
                                                             '/cgi-bin/faststream.jpg?stream=full&quality=60&fps=2.0&previewsize=640x480',
                                                             '/control/control?section=general&read&camera'),
                                    self::MOBOTIX2M => array('Mobotix Mono M26',
                                                             '/cgi-bin/image.jpg?imgprof=',
                                                             '/cgi-bin/faststream.jpg?stream=full&quality=60&fps=2.0&previewsize=640x480',
                                                             '/control/control?section=general&read&camera'),
                                    self::MOBOTIX2D => array('Mobotix Dual S15',
                                                             '/cgi-bin/image.jpg?imgprof=',
                                                             '/cgi-bin/faststream.jpg?stream=full&quality=60&fps=2.0&previewsize=640x480',
                                                             '/control/control?section=general&read&camera'),
                                    self::AXIS =>      array('Axis VAPIX',
                                                             '/axis-cgi/jpg/image.cgi?resolution=',
                                                             '/axis-cgi/mjpg/video.cgi?camera=1&resolution=640x480',
                                                             '/axis-cgi/param.cgi?action=list&group=Brand')
                                  );


    public static function get_image_url($type,$profile) {
      switch ($type) {
        case self::MOBOTIX:
        case self::MOBOTIX2M:
        case self::MOBOTIX2D:
          $path = self::$type[$type][1].$profile;
          break;
        case self::AXIS:
          $path = self::$type[$type][1].ImageProfile::size($profile,self::AXIS);
      }
      return $path;
    }

    public static function get_preview_url($type) {
      return self::$type[$type][1];
    }

    public static function get_stream_url($type) {
      return self::$type[$type][1];
    }
    
    public static function get_monitor_url($type) {
      return self::$type[$type][3];
    }
  }
