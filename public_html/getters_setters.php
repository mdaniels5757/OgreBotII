<?php
require_once __DIR__ . "/../base/bootstrap.php";

global $env;
$post = $env->get_request_args();

$in = @$post['in'];
$include_setters = @$post['setters'] ? true : false;
$use_camel_case = @$post['camel'] ? true : false;

if ($in !== null) {
	$out = Generate_Getters_Setters::generate_from_text($in, $include_setters, $use_camel_case, 
		true);
	$out_escaped = sanitize($out, false);
	$in_escaped = sanitize($in, false);
} else {
	$out_escaped = null;
	$in_escaped = "";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
<title>Getter / setter generator</title>
<style type="text/css">
.clear {
	clear: all;
}
</style>
</head>
<body>
	<form name='form' method='post'>

		<br class="clear" /> <label for='in'>Input:</label><br class="clear" />
		<textarea name='in' id='in' cols="60" rows="10"><?= $in_escaped ?></textarea>
		<br class="clear" /> <input type="checkbox" name="setters"
			id="setters" <?= $include_setters?" checked":"" ?> /> <label
			for='setters'>Include setters?</label> <br class="clear" /> <input
			type="checkbox" name="camel" id="camel"
			<?= $use_camel_case?" checked":"" ?> /> <label for='camel'>Use
			camel-case?</label> <br class="clear" /> <input type='submit' />
		
		<?php
		if ($out_escaped !== null) {
			?><br class="clear" /> <label for='out'>Output:</label><br
			class="clear" />
		<textarea name='out' id='out' cols="60" rows="10"><?= $out_escaped ?></textarea>
			<?php
		}
		?>		
	</form>
</body>
</html>