<?php 
require_once __DIR__ . "/../base/bootstrap.php";

$POST = $env->get_request_args();
//$POST['image'] = "File:Hbg fixed.jpg";

$obj = Do_Cleanup_Logic::get_cleanup_data($POST, function($title) {
	return wikilink($title, "commons.wikimedia");
});

$auto_submit = false;

if (!@$obj['error']) {
		
	if (@$obj['warnings']) {
		$button_text = "Continue";
		$below_button_text = "(continue anyway)";
	} else {
		$auto_submit = true;
		$button_text = "Click me if you have JavaScript disabled";
		$below_button_text = "(otherwise, wait a second or two...)";
	}
	
	$action_raw = array_key_or_exception($constants, "do_cleanup.url");
	$title = urlencode(str_replace(" ", "_", $POST['image']));
	$action = replace_named_variables(
			$action_raw, 
			array("title" => $title), 
			false
	);
	
	$cleanup_text_sanitized = sanitize($obj['text'], false);
	$edit_summary_sanitized = sanitize($obj['summary'], false);
	$startdate = date('YmdHis');
	$edittime = $obj['edittime'];
}
/**
 * HEADER
 */
header("Content-Type: text/html; charset=utf-8");

/**
 * HTML
 */
$http_io = new Http_Io();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- code blatantly and shamelessly ripped off from http://toolserver.org/~magnus/add_information.php, which is under GPL. This page too is under GPL as a derivative work. -->
<html>
<head>
<?php $http_io->transcludeScriptRemote('jquery.url'); ?>
<script>
$(document).ready(function() {
	if ($("#autoSubmit").val()) {
		$("[name='editform']").submit();
	}
});
</script>
</head>
<body>
	<input type="hidden" id="autoSubmit" value="<?= $auto_submit ?>" />
<?php 
if (@$obj['error']) {
?><span style="color: red"><?= $obj['error']?></span><?php 
} else {
	if (@$obj['warnings']) {?>
		<div style="color: red">
		Warning:<br />
		<ul><?php
			foreach ($obj['warnings'] as $warning) {?>
				<li><?= sanitize($warning);?></li><?php 
			}?>
			</ul>
	</div><?php 		
	}?>
	
	<form name="editform" method="post" enctype='multipart/form-data'
		action='<?= $action ?>'>
		<input type='hidden' name='wpTextbox1'
			value='<?= $cleanup_text_sanitized ?>' /> <input type='hidden'
			name='wpSummary' value='<?= $edit_summary_sanitized ?>' /> <input
			type='hidden' name='wpDiff' value='Show changes' /> <input
			type='hidden' name='wpStarttime' value='<?= $startdate ?>' /> <input
			type='hidden' name='wpEdittime' value="<?= $edittime ?>" /> <input
			type='hidden' name='wpAntispam' value='' /> <input type="hidden"
			name='format' value='text/x-wiki' /> <input type="hidden"
			name='model' value='wikitext' /> <input type="hidden" name='oldid'
			value='0' /> <input type="hidden" name="wpScrolltop" value="5" /> <input
			type="hidden" name="wpEditToken" value="+\\" /> <input type="hidden"
			name="wpUltimateParam" value="1" /> <input type="hidden"
			name="loadgroup" value="" /> <input type="hidden" name="loadtask"
			value="" /> <input type='submit' value='<?= $button_text ?>' />
	</form>
	<br /><?= $below_button_text ?>
	<?php 
}
?>	
</body>
</html>
