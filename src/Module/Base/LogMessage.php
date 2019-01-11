<?php
namespace Module\Base;

class LogMessage {

	protected static $arrMessage=array();

	static  function log($p_strMessage) {
		self::$arrMessage[] = $p_strMessage;
	}
	
	static function getMessage() {
		return self::$arrMessage;
	}
	
	static function getLastMessage() {
		return end(self::$arrMessage);
	}
}