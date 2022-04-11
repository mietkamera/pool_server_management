<?php
  require_once 'libs/cls_apitype.php';
  require_once 'libs/cls_movietype.php';
  require_once 'libs/cls_imageiteration.php';
  require_once 'libs/cls_imageprofile.php';
  require_once 'libs/cls_preprocessscript.php';

  class Shorttag extends Controller {

    function __construct() {
      parent::__construct();
      $this->shorttag = '';
      $this->other = '';
      $this->name = 'Neue Kamera';
      $this->description = '';
      $this->api_type = 'm12d';
      $this->active = true;                       // true = die Kamera ist aktiv und wird monitored
      $this->active_monitoring = true;            // false = Monitoring erfolgt passiv
      $this->image_iteration = '*/4';
      $this->image_profile = 'QXGA';
      $this->image_start = '6';
      $this->image_stop = '18';
      $this->pre_process_script = '';
      $this->alarm_mail = _ALARM_MAIL_;
      $this->alarm_mail_active = false;
      $this->alarm_mail2 = '';
      $this->alarm_mail2_active = false;
      $this->project_id = '';
      $this->project_contact = '';
      $this->project_name = '';
      $this->project_description = '';
      $this->camera_url_protocol = 'http';
      $this->camera_url_address = '192.168.0.1';
      $this->camera_url_port = '8080';
      $this->camera_url_secret = 'admin:pass';
      $this->router_type = 'teltonika';
      $this->router_url_port = '80';
      $this->router_url_secret = 'root:pass';
      $this->create_movie_week = false;
      $this->create_movie_month = false;
      $this->create_movie_year = false;
      $this->create_movie_complete = false;
      $this->movie_weekdays = MovieType::MO2FR;
      $this->movie_cropping = MovieType::CROP_BOTTOM_AND_TOP;
      $this->movie_daylight_filter = false;
      $this->movie_max_frames_week = 2520;
      $this->movie_max_frames_month = 2520;
      $this->movie_max_frames_year = 2520;
      $this->movie_max_frames_complete = 2520;
      $this->movie_quality_week = MovieType::HIGHT;
      $this->movie_quality_month = MovieType::MEDIUM;
      $this->movie_quality_year = MovieType::MEDIUM;
      $this->movie_quality_complete = MovieType::MEDIUM;
      $this->api_operator_secret = 'password';
      $this->api_user_secret = 'password';
      $this->payload = array();
      
      $this->var_names = array("other","name","description","active","active_monitoring","api_type","image_iteration",
                    "image_profile","image_start","image_stop","pre_process_script","alarm_mail", "alarm_mail_active",
                    "alarm_mail2", "alarm_mail2_active","project_id","project_contact","project_name","project_description",
                    "camera_url_protocol","camera_url_address","camera_url_port","camera_url_secret","router_type",
                    "router_url_port","router_url_secret","create_movie_week","create_movie_month","create_movie_year",
                    "create_movie_complete","movie_weekdays","movie_cropping","movie_daylight_filter","movie_max_frames_week",
                    "movie_max_frames_month","movie_max_frames_year","movie_max_frames_complete",
                    "movie_quality_week","movie_quality_month","movie_quality_year","movie_quality_complete",
                    "api_operator_secret","api_user_secret");
                    
    }
    
    private function set_properties_from_file() {
      $output = array();
      if ($this->shorttag != '' && is_file(_SHORT_DIR_.'/'.$this->shorttag.'/shorttag.data')) {
        foreach(file(_SHORT_DIR_.'/'.$this->shorttag.'/shorttag.data') as $row) {
          // Im Value können durchaus Doppelpunkte auftauchen
          $var_name  = trim(str_replace(array('"',':','\''),'',strstr($row, ':', true)));
          $var_value = trim(str_replace(array('"','\''),'',substr(strstr($row, ':'),1)));
          if ($var_name == 'api_operator_secret' || $var_name =='api_user_secret')
            $var_value = $this->encrypt_decrypt($var_value,'decrypt');
          switch (gettype($this->{$var_name})) {
            case "integer":
              $this->{$var_name} = intval($var_value);
              break;
            case "boolean":
              $this->{$var_name} = $var_value=='true'?1:0;
              break;
            default:
              $this->{$var_name} = utf8_decode($var_value);
          }
        }
      }
    }

    private function set_properties_from_post() {
      $text = "";
      foreach($this->var_names as $var) {
      	//error_log($var.': '.$_POST[$var]."\n");
        if (isset($_POST[$var])) {
          switch (gettype($this->{$var})) {
            case "integer":
              $this->{$var} = intval($_POST[$var]);
              break;
            case "boolean":
              $this->{$var} = $_POST[$var]==true;
              break;
            default:
              $this->{$var} = str_replace(array('\'','"','\\'),'',trim($_POST[$var]));
          }
        }
        switch (gettype($this->{$var})) {
          case "integer":
            $value = $this->{$var};
            break;
          case "boolean":
            $value = $this->{$var} ?'true':'false';
            break;
          default:
          	// Entferne alle eventuell vorhandenen Quotes oder Slashes und Backslashes
            $value = str_replace("'",'',str_replace('"','',stripslashes($this->{$var})));
        }
        if ($var != "api_operator_secret" && $var != "api_user_secret")
          $text .= ($text!=''?"\n":'').'"'.$var.'": "'.$value.'"';
         else
          $text .= ($text!=''?"\n":'').'"'.$var.'": "'.$this->encrypt_decrypt($value).'"';
      }
      $text .= "\n";
      $filename = _SHORT_DIR_.'/'.$this->shorttag.'/shorttag.data';
      $fh = fopen($filename, 'w') or die("can't open file");
      fwrite($fh, $text);
      fclose($fh);
      
      $this->createMonitorScript();
	  $this->createGetImageScript();
	  $this->createMovieScript(MovieType::WEEK);
	  $this->createMovieScript(MovieType::MONTH);
	  $this->createMovieScript(MovieType::COMPLETE);
	  $this->createMovieCronScript();
	  $this->createMrtgScript();
	  $this->createPasswordFiles();
	  $this->createJsDescription();
	  $this->createExportToCloudScript();
    }
    
    function create() {
      do {
        $this->shorttag = substr(md5(rand()),0,6);
      } while (is_dir(_SHORT_DIR_.'/'.$this->shorttag));
      shell_exec('mkdir -p '._SHORT_DIR_.'/'.$this->shorttag.'/img/240');
      shell_exec('mkdir -p '._SHORT_DIR_.'/'.$this->shorttag.'/rec/tasks');
      shell_exec('mkdir -p '._SHORT_DIR_.'/'.$this->shorttag.'/rec/tmp');
      shell_exec('echo -n "ok" >'._SHORT_DIR_.'/'.$this->shorttag.'/status');
      $this->view->errorcode = "200";
      $this->set_properties_from_post();
      $this->view->payload[] = array("shorttag" => $this->shorttag);
      foreach($this->var_names as $key)
        $this->view->payload[] = array($key => $this->{$key});
	  $this->view->msg = "Neuer Short-Tag erzeugt.";
  	  $this->view->render('shorttag/index');
    }
    
    function read($st='') {
      if ($st!='' && is_dir(_SHORT_DIR_.'/'.$st)) {
        $this->shorttag = $st;
        $this->view->errorcode = "200";
        $this->view->msg = "Short-Tag wurde gelesen.";
        $this->view->payload[] = array("shorttag" => $this->shorttag);
        $this->set_properties_from_file();
        foreach($this->var_names as $key) {
          //if ($key!="api_operator_secret" && $key != "api_user_secret")
          $this->view->payload[] = array($key => $this->{$key});
        }
        $this->view->payload[] = array("status" => file_get_contents(_SHORT_DIR_.'/'.$st.'/status'));
        if (is_link(_SHORT_DIR_.'/'.$st.'/img/lastimage.jpg')) {
          $last_img_array = explode('/',readlink(_SHORT_DIR_.'/'.$st.'/img/lastimage.jpg'));
          $last_img_time = substr($last_img_array[3],0,strpos($last_img_array[3],'.'));
      	  $lastimage = $last_img_array[2].'.'.$last_img_array[1].'.'.$last_img_array[0].' '.
      	             substr($last_img_time,0,2).':'.
      	             substr($last_img_time,2,2).':'.
      	             substr($last_img_time,4,2);
        } else $lastimage = '';
        $this->view->payload[] = array("lastimage" => $lastimage);
        $this->view->render('shorttag/index');
      } else {
        $this->view->errorcode = "500";
	    $this->view->msg = "Short-Tag existiert nicht.";
	    $this->view->render('error/index');
      }
    }
    
    function update($st='') {
      if ($st!='' && is_dir(_SHORT_DIR_.'/'.$st)) {
        $this->shorttag = $st;
        //$this->set_properties_from_file();
        $this->set_properties_from_post();
        $this->view->errorcode = "200";
        $this->view->msg = "Short-Tag wurde aktualisiert.";
        $this->view->payload[] = array("shorttag" => $this->shorttag);
        $this->view->payload[] = array("name" => $this->name);
        $this->view->render('shorttag/index');
      } else {
        $this->view->errorcode = "403";
	    $this->view->msg = "Short-Tag existiert nicht.";
	    $this->view->render('error/index');
      }
    }
    
    function delete($st='') {
      if ($st!='' && is_dir(_SHORT_DIR_.'/'.$st)) {
        $this->view->errorcode = "200";
        if (!is_dir(_TRASH_DIR_.'/'.$st)) {
          rename(_SHORT_DIR_.'/'.$st, _TRASH_DIR_.'/'.$st);
          $this->updateMonitorScriptFromShortdir();
          $this->updateGetImageScriptFromShortdir();
          $this->createMovieCronScript();
          $this->updateMrtgScriptFromShortdir();
          $this->view->errorcode = "200";
          $this->view->msg = "Shorttag gelöscht.";
        } else {
          $this->view->errorcode = "500";
          $this->view->msg = "Shorttag schon gelöscht.";
        }
        $this->view->payload[] = array("shorttag" => $st);
      } else {
      	if (is_dir(_TRASH_DIR_.'/'.$st)) {
      	  $this->view->errorcode = "200";
          $this->view->msg = "Shorttag gelöscht.";
          $this->view->payload[] = array("shorttag" => $st);
      	} else {
          $this->view->errorcode = "500";
          $this->view->msg = "Shorttag nicht vorhanden.";
          $this->view->payload[] = array();
      	}
      }
      $this->view->render('shorttag/index');
    }
    
    function undelete($st='') {
      if ($st!='' && is_dir(_TRASH_DIR_.'/'.$st)) {
        $this->view->errorcode = "200";
        if (!is_dir(_SHORT_DIR_.'/'.$st)) {
          rename(_TRASH_DIR_.'/'.$st, _SHORT_DIR_.'/'.$st);
          $this->updateMonitorScriptFromShortdir();
          $this->updateGetImageScriptFromShortdir();
          $this->createMovieCronScript();
          $this->updateMrtgScriptFromShortdir();
          $this->view->errorcode = "200";
          $this->view->msg = "Shorttag wiederhergestellt.";
        } else {
          $this->view->errorcode = "500";
          $this->view->msg = "Shorttag schon vorhanden.";
        }
        $this->view->payload[] = array("shorttag" => $st);
      } else {
      	if (is_dir(_SHORT_DIR_.'/'.$st)) {
      	  $this->view->errorcode = "200";
          $this->view->msg = "Shorttag wiederhergestellt.";
          $this->view->payload[] = array("shorttag" => $st);
      	} else {
          $this->view->errorcode = "500";
          $this->view->msg = "Shorttag nicht vorhanden.";
          $this->view->payload[] = array();
      	}
      }
      $this->view->render('shorttag/index');
    }
    
    
    function list($dir_type='') {
      switch ($dir_type) {
        case 'trash':
          $DIR = _TRASH_DIR_;
          break;
        default:
          $DIR = _SHORT_DIR_;
      }
      if (is_dir($DIR)) {
      	$list = "";
        $folders = scandir($DIR);
        foreach ($folders as $folder) {
          if (is_dir($DIR.'/'.$folder) && is_file($DIR.'/'.$folder.'/shorttag.data')) {
            $list .= ($list==""?$folder:':'.$folder);
          }
        }
        $this->view->errorcode = "200";
        $this->view->payload[] = array("list" => $list);
        if ($list=="") {
          $this->view->msg = "Keine Shorttags vorhanden.";
        } else {
          $this->view->msg = "Shorttags auf dem Server vorhanden.";
        }
      } else {
        $this->view->errorcode = "200";
        $this->view->msg = "Keine Shorttags vorhanden.";
        $this->view->payload[] = array("list" => "");
      }
      $this->view->render('shorttag/index');
    }
    
    function connection_status($st='') {
      
      if ($st!='' && is_dir(_SHORT_DIR_.'/'.$st)) {
        $this->shorttag = $st;
        $this->set_properties_from_file();
        $url = $this->camera_url_protocol.'://'.$this->camera_url_address.':'.$this->router_url_port.'/cgi-bin/luci';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $this->router_url_secret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
        $response = curl_exec($ch);
        if(curl_errno($ch)) {
          //throw new Exception(curl_error($ch));
          $this->view->errorcode = "400";
          $this->view->msg = "Fehler beim Abruf";
          $this->view->payload[] = array( "url"     => $url );
          $this->view->payload[] = array( "type"    => "curl" ); 
          $this->view->payload[] = array( "number"  => curl_errno($ch) ); 
          $this->view->payload[] = array( "message" => curl_error($ch) );
          
        } else {
          libxml_use_internal_errors(true);
          $dom = new DOMDocument();
          $dom->loadHTML($response);
          $provider_name   = $dom->getElementById('3gProv')?$dom->getElementById('3gProv')->nodeValue:'unbekannt';
          $connection_type = $dom->getElementById('3gCType')?$dom->getElementById('3gCType')->nodeValue:'unbekannt';
          $signal_strength = $dom->getElementById('3gStr')?filter_var($dom->getElementById('3gStr')->nodeValue, FILTER_SANITIZE_NUMBER_INT):'-200';
          $this->view->errorcode = "200";
          $this->view->msg = "Abruf erfolgreich";
          $this->view->payload[] = array( 'provider' => $provider_name);
          $this->view->payload[] = array( 'connection_type' => $connection_type);
          $this->view->payload[] = array( 'signal_strength' => $signal_strength);
        }
        curl_close($ch);
        $this->view->render('shorttag/index');
      } else {
        $this->view->errorcode = "403";
	    $this->view->msg = "Fehler beim Abruf";
	    $this->view->payload[] = array( "message" => "Shorttag exisitiert nicht" );
	    $this->view->render('shorttag/index');
      }
    }
    
    private function getShorttagDataFromFile($st) {
      $output = array();
      if ($st != '' && is_file(_SHORT_DIR_.'/'.$st.'/shorttag.data')) {
        foreach(file(_SHORT_DIR_.'/'.$st.'/shorttag.data') as $row) {
          list($name,$val) = explode(" ",$row);
          $var_name = substr(substr($name,0,-2),1);
          $var_value = substr(substr($val,0,-2),1);
          if ($var_name == 'api_operator_secret' || $var_name =='api_user_secret')
            $var_value = $this->encrypt_decrypt($var_value,'decrypt');
          switch (gettype($this->{$var_name})) {
          case "boolean":
            $output[$var_name] = $var_value=='true'?true:false;
            break;
          case "integer":
            $output[$var_name] = intval($var_value);
            break;
          default:
            $output[$var_name] = $var_value;
          }
        }
      }
      return $output;
    }
    
    private function createMonitorScript() {
      if ($this->shorttag != '' && is_dir(_SHORT_DIR_.'/'.$this->shorttag)) {
        $text = "#!/bin/bash\n\n".
                "SCRIPT_PATH='"._SHORT_DIR_.'/'.$this->shorttag."'\n".
                "ACTIVE_SCAN=".($this->active_monitoring?"1":"0")."\n".
                '# WIEVIELE MINUTEN DARF DAS LETZTE BILD ALT SEIN'."\n".
                '# + 3 MINUTEN TOLERANZ FUER LANGSAME UEBERTRAGUNGEN'."\n".
                'declare -i FILETIMEDIFF'."\n".
                'FILETIMEDIFF=`expr '.ImageIteration::minute($this->image_iteration).' \* 60 \+ 180`'."\n".
                'IMAGE_START="'.$this->image_start.'"'."\n".
                'IMAGE_STOP="'.$this->image_stop.'"'."\n".
                "EMAIL='".$this->alarm_mail."'\n".
                "EMAIL_ACTIVE='".($this->alarm_mail!=''?$this->alarm_mail_active:0)."'\n".
                "EMAIL2='".$this->alarm_mail2."'\n".
                "EMAIL2_ACTIVE='".($this->alarm_mail2!=''?$this->alarm_mail2_active:0)."'\n\n".
                "CONTACT='".$this->strip_quotes(html_entity_decode($this->project_contact))."'\n".
                "DESCRIPTION='".trim($this->strip_quotes(html_entity_decode($this->project_name." ".$this->name)))."'\n\n".
                'FROM=\'-r "[mietkamera.de] Monitoring <noreply@mietkamera.de>"\''."\n".
                "IDENT='".$this->strip_quotes(html_entity_decode($this->project_name.' : '.$this->name))."'\n";
        $absolute_path = $_SERVER["DOCUMENT_ROOT"].substr($_SERVER["PHP_SELF"],0,strlen($_SERVER["PHP_SELF"])-10);
        if ($this->active_monitoring) {
          $text .= "CURL_CAMERA_URI='".$this->camera_url_protocol.'://'.$this->camera_url_secret.'@'.$this->camera_url_address.':'.$this->camera_url_port.APIType::get_monitor_url($this->api_type)."'\n".
                   "CURL_ROUTER_URI='".$this->camera_url_protocol.'://'.$this->router_url_secret.'@'.$this->camera_url_address.':'.$this->router_url_port."/Status_Wwan.asp'\n";
          foreach(file($absolute_path.'/public/monitor.template.active') as $row) $text .= $row;
        } else {
          foreach(file($absolute_path.'/public/monitor.template.passive') as $row) $text .= $row;
        }
        foreach(file($absolute_path.'/public/monitor.template') as $row) $text .= $row;
        $filename = _SHORT_DIR_.'/'.$this->shorttag.'/monitor.sh';
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh, $text);
        fclose($fh);

        $this->updateMonitorScriptFromShortdir();
      }
    }

    private function updateMonitorScriptFromShortdir() {
      # Wenn die Kamera aktiv ist, wird das Monitoring über cron aktiviert
      $filename = _SHORT_DIR_.'/monitor.cron';
      $text = "";        
      $st_dir = scandir(_SHORT_DIR_);
      foreach($st_dir as $dir) {
        if (is_file(_SHORT_DIR_.'/'.$dir.'/shorttag.data')) {
          if(isset($output)) unset($output);
          exec('cat '._SHORT_DIR_.'/'.$dir.'/shorttag.data | grep \'"active":\' | cut -f2 -d" "',$output);
          if (is_array($output) && $output[0]=='"true"') $text .= '/usr/bin/bash '._SHORT_DIR_.'/'.$dir.'/monitor.sh &'."\n";
        }
      }
      $fh = fopen($filename, 'w') or die("can't open file");
      fwrite($fh,$text);
      fclose($fh);
      
    }
    
    private function camera_image_url_teltonika() {
      return $this->camera_url_protocol.'://'.$this->camera_url_address.':'.$this->camera_url_port.APIType::get_image_url($this->api_type,$this->image_profile);
    }
    
    private function camera_image_url_virtual() {
      return $this->camera_url_protocol.'://'.$this->camera_url_address;
    }
    
    private function createGetImageScript() {
      if ($this->shorttag != '' && is_dir(_SHORT_DIR_.'/'.$this->shorttag)) {
      	if (_PRINT_DEBUG_LOG_) error_log('createGetImageScript(): $this->camera_url_secret="'.$this->camera_url_secret.'"');
      	//list($user,$pass) = explode(':',$this->camera_url_secret);
      	$param = explode(':',$this->camera_url_secret);
      	$user = isset($param[0])?$param[0]:'';
      	$pass = isset($param[1])?$param[1]:'';
      	$image_url = $this->camera_url_protocol.'://'.
                     $this->camera_url_address.':'.$this->camera_url_port.APIType::get_image_url($this->api_type,$this->image_profile);
        $image_url = $this->{'camera_image_url_'.$this->router_type}();
        $secret = $this->router_type=="virtual"?$this->router_url_secret:$this->camera_url_secret;
        if ($secret!='')
          $secret = '--anyauth --user '.$secret;
        $text = "#!/bin/sh\n\n".
                'SPATH="'._SHORT_DIR_.'/'.$this->shorttag.'/img"'."\n".
                '# REMOVE STALE LCK-FILE AFTER 10 MINUTES'."\n".
                'if [ -f ${SPATH}/get_image.lck ]; then'."\n".
                '  declare -i staletime'."\n".
                '  declare -i now'."\n".
                '  now=`date +%s`'."\n".
                '  staletime=(`stat -c %Z ${SPATH}/get_image.lck`+10*60)'."\n".
                '  [ $now -gt $staletime ] && rm ${SPATH}/get_image.lck'."\n".
                'fi'."\n".
                '# THINK LCK IS OK'."\n".
                'if [ ! -f ${SPATH}/get_image.lck ]; then'."\n".
                '  touch ${SPATH}/get_image.lck'."\n".
                '  YEAR=`/bin/date +%Y`'."\n".
                '  MONTH=`/bin/date +%m`'."\n".
                '  DAY=`/bin/date +%d`'."\n".
                '  DUMMY=`/bin/date +%s`'."\n".
                '  [ -d ${SPATH}/${YEAR}/${MONTH}/${DAY} ] || mkdir -p  ${SPATH}/${YEAR}/${MONTH}/${DAY}'."\n".
                '  FILENAME=`/bin/date +%Y/%m/%d/%H%M%S`'."\n".
                '  export OPENSSL_CONF=/etc/ssl/openssl.tlsv1.cnf; curl -k --connect-timeout '.ImageIteration::connect_time_out($this->image_iteration).
                ' --max-time '.ImageIteration::max_time_out($this->image_iteration).
                ' '.$secret.' \''.$image_url.'&dummy=${DUMMY}\''.
                ' >${SPATH}/${FILENAME}.tmp 2>${SPATH}/get_image.log'."\n".
                '  if [ -s ${SPATH}/${FILENAME}.tmp -a "`file ${SPATH}/${FILENAME}.tmp | grep HTML >/dev/null; echo $?`" != "0" ]; then'."\n".
                '    FILESIZE=$(stat -c%s "${SPATH}/${FILENAME}.tmp")'."\n".
                '    /usr/bin/convert ${SPATH}/${FILENAME}.tmp /dev/null 2>&1 | grep \'Corrupt\' >/dev/null '."\n".
                '    if [ $? -ne 0 -a "${FILESIZE}" != "7073" ]; then'."\n".
                '      # pre_process_script: '.($this->pre_process_script==''?'none':$this->pre_process_script)."\n".
                '      '.PreProcessScript::command_to_execute($this->pre_process_script).' ${SPATH}/${FILENAME}.tmp ${SPATH}/${FILENAME}.jpg'."\n".
                '      [ -s ${SPATH}/${FILENAME}.tmp ] && rm ${SPATH}/${FILENAME}.tmp'."\n".
                '      [ -L ${SPATH}/lastimage.jpg ] && rm ${SPATH}/lastimage.jpg'."\n".
                '      ln -s ${FILENAME}.jpg ${SPATH}/lastimage.jpg'."\n".
                '     else'."\n".
                '      rm ${SPATH}/${FILENAME}.tmp'."\n".
                '    fi'."\n".
                '   else'."\n".
                '    rm ${SPATH}/${FILENAME}.tmp'."\n".
                '  fi'."\n".
                '  rm ${SPATH}/get_image.lck'."\n".
                'fi'."\n";
        $filename = _SHORT_DIR_.'/'.$this->shorttag.'/img/get_image.sh';
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh, $text);
        fclose($fh);
        switch ($this->image_iteration) {
          case '+':
            $cron_row = '* '.$this->image_start.'-'.$this->image_stop.' * * * /usr/bin/bash '.$filename.' >/dev/null 2>&1'."\n".
                        '* '.$this->image_start.'-'.$this->image_stop.' * * * ( sleep 30; /usr/bin/bash '.$filename.' >/dev/null 2>&1 )'."\n";
            break;
          case '-':
            $cron_row = '* '.$this->image_start.'-'.$this->image_stop.' * * * /usr/bin/bash '.$filename.' >/dev/null 2>&1'."\n".
                        '* '.$this->image_start.'-'.$this->image_stop.' * * * ( sleep 20; /usr/bin/bash '.$filename.' >/dev/null 2>&1 )'."\n".
                        '* '.$this->image_start.'-'.$this->image_stop.' * * * ( sleep 40; /usr/bin/bash '.$filename.' >/dev/null 2>&1 )'."\n";
            break;
          default:
            $cron_row = $this->image_iteration.' '.$this->image_start.'-'.$this->image_stop.
                        ' * * * /usr/bin/bash '.$filename.' >/dev/null 2>&1'."\n";
        }
        
        $this->updateGetImageScriptFromShortdir();
      }
    }
    
    private function updateGetImageScriptFromShortdir() {
      $text = "";        
      $st_dir = scandir(_SHORT_DIR_);
      foreach($st_dir as $dir) {
        if (is_file(_SHORT_DIR_.'/'.$dir.'/shorttag.data')) {
          $data = $this->getShorttagDataFromFile($dir);
          if ($data['active']==true) {
            $filename = _SHORT_DIR_.'/'.$dir.'/img/get_image.sh';
            switch ($data['image_iteration']) {
              case '+':
                $row = '* '.$data['image_start'].'-'.$data['image_stop'].' * * * /usr/bin/bash '.$filename.' >/dev/null 2>&1'."\n".
                       '* '.$data['image_start'].'-'.$data['image_stop'].' * * * ( sleep 30; /usr/bin/bash '.$filename.' >/dev/null 2>&1 )'."\n";
                break;
              case '-':
                $row = '* '.$data['image_start'].'-'.$data['image_stop'].' * * * /usr/bin/bash '.$filename.' >/dev/null 2>&1'."\n".
                       '* '.$data['image_start'].'-'.$data['image_stop'].' * * * ( sleep 20; /usr/bin/bash '.$filename.' >/dev/null 2>&1 )'."\n".
                       '* '.$data['image_start'].'-'.$data['image_stop'].' * * * ( sleep 40; /usr/bin/bash '.$filename.' >/dev/null 2>&1 )'."\n";
                break;
              default:
                $row = $data['image_iteration'].' '.$data['image_start'].'-'.$data['image_stop'].
                       ' * * * /usr/bin/bash '.$filename.' >/dev/null 2>&1'."\n";
            }
            $text .= $row;
          }
        }
      }
      # Die crontab wird neu eingelesen
      $filename = _SHORT_DIR_.'/getimage.cron';
      $fh = fopen($filename, 'w') or die("can't open file");
      fwrite($fh,$text);
      fclose($fh);
      exec('/usr/bin/crontab '._SHORT_DIR_.'/getimage.cron');
    }
    
    private function createMovieScript($type=MovieType::WEEK) {
      if ($this->shorttag != '' && is_dir(_SHORT_DIR_.'/'.$this->shorttag)) {
        if (!is_dir(_SHORT_DIR_.'/'.$this->shorttag.'/'.MovieType::movie_subdir($type)))
          shell_exec('mkdir -p '._SHORT_DIR_.'/'.$this->shorttag.'/'.MovieType::movie_subdir($type));
        
        $absolute_path = $_SERVER["DOCUMENT_ROOT"].substr($_SERVER["PHP_SELF"],0,strlen($_SERVER["PHP_SELF"])-10);
        if (!is_file(_SHORT_DIR_.'/'.$this->shorttag.'/stille-10m.m4a'))
          shell_exec('cp '.$absolute_path.'/public/stille-10m.m4a'.' '._SHORT_DIR_.'/'.$this->shorttag.'/');
          
        # Das ist das Filter-Script. Es sortiert defekte Bilder und SW-Bilder aus
        $text = '';
        foreach(file($absolute_path.'/public/daylight.table') as $row) $text .= $row;
        $filename = _SHORT_DIR_.'/'.$this->shorttag.'/daylight.table';
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh, $text);
        fclose($fh);
        
        $text = '#!/bin/sh'."\n".
                'while read i; do'."\n";
        if ($this->movie_daylight_filter) {
          $text .= '  M=`echo $i | cut -d\'/\' -f3`'."\n".
                   '  D=`echo $i | cut -d\'/\' -f4`'."\n".
                   '  Z=`echo $i | cut -d\'/\' -f5 | cut -b1-4 | sed -e \'s/^[0]*//\'`'."\n".
                   '  MIN=`cat daylight.table | grep "$M\\.$D" | cut -d\';\' -f2 | sed -e \'s/^[0]*//\'`'."\n".
                   '  MAX=`cat daylight.table | grep "$M\\.$D" | cut -d\';\' -f3 | sed -e \'s/^[0]*//\'`'."\n".
                   '  if [ $Z -ge $MIN -a $Z -le $MAX ]; then'."\n";
        }
        $text .= ($this->api_type==APIType::AXIS || $this->movie_daylight_filter ?
                          '    echo >/dev/null 2>&1'."\n":
                          '    strings $i | grep CAM=RIGHT >/dev/null 2>&1'."\n").
                 '    if [ $? -eq 0 ]; then'."\n".
                 '      nice -19 convert $i /dev/null 2>&1 | grep \'Corrupt\' >/dev/null'."\n".
                 '      [ $? -eq 0 ] || echo $i'."\n".
                 '    fi'."\n".
                 ($this->movie_daylight_filter?'  fi'."\n":'').
                 'done < $1'."\n";
        $filename = _SHORT_DIR_.'/'.$this->shorttag.'/filter.sh';
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh, $text);
        fclose($fh);
        
        # Das ist das eigentliche Script zum Erzeugen der Videos
        switch($type) {
          case MovieType::WEEK:     $q = $this->movie_quality_week; break;
          case MovieType::MONTH:    $q = $this->movie_quality_month; break;
          case MovieType::YEAR:     $q = $this->movie_quality_year; break;
          case MovieType::COMPLETE: $q = $this->movie_quality_complete; break;
        }
        $quality = MovieType::ffmpeg_quality_parameter($q,'h264');
        $text = '#!/bin/bash'."\n".
                "SCRIPT_PATH='"._SHORT_DIR_.'/'.$this->shorttag."'\n".
                "SOUND_FILE='"._SHORT_DIR_.'/'.$this->shorttag."/stille-10m.m4a'\n".
                "FILESTUB='".MovieType::movie_subdir($type)."/'$1\n".
                'cd ${SCRIPT_PATH}'."\n".
                'if [ -f "${FILESTUB}.lst" ]; then'."\n".
                '  # NUR RECHNEN, WENN NEUE BILDER VORLIEGEN'."\n".
                '  if [ -f ${FILESTUB}.mp4 ]; then'."\n".
                '    [ `stat -c %Y ${FILESTUB}.mp4` -gt `stat -c %Y ${FILESTUB}.lst` ] && exit'."\n".
                '  fi'."\n".
                '  # LOESCHE ALTE DATEIEN'."\n".
                '  for ENDUNG in jpg mp4 webm tmp.mp4; do'."\n".
                '    [ -f ${FILESTUB}.${ENDUNG} ] && rm ${FILESTUB}.${ENDUNG}'."\n".
                '  done'."\n".
                '  # FILTERN DER BILDER'."\n".
                '  while IFS= read -r line; do'."\n".
                '    farr+=("$line")'."\n".
                '  done < <(/usr/bin/sh ./filter.sh ${FILESTUB}.lst)'."\n".
                '  DIMENSION=`identify ${farr[1]} | cut -d\' \' -f3`'."\n".
                '  case ${DIMENSION} in'."\n".
                '    "3640x2160"|"3840x2160" )'."\n".
                '      echo ${farr[1]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -frames:v 1 -s 3840x2160 ${FILESTUB}.jpg >/dev/null 2>&1'."\n".
                '      echo ${farr[@]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -codec:v h264 '.$quality.' -s 3840x2160 ${FILESTUB}.mp4 >/dev/null 2>&1'."\n".
                '      nice -19 ffmpeg -i ${SOUND_FILE} -i ${FILESTUB}.mp4 -shortest -c:a copy -c:v copy -s 3840x2160 ${FILESTUB}.tmp.mp4 >/dev/null 2>&1'."\n".
                '      ;;'."\n".
                '    "3072x2048" )'."\n";
        $crop  = '-filter:v "crop=3072:1728:0:'.($this->movie_cropping/192*160).',scale=1920x1080"';
        $text .= '      echo ${farr[1]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -frames:v 1 '.$crop.' -s 1920x1080 ${FILESTUB}.jpg >/dev/null 2>&1'."\n".
                 '      echo ${farr[@]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -codec:v h264 '.$quality.' '.$crop.' -s 3072x1728 ${FILESTUB}.mp4 >/dev/null 2>&1'."\n".
                 '      nice -19 ffmpeg -i ${SOUND_FILE} -i ${FILESTUB}.mp4 -shortest -c:a copy -c:v copy -s 1920x1080 ${FILESTUB}.tmp.mp4 >/dev/null 2>&1'."\n".
                 '      ;;'."\n".
                 '    "2592x1944" )'."\n";
        $crop  = '-filter:v "crop=2592:1458:0:'.($this->movie_cropping/192*243).',scale=1920x1080"';
        $text .= '      echo ${farr[1]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -frames:v 1 '.$crop.' -s 1920x1080 ${FILESTUB}.jpg >/dev/null 2>&1'."\n".
                 '      echo ${farr[@]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -codec:v h264 '.$quality.' '.$crop.' -s 2592x1458 ${FILESTUB}.mp4 >/dev/null 2>&1'."\n".
                 '      nice -19 ffmpeg -i ${SOUND_FILE} -i ${FILESTUB}.mp4 -shortest -c:a copy -c:v copy -s 1920x1080 ${FILESTUB}.tmp.mp4 >/dev/null 2>&1'."\n".
                 '      ;;'."\n".
                 '    "2048x1536" )'."\n";
        $crop  = '-filter:v "crop=2048:1152:0:'.$this->movie_cropping.',scale=1920x1080"';
        $text .= '      echo ${farr[1]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -frames:v 1 '.$crop.' -s 1920x1080 ${FILESTUB}.jpg >/dev/null 2>&1'."\n".
                 '      echo ${farr[@]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -codec:v h264 '.$quality.' '.$crop.' -s 2048x1152 ${FILESTUB}.mp4 >/dev/null 2>&1'."\n".
                 '      nice -19 ffmpeg -i ${SOUND_FILE} -i ${FILESTUB}.mp4 -shortest -c:a copy -c:v copy -s 1920x1080 ${FILESTUB}.tmp.mp4 >/dev/null 2>&1'."\n".
                 '      ;;'."\n".
                 '    "1456x1088" )'."\n";
        $crop  = '-filter:v "crop=1408:792:24:'.(round($this->movie_cropping/284*264)).'"';
        $text .= '      echo ${farr[1]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -frames:v 1 '.$crop.' -s 1408x796 ${FILESTUB}.jpg >/dev/null 2>&1'."\n".
                 '      echo ${farr[@]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -codec:v h264 '.$quality.' '.$crop.' -s 1408x792 ${FILESTUB}.mp4 >/dev/null 2>&1'."\n".
                 '      nice -19 ffmpeg -i ${SOUND_FILE} -i ${FILESTUB}.mp4 -shortest -c:a copy -c:v copy -s 1408x792 ${FILESTUB}.tmp.mp4 >/dev/null 2>&1'."\n".
                 '      ;;'."\n".
                 '    "1920x1080" )'."\n".
                 '      echo ${farr[1]} | xargs cat | nice -19 ffmpeg -f mjpeg -i - -frames:v 1 ${FILESTUB}.jpg >/dev/null 2>&1'."\n".
                 '      echo ${farr[@]} | xargs cat | nice -19 ffmpeg -i ${SOUND_FILE} -f mjpeg -i - -shortest -c:a copy -c:v h264 '.$quality.' ${FILESTUB}.tmp.mp4 >/dev/null 2>&1'."\n".
                 '      ;;'."\n".
                 '  esac'."\n".
                 '  /usr/bin/qt-faststart ${FILESTUB}.tmp.mp4 ${FILESTUB}.mp4 >/dev/null 2>&1'."\n".
                 '  rm ${FILESTUB}.tmp.mp4'."\n".
                 '  # Berechne Filme für die Vorschau in geringer Auflösung'."\n".
                 '  for SIZE in 768x432; do'."\n".
                 '    for ENDUNG in jpg webm mp4; do'."\n".
                 '      [ -f ${FILESTUB}-${SIZE}.${ENDUNG} ] && rm ${FILESTUB}-${SIZE}.${ENDUNG}'."\n".
                 '    done'."\n".
                 '    convert ${FILESTUB}.jpg -resize ${SIZE} ${FILESTUB}-${SIZE}.jpg'."\n".
                 '    nice -19 ffmpeg -i ${FILESTUB}.mp4 -s ${SIZE} ${FILESTUB}-${SIZE}.tmp.mp4 >/dev/null 2>&1'."\n".
                 '    /usr/bin/qt-faststart ${FILESTUB}-${SIZE}.tmp.mp4 ${FILESTUB}-${SIZE}.mp4 >/dev/null 2>&1'."\n".
                 '    rm ${FILESTUB}-${SIZE}.tmp.mp4'."\n".
                 '  done'."\n".
                 'fi'."\n";
        // Das ist das Script zum Erzeugen der Filme
        $filename = _SHORT_DIR_.'/'.$this->shorttag.'/'.MovieType::create_movie_script_name($type);
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh, $text);
        fclose($fh);
        
      }
    }
    
    private function createMovieCronScript() {
      $text = "";
      $st_dir = scandir(_SHORT_DIR_);
      foreach($st_dir as $dir) {
        if (is_file(_SHORT_DIR_.'/'.$dir.'/shorttag.data')) {
          $data = $this->getShorttagDataFromFile($dir);
          if ($data['active']==true) {
             if ($data['create_movie_week']==true) {
              $filename= _SHORT_DIR_.'/'.$dir.'/'.MovieType::create_movie_script_name(MovieType::WEEK);
              $text .= '/usr/bin/bash '.$filename.' `date +%Y%02V` '."\n";
            }
            if ($data['create_movie_month']==true) {
              $filename= _SHORT_DIR_.'/'.$dir.'/'.MovieType::create_movie_script_name(MovieType::MONTH);
              $text .= '/usr/bin/bash '.$filename.' `date +%Y%m` '."\n";
            }      	  
            if ($data['create_movie_year']==true) {
              $filename= _SHORT_DIR_.'/'.$dir.'/'.MovieType::create_movie_script_name(MovieType::YEAR);
              $text .= '/usr/bin/bash '.$filename.' `date +%Y` '."\n";
            }
            if ($data['create_movie_complete']==true) {
              $filename= _SHORT_DIR_.'/'.$dir.'/'.MovieType::create_movie_script_name(MovieType::COMPLETE);
              $text .= '/usr/bin/bash '.$filename.' complete '."\n";
            }
          }
        }
      }
      $filename = _SHORT_DIR_.'/movie.cron';
      $fh = fopen($filename, 'w') or die("can't open file");
      fwrite($fh,$text);
      fclose($fh);
    }
    

    private function createMrtgScript() {
      if ($this->shorttag != '' && is_dir(_SHORT_DIR_.'/'.$this->shorttag)) {
      	$identifier = $this->shorttag;
      	$filename = _MRTG_DIR_.'/'.$identifier.'.sh';
      	$text     = '#!/bin/bash'."\n".
      	            'if [ "`awk \'{ print $1; }\' '._SHORT_DIR_.'/'.$identifier.'/status`" == "ok" ]; then'."\n".
      	            '  echo -en "1\n0"'."\n".
      	            'else'."\n".
      	            '  echo -en "0\n0"'."\n".
      	            'fi'."\n";
      	$fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh,$text);    
        chmod($filename,0755);
      	$fieldname = $this->shorttag.'-img';
        $text = 'Title['.$identifier.']: Online-Zeit '.$this->name."\n".
                'Target['.$identifier.']: `'.$filename.'`'."\n".
                'Options['.$identifier.']: gauge,growright,noinfo,nolegend,nobanner,integer,noborder,transparent,noo'."\n".
                'XSize['.$identifier.']: 500'."\n".
                'YSize['.$identifier.']: 30'."\n".
                'YTics['.$identifier.']: 1'."\n".
                'MaxBytes['.$identifier.']: 1'."\n".
                'Ylegend['.$identifier.']: On'."\n".
                'LegendI['.$identifier.']:'."\n".
                'Legend1['.$identifier.']:'."\n\n".
                'Title['.$fieldname.']: Pic Counter'."\n".
                'Target['.$fieldname.']: `ls -l '._SHORT_DIR_.'/'.$identifier.'/img/\\`date +%Y/%m/%d/\\`*.jpg | wc -l;echo 0`'."\n".
                'Options['.$fieldname.']: gauge,growright,noinfo,nolegend,nobanner,integer,noborder,transparent,noo'."\n".
                'XSize['.$fieldname.']: 500'."\n".
                'YSize['.$fieldname.']: 100'."\n".
                'MaxBytes['.$fieldname.']: 1000'."\n".
                'Ylegend['.$fieldname.']: Bilder Serie'."\n".
                'LegendI['.$fieldname.']:'."\n".
                'Legend1['.$fieldname.']:'."\n";
        $filename = _MRTG_DIR_.'/'.$identifier.'.inc';
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh,$text);
        fclose($fh);
        
        # Aktualisiere nun die Steuerdatei für MRTG
        $this->updateMrtgScriptFromShortdir();
      }
    }
    
    private function updateMrtgScriptFromShortdir() {
      $filename = _MRTG_DIR_.'/mrtg.cfg';
      $text = 'WorkDir: '._MRTG_DIR_."\n".
              'Forks: 4'."\n".
              'Refresh: 300'."\n".
              'Interval: 5'."\n".
              'Language: german'."\n\n";
        
      $st_dir = scandir(_SHORT_DIR_);
      foreach($st_dir as $dir) {
        if (is_file(_SHORT_DIR_.'/'.$dir.'/shorttag.data')) {
          if (isset($output)) unset($output);
          exec('cat '._SHORT_DIR_.'/'.$dir.'/shorttag.data | grep \'"active":\' | cut -f2 -d" "',$output);
          if (is_array($output) && $output[0]=='"true"') $text .= "Include: ".$dir.".inc\n";
        }
      }
      $fh = fopen($filename, 'w') or die("can't open file");
      fwrite($fh,$text);
      fclose($fh);
    }
    
    private function createPasswordFiles() {
      if ($this->shorttag != '' && is_dir(_SHORT_DIR_.'/'.$this->shorttag)) {
        $directory = _SHORT_DIR_.'/'.$this->shorttag;
        $inhalt = '';
        $htpasswd = '';
        $htaccess = 'AuthType Basic'."\n".'AuthName "Members"'."\n".
                    'AuthUserFile '.$directory.'/.htpass'."\n".
                    'Require valid-user'."\n";
        if ($this->api_operator_secret!='') {
          $inhalt .= ($inhalt!=''?"\n":'').'operator:'.md5($this->api_operator_secret);
          $htpasswd .= ($htpasswd!=''?"\n":'').'operator:'.crypt($this->api_operator_secret,base64_encode($this->api_operator_secret));
        }
        if ($this->api_user_secret!='') {
          $inhalt .= ($inhalt!=''?"\n":'').'user:'.md5($this->api_user_secret);
          $htpasswd .= ($htpasswd!=''?"\n":'').'user:'.crypt($this->api_user_secret,base64_encode($this->api_user_secret));
        }
        if ($inhalt!='') {
          file_put_contents($directory.'/.password',$inhalt);
          file_put_contents($directory.'/.htaccess',$htaccess);
          file_put_contents($directory.'/.htpass',$htpasswd);
        } else {
          if (is_file($directory.'/.password')) unlink($directory.'/.password');
          if (is_file($directory.'/.htaccess')) unlink($directory.'/.htaccess');
          if (is_file($directory.'/.htpass')) unlink($directory.'/.htpass');
        }

      }
    }
    
    private function createJsDescription() {
      if ($this->shorttag != '' && is_dir(_SHORT_DIR_.'/'.$this->shorttag)) {
      	$text_other = '';
      	$others = explode(':',$this->other);
      	if (count($others)>0)
      	  foreach($others as $other)
      	  $text_other .= ($text_other!=''?', ':'').'"'.$other.'"';
        $text = "\t\t".'{ "name": "'.$this->name.'",'."\n".
                "\t\t".'  "beschreibung": "'.$this->description.'",'."\n".
                "\t\t".'  "projekt": "'.$this->project_name.'",'."\n".
                "\t\t".'  "aktiv": '.($this->active?'true':'false').','."\n".
                "\t\t".'  "monitor": '.($this->active_monitoring?'true':'false').','."\n".
                "\t\t".'  "dir": "'.$this->shorttag.'",'."\n".
                "\t\t".'  "other": [ '.$text_other.' ],'."\n".
                "\t\t".'  "serien": ['."\n".
                "\t\t\t".'{ "dir": "img",'."\n".
                "\t\t\t".'  "thumbdir": "240",'."\n".
                "\t\t\t".'  "quality": "'.$this->image_profile.'",'."\n".
                "\t\t\t".'  "iteration": "'.ImageIteration::description($this->image_iteration).'"'."\n".
                "\t\t\t".'}'."\n".
                "\t\t".'  ]'."\n".
                "\t\t".'}';
                
        $filename = _SHORT_DIR_.'/'.$this->shorttag.'/description.js';
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh,$text);
        fclose($fh);
      }
    }
    
    private function createExportToCloudScript() {
      if ($this->shorttag != '' && is_dir(_SHORT_DIR_.'/'.$this->shorttag)) {
        $directory = _SHORT_DIR_.'/'.$this->shorttag;
        $text = '#/bin/bash'."\n".
                'cd '.$directory."\n".
                'SHORTTAG='.$this->shorttag."\n".
                'SUBDIR=img'."\n".
                'cd '.$directory."/${SUBDIR}\n".
                'YEARS=`ls -d 20[[:digit:]][[:digit:]] | sort`'."\n".
                'cd '.$directory."\n".
                'if [ "${YEARS}" != "" ]; then'."\n".
                '  [ -d tmp ] && rm -rf tmp'."\n".
                '  mkdir -p tmp/${SHORTTAG}'."\n".
                ($this->create_movie_week || $this->create_movie_month || $this->create_movie_year || $this->create_movie_complete ?
                '  mkdir -p tmp/${SHORTTAG}/filme'."\n".'  cp movies/complete.mp4 tmp/${SHORTTAG}/filme/'."\n":'');
        if ($this->create_movie_week)
          $text .= '  mkdir -p tmp/${SHORTTAG}/filme/kw'."\n".
                   '  echo "Erzeuge Filme"'."\n".
                   '  for w in `ls movies/week/*.lst | cut -d\'/\' -f3 | cut -d\'.\' -f1`; do'."\n".
                   '    if [ ! -f movies/week/$w.mp4 ]; then'."\n".
                   '      echo "Kalenderwoche "$w'."\n".
                   '      /usr/bin/bash '.$directory.'/create_movie_week.sh $w'."\n".
                   '    fi'."\n".
                   '  done'."\n".
                   '  cp movies/week/*.mp4 tmp/${SHORTTAG}/filme/kw/'."\n";
        $text .= '  for Y in ${YEARS}; do '."\n".
                 '    for M in 01 02 03 04 05 06 07 08 09 10 11 12; do '."\n".
                 '      [ -d ${SUBDIR}/${Y}/${M} ] && zip -r tmp/${SHORTTAG}/${SHORTTAG}-${SUBDIR}-${Y}-${M} ${SUBDIR}/${Y}/${M}'."\n".
                 '    done'."\n".
                 '  done'."\n".
                 '  rsync -az -e ssh tmp/ cloud:/var/lib/wwwrun/data/mietkamera/files/'."\n".
                 '  sleep 10'."\n".
                 "  ssh cloud 'cd /var/lib/wwwrun/data/mietkamera/;chown -R wwwrun.nogroup *;'\n".
                 "  ssh cloud 'cd /var/lib/wwwrun/cloud/;sudo -u wwwrun php ./occ files:scan mietkamera;'\n".
                 '  rm -rf tmp'."\n".
                 '  echo "Die Archive befinden sich im Verzeichnis ${SHORTTAG}"'."\n".
                 'fi'."\n";
        $filename = _SHORT_DIR_.'/'.$this->shorttag.'/export_to_cloud.sh';
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh,$text);
        fclose($fh);
      }
    }


  }
?>