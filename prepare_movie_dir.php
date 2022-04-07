<?php
  require_once('/var/www/html/management/globals.php');
  require_once('/var/www/html/management/personal.php');
  require_once('/var/www/html/management/libs/cls_movietype.php');

  /* Hilfsfunktionen ********************************************************************* */
  
  function get_image_file_names($st,$date='') {
  	
  	$images = array();
  	$image_stub_dir = _SHORT_DIR_.'/'.$st.'/img';
    $use_cache = false;
  	
  	if (is_dir($image_stub_dir)) {
  	  $last_image_time = is_link($image_stub_dir.'/lastimage.jpg')?filemtime($image_stub_dir.'/lastimage.jpg'):0;
  	  // Zuerst mal schauen, ob es einen Cache gibtse 
  	  switch (strlen($date)) {
  	    case 0:
  	      $image_dir = $image_stub_dir;
   	      $cache_time_offset = 86400000;
   	      break;
  	    case 4:  // Bilder eines bestimmten Jahres
  	      $image_dir = $image_stub_dir.'/'.$date;
          $cache_time_offset = 31536000000; // 365*24*60*60*1000
  	  	  break;
  	    case 6:
  	      $y = substr($date,0,4);
  	      $m = substr($date,4,2);
          $image_dir = $image_stub_dir.'/'.$y.'/'.$m;
          $cache_time_offset = 2678400000; // 31*24*60*60*1000
          break;
        case 8:
          $y = substr($date,0,4);
  	      $m = substr($date,4,2);
  	      $d = substr($date,6,2);
          $image_dir = $image_stub_dir.'/'.$y.'/'.$m.'/'.$d;
          $cache_time_offset = 86400000; // 24*60*60*1000
          break;
        default:
          $image_dir = '';
  	  }
	 
	  if (is_dir($image_dir)) {
	    $image_cache = $image_dir.'/.img_cache';
	  	$image_first = $image_dir.'/.first';
	  	$image_last = $image_dir.'/.last';
	  	$cache_time = is_file($image_cache)?filemtime($image_cache):0;
	  	if (is_file($image_cache)) {
	  	  if ($cache_time>$last_image_time || 
	  	       ($cache_time+$cache_time_offset)<$last_image_time) {
	  	    
	  	    $cache = file($image_cache);
	  	    switch (strlen($date)) {
	  	  	  case 0:
	  	      	foreach($cache as $row)
	  	  		  $images[] = 'img/'.$row;
	  	  		break;
	  	      case 4:
	  	      	foreach($cache as $row)
	  	      	  $images[] = 'img/'.$date.'/'.$row;
	  	      	break;
	  	      case 6:
	  	      	foreach($cache as $row)
	  	      	  $images[] = 'img/'.$y.'/'.$m.'/'.$row;
	  	      	break;
	  	      case 8:
	  	      	foreach($cache as $row)
	  	      	  $images[] = 'img/'.$y.'/'.$m.'/'.$d.'/'.$row;
	  	      	break;
	  	      default:      
	  	    }
	  	    $use_cache = true;
	  	  }
	    } 
	    if (!$use_cache) {
	  	  switch (strlen($date)) {
	  	  	case 0:
	  	      $all_files = scandir($image_stub_dir);
	  	      foreach($all_files as $year) {
	  	        if (strlen($year)==4 && is_numeric($year)) {
	  	          $image_dir_y = $image_stub_dir.'/'.$year;
	  	          $di = new RecursiveDirectoryIterator($image_dir_y);
	  	          $it = new RecursiveIteratorIterator($di);
                  $rx = new RegexIterator($it, "/^.+\.jpg$/i");
                  $img = iterator_to_array($rx);
                  $image_dir_len = strlen($image_dir_y)-4;
                  foreach($img as $image) {
                    $images[] = 'img/'.substr($image,$image_dir_len).PHP_EOL;
                  }
	  	   	    }
	  	      }
	  	      break;
	  	    case 4:
	  	    case 6:
	  	    case 8:
	  	      $di = new RecursiveDirectoryIterator($image_dir);
	  	      $it = new RecursiveIteratorIterator($di);
              $rx = new RegexIterator($it, "/^.+\.jpg$/i");
              $img = iterator_to_array($rx);
              $image_dir_len = strlen($image_stub_dir)+1;
              foreach($img as $image) {
                $images[] = 'img/'.substr($image,$image_dir_len).PHP_EOL;
              }
              break;
            default:
	  	  }
        }
      }
    }
    sort($images);
    return $images;
  }
  
  function get_video_file_names($st) {

    $videos = array();
    $video_dir = _SHORT_DIR_.'/'.$st.'/movies';
  	  
    if (is_dir($video_dir)) {
  	  
      $subdirs = array('all'=>'',
                       'kw'=>'weeks',
                       'month'=>'month');
      
      foreach($subdirs as $name => $subdir) {
        $dir_name = $video_dir.($subdir==''?'':'/').$subdir;
       
        $dir = new DirectoryIterator($dir_name);
        $farr = array();
        foreach ($dir as $fileinfo) {
          $filename = $fileinfo->getFilename();
          if (!$fileinfo->isDot() && strpos($filename,'.mp4') && !strpos($filename,'768')) {
            $farr[] = substr($filename,0,-4);
          }
        }
        sort($farr);
        if (count($farr)>0) $videos[$name] = $farr;
        unset($farr);
      }
    }
    return $videos;
  }
  	
  function getFirstDayOfWeek($kw,$year=false) {
    if ($year==false) $year = date('Y');
    $offset = date('w', mktime(0,0,0,1,1,$year));
    $offset = ($offset < 5) ? 1-$offset : 8-$offset;
    $monday = mktime(0,0,0,1,1+$offset,$year);
    return strtotime('+'.($kw-1).' weeks', $monday);
  }
  

  function pictures_of_week($st,$kw,$year=false) {
    if ($year==false) $year = date('Y');
    $pictures = array();
    if ($kw<1 || $kw>53) return pictures;
    $start = getFirstDayOfWeek($kw,$year);
    for ($i=0;$i<=6;$i++) {
      $day_pictures = array();
      $weekday = date('w',$start);
      if ($weekday==0) $weekday = 7;
      
      $day_pictures = get_image_file_names($st, date('Ymd',$start));
      if (count($day_pictures)>0)
        foreach($day_pictures as $picture)
          $pictures[] = $picture;
      unset($day_pictures);
        
      $start += 60*60*24;
    }
    return $pictures;
  }

  /* Ende Hilfsfunktionen **************************************************************** */
  
  if (isset($argv[1]) && intval($argv[1])>0 && intval($argv[1])<53) {
    $kw = $argv[1];
  } else {
  	$kw = date("W",time());
  }
  	
  $st_dir = scandir(_SHORT_DIR_);
  
  foreach($st_dir as $dir) {
    if (is_file(_SHORT_DIR_.'/'.$dir.'/shorttag.data')) {
      $data = array();
      foreach(file(_SHORT_DIR_.'/'.$dir.'/shorttag.data') as $row) {
        list($name,$val) = explode(" ",$row);
        $var_name = substr(substr($name,0,-2),1);
        $var_value = substr(substr($val,0,-2),1);
        switch (gettype($var_value)) {
          case "boolean":
            $data[$var_name] = $var_value=='true'?true:false;
            break;
          case "integer":
            $data[$var_name] = intval($var_value);
            break;
            default:
              $data[$var_name] = $var_value;
        }
      }

      if ($data['active']=="true") {
        if ($data['create_movie_week']==="true") {
          $dirname = _SHORT_DIR_.'/'.$dir.'/movies/week'; 
          $filename= $dirname.'/'.date('Y',time()).str_pad($kw,2,"0", STR_PAD_LEFT).'.lst';
          if (!is_dir($dirname)) mkdir($dirname, 0770, true);
          $pictures = pictures_of_week($dir,$kw);
          file_put_contents($filename,$pictures);
        }
        if ($data['create_movie_month']==="true") {
          $dirname = _SHORT_DIR_.'/'.$dir.'/movies/month';
          $filename= $dirname.'/'.date('Ym',time()).'.lst';
          if (!is_dir($dirname)) mkdir($dirname, 0770, true);
          $pictures = get_image_file_names($dir, date('Ym',time()));
          file_put_contents($filename,$pictures);
        }      	  
        if ($data['create_movie_year']==="true") {
          $dirname = _SHORT_DIR_.'/'.$dir.'/movies/year';
          $filename= $dirname.'/'.date('Y',time()).'.lst';
          if (!is_dir($dirname)) mkdir($dirname, 0770, true);
          $pictures = get_image_file_names($dir, date('Ym',time()));
          file_put_contents($filename,$pictures);
        }
        if ($data['create_movie_complete']==="true") {
          $dirname = _SHORT_DIR_.'/'.$dir.'/movies';
          $filename= $dirname.'/complete.lst';
          if (!is_dir($dirname)) mkdir($dirname, 0770, true);
          $pictures = get_image_file_names($dir);
          file_put_contents($filename,$pictures);
        }
      }
      unset($data);
    }
  }
?>
