<?php 
require_once __DIR__ . "/../base/bootstrap.php";

$logic = new Process_Upload_Logic();
$http_io = new Http_Io();
$http_io->ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<title>Old version filemover</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php $http_io->transcludeScriptRemote('bootstrap.css.url'); ?>
<?php $http_io->transcludeScript("oldver", "css"); ?>
<script type="text/javascript">
	var isAngular = true;
	var logic = <?= json_encode($logic); ?>;
</script>
</head>
<body ng-cloak ng-app="app" ng-controller="process-uploads"> 
<?php
require __DIR__ . "/../oldver/header.php";
?>
Idenity verification...
<span ng-show="tusc_verified">
	<span class="tusc success">Success</span> (time: {{tusc_time}} seconds)
	<span class="error" ng-show="tusc_time > 10"> (TUSC is responding slowly)</span>
	<br />
</span>
<span ng-show="error">
	<span class="tusc error">Failed</span>
	<br />
	<span>{{error}}</span>
</span>
<div ng-show="tusc_verified && uploads && uploads[0]">
	<a href="{{'https://' + proj + '.org/wiki/' + (loc_title | escape)}}" target="_blank">{{loc_title}}</a> 
	({{proj}}) =&gt;
	
	<a href="https://commons.wikimedia.org/wiki/{{com_title | escape}}" target="_blank">{{com_title}}</a>
	(commons.wikimedia)
	(<a href="https://commons.wikimedia.org/w/index.php?title={{com_title | escape}}&action=edit" target="_blank">edit</a>)
	(<a href="do_cleanup.php?image={{com_title | escape}}" target="_blank">cleanup</a>)
	<br/>
	<div ng-repeat="(index, upload) in uploads">
		<div ng-show="upload.download_attempted">
			Downloading version #{{index + 1}} ({{upload.size}} bytes)...
			<span ng-show="upload.download_time != null">
				<span class="tusc success">Success</span>
				(time: {{upload.download_time}} seconds)<br />
			</span>
			<span ng-show="upload.download_time == null" class="tusc error">Failed</span>
		</div>
	</div>
	<div ng-show="wrote_history">
		Writing history to file description page...
		<span ng-show="wrote_time != null">
			<span ng-show="wrote_history === 1">original upload header found...</span>
			<span ng-show="wrote_history !== 1">original upload header not found, not appending it...</span>
			(time: {{wrote_time}} seconds)
		</span>
		<span ng-show="wrote_time == null" class="tusc error">Failed</span>
	</div>
	<div ng-repeat="(index, upload) in uploads">
		<div ng-show="upload.upload_attempted">
			Uploading version #{{index + 1}} ({{upload.size}} bytes)...
			<span class="comment">{{upload.edit_summary}}</span>
			<span ng-show="upload.upload_error == null">
				<span class="tusc success">Success</span>
				(time: {{upload.upload_time}} seconds)<br />
			</span>
			<span ng-show="upload.upload_error != null" class="tusc error">Failed</span>
		</div>
	</div>
	<div ng-show="!error" class="ob-table">
		<form method='post' enctype='multipart/form-data' target="_blank"
			action="{{('https://' + proj + '.org/w/index.php') | trusted}}" style="display: inline">
			<input type='hidden' name='title' value='{{loc_title}}' />
			<input type='hidden' name='action' value='edit' />
			<input type='hidden' name='section' value='new' />
			<input type='hidden' name='wpTextbox1' value='{{nowcommons_text}}' />
			<input type='hidden' name='wpSummary' value='{{nowcommons_summary}}' />
			<input type='hidden' name='wpStarttime' value='{{start_time_wiki}}' />
			<input type='hidden' name='wpEdittime' value='{{last_edit_time}}' />
			<input type='submit' class="btn btn-default" name='wpPreview'
				value='Add {{"{{" + "NowCommons}\}"}}' />
		</form>
		<form method="post" action="{{('https://' + proj + '.org/w/index.php') | trusted}}" 
			target="_blank" style="display: inline">
			<input type='hidden' name='title' value='{{loc_title}}' />
			<input type="hidden" name="action" value="delete" />
			<input type="hidden" name="wpReason" value="{{delete_text}}" />
			<input class="btn btn-warning" type="submit" value="Delete this image" />
		</form>
		<a href="oldver.php" class="btn btn-primary">Upload another image</a>
	</div>	
</div>
<?php 
	$http_io->transcludeScriptRemote(['angular.js.url']);
	$http_io->transcludeScript('oldver', 'js');
?>
</body>
</html>