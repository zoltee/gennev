<?php
namespace Model;
use Exception;

/**
 * Testimonials Model that interfaces with the database
 */
class Testimonials {
	/**
	 * adds a new project
	 * @param array $fields
	 * @return string
	 * @throws Exception
	 */
	static function add(array $fields) {
        $fields['id'] = self::generateId($fields['name'], $fields['age'], $fields['location']);
		\Lib\DB::insert('testimonials', $fields);
        return $fields['id'];
	}

    private static function generateId($name, $age, $location) {
        $normalized = strtolower($name.$age.$location);
        $normalized = preg_replace("/[^[:alnum:][:space:]]/u", '', $normalized);
        return hash("md5", $normalized);
    }

    /**
     * gets a single project
     * @param string $id
     * @return mixed
     */
    static function get(string $id) {
        $params = ['table' => 'testimonials', "conditions" => ['id' => $id]];
        return \Lib\DB::getRow($params);
    }

	/**
	 * lists the testimonials
	 * @param $criteria array
	 * @return array
	 * @throws Exception
	 */
	static function list(&$criteria){
		$params=[
			'table'=>'testimonials',
			'conditions'=> $criteria['conditions'] ?? [],
			'fields'=>['*']
		];
		if(!empty($criteria['pagination']))$params['pagination']=&$criteria['pagination'];

        if (!empty($criteria['conditions']['search'])){
            $params['conditions'] = [
                [
                    '#type' => 'search',
                    '#value' => $criteria['conditions']['search'],
                    '#field' => 'name,comments'
                ]
            ];
            unset($params['conditions']['search']);
        }
		return \Lib\DB::getArray($params);
	}

    static function init($file){
        $fp = fopen($file, 'r');
        $record = [];
        while (true) {
            $line = fgets($fp);
            if ($line === false){
                break;
            }
            $identified = self::identifyLine($line, $record);
            if (!$identified){
                if (count($record) == 5) {
                    self::add($record);
                }
                $record = [];
            }
        }
        fclose($fp);
    }

    private static function identifyLine($line, &$record){
        if (preg_match("/^\n$/", $line, $matches)){
            return false;
        }
        if (preg_match("/^Name:?\s(.+)\s?$/", $line, $matches)){
            $record['name'] = $matches[1];
            return true;
        }
        if (preg_match("/^Age:?\s(\d+)\s?$/", $line, $matches)){
            $record['age'] = $matches[1];
            return true;
        }
        if (preg_match("/^Location:?\s(.+)\s?/", $line, $matches)){
            $record['location'] = $matches[1];
            return true;
        }
        if (preg_match("/^imageUrl:?\s(.+)\s?$/", $line, $matches)){
            $record['imageUrl'] = $matches[1];
            return true;
        }
        if (preg_match("/^Comments:\s?$/", $line, $matches)){
            $record['comments'] = '';
            return true;
        }
        if (!empty($line) && array_key_exists('comments', $record)){
            $record['comments'].= $line;
            return true;
        }
        return false;
    }

}