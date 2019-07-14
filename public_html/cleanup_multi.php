<?php
require_once __DIR__ . "/../base/bootstrap.php";

$talk_page_name  = Environment::prop("constants", "eligibility.page.cleanup.commons.wikimedia");
$talk_page_link = (new Project_Data("commons.wikimedia"))->getRawLink($talk_page_name);
$permission_required = replace_named_variables(
	Environment::prop("messages", "cleanup_multi.permission_required"), [
		"link" => $talk_page_link]);

$minimum_limit = Environment::prop("constants", "cleanup_multi.limit.min");
$maximum_limit = Environment::prop("constants", "cleanup_multi.limit.max");
$disclaimer_lines =  Environment::prop("messages", "cleanup_multi.disclaimers");

$http_io = new Http_Io();
$http_io->ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php

$http_io->transcludeScriptRemote(
	['bootstrap.css.url', 'bootstrap.css.theme.url', "timepicker.css.url", 'fontawesome.css.url',
			'awesome.css.url', "jquery-ui.css.url"]);
$http_io->transcludeScript(['do_cleanup_multi', 'shared'], 'css');
$http_io->transcludeScriptRemote(['jquery.url', 'jquery-ui.url', "timepicker.url"]);
$http_io->transcludeScript("do_cleanup_multi", "js", null, ["defer"]);
?>
</head>
<body>
	<div class="sharedHeader">
		<div>
			<div class="toolTitle">Cleanup Tool</div>
			<div class="toolDescription">
				A tool to run OgreBot 2's cleanup script on a bulk number of files.<br />&nbsp;
			</div>
			<div class="toolLinks">
				<br /> <a
					href='//commons.wikimedia.org/wiki/User_talk:Magog_the_Ogre'>
					Report a bug or suggest a feature </a>
			</div>
		</div>
	</div>

	<form method='post' action='do_cleanup_multi.php'>
		<input type="hidden" name="ident-cookie" id="ident-cookie" />
		<div class="head">
			<div>
				<div>Type</div>
				<div>
					<select class="form-control" name='type'>
						<option selected="selected" value="category">Category</option>
						<option value="search">Wiki search</option>
						<option value="uploader">Uploader</option>
					</select>
				</div>
			</div>
			<div>
				<div id="type-text">Category</div>
				<div>
					<input class="form-control" type='text' size='50' name='src' id='src' value='Category:' />
				</div>
			</div>
			<div class="uploader-option">
				<div id="type-text">Start</div>
				<div>
					<input class="form-control" type='text' size='50' name='start' id='start' value='' />
				</div>
			</div>
			<div class="uploader-option">
				<div id="type-text">End</div>
				<div>
					<input class="form-control" type='text' size='50' name='end' id='end' value='' />
				</div>
			</div>
			<div>
				<div></div>
				<div>
					<span id="subcategories-wrapper" class="checkbox">
						<input type="checkbox" name='subcategories' id='subcategories'/>
						<label for="subcategories">Subcategories?</label>
					</span>
					<button class="btn btn-default" id="test-search" type="button">
						Test this search
					</button>
				</div>
			</div>

			<div>
				<div>Limit</div>
				<div>
					<input class="form-control limit-input" type="number" name="limit" value="500" 
						min="<?= $minimum_limit ?>" max="<?= $maximum_limit ?>"/>
				</div>
			</div>
		</div>


		<!-- TUSC verification box -->
		<br /> <br />
		<div class="tusc-box">
			<div class="signup-notice"><?= $permission_required ?></div>
			<span id="ajax_progress_text"></span> 
			<span id="verify-text">Verify your Commons user name</span><br /> 
			<div class='submit-wrapper'>
				<input type="submit" class="btn btn-primary submit_button" name="submit_button" 
					value='Process' style="display: none;"/>
				<br /><br />
				<span id="clickonce_warn"> Submitting...<br/> Please wait while
					OgreBot processes your request. <em>Please do not submit the page
						multiple times.</em>
				</span>
			</div>
			<iframe id="ident-frame" src="identity/start.php?compact=1"></iframe>
		</div>
	</form>
	<div id="dialog-disclaimers" title="Disclaimers">
		<?php
		foreach ($disclaimer_lines as $disclaimer_lines) {
			?><p><?= $disclaimer_lines ?></p><?php
		}
		?>
		<hr />
		<div class="checkbox">
			<input type="checkbox" id="checkbox-disclaimers" />
			<label for="checkbox-disclaimers">
				<?= $messages["cleanup_multi.checkbox"] ?></label>
		</div>
	</div>
</body>
</html>
