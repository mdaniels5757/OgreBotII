<?php 
require_once __DIR__ . "/../base/bootstrap.php";

$http_io = new Http_Io();
$http_io->ob_start();
$oldverlog_logic = new Oldverlog_Logic();
$oldverlog_logic->run();
Oldver_Shared::load_messages();
?><!DOCTYPE HTML> 
<html>
<head>
<title><?= $msg['header.title'] ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php 
$http_io->transcludeScriptRemote(['bootstrap.css.url', 'bootstrap.css.theme.url', "jquery-ui.css.url"]);
$http_io->transcludeScript("oldver", "css");
$http_io->transcludeScriptRemote(['jquery.url', "jquery-ui.url"]);
$http_io->transcludeScript("oldver", "js"); 
?>
</head>
<body><?php

require "../oldver/header.php";

?>
<input type="hidden" id="start-date" value="<?= $oldverlog_logic->get_start_date() ?>" />
<input type="hidden" id="end-date" value="<?= $oldverlog_logic->get_end_date() ?>" />
<div class="view-by-date">
	<form method="get" id="view-by-date-form">
		From: <input type="text" placeholder="yyyy-mm-dd" name="from" value="<?= $oldverlog_logic->get_from() ?>"/><br/>
		To: <input type="text" placeholder="yyyy-mm-dd" name="to"  value="<?= $oldverlog_logic->get_to() ?>"/>
		<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
			<div class="ui-dialog-buttonset">
				<input type="submit" class="btn btn-primary" value="Submit" /> 
				<input type="reset" class="btn btn-warning" value="Reset" /> 
				<input type="button" class="btn btn-default" id="view-by-date-cancel" value="Cancel" />
			</div>
		</div>
	</form>
</div>
<input class="view-by-date-button btn btn-primary" type="button" value="View by date" />
<table class="log"> 
	<tr>
		<th class="logDate">Date/Time</th>
		<th class="logUser">Authorizing user</th>
		<th class="logProject">Project</th>
		<th class="logSource">Source File</th>
		<th class="logDest">Destination file</th>
	</tr>
<?php
$project_datas = $oldverlog_logic->get_project_datas();
$project_data_commons = $project_datas['commons.wikimedia'];
$entries = $oldverlog_logic->get_entries();

foreach($entries as $entry) {
	$userLink = $project_data_commons->getRawLink("User:$entry->user");
	$userHtml = sanitize($entry->user);
	
	$project_data = $project_datas[$entry->project];
	$localLink = $project_data->getRawLink($entry->source);
	$localHtml = sanitize($entry->source);
	
	$commonsLink = $project_data_commons->getRawLink($entry->dest);
	$commonsHtml = sanitize($entry->dest);
	
	?>
	<tr>
		<td class="logDate"><?= $entry->datetime ?></td>
		<td class="logUser"><a href="<?= $userLink ?>"><?= $userHtml ?></a></td>
		<td class="logProject"><?= $entry->project ?></td>
		<td class="logSource"><a href="<?= $localLink ?>"><?= $localHtml ?></a></td>
		<td class="logDest"><?php 
			if ($entry->same_name) {
				?><span class="sameName">(same name)</span> <?php
			}
		?><a href="<?= $commonsLink ?>"><?= $commonsHtml ?></a></td>
	</tr>
<?php		
}

?>
</table>
<input class="view-by-date-button btn btn-primary" type="button" value="View by date" /></body></html>
