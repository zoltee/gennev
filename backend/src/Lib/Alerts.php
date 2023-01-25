<?php
namespace Lib;
class Alerts{
	const MSG_TYPE_ERROR = 'err';
	const MSG_TYPE_MESSAGE = 'msg';
	const MSG_TYPE_SUCCESS = 'ok';
	private static $dumps = [];
	public static function add_message($msg, $type=self::MSG_TYPE_MESSAGE){
		if($type==self::MSG_TYPE_ERROR)Logger::log($msg);
		$messages = $_SESSION['messages'] ?? [];
		$messages[] = ["type"=>$type, 'user_id'=>false,'message'=>$msg, 'seen'=>0];
		$_SESSION['messages'] = $messages;
	}

	public static function get_all_messages($clear = true){
		$ra = [];
		$ra+=$_SESSION['messages'] ?? [];
		if ($clear){
			$_SESSION['messages'] = [];
		}
		return $ra;
	}

	public static function messages_by_type($clear = true){
		$ra=[];
		$messages = self::get_all_messages($clear);
		if(!empty($messages)){
			$map = ['err'=>'error', 'msg' => 'message', 'ok' => 'success'];
			foreach ($messages as $message) {
				$ra[$map[$message['type']]][]=$message['message'];
			}
			if (!empty($ra['success'])) $ra['success'] =    implode('<br>',$ra['success']);
			if (!empty($ra['message'])) $ra['message'] =    implode('<br>',$ra['message']);
			if (!empty($ra['error']))   $ra['error'] =      implode('<br>',$ra['error']);
		}

		return $ra;
	}

	public static function hasError(){
		$messages = self::get_all_messages(false);
		if (empty($messages)){
			return false;
		}
		foreach ($messages as $key =>$value) {
			if ($value['type'] == self::MSG_TYPE_ERROR){
				return true;
			}
		}
		return false;
	}

	public static function dump($var){
		self::$dumps[] = $var;
	}
	public static function hasDumps(){
		return !empty(self::$dumps);
	}
	public static function getDumps(){
		return self::$dumps;
	}

}
