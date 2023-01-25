<?php
namespace Lib;
/**
 * Data handler class
 */
class Data {
static $data = [];

	static function &set($name, $data){
		self::$data[$name] = $data;
		return self::$data[$name];
	}

	static function &push($name, $data, $index = false){
		if (!array_key_exists($name, self::$data)){
			self::$data[$name] = [];
		}
		if (!is_array(self::$data[$name])){
			throw new Exception("Can only push into an array.");
		}
		if ($index !==  false){
			self::$data[$name][$index] = $data;
		}else{
			self::$data[$name][] = $data;
		}
		return self::$data[$name];
	}

	static function get($name, $default = null){
		if (!array_key_exists($name, self::$data)){
			return $default;
			//throw new Exception("Data not loaded $name.");
		}
		return self::$data[$name];
	}

	static function &getRef($name){
		if (!array_key_exists($name, self::$data)){
			throw new Exception("Data not loaded $name.");
		}
		return self::$data[$name];
	}

	static function exists($name, $index = false){
		if ($index !== false && is_array(self::$data[$name])){
			return array_key_exists($index, self::$data[$name]);
		}
		return array_key_exists($name, self::$data);
	}

	static function empty($name){
		if (!array_key_exists($name, self::$data)){
			return true;
		}
		return empty(self::$data[$name]);
	}

	static function getKeys($name, $discard=false){
		$data = self::get($name);
		if (is_object($data) && is_a($data, 'Generator')){
			$newData = [];
			$keys = [];
			foreach ($data as $key => $value) {
				$keys[] = $key;
				if (!$discard)$newData[$key] = $value;
			}
			if (!$discard)self::$data[$name] = $newData;
			return $keys;
		}
		if (is_array($data)){
			return array_keys($data);
		}
		return [];
	}

}