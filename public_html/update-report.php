<?php
require_once __DIR__ . "/../base/bootstrap.php";

$http_io = new Http_Io();
$http_io->ob_start();

$request_args = $env->get_request_args();

switch (@$request_args['type']) {
	case "report":
		$action = "UpdateNewUploads.php";
		break;
	case "uploads":
		$action = "UpdateUploadReport.php";
		break;
	default:
		die("Page cannot be loaded directly");
}
?>
<!DOCTYPE html>
<html>
<head>
<?php $http_io->transcludeScriptRemote('bootstrap.css.url'); ?>
</head>
<body>
	<form method="post" action="<?= $action ?>">
		<input type="hidden" name="project" value="<?= sanitize($request_args['project']) ?>" />
		<input type="hidden" name="start" value="<?= sanitize($request_args['start']) ?>" />
		<input type="submit" class="btn btn-primary" value="Click here to update" />	
	</form>
</body>
</html>
