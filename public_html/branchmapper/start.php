<?php
define("LOGGER_NAME", "branchmapper-start");
require_once __DIR__ . "/../../base/bootstrap.php";

$http_io = new Http_Io();
$http_io->ob_start();
?>
<!DOCTYPE HTML>
<html>
<head>
<title>Create maps of branches</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php

$http_io->transcludeScriptRemote(
	['jquery-ui.css.url', 'bootstrap.css.url', 'bootstrap.css.theme.url', 'fontawesome.css.url', 
		'awesome.css.url']);
$http_io->transcludeScript(['shared', 'branchmapper'], 'css', '../');
$http_io->transcludeScriptRemote(['jquery.url', 'jquery-ui.url', 'filesaver.js.url']);
$http_io->transcludeScript(['shared', 'branchmapper'], 'js', '../', ["defer"]);
?>
</head>
<body class="start">
	<div class="body-wrapper">
	<?php require_once "header.php" ?>
	<input type="hidden" id="web-root" value=".." />
	<input type="hidden" id="phase" value="0" />
	<input type="hidden" id="cookie-path" value="<?= $environment["cookie.path"] ?>" />
	<div>
		<input class="btn btn-warning" type="button" id="load-state" value="Load" />
		<input class="btn btn-warning" type="button" id="save-state" value="Save" />
		<input type="file" id="import" accept="text/plain"/>
		<label role="button" for="import" class="btn btn-warning">Import from disk</label>
		<input type="button" id="export" class="btn btn-warning" value="Export to disk" />
		<form id="start-form" method="post" action="select.php">
			<div class="panel" id="type-select-panel">
				<div class="title">How do you want to input map points?</div>
				<div class="radio type-select-panel-row">
					<input type="radio" name="type" id="latlong" value="latlong" />
					<label for="latlong">By latitude/longitude</label>
				</div>
				<div class="radio type-select-panel-row">
					<input type="radio" name="type" id="zip" value="zip" />
					<label for="zip">By zip code</label>
				</div>
				<div class="radio type-select-panel-row">
					<input type="radio" name="type" id="fdic" value="fdic" /> <label
						for="fdic">FDIC branch ID (for financial institutions)</label>
					<a href="#" id="fdic-tooltip" class="tooltip-div"
						data-tooltip-class="tooltip-15"></a>
				</div>
				<div class="radio type-select-panel-row">
					<input type="radio" name="type" id="radio" value="radio" /> <label
						for="radio">Radio station call letters</label>
					<a href="#" id="radio-station-tooltip" class="tooltip-div"
						data-tooltip-class="tooltip-15"></a>
				</div>
			</div>
			<div class="panel">
				<div id="branch-latlong" class="branch-div">
					<div class="title">Enter latitude/longitudes directly</div>
					<div class="input-instructions">
						<p>Enter latitude/longitudes separated by semi-colon (;) or a new
							line</p>
						<p>Each entry must have a latitude and longitude:</p>
						<ul>
							<li>The latitude and longitude must be separated by whitespace or
								a comma.</li>
							<li>Each latitude and longitude <em>must</em> be followed by N,
								S, E, or W.
							</li>
							<li>You may enter either decimal values or hours/minutes/seconds.</li>
						</ul>
						<p>Examples of valid entries include:</p>
						<ul>
							<li>39°26′40.37″N 76°37′21.50″W</li>
							<li>39°26&#39;40.37&quot;N 76°37&#39;21.50&quot;W</li>
							<li>39.34457°N 76.445°W</li>
							<li>39.34457N, 76.445W</li>
						</ul>
					</div>
					<textarea name="latlong"></textarea>
				</div>
				<div id="branch-zip" class="branch-div">
					<div class="title">Enter zip codes directly</div>
					<div class="input-instructions">
						<p>Enter US zip codes separated by comma, whitespace, or
							semicolon. Duplicate zip codes will be treated as larger points.</p>
						<p>Please note zip codes are less exact than direct longitude
							latitudes</p>
					</div>
					<textarea name="zip"></textarea>
				</div>
				<div id="branch-fdic" class="branch-div">
					<div class="title">FDIC branch IDs</div>
					<div class="input-instructions">
						<p>
							See <a href="https://research.fdic.gov/bankfind/" target="_blank">
								FDIC bankfind</a> for more information.
						</p>
						<p>Enter FDIC IDs separated by comma, whitespace, or semicolon.</p>
						<p>This tool aggregates branches by zip code. Duplicate zip codes
							will be treated as larger points.</p>
						<p>Please note zip codes are less exact than direct longitude
							latitudes.</p>
					</div>
					<p class="input-caution">Be careful! Some banks have more than one
						ID.</p>
					<textarea name="fdic"></textarea>
				</div>
				<div id="branch-radio" class="branch-div">
					<div class="title">Enter radio station call letters.</div>
					<div class="input-instructions">
						<p>Enter radio station call letters (e.g., KDKA), separated by a
							by comma, whitespace, or semicolon.</p>
						<p>
							AM/FM Stations must be <a
								href="https://www.fcc.gov/encyclopedia/am-query-broadcast-station-search"
								target="_blank">registered with the FCC</a>.
						</p>
					</div>
					<div class="branch">
						AM:
						<textarea name="am"></textarea>
						<br /> FM:
						<textarea name="fm"></textarea>
					</div>
				</div>
			</div>

			<div class="panel verify-input">
				<textarea disabled="disabled" id="verify-text"></textarea>
				<input class="btn btn-info" type="button" id="verify-input"
					value="Verify input" />
			</div>

			<div id="buttons">
				<input class="btn btn-info previous" type="button" id="previous"
					value='Previous'> <input class="btn btn-info next" type="button"
					id="next" value='Next'> <input class="btn btn-primary next"
					type="submit" id="submit" value='Submit'>
			</div>
		</form>
	</div>
	<span class="tooltip-text" data-for="fdic-tooltip"> See <a
		href="https://research.fdic.gov/bankfind/" target="_blank">FDIC
			bankfind</a> for more information.
	</span>
	<span class="tooltip-text" data-for="radio-station-tooltip">AM/FM
		Stations must be <a
		href="https://www.fcc.gov/encyclopedia/am-query-broadcast-station-search"
		target="_blank">registered with the FCC</a>.
	</span>
	</div>
</body>
</html>