<?php
define("LOGGER_NAME", "ident-authorize");
require_once __DIR__ . "/../../base/bootstrap.php";

$response = [];
try {
	$response['redirect'] = (new Identity_Verifier())->do_authorization_redirect();
} catch (OAuthException $e) {
	$response['error'] = $e->getMessage();
} catch (Exception $e) {
	ogrebotMail($e);
	$response['error'] = "Unknown error, the bot owner has been notified.";
}

header('content-type: application/json; charset=utf-8');
echo json_encode($response, Environment::prop("environment", "jsonencode.options"));