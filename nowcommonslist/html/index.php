<?php 
$http_io = new Http_Io(false);
?>
<!DOCTYPE html> 
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<?php $http_io->transcludeScriptRemote("jquery.url") ?>
<?php $http_io->transcludeScript("nowcommonslistindex", "js") ?>
</head>
<body>
<form method="post" action="commons-images-update.php">
	<input type="hidden" name="project" value="<?= $project_key ?>" />
	<input type="submit" id="update" value="Update this gallery" />
</form>
<br style="clear: both;" />
<?= date('Y-m-d H:i', $this->start_time) ?> (<?= date_default_timezone_get() ?>)<br/>
<?php 
if (count($galleries) === 0) {
	?>No files are marked NowCommons.<?php
} else {
	foreach ($galleries as $range => $link) {
		?><a href="<?= $link ?>"><?= $range ?></a><br/><?php
	}
}
?></body>
</html>