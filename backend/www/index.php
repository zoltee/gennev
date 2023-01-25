<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code (200);
    die();
}

//error handler
register_shutdown_function(function () {
	$error = error_get_last();
	if ($error && $error['type'] === E_ERROR) {
		http_response_code (500);
		print(json_encode($error));
	}
} );

//class autoload handler
spl_autoload_register(function ($class) {
	if(!strpos($class, '\\'))$class = 'Model\\'.$class;
	$class=str_replace('\\','/', $class);
	if (!file_exists(PATH_SRC_ROOT.$class.'.php')){
		http_response_code (500);
		print(json_encode("Class not found: $class at ".PATH_SRC_ROOT."$class.php"));
		print(json_encode(debug_backtrace()));
		exit();
	}
	require_once(PATH_SRC_ROOT.$class.'.php');
});

//load configuration file
require_once "../src/config.php";
session_start();
if (!empty($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], 'application/json') !== false){
    ob_start();
    $json_content = file_get_contents('php://input');
    if (!empty($json_content)){
        $json = @json_decode($json_content, true);
        if (!empty($json)){
            $_REQUEST += $json;
        }
    }
    ob_end_clean();
}

if (DEBUG_MODE){
	error_reporting(E_ALL);
}

header('Content-type: application/json');
try {
    // main switch to handle different entry points
    switch ($_REQUEST['action'] ?? '') {
        case 'init':
            \Controller\Testimonials::init();
        break;
		// REST API for testimonials
		default:
		case 'testimonials':
			$data = \Controller\Testimonials::testimonials() ?: [];
			$data += \Lib\Alerts::messages_by_type();
			print(json_encode($data ?? [],JSON_THROW_ON_ERROR));
		break;
	}
}catch(\PDOException $pdoex){ //database error handler
	http_response_code (400);
	print(json_encode(['error' => $pdoex->getMessage()]));
	if(DEBUG_MODE){
		print(json_encode(['lastQuery' => \DB::$lastQuery]));
		print(json_encode(['error' => $pdoex->getTraceAsString()]+ \Lib\Alerts::messages_by_type()));
	}
}catch(\Exception $e){//general exception handler
    $code = $e->getCode();
    http_response_code (empty($code) ? 400 : $code);
	print(json_encode(['error' => $e->getMessage(), 'exception' => get_class($e)] + \Lib\Alerts::messages_by_type()));
}
