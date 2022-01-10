<?php
  $json = '{'."\n".
          '  "returncode": "'.(isset($this->errorcode)?$this->errorcode:'500').'",'."\n".
          '  "message": "'.(isset($this->msg)?$this->msg:'unbekannter Fehler').'"'."\n";
  
  $json .= '}';
?>