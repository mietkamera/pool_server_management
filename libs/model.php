<?php

require 'globals.php';

class Model {
	
	function __construct() {
		$this->db = new Database;
	}
	
    public function _add($schema,$data) {
      $result = array('status'=>_SUCCESS_,'id'=>0,'message'=>"Datensatz hinzugef&uuml;gt.");
      
      $fieldlist = '';
      $valuelist = '';
      foreach ($data as $field=>$value) {
      	if (isset($schema['fields'][$field])) {
          $fieldlist .= ($fieldlist==''?'':',').$field;
      	  $value = str_replace("'","",$value);
      	  switch ($schema['fields'][$field]['type']) {
      	    case 'point':
      	  	  $val = 'GeomFromText(\'POINT('.$value.')\')';
      	  	  break;
      	    case 'int':    $val = intval(preg_replace('![^0-9]!', '',$value)); break;
      	    case 'text':   
      	    case 'string': $val = "'".str_replace("\"", '', str_replace("'", '', $value))."'"; break;
      	    case 'float':  $val = floatval(str_replace(',','.',preg_replace('![^0-9,.]!', '',$value))); break;
      	    case 'bool':   $val = $value?'true':'false'; break;
      	    case 'date':   $val = "'".substr($value,6,4).'-'.substr($value,3,2).'-'.substr($value,0,2)." 00:00:00'"; break;
      	    default:       $val = "'".$value."'"; break;
      	  }
      	  $valuelist .= ($valuelist==''?'':',').$val;
      	}
      }
      $sql = 'INSERT INTO '.$schema['name'].' ('.$fieldlist.') VALUES ('.$valuelist.')';
      if (_PRINT_DEBUG_LOG_) error_log('model->_add(): '.$sql);
      try {
	  	  $stmt = $this->db->prepare($sql);
	  	  $stmt->execute();
	  	  $id = $this->db->lastInsertId();
	  	  $result['status'] = _SUCCESS_;
	  	  $result['id'] = $id;
	  	  $result['maxpage'] = ceil($this->countRecords($schema['name'])/_MAX_ROWS_);
      }
      catch(PDOException $e){
	  	$result['status'] = _ERROR_;
	  	$result['id'] = 0; 
		$result['message'] = $e->getMessage();
	  }
	  return $result;
    }
    
    public function _update($schema,$data) {
      if (_PRINT_DEBUG_LOG_) error_log('model->_update(): '.print_r($data,true));
      $result = array('status'=>_SUCCESS_,'message'=>"Datensatz aktualisiert.");
      $setlist = '';
      foreach ($data as $field=>$value) {
      	if (isset($schema['fields'][$field])) {
          if ($field!=$schema['primary_key']) {
            $value = str_replace("'","",$value);
      	    switch ($schema['fields'][$field]['type']) {
              case 'int':    $val = intval(preg_replace('![^0-9]!', '',$value)); break;
      	      case 'text':
              case 'string': $val = "'".str_replace("\"", '', str_replace("'", '', $value))."'"; break;
              case 'select': $val = "'".$value."'"; break;
              case 'float':  $val = floatval(str_replace(',','.',preg_replace('![^0-9,.]!', '',$value))); break;
              case 'bool':   $val = $value?'true':'false'; break;
              case 'date':   $val = "'".substr($value,6,4).'-'.substr($value,3,2).'-'.substr($value,0,2)." 00:00:00'"; break;
              default:       $val = "'".$value."'"; break;
            }
            $setlist .= ($setlist==''?'':',').$field.'='.$val;
          }
      	}
      }
      $sql = 'UPDATE '.$schema['name'].' SET '.$setlist.
             ' WHERE '.$schema['primary_key'].'='.$data[$schema['primary_key']];
      if (_PRINT_DEBUG_LOG_) error_log('model->_update(): '.$sql);
      try {
	    $stmt = $this->db->query($sql);
      }
      catch(PDOException $e){
	  	$result['status'] = _ERROR_;
		$result['message'] = $e->getMessage();
	  }
	  return $result;
    }
    
    public function _delete($schema,$data) {
      $result = array('status'=>_SUCCESS_,'message'=>"Datensatz gel&ouml;scht.");
      $sql = 'DELETE FROM '.$schema['name'].' WHERE '.$schema['primary_key'].'='.$data;
      if (_PRINT_DEBUG_LOG_) error_log('model->_delete(): '.$sql);
      try {
	    $stmt = $this->db->query($sql);
	    $result['status'] = _SUCCESS_;
	    $this->maxpage = ceil($this->countRecords($schema['name'])/_MAX_ROWS_);
	    $result['maxpage'] = "$this->maxpage";
      }
      catch(PDOException $e){
	  	$result['status'] = _ERROR_;
		$result['message'] = $e->getMessage();
	  }
	  return $result;
    }
    
	public function countRecords($table,$where='') {
	  try {
	    $stmt = $this->db->prepare("SELECT * FROM $table".($where!=''?" WHERE $where":""));
	  	$stmt->execute();
	  	return $stmt->rowCount();
	  }
	  catch(PDOException $e) {
	  	return 0;
	  }
	}
	
	public function getSQLData($sql) {
	  try {
	    $stmt = $this->db->query($sql);
	    if (_PRINT_DEBUG_LOG_) error_log('model->getSQLData(): '.$sql);
	    if (!is_null($stmt)) 
	      return $stmt->fetchAll();
	     else
	      return NULL;
	  }
	  catch(PDOException $e) {
	  	return NULL;
	  }
	}
}
?>
