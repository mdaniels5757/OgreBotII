<?php
define("LOGGER_NAME", "ident-complete");
require_once __DIR__ . "/../../base/bootstrap.php";

try {
	(new Identity_Verifier())->do_identify();
} catch (OAuthException $e) {
	header("HTTP/1.1 500 Internal Server Error", 500);
	die(htmlspecialchars($e->getMessage()));
} catch (Exception $e) {
	header("HTTP/1.1 500 Internal Server Error", 500);
	ogrebotMail($e);
	die("Unknown error, the bot owner has been notified.");
}
require_once "start.php";