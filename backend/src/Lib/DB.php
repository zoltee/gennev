<?php
namespace Lib;
class DB {
	/**
	 * @var PDO $conn
	 */

	private static $conn = false;
	public static $lastQuery = false;
	public static $collect_metrics = false;
	public static $quote = '';

	public static function connect($host = false,$username = false,$password = false, $db = false){
		if (empty($host)) $host = DB_SERVER;
		if (empty($username)) $username = DB_USERNAME;
		if (empty($password)) $password = DB_PASSWORD;
		$db_type = defined('DB_SERVER_TYPE') ? DB_SERVER_TYPE : 'mysql';
		$dsn = '';
		switch ($db_type){
			case 'postgres':
				$dsn = "pgsql:host=$host" . (!is_null($db) ? (';dbname=' . ($db ?: DB_NAME)) : '');
				self::$quote = '';
			break;
			default:
			case 'mysql':
				$dsn = "mysql:host=$host;charset=utf8" . (!is_null($db) ? (';dbname=' . ($db ?: DB_NAME)) : '');
				self::$quote = '`';
			break;
		}
		try {
			self::$conn = new \PDO($dsn, $username, $password);
		} catch (\PDOException $e) {
			throw new \Exception("Error connecting to the database: " . $e->getMessage());
		}
		self::$conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		return self::$conn;
	}
	public static function setConnection($conn){
		self::$conn = $conn;
	}

	public static function execute($query){
		self::connect(false,false,false,null);

		self::$lastQuery = $query;
		return self::$conn->query($query);
	}

	public static function insert($table, $fields, $params = []){
		if (empty(self::$conn)){
			self::connect();
		}
		$def_params= ["conditions"=>[],"replacements"=>[], "save"=>true];
		$params=array_merge($def_params, $params);
		$vals = [];
		$names = [];
		$ODKUs = [];
		foreach ($fields as $name => $value) {
			$names[$name] = self::$quote.$name.self::$quote;
			if (!empty($params['ODKU'])){
				$ODKUs[$name] = self::$quote.$name.self::$quote.' = VALUES('.self::$quote.$name.self::$quote.')';
			}

			if (is_array($value) && isset($value['#type']) && isset($value['#value'])){
				if ($value['#type'] == 'literal'){
					$vals[$name] = $value;
				}else{
					$key = self::replacementKey($name, $value['#value'], $params['replacements']);
					$vals[$name] = $key;
				}
			}else{
				$key = self::replacementKey($name, $value, $params['replacements']);
				$vals[$name] = $key;
			}
		}
		$ignore = empty($params['ignore']) ? '' : 'IGNORE';

		$ODKU = empty($params['ODKU']) ? '' : (' ON DUPLICATE KEY UPDATE ' .implode(',',$ODKUs));
		$query = "INSERT $ignore INTO $table (".implode(',',$names).") VALUES(".implode(',',$vals).") $ODKU";
		self::$lastQuery = $query;

		$stmt = self::$conn->prepare($query);
		foreach ($params['replacements'] as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		try{
			$stmt->execute();
		} catch (\PDOException | \Exception $e) {
			if (DEBUG_MODE){
                \Lib\Alerts::add_message($e->getMessage());
                \Lib\Alerts::add_message($query);
                \Lib\Alerts::add_message(print_r(func_get_args(),true));
			}
			throw new \Exception("DB error inserting into $table", $e->getCode());
		}
		return self::$conn->lastInsertId();
	}


	public static function insertMany($table, $fields, $values, &$params = null){
		if (empty(self::$conn)){
			self::connect();
		}
		$def_params= ["conditions"=>[],"replacements"=>[], "save"=>true];
		$params= array_merge($def_params, $params ?? []);
		$names = array_map(function($name){return self::$quote.$name.self::$quote;},$fields);
		$rows = [];
		$valuesStr = "VALUES";
		if (is_string($values)){
			$rows[] = $values;
			$valuesStr = '';
		}else{
			foreach ($values as $row) {
				$vals=[];
				foreach ($fields as $name) {
					if (!array_key_exists($name,$row)){
						continue;
					}
					$value = $row[$name];

					if (is_array($value) && isset($value['#type']) && isset($value['#value'])){
						if ($value['#type'] == 'literal'){
							$vals[$name] = $value;
						}else{
							$key = self::replacementKey($name, $value['#value'], $params['replacements']);
							$vals[$name] = $key;
						}
					}else{
						$key = self::replacementKey($name, $value, $params['replacements']);
						$vals[$name] = $key;
					}
				}

				$rows[] = '('.implode(',',$vals).')';
			}
			if (empty($rows)) return;
		}


		$ignore = empty($params['ignore']) ? '' : 'IGNORE';
		$query = "INSERT $ignore INTO $table (".implode(',',$names).") $valuesStr ".implode(",\n",$rows);
		self::$lastQuery = $query;

		$stmt = self::$conn->prepare($query);
		foreach ($params['replacements'] as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		try{
			$stmt->execute();
		} catch (PDOException | Exception $e) {
			if (DEBUG_MODE){
                \Lib\Alerts::add_message($e->getMessage());
                \Lib\Alerts::add_message($query);
                \Lib\Alerts::add_message(print_r(func_get_args(),true));
			}
			throw new Exception("DB error inserting into $table");
		}
	}

	public static function insertTable($table, $grid, &$params = null){
		$fields =  array_keys($grid);
		$values = [];
		$columnLength = 0;
		foreach ($grid as $field => $column) {
			$index = 0;
			if (!is_array($column)){
				if( $columnLength == 0) {
					throw new \Exception("Invalid column order - use longer column first");
				}
				for ($i=0; $i<$columnLength; $i++){
					$values[$i][$field] = $column;
				}
			}else {
				foreach ($column as $cell) {
					$values[$index][$field] = $cell;
					$index++;
				}
				$columnLength = max($columnLength, $index);
			}
		}
		self::insertMany($table, $fields, $values, $params);
	}

	public static function replace($table, $fields, $params = []){
		if (empty(self::$conn)){
			self::connect();
		}
		$def_params= ["conditions"=>[],"replacements"=>[], "save"=>true];
		$params=array_merge($def_params, $params);
		$vals = [];

		foreach ($fields as $name => $value) {
			if (is_array($value) && isset($value['#type']) && isset($value['#value'])){
				if ($value['#type'] == 'literal'){
					$vals[] = self::$quote.$name.self::$quote.' = '.$value['#value'];
				}else{
					$key = self::replacementKey($name, $value['#value'], $params['replacements']);
					$vals[] = self::$quote.$name.self::$quote.' = '.$key;
				}
			}else{
				$key = self::replacementKey($name, $value, $params['replacements']);
				$vals[] = self::$quote.$name.self::$quote . ' = '.$key;
			}
		}


		$query = "REPLACE INTO $table SET " . implode(', ', $vals);
		$where = self::buildConditions($params);
		if (!empty($where)){
			$query .= ' WHERE '. $where;
		}
		self::$lastQuery = $query;
		$stmt = self::$conn->prepare($query);
		foreach ($params['replacements'] as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		try{

			$stmt->execute();
		} catch (PDOException $e) {
			if (DEBUG_MODE){
                \Lib\Alerts::add_message($e->getMessage());
                \Lib\Alerts::add_message($query);
                \Lib\Alerts::add_message(print_r(func_get_args(),true));
			}
			throw new \Exception("DB error replacing into $table");
		}

		return $stmt->rowCount();
	}

	public static function update($table, $fields, $params = []){
		if (empty(self::$conn)){
			self::connect();
		}
		$def_params= ["conditions"=>[],"replacements"=>[], "save"=>true];
		$params=array_merge($def_params, $params);
		$vals = [];

		foreach ($fields as $name => $value) {
			if (is_array($value) && isset($value['#type']) && isset($value['#value'])){
				if ($value['#type'] == 'literal'){
					$vals[] = self::$quote.$name.self::$quote.' = '.$value['#value'];
				}else{
					$key = self::replacementKey($name, $value['#value'], $params['replacements']);
					$vals[] = self::$quote.$name.self::$quote . ' = '.$key;
				}
			}else{
				$key = self::replacementKey($name, $value, $params['replacements']);
				$vals[] = self::$quote.$name.self::$quote.' = '.$key;
			}
		}


		$query = "UPDATE $table SET " . implode(', ', $vals);
		$where = self::buildConditions($params);
		if (!empty($where)){
			$query .= ' WHERE '. $where;
		}
		self::$lastQuery = $query;
		$stmt = self::$conn->prepare($query);
		foreach ($params['replacements'] as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		try{
			$stmt->execute();
		} catch (PDOException $e) {
			if (DEBUG_MODE){
                \Lib\Alerts::add_message($e->getMessage());
                \Lib\Alerts::add_message($query);
                \Lib\Alerts::add_message(print_r(func_get_args()));
			}
			throw new \Exception("DB error updating $table");
		}

		return $stmt->rowCount();
	}

	public static function delete($table, $params){
		if (empty(self::$conn)){
			self::connect();
		}
		$def_params= ["conditions"=>[],"replacements"=>[], "save"=>true, "truncate" => false];
		$params=array_merge($def_params, $params);
		$where = self::buildConditions($params);
		if (!empty($where)){
			$query = "DELETE FROM $table WHERE " . $where;
		}elseif (!empty($params['truncate'])){
			$query = "TRUNCATE $table";
		}else{
			throw new \Exception('Truncating the table requires confirmation');
		}
		$stmt = self::$conn->prepare($query);
		foreach ($params['replacements'] as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		try{
			$stmt->execute();
		} catch (PDOException $e) {
			if (DEBUG_MODE){
                \Lib\Alerts::add_message($e->getMessage(), \Alerts::MSG_TYPE_ERROR);
                \Lib\Alerts::add_message($query, \Alerts::MSG_TYPE_MESSAGE);
                \Lib\Alerts::add_message(print_r(func_get_args(),true), \Alerts::MSG_TYPE_MESSAGE);
			}
			throw new \Exception("DB error deleting from $table");
		}

		return $stmt->rowCount();
	}

	/**
	 *
	 * @param array $params
	 * @return PDOStatement
	 */
	public static function getResultSet(&$params){
		if (self::$collect_metrics){$params['metrics']['start'] = microtime(true);}
		if (empty(self::$conn)){
			self::connect();
		}
		if (self::$collect_metrics){$params['metrics']['connected'] = microtime(true);}
		$def_params= ["table"=>false, "fields"=> ['*'],"conditions"=>[],"replacements"=>false,"order"=>false,"limit"=>false,'count'=>false, "save"=>false];
		$params=array_merge($def_params, $params);
		if(is_string($params['fields']))$params['fields']= [$params['fields']];
		if(is_object($params['limit']) && empty($params['pagination']))$params['pagination']=$params['limit'];
		if(!empty($params['pagination'])){
			$params['limit']=$params['pagination']->get_limit_string();
			$params['calc'] = true;
		}
		$query=self::buildQuery($params);
		if (self::$collect_metrics){$params['metrics']['built_query'] = microtime(true);}

		self::$lastQuery = $params['query'] = $query;
		try{
			if (!empty($params['replacements'])){
				$stmt = self::$conn->prepare($query);
				foreach ($params['replacements'] as $key => $value) {
					$stmt->bindValue($key, $value);
				}
				$stmt->setFetchMode (\PDO::FETCH_ASSOC);
				$stmt->execute ();
			}else{
					$stmt = self::$conn->query($query, \PDO::FETCH_ASSOC);
			}
			if (self::$collect_metrics){$params['metrics']['executed'] = microtime(true);}
		}catch(\Exception $e){
			if (self::$collect_metrics){$params['metrics']['executed_error'] = microtime(true);}
			if (DEBUG_MODE){
                \Lib\Alerts::add_message($e->getMessage());
                \Lib\Alerts::add_message(print_r(func_get_args(),true));
                \Lib\Alerts::add_message(self::$lastQuery);
			}
			throw $e;
		}
		if (!empty($params['calc']) && !empty($params['pagination']) && is_object($params['pagination'])){
			$params['pagination']->set_total(self::$conn->query('SELECT FOUND_ROWS()')->fetchColumn());
		}
		if (self::$collect_metrics){$params['metrics']['calculated_total'] = microtime(true);}
		return $stmt;
	}



	public static function getRow(&$params = []){
		if (empty(self::$conn)){
			self::connect();
		}
		$def_params= ["limit"=>1];
		$params=array_merge($def_params, $params);
		$res = self::getResultSet($params);
		return $res->fetch(\PDO::FETCH_ASSOC);
	}

	public static function getOne($params){
		if (empty(self::$conn)){
			self::connect();
		}
		$params['limit'] = 1;
		$res = self::getResultSet($params);
		$ret = $res->fetch(\PDO::FETCH_NUM);
		if (empty($ret)){
			return null;
		}
		return $ret[0];
	}

	public static function getColumn(&$params, $colname=false){
		if (empty(self::$conn)){
			self::connect();
		}
		$res = self::getResultSet($params);
		$return = [];
		$colindex = null;
		foreach ($res as $row){
			if ($colindex === null){
				$cols = array_keys($row);
				if ($colname !== false && array_key_exists($colname, $row)){
					$colindex = $colname;
				}elseif (is_numeric($colname)){

					$colindex = isset($cols[$colname]) ? $cols[$colname] : 0;
				}else{
					$colindex = 0;
				}
			}
			if (!empty($params['keyField']) && isset($row[$params['keyField']])){
				//$cols = array_keys($row);
				if ($colindex == 0 && array_search($params['keyField'], $cols) === 0 && count($cols) > 1){
					$colindex = $cols[1];
				}
				if (is_numeric($colindex) && array_key_exists($cols[$colindex], $row)){
					$colindex = $cols[$colindex];
				}

				//yield $row[$params['keyField']] => $row[$colindex];
				$return[$row[$params['keyField']]] = $row[$colindex];
			}else{
				//yield $row[$colindex];
				if (is_numeric($colindex)){
					$colindex = $cols[$colindex];
				}
				$return[] = $row[$colindex];
			}
		}
		return $return;
	}

	public static function getArray(&$params){
		if (empty(self::$conn)){
			self::connect();
		}
		$res = self::getResultSet($params);
		$return = [];
		foreach ($res as $row){
			if (!empty($params['keyField']) && isset($row[$params['keyField']])){
				//yield  $row[$params['keyField']] => $row;
				$return[$row[$params['keyField']]] = $row;
			}else{
				//yield $row;
				$return[] = $row;
			}
		}
		return $return;
	}

	public static function buildQuery(&$params){
		$query = 'SELECT' . (empty($params['calc']) ? '' : ' SQL_CALC_FOUND_ROWS');
		$query .= ' ' . implode(',', $params['fields']);
		$alias = isset($params['alias']) ? (' AS ' . $params['alias']) : '';
		$query .= ' FROM ' . $params['table'] . $alias;
		if (!empty($params['join'])){
			$query .= self::buildJoin($params);
		}
		$where = self::buildConditions($params);
		if (!empty($where)){
			$query .= ' WHERE '. $where;
		}
		if (!empty($params['group by'])){
			$query .= ' GROUP BY ' .$params['group by'];
		}

		if (!empty($params['order'])){
			$query .= ' ORDER BY ' .$params['order'];
		}
		if(is_numeric($params['limit']) || is_string($params['limit'])){
			$query.=' LIMIT ' . $params['limit'];
		}
		if(is_object($params['limit']) && is_a($params['limit'], '\Lib\Pagination')){
			$query.=" LIMIT ".$params['limit']::get_limit_string();
		}
		return $query;
	}

	public static function buildConditions(&$params){
		if (empty($params['conditions'])){
			return false;
		}
		if (empty($params['replacements'])){
			$params['replacements'] = [];
		}
		$conditions = [];

		foreach ($params['conditions'] as $field => $value) {
			$conditions[] = self::buildCondition($field, $value, $params['replacements']);
		}

		if (count($conditions) > 1){
			return '(' .implode(' AND ', $conditions). ')';
		}else{
			return implode(' AND ', $conditions);
		}
	}

	private static function buildCondition($field, $condition, &$replacements){
		if (!empty($field) && $field == '#OR'){
			$conds = [];
			foreach ($condition as $fld => $cnd) {
				$conds[] = self::buildCondition($fld, $cnd, $replacements);
			}
			return '(' .implode(' OR ', $conds). ')';
		}

		if (is_array($condition)){
			if (!empty($condition['#field'])){
				$field = $condition['#field'];
			}
			if (array_key_exists('#op', $condition)){
				$op = strtoupper($condition['#op']);
				if(($op == 'IN' || $op == 'NOT IN') && is_array($condition['#value'])){
					//@todo needs work
					$rep_keys = [];
					foreach ($condition['#value'] as $cond_val) {
						$rep_keys[] = self::replacementKey($field, $cond_val, $replacements);
					}
					if (empty($rep_keys))$rep_keys='NULL';
					return self::$quote.$field.self::$quote . ' ' . $op . ' ('.implode(',',$rep_keys).')';
				}elseif ($op == 'NULL' || $op == 'NOT NULL'){
					return self::quoteFieldName($field) . ' IS ' . $op;
				}else{
					if (array_key_exists('#type', $condition)){
						if($condition['#type'] == 'literal'){
							return self::quoteFieldName($field) . ' ' . $op . ' '. $condition['#value'];
						}elseif($condition['#type'] == 'function'){
							return  $condition['#function']. ' ' . $op . ' '. self::replacementKey(func_get_arg(0), $condition['#value'], $replacements);
						}elseif($condition['#type'] == 'function2field'){
							return  $condition['#function']. ' ' . $op . ' '. $condition['#value'];
						}
					}else{
						$key = self::replacementKey($field, $condition['#value'], $replacements);
						return self::quoteFieldName($field) . ' ' . $op . $key;
					}
				}
			}else{
				if (array_key_exists('#type', $condition)){
						if($condition['#type'] == 'literal'){
							return self::quoteFieldName($field) . ' = '. $condition['#value'];
						}elseif($condition['#type'] == 'function'){
							$val = self::replacementKey(func_get_arg(0), $condition['#value'], $replacements);
							return $condition['#function'] . ' = '. $val;
						}elseif($condition['#type'] == 'function2field'){
							return $condition['#function'] . ' = '. $condition['#value'];
						}elseif($condition['#type'] == 'search'){
                            $as = !empty($condition['#search_rank']) ? ('as '. $condition['#search_rank']) :'';
                            $allFields = explode(',', $field);
                            $key = substr(implode('', $allFields), 0, 20);
                            $val = self::replacementKey($key, $condition['#value'], $replacements);
							return "MATCH (".
								implode(',',array_map(function($fld){return self::quoteFieldName($fld);}, $allFields)) .
								") AGAINST ($val IN BOOLEAN MODE) $as";
						}
				}else{
					//assume IN operator
					//@todo needs work
					$keys = self::addReplacements($field, $condition, $replacements);
					if (empty($keys))$keys=['NULL'];
					return self::quoteFieldName($field) . ' IN ('.implode(',',$keys).')';
				}
			}
		}else{
			$key = self::replacementKey($field, $condition, $replacements);

			return self::quoteFieldName($field) . ' = '.$key;
		}

	}

	private static function replacementKey($field, $value, &$replacements){
		if (strpos($field, '.') !== FALSE){
			$field = str_replace('.', '_', $field);
		}
		$key = ':'.$field;
		$i = 1;

		while (isset($replacements[$key])){
			$key = ':'.$field.$i;
			$i++;
		}
		$replacements[$key] = $value;
		return $key;
	}
	private static function addReplacements($field, $values, &$replacements){
		$keys = [];
		if (strpos($field, '.') !== FALSE){
			$field = str_replace('.', '_', $field);
		}
		$i = 1;
		foreach ($values as $k => $value){
			$key = ':'.$field;
			while (isset($replacements[$key])){
				$key = ':'.$field.$i;
				$i++;
			}
			$replacements[$key] = $value;
			$keys [$k] = $key;
		}
		return $keys;
	}

	private static function buildJoin(&$params){
		if (empty($params['replacements'])){
			$params['replacements'] = [];
		}
		if (!is_array($params['join'])) return '';

		if (array_key_exists('#table', $params['join'])){
			$table = $params['join']['#table'];
			$p['replacements'] = &$params['replacements'];
			$p['conditions'] = &$params['join']['#conditions'];
			$conditions = self::buildConditions($p);
			$type = isset($params['join']['#type']) ? $params['join']['#type'] : 'INNER';
			$alias = isset($params['join']['#alias']) ? (' AS ' . $params['join']['#alias']) : '';
			return ' ' . $type . ' JOIN ' . $table.$alias . ' ON ' . $conditions;
		}
		$return = '';
		foreach ($params['join'] as $index => &$join) {
			if (!array_key_exists('#table', $join)) continue;
			$table = $join['#table'];
			$p=[];
			$p['replacements'] = &$params['replacements'];
			$p['conditions'] = &$join['#conditions'];
			$conditions = self::buildConditions($p);
			$type = isset($join['#type']) ? $join['#type'] : 'INNER';
			$alias = isset($join['#alias']) ? (' AS ' . $join['#alias']) : '';
			$return .= " \n " . $type . ' JOIN ' . $table.$alias . ' ON ' . $conditions;

		}
		return $return;
	}
	private static function quoteFieldName($field){
		if (strpos($field, '.') === FALSE){
			$field = self::$quote.$field.self::$quote;
		}
		return $field;
	}

	/**
	 * Generates a random integer that is consistent throughout the session to be used as seed for the RAND() function
	 * @return int
	 */
	public static function sessionRand($extra = ''){
		//$max= strlen(PHP_INT_MAX);
		$nr = '';
		$sid = session_id().$extra;
		for ($i=0; $i<strlen($sid); $i++){
			$nr.=ord($sid[$i])%10;
		}
		return (int)$nr;
	}
}
