<?php 
require_once __DIR__ . "/../base/bootstrap.php";
global $logger, $wiki_interface;

try {
	ini_set("max_execution_time", 60 * SECONDS_PER_MINUTE);
	$co2 = $wiki_interface->new_wiki("OgreBot_2Commons");
	$cleanup = Abstract_Cleanup::get_by_post_key($co2, Service_Call::read_service_call());
	$cleanup->run();
	$logger->debug("Done.");
} catch (Exception $e) {
	ogrebotMail($e);
}

?>