<?php
define("LOGGER_NAME", "branchmapper-select");
require_once __DIR__ . "/../../base/bootstrap.php";

$request_key = get_request_key();
$which_result = (new Web_Branch_Map_Factory())->create()->which();

if (!$which_result->latitude_longitudes_svg) {
	$which_result->warnings[] = "No maps rendered.";
}

$primary = Latitude_Longitude_Svg::get_primary_instance();
$primary_dimensions = round($primary->get_viewbox_east() - $primary->get_viewbox_west()) .
	 "&nbsp;×&nbsp;" . round($primary->get_viewbox_north() - $primary->get_viewbox_south());

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
	['jquery-ui.css.url', 'bootstrap.css.url', 'bootstrap.css.theme.url']);
$http_io->transcludeScript(['shared', 'branchmapper'], 'css', '../');
?>
</head>
<body>
	<div class="body-wrapper">
	<?php require_once "header.php" ?>
	<input type="hidden" id="web-root" value=".." />
	<input type="hidden" id="request_key" value="<?= $request_key ?>" />
	<input type="hidden" id="cookie-path"
		value="<?= $environment["cookie.path"] ?>" />
<?php
if ($which_result->warnings) {
	?><div class="svg-warning">
		<p>Warnings:</p>
		<ul>
		<?php
	foreach ($which_result->warnings as $warning) {
		?><li><?= $warning ?></li><?php
	}
	?></ul>
	</div><?php
}

if ($which_result->latitude_longitudes_svg) {
	?>
	<form action="start.php" method="get">
		<input class="btn btn-warning" type="submit" value="Upload another" />
	</form>
	<table class="select-table">
		<tr>
			<th class="map">Map name</th>
			<th class="selection">Download</th>
			<th class="wikitext">Wikitext for attribution</th>
		</tr>
		<?php
	foreach ($which_result->latitude_longitudes_svg as $svg) {
		$name_sanitize = sanitize($svg->get_name(), false);
		?><tr data-map-name="<?= $name_sanitize ?>"
			data-map-human-readable="<?= sanitize($svg->get_human_readable(), false) ?>"
			data-map-recommended-height="<?= round($svg->get_recommended_height()) ?>"
			data-map-recommended-width="<?= round($svg->get_recommended_width()) ?>">
			<td class="map"><?= sanitize($svg->get_human_readable(), false) ?><br />
				<img src="../images/<?= $name_sanitize ?>-thumb.png" /></td>
			<td class="selection"><input type="button"
				class="btn btn-primary download-button" value="download" /></td>
			<td class="wikitext">
				<div class="wikitext-toggle">
					<a href="#" class="show-wikitext">Show wikitext</a>
				</div>
				<div class="wikitext-toggle wikitext-shown">
					<a href="#" class="show-wikitext">Hide wikitext</a> <br />
					<textarea rows="11"><?= sanitize($svg->get_wikitext(), false)?></textarea>
				</div>
			</td>
		</tr><?php
	}
	?></table>
	<div class="svg-options">
		<form id="resolutions-form">
			<div class="recommended-dimensions-wrapper">
				Recommended dimensions for combining this map with the primary: 
				<span id="recommended-dimensions"></span>
				<a href="#" id="tooltip0" class="tooltip-div"></a>
			</div>
			<div class="svg-option">
				<span class="svg-option-title">
					Dot radius (in latitude seconds):
				</span>
				<span class="svg-option-input">
					<input type="number" id="option-radius" maxlength="3" value="170" max="999" />
				</span>
			</div>
			<div class="svg-option">
				<span class="svg-option-title">Color:</span>
				<span class="svg-option-input">
					<input type="color" id="option-color" value="#0000ff" />
				</span>
			</div>
			<div id="buttons">
				<input class="btn btn-warning" type="reset" value="Reset" />
				<input class="btn btn-primary" type="button" id="final-download"
					value="Download">
			</div>
		</form>
	</div><?php 
}?>	<div data-for="tooltip0" class="tooltip-text">
		<p>Use this if you want to rasterize a map with the same number of pixels per latitude 
		and longitude as the primary map (<em><?= sanitize($primary->get_name()) ?></em>).</p>
		<p>This can be useful for creating combination maps (for example, 
		<a href="https://commons.wikimedia.org/wiki/File:Seattle_Seahawks_radio_affiliates_(lower_continent).png"
		target="_blank">a map with both US states and Canadian provinces on it</a>.</p>  
		<p>The resulting maps may look a bit distorted due to map projection artifacts.</p>
	</div>
</div>
<?php 

$http_io->transcludeScriptRemote(['jquery.url', 'jquery-ui.url', 'jquery.filedownload.url']);
$http_io->transcludeScript(['shared', 'branchmapper'], 'js', '../');
?>
</body>