<?php
require_once __DIR__ . "/../base/bootstrap.php";

/**
 * 
 * @param string $time
 * @param string $default
 * @throws Exception
 * @return string
 */
function read_time($time, $default) {
	if (!$time) {
		return $default;
	}
	
	if (!preg_match("/^(\d{4})\\/(\d{2})\\/(\d{2}) (\d{2}):(\d{2}):(\d{2})$/", $time, $match)) {
		throw new Exception("Illegal time: $time");
	}
	array_shift($match);
	return join("", $match);
}


$POST = $env->get_request_args();

load_property_file_into_variable($secrets, "secrets");
$hash = array_key_or_exception($secrets, 'request_key');

$time = time();
$success = false;
try {
	list($type, $src, $start_string, $end_string, $limit, $ident_cookie) = extract_array_params(
		$POST, 'type', 'src', 'start', 'end', 'limit', 'ident-cookie');
	
	$start = read_time($start_string, "20030104000000");
	$end = read_time($end_string, unixTimestampToMediawikiTimestamp(time() + SECONDS_PER_DAY));
	
	list($limit_min, $limit_max) = Environment::props("constants", 
		["cleanup_multi.limit.min", "cleanup_multi.limit.max"]);
	
	if ($limit < $limit_min || $limit > $limit_max) {
		$exception = new ArrayIndexNotFoundException("Invalid limit: $limit.");
		ogrebotMail($exception);
		throw $exception; 
	}
	
	$subcats = array_key_exists("subcats", $POST);
	
	$validator->validate_args_condition($type, "recognized abstract cleanup type", 
		in_array($type, Abstract_Cleanup::get_all_post_keys()));
	
	$tusc_user = Environment::get()->get_remote_io()->verify_ident($ident_cookie);
	$time_tusc = time() - $time;
	
	if ($tusc_user) {
		$project_data = new Project_Data("commons.wikimedia");
		$success = User_Data_Eligibility_Parser::is_elevated_cleanup_rights($project_data, $tusc_user);
		if (!$success) {
			$error = "Not eligible!";
		}
	} else {
		$error = "Identity verification failed!";
	}	
	
} catch (TuscException $e) {
	ogrebotMail($e);
	$error = " Can't connect to Identity Verifier! The bot owner has been notified. Please check back later.";
} catch (ArrayIndexNotFoundException $e) {
	$logger->error($e);
	$error =  " Not enough data provided.";
}

if ($success) {	
	$request_key = hash("adler32", "$tusc_user$src$time");
	$service_call = new Service_Call("do_cleanup_multi_service.php");
	$service_call->add_post_params(
		["request_key" => $request_key, "user" => $tusc_user, "type" => $type, 
			"limit" => $limit, "src" => $src, "start" => $start, "end" => $end, 
			"subcats" => $subcats]);
	
	$service_call->call();
} 

$http_io = new Http_Io();
$http_io->ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<?php 
$http_io->transcludeScriptRemote(
	['bootstrap.css.url', 'bootstrap.css.theme.url', 'fontawesome.css.url', 'awesome.css.url']);
$http_io->transcludeScript('do_cleanup_multi', 'css');
?>
</head>
<body ng-app="app" ng-controller="progress" ng-init='
	error = <?= $env->get_string_utils()->encode_json_for_html(@$error) ?>;
	request_key = <?= $env->get_string_utils()->encode_json_for_html($request_key) ?>;
	scrollBottom = true'>
<div ng-show="error">
	<span class="failure">Failed</span>: {{error}}
</div>
<div ng-show="!error">
	<div class="success">Success</div>
	<strong>The bot will run whether you close this window or not.</strong>
	<div>Loading...</div>
	<div ng-show="started" class="success">Started...</div>
	<div ng-show="filesCount != null">
		{{filesCount}} files found... Loading file information...
	</div>
	<table ng-show="lines.length">
		<tr>
			<th class="file-name-row">File name</th>
			<th class="edited-row">Edited?</th>
			<th class="percent-row">Percent complete</th>
		</tr>
		<tr ng-repeat="line in lines">
			<td><a target="_blank" href="http://commons.wikimedia.org/wiki/File:{{line.file | escape}}"
				>{{line.file}}</a></td>
			<td><span ng-show="line.changed">
					<span class="success">Edited</span> 
					(<a target="_blank" 
						href="https://commons.wikimedia.org/w/index.php?&diff=cur&title=File:{{line.file | escape}}">diff</a>)
				</span>
				<span ng-show="!line.changed">Skipped</span></td>
			<td>{{line.percent}}% complete</td>
		</tr>
	</table>
	<div ng-show="complete">
		<span class="success">Complete!</span><br/>
		<strong>Statistics:</strong><br/>
		<table class="statistics">
			<tr>
				<th>Run time (approximate)</th>
				<th>Files edited</th>
				<th>Filed ignored</th>
				<th>Percent edited</th>
			</tr>
			<tr>
				<td>{{runTime}}</td>
				<td>{{changedTotal()}}</td>
				<td>{{lines.length - changedTotal()}}</td>
				<td>{{lines.length ? (100 * changedTotal() / lines.length | round : 1 ) : "-"}}%</td>
			</tr>
		</table>
		<a href="cleanup_multi.php" class="btn btn-primary">Perform another mass cleanup</a>
	</div>
	<div ng-show="processError" class="failure">Error! Unable to complete the process. :(</div>
</div>
<div class="scroll-bottom-div">
	<div class="checkbox">
		<input id="scroll-bottom" ng-model="scrollBottom" type="checkbox" />
		<label for="scroll-bottom">Automatic scroll?</label>
	</div>
</div>
<?php 
$http_io->transcludeScriptRemote(["angular.js.url"]);
$http_io->transcludeScript("do_cleanup_multi", 'js');
?>
</body>
</html>
