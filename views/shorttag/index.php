<?php
  $json = '{'."\n".
          '  "returncode": "'.(isset($this->errorcode)?$this->errorcode:'500').'",'."\n".
          '  "message": "'.(isset($this->msg)?$this->msg:'unbekannter Fehler').'",'."\n";
  if (isset($this->payload)) {
    $json .= '  "payload": {'."\n";
    $first_item = true;
    foreach ($this->payload as $item) {
      if (!$first_item) $json .= ','."\n";
      foreach ($item as $key => $val) {
        $json .= '     "'.$key.'": "'.$val.'"';
      }
      $first_item = false;
    }
    $json .= "\n".'  }'."\n";
  } 
  $json .= '}';
?>