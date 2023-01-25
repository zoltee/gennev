<?php
namespace Lib;

class Param {
	const GET_AS_IS = 'asIs';
	const GET_AS_HTML_SAFE = 'asHTML';
	const GET_AS_NUMBER = 'asNr';
	const GET_AS_DATE = 'asDate';
	const GET_AS_SANITIZED = 'asSanitized';

	const TYPE_NUMBER = 'number';
	const TYPE_BOOLEAN = 'boolean';
	const TYPE_STRING = 'string';
	const TYPE_TEXT = 'text';
	const TYPE_ARRAY = 'array';
	const TYPE_SHA1 = 'sha1';
	const TYPE_MD5 = 'md5';
	const TYPE_EMAIL = 'email';
	const TYPE_PASSWORD = 'password';
	const TYPE_SET = 'set';
	const TYPE_DATE = 'date';

	const TYPE_OPTION_MIN = 'min';
	const TYPE_OPTION_MAX = 'max';
	const TYPE_OPTION_FLOAT = 'float';
	const TYPE_OPTION_DECIMAL = 'decimal';

	const PARAM_TYPE = 'type';
	const PARAM_DEFAULT = 'default';
//	const PARAM_SET_DEFAULT = 'set_default';
	const PARAM_TRIM = 'trim';
	const PARAM_THROW_EXCEPTION = 'throw_exception';
	const PARAM_ERROR = 'exception';
	const PARAM_SET_MESSAGE = 'set_message';
	const PARAM_UNSET_VAR = 'unset_var';
	const PARAM_FORMAT = 'format';
	const PARAM_SET = 'set';
	const PARAM_KEY_SET = 'key_set';
	const PARAM_NAME = 'name';
	const PARAM_AS = 'as';

	const PARAM_SOURCE = 'source';

	const ERROR_MISSING_ARGUMENT="One or more arguments are missing: <!arg!>. Please provide all the arguments.";
	const ERROR_INVALID_NUMBER="The number you provided for <!arg!> is invalid.";
	const ERROR_INVALID_DATE="The date you provided for <!arg!> is invalid.";
	const ERROR_INVALID_ARRAY="The data you provided for <!arg!> is of invalid type.";
	const ERROR_INVALID_SET="The data you provided for <!arg!> is of invalid value .";
	const ERROR_INVALID_EMAIL="The email you provided for <!arg!> is invalid.";
	const ERROR_INVALID_HASH="The hash you provided for <!arg!> is invalid.";
	const ERROR_INVALID_VALUE="The value you provided for <!arg!> is invalid.";
	const ERROR_INVALID_BOOLEAN="The value you provided for <!arg!> is invalid.";
	const ERROR_INVALID_ARGUMENT="Invalid arguments. <!arg!>.";
	const ERROR_NOT_LOGGED_IN="Your session expired, please login again.";
	const ERROR_NOT_ADMIN="You don't have enough privileges to do this.";
	const ERROR_NOT_OWNER="You are not allowed to access this.";
	const ERROR_UNKNOWN="An unknown error occurred receiving the parameter <!arg!>.";
	const ERROR_NO_FILE="No File Uploaded";



	static $params;

	/** Validates a variable value
	 * @param $name string
	 * @param $value
	 * @param $desc array
	 *      has the following structure:
	 *          * type []
	 *          -
	 * @throws Exception
	 */

	static function validate($name, $value, $desc){
		if (is_scalar($value) && (!isset($desc[self::PARAM_TRIM]) || $desc[self::PARAM_TRIM] !== false)){
			$value = trim($value);
		}

		switch ($desc[self::PARAM_TYPE]){
			case self::TYPE_NUMBER:
				$options = ['options' => [], 'flags' => []];
				if (array_key_exists(self::TYPE_OPTION_MIN, $desc)){
					$options['options']['min_range'] = $desc[self::TYPE_OPTION_MIN];
				}
				if (array_key_exists(self::TYPE_OPTION_MAX, $desc)){
					$options['options']['max_range'] = $desc[self::TYPE_OPTION_MAX];
				}
				if (array_key_exists(self::TYPE_OPTION_DECIMAL, $desc)){
					$options['options']['decimal'] = $desc[self::TYPE_OPTION_DECIMAL];
					$desc[self::TYPE_OPTION_FLOAT] = true;
				}
				if(($value = filter_var($value, empty($desc[self::TYPE_OPTION_FLOAT]) ? FILTER_VALIDATE_INT : FILTER_VALIDATE_FLOAT, $options )) === false){
					if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_NUMBER;
					$error = self::handleError($name, $value, $desc);
				}
				if (isset($value)) self::$params[$name] = $value;
			break;
			case self::TYPE_BOOLEAN:
				if(!filter_var($value, FILTER_VALIDATE_BOOLEAN)){
					if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_BOOLEAN;
					$error = self::handleError($name, $value, $desc);
				}
				if (isset($value)) self::$params[$name] = $value;
			break;
			case self::TYPE_EMAIL:
				if(!filter_var($value, FILTER_VALIDATE_EMAIL)){
					if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_EMAIL;
					$error = self::handleError($name, $value, $desc);
				}
				if (isset($value)) self::$params[$name] = $value;
			break;

			case self::TYPE_SHA1:
				if(!filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regex' => "/^[0-9a-fA-F]{40}$/"]])){
					if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_HASH;
					$error = self::handleError($name, $value, $desc);
				}
				if (isset($value)) self::$params[$name] = $value;
			break;

			case self::TYPE_MD5:
				if(!filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regex' => "/^[0-9a-fA-F]{32}$/"]])){
					if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_HASH;
					$error = self::handleError($name, $value, $desc);
				}
				if (isset($value)) self::$params[$name] = $value;
			break;

			case self::TYPE_DATE:
				$timestamp=strtotime($value);
				if($timestamp===FALSE){
					if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_DATE;
					$error = self::handleError($name, $value, $desc);
				}
				$dateFormat = $desc[self::PARAM_FORMAT] ?? 'Y-m-d';
				$value=date($dateFormat,$timestamp);
				if (isset($value)) self::$params[$name] = $value;
			break;

			case self::TYPE_ARRAY:
				if(!is_array($value)){
					if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_ARRAY;
					$error = self::handleError($name, $value, $desc);
				}elseif (!empty($desc[self::PARAM_SET]) || !empty($desc[self::PARAM_KEY_SET])){
					foreach ($value as $index => &$item) {
						if(
							(!empty($desc[self::PARAM_SET]) && !in_array($item, $desc[self::PARAM_SET])) ||
							(!empty($desc[self::PARAM_KEY_SET]) && !in_array($index, $desc[self::PARAM_KEY_SET]))
						){
							if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_SET;
							$error = self::handleError($name.'['.$index.']', $item, $desc);
							if(!empty($desc[self::PARAM_UNSET_VAR]))unset($value[$index]);
						}
					}
				}
				if (isset($value)) self::$params[$name] = $value;
			break;

			case self::TYPE_SET:
				if(!in_array($value, $desc[self::PARAM_SET])){
					if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_SET;
					$error = self::handleError($name, $value, $desc);
				}
				if (isset($value)) self::$params[$name] = $value;
			break;

			case self::TYPE_STRING:

			default:
				if (empty($value)){
					if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_VALUE;
					$error = self::handleError($name, $value, $desc);
				}
				if (isset($value)) self::$params[$name] = $value;
		break;
		}
	}

	/**
	 * validate the input params listed, keyed by the param name
	 * @param array $list
	 * @param null $source
	 * @throws Exception
	 */
	static function validateList($list = [], &$source = null){
		if (is_null($source)){
			$source = &$_REQUEST;
		}
		foreach ($list as $name => $desc) {
			// type is mandatory
			if (empty($desc[self::PARAM_TYPE])){
				continue;
			}
			//parameter doesn't exist
			if (!array_key_exists($name, $source)){
				if (array_key_exists(self::PARAM_DEFAULT, $desc)){
					self::$params[$name] = $desc[self::PARAM_DEFAULT];
				}
				continue;
			}
			//validate the variable value from source
			self::validate($name, $source[$name], $desc);
		}
	}

	/**
	 * get a particular parameter
	 * @param $name
	 * @param null $as
	 * @param array $desc
	 * @return false|mixed|string|null
	 */
	static function get($name, $as = null, $desc=[]){
		if (empty(self::$params) || !array_key_exists($name, self::$params)){
			return null;
		}
		$options = ['options' => [], 'flags' => []];
		if (!empty($desc['flags'])) $options['flags'] = $desc['flags'];
		if (!empty($desc['options'])) $options['options'] = $desc['options'];
		switch ($as){
			case self::GET_AS_NUMBER:
				if (array_key_exists(self::TYPE_OPTION_MIN, $desc)){
					$options['options']['min_range'] = $desc[self::TYPE_OPTION_MIN];
				}
				if (array_key_exists(self::TYPE_OPTION_MAX, $desc)){
					$options['options']['max_range'] = $desc[self::TYPE_OPTION_MAX];
				}
				if (array_key_exists(self::TYPE_OPTION_DECIMAL, $desc)){
					$options['options']['decimal'] = $desc[self::TYPE_OPTION_DECIMAL];
					$desc[self::TYPE_OPTION_FLOAT] = true;
				}

				if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_INVALID_NUMBER;
				return filter_var(self::$params[$name], empty($desc[self::TYPE_OPTION_FLOAT]) ? FILTER_VALIDATE_INT : FILTER_VALIDATE_FLOAT, $options );
			break;
			case self::GET_AS_DATE:
				$timestamp=strtotime(self::$params[$name]);
				if($timestamp===FALSE){
					return "{invalid date format for $name}";
				}
				$dateFormat = $desc[self::PARAM_FORMAT] ?? 'Y-m-d';
				return date($dateFormat,$timestamp);
			break;
			case self::GET_AS_HTML_SAFE:
				return strip_tags(self::$params[$name], $desc['options'] ?? null);
			break;
			case self::GET_AS_SANITIZED:
				$filter = $desc['filter'] ?? FILTER_DEFAULT;
				return filter_var(self::$params[$name], $filter, $options);
			default:
			case self::GET_AS_IS:
				return self::$params[$name];
		}
	}

	static function exists($name){
		if (empty(self::$params)) return false;
		return array_key_exists($name, self::$params);
	}

	/**
	 * get the list of validated params
	 * @param $list
	 * @return array
	 */
	static function getList($list){
		if (empty($list)){
			return [];
		}
		$first = reset($list);
		//it is a list of keys
		if (!is_array($first)){
			return array_intersect_key(self::$params, array_flip($list));
		}else{
			$return = [];
			foreach ($list as $key => $desc) {
				$as = $desc[self::PARAM_AS] ?? null;
				$return[$key] = self::get($key, $as, $desc);
			}
			return $return;
		}
	}

	/**
	 * generate a querystring from the validated params
	 * @return string
	 */
	static function getQuery(){
		return http_build_query(self::$params);
	}

	/**
	 * @param array $desc
	 * @return bool|mixed|string|string[]
	 * @throws Exception
	 */
	public static function checkAdmin($desc=[]){
		if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_NOT_ADMIN;
		if(!\Model\User::isAdmin())return self::handleError('Admin', $value, $desc);
	}

	/**
	 * @param array $desc
	 * @return bool|mixed|string|string[]
	 * @throws Exception
	 */
	public static function checkLoggedIn($desc=[]){
		if(!\Model\User::isLoggedIn() ){
			if (empty($desc[self::PARAM_ERROR]))$desc[self::PARAM_ERROR] = self::ERROR_NOT_LOGGED_IN;
			return self::handleError('Login', $value, $desc);
		}
		return true;
	}


	/**
	 * performs a redirect
	 * @param array $arr
	 */
	public static function redirect($arr=[]){
		$link=$arr['server'] ?? '/';
		$link .= $arr['action'] ?? '';

		if(empty($arr['params']))$arr['params']=[];
		if(!empty($arr['include_params'])){
			$arr['params']=array_merge($arr['params'],self::$params);
		}
		if(!empty($arr['params'])){
			$params = $arr['params'];
			unset($params['action']);
			$query = http_build_query($params);
			if (!empty($query)) $link .= '?' . $query;
		}
		if(!headers_sent()){
			header("Location: $link", true,empty($arr['permanent'])?302:301);
		} else{
			print("<script>location.href=\"$link\"</script>");
		}
		die();
	}

	/**
	 * error handler for input validator
	 * @param $name
	 * @param $value
	 * @param $desc
	 * @return bool|mixed|string|string[]
	 * @throws Exception
	 */
	public static function handleError($name, &$value, $desc){

		if(isset($desc[self::PARAM_DEFAULT])){
			$value=$desc[self::PARAM_DEFAULT];
			return false;
		}
		if(empty($error))$error= !empty($desc[self::PARAM_ERROR]) ? $desc[self::PARAM_ERROR] : self::ERROR_UNKNOWN;

		$title = array_key_exists(self::PARAM_NAME, $desc) ? $desc[self::PARAM_NAME] : $name;
		$error = str_replace('<!arg!>', $title, $error);

		if(!empty($desc[self::PARAM_SET_MESSAGE])) \Lib\Alerts::add_message($error,\Lib\Alerts::MSG_TYPE_ERROR);
		//if(!empty($desc[self::PARAM_UNSET_VAR])) $value = null;
		if(!empty($desc[self::PARAM_UNSET_VAR]))unset($value);
		if(!empty($desc[self::PARAM_THROW_EXCEPTION])) {
			throw new Exception($error);
		}
		return $error;
	}

    public static function getHeaders(){
        $headers = [];
        foreach (getallheaders() as $key => $value){
            $headers[$key] = $value;
        }
        return $headers;
    }
}