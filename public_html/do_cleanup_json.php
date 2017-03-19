<?php 

require_once __DIR__ . "/../base/bootstrap.php";

$POST = $env->get_request_args();
//$POST['image'] = "File:Hbg fixed.jpg";

$obj = Do_Cleanup_Logic::get_cleanup_data($POST, function($title) {
	return wikilink($title, "commons.wikimedia", true, false,
			null, "", false, null);
});



header('content-type: application/json; charset=utf-8');

//site is stateless; nothing harmful to be learned.
header("Access-Control-Allow-Origin: *");

$json_encode = json_encode($obj);
$logger->debug($json_encode);	
echo $json_encode;


?>