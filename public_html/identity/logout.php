<?php
define("LOGGER_NAME", "ident-logout");
require_once __DIR__ . "/../../base/bootstrap.php";

(new Identity_Verifier_Impl())->logout();
header('content-type: application/json; charset=utf-8');
echo json_encode(["success" => true], Environment::prop("environment", "jsonencode.options"));