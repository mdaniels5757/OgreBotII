<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $constants;

$f = new Fileinfo_Web_Logic();
$f->load();
$http_io = new Http_Io();
$http_io->ob_start();
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?= $f->msg("title") ?></title>
<?php 
$http_io->transcludeScriptRemote(['bootstrap.css.url', 'bootstrap.css.theme.url',
		'fontawesome.css.url', 'awesome.css.url']);
$http_io->transcludeScript(["fileinfo", "shared"], "css");
$http_io->transcludeScriptRemote("jquery2.url");
?>
</head>
<body>
	<?php if ($f->get_global_error()) {
		echo $f->get_global_error();
	} else {
		?>
	<div class='table-div header'>
		<div class="table-row">
			<div class='table-cell title'><?= $f->msg("title") ?></div>
			<div class="table-cell description"><?= $f->msg("description") ?>
				<br /> <a href='<?= $constants["fileinfo.report_bug_link"] ?>'> <?= $f->msg("report_bug_text") ?></a>
			</div>
		</div>
	</div>
	<form name='form' method='post' action='<?= $_SERVER['PHP_SELF']?>'>
		<div class='border-div table-div'>
			<div class="table-row">
				<div class='table-header'><?= $f->msg("project") ?></div>
				<div class='table-cell'><select class="form-control" name='project'><?php 
					foreach ($f->get_all_projects() as $project) {
						?><option<?php 
						if ($f->get_project() === $project) {
							?> selected="selected"<?php
						}
						?>><?= $project ?></option><?php 
					}
					?></select></div>
			</div>
			<div class="table-row">
				<div class='table-header'><?= $f->msg("source") ?></div>
				<div class='table-cell'>
					<input class="form-control" type='text' size='50' name='src' 
						value='<?= $f->get_src_sanitized() ?>' required="required"/> 
					<span class="error"><?= $f->get_errmessage() ?></span>
				</div>
			</div>
			<div class="table-row">
				<div class='table-header'><?= $f->msg("style") ?></div>
				<div class='table-cell'><select name="style" class="form-control"><?php 
					foreach (Upload_History_Wiki_Text_Writer::get_instances() as $history_instance) {
						$name = $history_instance->get_name();
						?><option<?= $f->get_style() === $name ? " selected=\"selected\"" : "" ?>><?=
							$name ?></option><?php
					}
				?></select></div>
			</div>
			<div class="table-row">
				<div class='table-cell'></div>
				<div class='table-cell'>
				
  					<div class="checkbox">
						<input type="checkbox" name='information' id='information' 
							<?= $f->get_informationChecked() ?> /> 
						<label for="information"><?= $f->msg("generate_information")?></label>
					</div>
					
					<div class="checkbox">
						<input type="checkbox" name='fields' id='fields'
							<?= $f->get_fieldsformtext() ?> /> 
						<label for="fields">&nbsp;&nbsp;<?= $f->msg("fill_in_fields")?></label>
					</div>

					<div class="checkbox">
						<input type="checkbox" name='authordate' id='authordate' 
							<?= $f->get_authordateformtext() ?> />
						<label for="authordate">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $f->msg("including_upload_date")?></label>
					</div>

					<div class="checkbox">
						<input type="checkbox" name='license' id='license'
						<?= $f->get_licenseformtext() ?> />
						<label for="license">&nbsp;&nbsp;<?= $f->msg("add_license") ?> 
							<span class="add_license"> <?= $f->msg("add_license_warn") ?></span></label>
					</div>
				</div>
			</div>
			<div class="table-row">
				<div class='table-cell'></div>
				<div class='table-cell'>
					<span id="empty"><?= $f->msg("please_enter_valid_name") ?><br /></span>
					<span id="submitting">
						<?= $f->msg("submitting") ?>...
						<br />
						
						<?= $f->msg("please_wait") ?>
						<br />
					</span>
					<input class="btn btn-primary" type='submit' value='<?= $f->msg("next") ?>' />
				</div>
			</div>
		</div>
		<?php
		if (!$f->get_startpage()) {
			?><?= $f->get_view_file_local_link()?>
			<br /><?php
			if ($f->get_view_file_commons_link()) {
			?><?= 
				$f->get_view_file_commons_link(); ?>
				<br /><?php
			}
			?><?=

			$f->get_edit_file_local_link()?><br /><?php
			if ($f->get_edit_file_commons_link()) {
				?><?= $f->get_edit_file_commons_link(); ?>
				<br /><?php
			}
			?>
			<textarea tabindex="1" cols="60" rows="20"><?=
				sanitize($f->get_text(), false) ?></textarea><?php 
		}
		?>
	</form>
	<?php
	}

	$http_io->transcludeScript("fileinfo", "js");
	?>
</body>
</html>
