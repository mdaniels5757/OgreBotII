<?php
require_once __DIR__ . "/../base/bootstrap.php";

echo  ( new NewUploadsRunnerWrapper())->getPageNameByTimestamp("20170224000000")."\n";

?>
