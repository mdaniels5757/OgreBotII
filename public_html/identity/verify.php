<?php
define("LOGGER_NAME", "ident-verify");
require_once __DIR__ . "/../../base/bootstrap.php";
global $logger;

$verifier = new Identity_Verifier_Impl();

$auth_tool_keys = $verifier->get_auth_tool_keys();
$tooluser = @$_REQUEST["tooluser"];
//$tool_name = $auth_tool_keys[$tooluser];

$response = new Identity_Response();
if ($tooluser) {
	$logger->info("Incoming request from tool $tooluser");
	$tool_password = @$_REQUEST["toolpass"];
	if ($tool_password) {		
		if ($auth_tool_keys[$tooluser] == $tool_password) {
			try {
				$username = $verifier->get_username_by_cookie(@$_REQUEST["cookie"]);
				$response->username = $username;
				$response->found = !!$username;
			} catch (Exception $e) {
				ogrebotMail($e);
				$response->error = "An unknown error has occurred; the bot owner has been notified";
			}
		} else {
			$logger->info("Password mismatch.");
			$response->error = "Tool password mismatch.";
		}
	} else {
		$logger->info("Password not provided.");
		$response->error = "Tool password not provided.";
	}
} else {
	$logger->info("Tool key not found.");
	$response->error = "Tool owner key not provided or not found.";
}
$json = json_encode($response, Environment::prop("environment", "jsonencode.options"));
$logger->info("Response: $json");

header('content-type: application/json; charset=utf-8');
echo $json;
