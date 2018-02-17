<?php 
/* @var $now_commons_gallery Now_Commons_Gallery */
global $environment;

$local_project_data = $now_commons_gallery->local_project_data;
$commons_project_data = $now_commons_gallery->commons_project_data;

$http_io = new Http_Io();
?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<?php 
$http_io->transcludeScriptRemote(
	["jquery-ui.css.url", "bootstrap.css.url", 'fontawesome.css.url', 'awesome.css.url']);
$http_io->transcludeScript(["shared", "nowcommonslist"], "css");
$http_io->transcludeScriptRemote(["jquery2.url", "jquery-ui.url"]);
$http_io->transcludeScript(["shared", "nowcommonslist"], "js");

$xd_path = "wiki/User:Magog_the_Ogre/xdomain";
if (!boolean_or_exception($GLOBALS['environment']['minify'])) {
	$xd_path .= "?debug=true";
}
?>
</head>
<body>
<?php 
if ($local_project_data->getProject() === "wikipedia" && $local_project_data->getSubproject() === "en") {
	?>
	<input data-xd-auto="true" type="hidden" data-xd-domain="https://en.wikipedia.org" 
		data-xd-path="<?= $xd_path ?>" data-xd-timeout="50000"/>
	<?php
}
?>
Last updated at <?= date('Y-m-d H:i', $now_commons_gallery->start_time) ?> (<?= date_default_timezone_get() ?>)<br />
	<form method="post" action="commons-images-update.php">
		<input type="hidden" name="project" value="<?= $this->local_project_key ?>" />
		<input type="submit" id="update" value="Update this gallery" class="btn btn-sm btn-primary" />
	</form>
	<input type="hidden" id="files-count"
		value="<?= count($now_commons_gallery->now_commons_images) ?>" />
	<div class="ready-count">
		<p id="doc-load-per">Document <span id="ready-count">0</span>% loaded...</p>
	</div>
	<div class="main-table">
		<div class="nc-row nc-header">
			<div>Local name</div>
			<div>Commons name</div>
			<div>Issues?</div>
			<div>Local preview</div>
			<div>Commons preview</div>
			<div>Local uploader</div>
			<div>Commons uploader</div>
		</div><?php 
	foreach ($now_commons_gallery->now_commons_images as $nc_row => $image) {
		$local_href = $image->local_url;
		$local_html = sanitize($image->local_title);
		$commons_href = $image->commons_url;
		$commons_html = sanitize($image->commons_title);
		?>
		<input type="hidden" class="file" />
		<div class="nc-row">
			<!-- local name -->
			<div class="name">
				<a target="_blank" href="<?= $local_href ?>" 
					class="<?=$image->local_exists ? "" : "redlink"?>"><?= $local_html ?></a>
			</div>

			<!-- commons name -->
			<div class="name">
				<div>
					<?php 
					if ($image->same_name) {
						?><span class="samename">Same name: </span><?php
					}		
					?><a target="_blank" href="<?= $commons_href ?>"
							class="<?= $image->commons_exists ? "" : "redlink" ?>"><?= $commons_html?></a>
				</div>
			</div>

			<!-- issues -->
			<div class="issues">
				<div>
					<?php
					if ($image->errors || $image->warnings) {
						?><span class="glyphicon glyphicon-remove status"></span><?php
						foreach (($image->errors + $image->warnings) as $i => $error) {
							?><br /><span class="error-warning"><?= sanitize($error) ?></span><?php
						}
					} else {
						?><span class="glyphicon glyphicon-ok status"></span><?php
					}
					?>
				</div>
			</div>

			<!-- local preview -->
			<div class="preview">
				<div>
					<?php
					if ($image->local_upload_history) {
						$direct_url = end($image->local_upload_history)->url;
					} else {
						$direct_url = null;
					}
					if (!$image->local_exists) {
						?><span class="glyphicon glyphicon-remove status"></span><?php
					} else if ($image->local_width === 0) {
						?><a target="_blank" href="<?= $direct_url ?>"><em>(not an image)</em></a><?php
					} else {
						?>
						<a target="_blank" href="<?= $direct_url ?>">
							<img class="thumb" <?php			
								if ($image->local_preview_height && $image->local_preview_width) {
									?>width="<?= $image->local_preview_width ?>" height="<?= 
										$image->local_preview_height ?>"<?php
								}?> src="<?= $image->local_thumb ?>" />
						</a><br/> <code><?= $image->local_width ?> x <?= $image->local_height ?></code><?php
					}
					?>
				</div>
			</div>

			<!-- commons preview -->
			<div class="preview">
				<div>
					<?php
					if ($image->commons_upload_history) {
						$direct_url = end($image->commons_upload_history)->url;
					} else {
						$direct_url = null;
					}
					
					if (!$image->commons_exists) {
						?><span class="glyphicon glyphicon-remove status"></span><?php
					} else if ($image->local_hash === $image->commons_hash) {
						?><a target="_blank" href="<?= $direct_url ?>"> <span class="glyphicon glyphicon-ok status"></span></a><?php
					} else if ($image->commons_width === 0) {
						?><a target="_blank" href="<?= $direct_url ?>"><em>(not an image)</em></a><?php
					} else {
						?>
						<a target="_blank" href="<?= $direct_url ?>"> 
							<img class="thumb" <?php			
								if (	$image->commons_preview_height && $image->commons_preview_width) {
									?>width="<?= $image->commons_preview_width ?>" height="<?= 
										$image->commons_preview_height ?>"<?php
								}?> src="<?= $image->commons_thumb ?>" />
						</a>
						<br/><code><?= $image->commons_width ?> x <?= $image->commons_height ?></code><?php
					}
					?>
				</div>
			</div>

			<!-- local upload history -->
			<div class="history">
				<div>
					<?php 
					$local_upload_history = array_reverse($image->local_upload_history, false);
					foreach ($local_upload_history as $revision => $upload_history) {
						upload_template($local_project_data, $upload_history, $revision + 1);
					}			
					?>
				</div>
			</div>


			<!-- commons upload history -->
			<div class="history">
				<div>
					<?php 
					$commons_upload_history = array_reverse($image->commons_upload_history, false);
					foreach ($commons_upload_history as $revision => $upload_history) {
						upload_template($commons_project_data, $upload_history, $revision + 1);
					}			
					?>
				</div>
			</div>

			<!-- local text -->
			<div class="text">
				<?= $image->local_formatted_text ?>
			</div>

			<!-- commons text -->
			<div class="text">
				<?= $image->commons_formatted_text ?>
			</div>

			<!-- action text -->
			<div class="links">
				<a class="mark-auto" href="#">Mark</a><br />
	
				<!-- delink text -->
				<?php 
				if ($image->errors) {
					?>Ineligible to delink <span class="glyphicon glyphicon-remove status"></span><?php
				} else if ($image->same_name) {
					?>Same name (no delink) <span class="glyphicon glyphicon-ok status"></span><?php
				} else {
					if ($image->local_links) {
						$local_encode = sanitize(wiki_urlencode($image->local_title));
						$commons_encode = sanitize(wiki_urlencode($image->commons_title));
						 
						foreach ($image->local_links as $i => $link) {
							?><a target="_blank" class="linkback"
					href="<?= $local_project_data->getRawLink($link)?>"><?= $i + 1 ?></a><br/><?php
						}
					} else {
						?>No links <span class="glyphicon glyphicon-ok status"></span><?php
					}
				}			
				?><br /> <!-- delete text --> <a target="_blank" class="delete"
					data-name="<?= $local_html ?>"
					data-reason="<?= sanitize($image->local_delete_reason) ?>"
					href="<?= $image->local_delete_link?>">Delete</a><br /> <!-- edit text -->
					<a target="_blank" class="edit" href="<?= $image->local_edit_link?>">Edit local</a><br />
					<a target="_blank" class="edit" href="<?= $image->commons_edit_link?>">Edit comm</a><br />
	
					<!-- cleanup text --> <a target="_blank" class="cleanup"
					href="<?= $image->commons_cleanup_link ?>">Cleanup 2</a><br /> <!-- talk action -->
				<?php 
				if ($image->local_view_talk_link) {
					?><a target="_blank" class="talk" href="<?= $image->local_view_talk_link?>">View
						talk</a><br /><?php
				} else if ($image->local_move_talk_link) {
					?><a target="_blank" class="talk" href="<?= $image->local_move_talk_link?>">Move
						talk</a><br /><?php
				}?>
				
				<!-- NowCommons link --> 
				<br /> 
				<a target="_blank" class="nowcommons" href="<?= $image->local_now_commons_link ?>">
					NowCommons OK</a>
	
				<!-- Fileinfo link -->
				<br />
				<a target="_blank" class="fileinfo" href="<?= $image->local_fileinfo_link ?>">
					File info</a> 
				
				<!-- Old versions link -->
				<?php 
				if ($image->old_versions_link) {
					?>
					<br />
					<a target="_blank" class="oldver" href="<?= $image->old_versions_link ?>">Old
						versions</a><?php
				}
				?>
			</div>
		</div>
		<?php
	}	
	?>
	</div>
<span class="bottom-buttons">
	<span class="glyphicon glyphicon-remove close-buttons"></span> 
	<span class="bottom-buttons-buttons">
		<span class="ajax-count"></span>
		<input id="clear-marks" class="clear-marks btn btn-sm btn-default" type="button"
			value="Clear marks" />
		<input id="auto-mark-open" class="btn btn-sm btn-warning" type="button"
			value="Auto Mark" /> 
		<input id="auto-delete-open" class="btn btn-sm btn-primary" type="button"
			value="Delete (!)" />
	</span>
	</span>
	<div id="auto-mark">
		<div class="table-row">
			<div class="table-cell">
				<label for="auto-mark-text">Text in Commons or local description:</label>
			</div>
			<div class="table-cell">
				<textarea class="form-control" id="auto-mark-text"></textarea>
			</div>
		</div>
		<div class="table-row">
			<div class="table-cell">
				<label for="auto-mark-text-omit">Text NOT in Commons or local
					description:</label>
			</div>
			<div class="table-cell">
				<textarea class="form-control auto-delete-text"
					id="auto-mark-text-omit"></textarea>
			</div>
		</div>
		<div class="table-row">
			<div class="table-cell">
				<label for="auto-mark-text-omit">Uploader</label>
			</div>
			<div class="table-cell">
				<select class="form-control" id="auto-user"></select>
			</div>
		</div>
		<div class="table-row">
			<div class="table-cell">
				<label for="auto-delete-start">Start:</label>
			</div>
			<div class="table-cell">
				<input class="form-control" type="number" id="auto-mark-start"
					value="0" min="0"
					max="<?= count($now_commons_gallery->now_commons_images) ?>"/>
			</div>
		</div>
		<div class="table-row">
			<div class="table-cell">
				<label for="auto-delete-count">Count:</label>
			</div>
			<div class="table-cell">
				<input class="form-control" type="number" id="auto-mark-count"
					min="0"
					max="<?= count($now_commons_gallery->now_commons_images) ?>"
					value="<?= count($now_commons_gallery->now_commons_images) ?>" />
			</div>
		</div>
		<div class="table-row">
			<div class="table-cell">
				<label for="include-warnings">Include images with warnings (including redirects)</label>
			</div>
			<div class="table-cell checkbox">
				<input type="checkbox" id="include-warnings"/>
				<label for="include-warnings"></label>
			</div>
		</div>
		<div class="table-row">
			<div class="table-cell">
				<label for="include-diff-name">Include images in use with a different name</label>
			</div>
			<div class="table-cell checkbox">
				<input type="checkbox" id="include-diff-name"/>
				<label for="include-diff-name"></label>
			</div>
		</div>
		<div class="table-row">
			<div class="table-cell">
				<label for="include-multiple">Include images with a history version mismatch</label>
			</div>
			<div class="table-cell checkbox">
				<input type="checkbox" id="include-multiple"/>
				<label for="include-multiple"></label>
			</div>
		</div>
		<div class="table-row">
			<div class="table-cell">
				<label for="include-talk">Include images with a talk page</label>
			</div>
			<div class="table-cell checkbox">
				<input checked="checked" type="checkbox" id="include-talk"/>
				<label for="include-talk"></label>
			</div>
		</div>
		<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
			<div class="ui-dialog-buttonset">
				<input type="button" class="btn btn-primary" id="auto-mark-ok"
					value="Mark" />
				<input type="button" class="btn btn-default" id="auto-mark-test"
					value="Test" />
				<input type="button" class="btn btn-default" id="auto-mark-cancel"
					value="Cancel" />
			</div>
		</div>
		<span id="searchRegexBox"> <input id="searchRegex"
			class="btn btn-info btn-sm searchRegexBox" type="button"
			value="regexify?" />
		</span> <span id="searchRegexOmitBox"> <input id="searchRegexOmit"
			class="btn btn-info btn-sm searchRegexBox" type="button"
			value="regexify?" />
		</span>
	</div>
	<div id="auto-delete">
		<div class="table-row">
			<div class="table-cell">
				<label for="auto-delete-count">Count:</label>
			</div>
			<div class="table-cell">
				<input class="form-control" type="number" id="auto-delete-count"
					value="0" min="0"
					max="<?= count($now_commons_gallery->now_commons_images) ?>" />
			</div>
		</div>
		<div class="table-row">
			<div class="table-cell">
				<label for="auto-delete-nc">Click NowCommons (instead of delete)</label>
			</div>
			<div class="table-cell checkbox">
				<input type="checkbox" id="auto-delete-nc" />
				<label for="auto-delete-nc"></label>
			</div>
		</div>
		<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
			<div class="ui-dialog-buttonset">
				<input type="button" class="btn btn-primary" id="auto-delete-ok"
					value="OK" />
				<input type="button" class="btn btn-default" id="auto-delete-test" 
					value="Test" />
				<input type="button" class="btn btn-default" id="auto-delete-cancel" 
					value="Cancel" />
			</div>
		</div>
	</div>
	<div id="auto-files-found">
		<p>
			<span id="count"></span> files found.
		</p>
		<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
			<div class="ui-dialog-buttonset">
				<input type="button" class="btn btn-primary"
					id="auto-delete-found-ok" value="OK" />
			</div>
		</div>
	</div>
</body>
</html>