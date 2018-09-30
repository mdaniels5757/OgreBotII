<?php 
require_once __DIR__ . "/../base/bootstrap.php";
global $validator, $wiki_interface;

$http_io = new Http_Io();
$http_io->ob_start();
$oldver_logic = new Oldver_Logic();
$oldver_logic->run();
Oldver_Shared::load_messages();
?>
<!DOCTYPE html>
<html>
<head>
<title>Old version filemover</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php 
$http_io->transcludeScriptRemote(
	["bootstrap.css.url", "bootstrap.css.theme.url", 'fontawesome.css.url', 'awesome.css.url']);
$http_io->transcludeScript(["oldver", "shared"], "css");
$http_io->transcludeScriptRemote("jquery.url");
?>
</head>
<body><?php
require "../oldver/header.php";
?><form id="form" method='post' action='<?= $_SERVER["SCRIPT_NAME"] ?>'>
		<div class="head">
			<div>
				<div>Project</div>
				<div>
					<select class="form-control" name='project'><?php
				foreach ($projects as $next_proj) {
					$selected = ($next_proj==$proj)?" selected=\"selected\"":"";
					?><option <?= $selected ?>><?= $next_proj ?></option><?php
				}
			?></select>
				</div>
			</div>
			<div>
				<div>Source file</div>
				<div>
					<input class="form-control" type='text' size='50' name='src' id='src' 
				value='<?= $srcval ?>' required="required"
				style="<?= !$startpage ? "display: none;" : "" ?>"
			/><?php
			if (!$startpage) {
				?>
				<span id="srcspan"><a href="<?= $localTitleLink ?>"><?= $localTitleHtml ?></a></span>
				<?php 
			}?>
			<span class="error"><?= Oldver_Shared::msg($localerrmessage) ?></span>
				</div>
			</div>
			<div>
				<div>Target file</div>
				<div>
					<input class="form-control" type='text' size='50' name='trg' id='trg' 
				value='<?= $trgval ?>' required="required"
				style="<?= !$startpage ? "display: none;" : "" ?>"
			/><?php 
			if (!$startpage) {
				?>
				<span id="trgspan"><a href="<?= $comTitleLink ?>"><?= $comTitleHtml ?></a></span>
				<?php 
			}?>
			<span class="error"><?= Oldver_Shared::msg($sharederrmessage) ?></span>
				</div>
			</div>
			<div>
				<div></div>
				<div>
					<span class="pleaseWait">
				<?= Oldver_Shared::msg("submitting") ?><br />
				<?= Oldver_Shared::msg("please_wait") ?><br />
					</span> <input class="btn btn-primary" type='submit' id="submit_OV" value='next' 
				style="<?= !$startpage ? "display: none;" : ""?>"/><?php 
			if (!$startpage) { 
				?><input class="btn btn-default" type='button' id="change_files"
						value='change' /> <?php 
			}?>
		</div>
			</div>
		</div>
	</form>
<?php
if (!$startpage) {
	$critical_error=false;

	preg_match("/^(\d+)\.?/", microtime(true)*1000, $timestamp_milli) || die("Can't parse local time");
	$lochistory = $wiki_interface->get_upload_history($locimg);
	$allLocalHashes = array();
	$comhistory = $wiki_interface->get_upload_history($comimg);
	$count_l = count($lochistory);
	$count_c = count($comhistory);
	
	?>
<form method='post' id="process_form" name="process_form"
		action='process_uploads.php'>
	<input type="hidden" name="ident-cookie" id="ident-cookie" />
	<input type="hidden" name="src" id="src" value="<?= sanitize($src) ?>" />
	<input type="hidden" name="trg" id="trg" value="<?= sanitize($trg) ?>" />
	<input type="hidden" name="project" id="project" value="<?= $proj ?>" />
	<input type="hidden" name="timestamp" id="timestamp" value="<?= $timestamp_milli[1] ?>" />
	<h2>Revisions on <?= $proj ?></h2>
	<?php 
	
	if (!$count_l) {
		?><span class="error">No revisions present!</span><?php
	} else {
		?>
		<table border="3">
			<tr>
				<th><?= $proj ?> image</th>
				<th>preview</th>
				<th>upload to commons?</th>
			</tr><?php
			/* i.e., else... loop will only be entered if count_l>0 */
		for($i = 0; $i < $count_l; $i++) {
			$display_index = $count_l - $i;
			$instance = $lochistory[$i];
			$hash = $instance['sha1'];
			$allLocalHashes[$hash] = 1;
			
			/* verify this version is not a duplicate */
			$revert_no = -1;
			$unchanged = false;
			if ($display_index > 1) {
				if ($lochistory[$i + 1]['sha1'] === $hash) {
					$unchanged = true;
				} else {
					$revert_no = -1;
					for($j = $count_l - 1; $j > $i; $j--) {
						if ($lochistory[$j]['sha1'] === $hash) {
							$revert_no = $count_l - $j;
							$validator->assert($revert_no != -1);
							break;
						}
					}
				}
			}
	
			/* FIXME: need to check that commons file exists at all */
			/* checking if this revision is already uploaded to commons */
			$commons_equiv_samefile = -1;
			$commons_equiv_samefile_currentver = false;
			$commons_equiv_altfile = NULL;
			$commons_equiv_altfile_dupe = false;
	
			if ($unchanged === false && $revert_no == -1) {
				/* check filename provided */
				for($j = $count_c - 1; $j >= 0; $j--) {
					if ($comhistory[$j]['sha1'] === $hash) {
						$commons_equiv_samefile = $j;
						if ($comhistory[0]['sha1'] === $hash) {
							$commons_equiv_samefile_currentver = true;
						}
						break;
					}
				}
				
				/* check elsewhere on commons */
				$dupesotherfile;
				/* very occasionally, the API is spitting nothing back at us for the hash, or it returns false */
				if ($hash != NULL) {
					if (@$co === null) {
						$co = $wiki_interface->new_wiki("OgreBotCommons");
					}
					
					$dupe_query = $wiki_interface->api_query($co, 
						array('action' => 'query', 'list' => 'allimages', 'aisha1' => $hash));
					$dupesotherfile = array_key_or_exception($dupe_query, 'query', 'allimages');
					$validator->validate_arg($dupesotherfile, "array");
					
					/* remove current file */
					foreach ($dupesotherfile as $j => $dupe_instance) {
						if (str_replace("_", " ", $dupe_instance['name']) ==
							 $comimg->get_page()->get_title(false)) {
							unset($dupesotherfile[$j]);
							break;
						}
					}
					
					if (count($dupesotherfile) > 0) {
						$commons_equiv_altfile_dupe = true;
						$commons_equiv_altfile_arr = array_pop($dupesotherfile);
						$commons_equiv_altfile = $commons_equiv_altfile_arr['name'];
						$validator->assert($commons_equiv_altfile !== NULL, 
							"API ERROR: Searching for duplicate of revision File:$localname, hash: $hash");
					}
				}
			}
			
			if ($hash === null) {
				if (array_key_exists('filehidden', $instance)) {
					$revert_text = Oldver_Shared::msg('status.hidden');
				} else {
					$revert_text = Oldver_Shared::msg('status.corrupt');
				}
			} else if ($unchanged) {
				$revert_text = Oldver_Shared::msg('status.unchanged');
			} else if ($revert_no != -1) {
				$revert_text = Oldver_Shared::msg('status.revert', array("revert_no" => $revert_no));
			} else {
				$revert_text = null;
			}
			
			/* display revision information for local image */
			$cleanup_timestamp = $oldver_logic->get_human_readable_timestamp($instance);
			$clean_byte_size = parse_number($instance['size']);
			
			$userpageLink = $project_data_local->getRawLink("User:$instance[user]");
			$userpageHtml = sanitize($instance["user"]);
			$comment_html = preg_replace("/\s+/", " ", sanitize($instance['comment'], false));
			
			$previewWidth = 200;
			$ratio = $instance['width'] / $instance['height'];
			if ($ratio < 1) {
				$previewWidth = intval($previewWidth * $ratio);
			} /* could conceivably leave preview at 0px... but that's OK */
			$thumb = $wiki_interface->get_thumb($instance, $previewWidth);
			$loctitle = $locimg->get_page()->get_title(false);
			
			$recommended = true;
			
			$onCommons = null;
			if (@$revert_text) {
				$recommended = false;
			} else if ($commons_equiv_altfile !== NULL) {
				$recommended = false;
				$revert_text = "Already on Commons as " . wikilink("File:$commons_equiv_altfile", 
					"commons.wikimedia", true, false, NULL, "", true);
			} else if (!$commons_equiv_samefile_currentver && $commons_equiv_samefile > -1) {
				/* avoid duplicate text */
				$recommended = false;
				$validator->assert(
					$commons_equiv_samefile !== 0 || $commons_equiv_samefile_currentver);
				if ($commons_equiv_samefile > -1 && !$commons_equiv_samefile_currentver) {
					$onCommons = $comhistory[$commons_equiv_samefile]['url'];
				}
			}
			?>
				<tr>
				<td>
						<?php 
						if (@$instance['url']) {
							?><a href="<?= $instance['url'] ?>"><?php
						}
						?>Rev #<?= $display_index ?><?php
						if (@$instance['url']) {
							?></a><?php
						}
						?>:
						<?= $cleanup_timestamp ?> 
						<?= Oldver_Shared::msg("dimensions", $instance) ?>
						<?= Oldver_Shared::msg("bytes", array("size" => $clean_byte_size))?>
						<a class="softlink" href="<?= $userpageLink ?>"><?= $userpageHtml ?></a>
					<span class="comment"><?= $comment_html ?></span>
				</td>
				<td style="text-align: center">
						<?php 
						if (@$instance['url']) {
							?><a href="<?= $instance['url'] ?>"><?php
						}						
						if ($thumb!==null) {
							?><img src="<?= $thumb ?>" width="<?= $previewWidth ?>" /><?php
						} else if (array_key_exists('filehidden', $instance)) {
							?><span class="glyphicon glyphicon-remove status"></span><br /> <span class="error revert"><?=  Oldver_Shared::msg('upload.hidden') ?></span><?php
						} else if (preg_match("/\.(?:ogg|midi?)$/", $loctitle)) {
							?><span class="error revert"><?=  Oldver_Shared::msg('upload.badmime') ?></span><?php
						} else {
							?><span class="glyphicon glyphicon-remove status"></span><br /> <span class="error revert"><?=  Oldver_Shared::msg('upload.unavailable') ?></span><?php
						}
						if (@$instance['url']) {
							?></a><?php
						}
						?>
					</td>
				<td style="text-align: center"> <?php 
				/* display info for commons equivalent; defaults to unchecked if image already on commons, or if image is a duplicate locally */
				
				if ($recommended) {
					?><span class="glyphicon glyphicon-ok status"></span><?php
					if ($commons_equiv_samefile_currentver) {
						?><span class="recommend">
							<?=  Oldver_Shared::msg("action.commons") ?> (<a
						href="<?= $comhistory[0]['url'] ?>">rev #<?= $count_c ?>)</a>
				</span> <br /> <span class="action revert_text">(<?=  Oldver_Shared::msg("action.revert") ?>)</span><?php
					}
				} else {
					?><span class="glyphicon glyphicon-remove status"></span> <span class="recommend"><?=  Oldver_Shared::msg("action.notrecommended") ?></span>
					<br /><?php 
					if (@$revert_text) {
						?><span class="error revert_text">(<?= $revert_text ?>)<?php
					}
					if ($onCommons) {
					   ?>Already in file history (<a href="<?= $onCommons ?>"><?php
					   ?>rev #<?= $count_c - $commons_equiv_samefile ?></a>)<?php 
					}
					?></span><?php
				}
				?> <br />
					<div class="checkbox">
						<input type="checkbox" id="upload<?= $display_index ?>" name="upload<?= $display_index ?>" <?= $recommended ? "checked":"" ?> />
						<label for="upload<?= $display_index ?>"></label>
					</div>
				</td>
			</tr><?php
		}?>
			</table><?php	
	} 

	/* not displaying versions in Local and on Commons */
	foreach ($comhistory as $i => $instance) {
		//was this version in the local history?
		if (@$allLocalHashes[$instance['sha1']]) {
			unset($comhistory[$i]);
			continue;
		}
		
		//is this version just a duplicate of a later upload on the same page?
		for ($j=count($comhistory)-1; $j>$i; $j--) {
			if ($instance['sha1']===$comhistory[$j]['sha1']) {
				unset($comhistory[$i]);
				continue 2;
			}
		}
		
	}
	/* display commons versions not in local image */
	if (count($comhistory) > 0) {
		?><br /> <br />
		<h2>Versions of Commons image not found in the <?= $proj ?> image</h2>
		<table border="3">
			<tr>
				<th>Description</th>
				<th>preview</th>
				<th>notes</th>
			</tr>
				
	  <?php

		foreach ($comhistory as $i => $instance) {
			$display_index = $count_c-$i;

			$cleanup_timestamp = $oldver_logic->get_human_readable_timestamp($instance);
			$clean_byte_size = parse_number($instance['size']);
			
			$userpageLink = $project_data_commons->getRawLink("User:$instance[user]");
			$userpageHtml = sanitize($instance["user"]);
			$comment_html = preg_replace("/\s+/", " ", sanitize($instance['comment'], false));
			
			$previewWidth = 200;
			$thumb = $wiki_interface->get_thumb($instance, $previewWidth);
			
			
			?><tr>
				<td>
					<?php 
					if (@$instance['url']) {
						?><a href="<?= $instance['url'] ?>"><?php
					}
					?>Rev #<?= $display_index ?><?php 
					if (@$instance['url']) {
						?></a><?php
					}
					?>: 
					<?= $cleanup_timestamp ?>
					<?=  Oldver_Shared::msg("dimensions", $instance) ?>
					<?=  Oldver_Shared::msg("bytes", array("size" => $clean_byte_size))?>
					<a class="softlink" href="<?= $userpageLink ?>"><?= $userpageHtml ?></a>
					<span class="comment"><?= $comment_html ?></span>
				</td>
				<td style="text-align: center">
					<?php 
						if (@$instance['url']) {
							?><a href="<?= $instance['url'] ?>"><?php
						}
						
						if ($thumb!==null) {
							?><img src="<?= $thumb ?>" width="<?= $previewWidth ?>" /><?php
						} else if (array_key_exists('filehidden', $instance)) {
							?><span class="glyphicon glyphicon-remove status"></span><br /> <span class="error revert"><?=  Oldver_Shared::msg('upload.hidden') ?></span><?php
						} else if (preg_match("/\.(?:ogg|midi?)$/", $loctitle)) {
							?><span class="error revert"><?=  Oldver_Shared::msg('upload.badmime') ?></span><?php
						} else {
							?><span class="glyphicon glyphicon-remove status"></span><br /> <span class="error revert"><?=  Oldver_Shared::msg('upload.unavailable') ?></span><?php
						}
						?><?php 
						
						if (@$instance['url']) {
							?></a><?php
						}												
						?>
					</td><?php 
			if ($instance['sha1'] == $comhistory[0]['sha1']) {
				?><td style="text-align: center"><br />
					<span class="glyphicon glyphicon-ok status"></span><br /> <span class="action revert_text">(<?=  Oldver_Shared::msg("action.most_recent") ?>)</span>
					<span class="error revert_text"><br />(<?=  Oldver_Shared::msg("action.revert") ?>)</span><br />
					<div class="checkbox">
						<input type="checkbox" id="revert" name="revert" checked="checked" />
						<label for="revert"></label>
					</div>
				  </td>
				<?php
			}
			?></tr><?php
		}
		?></table>
		<?php
	}
	?>
	<span class="upload_text">
		<?=  Oldver_Shared::msg("submitting") ?><br />
		<?=  Oldver_Shared::msg("uploading_text") ?><br />
	</span>
	<input class="btn btn-primary submit-pu" type="submit" value='Upload' style="display: none;" /><br />
	
	<div class="checkbox">
		<input type="checkbox" name="upload_history" id="upload_history" />
		<label for="upload_history" ><?=  Oldver_Shared::msg("output_upload_history")?></label>
	</div>

	<!--  TUSC header -->
	<span class="tusc_username">
		<?= $tusc_header ?>
	</span><br />
		
	<iframe style='border: 2px solid gray; padding: 2px; margin: 2px; width: 100%' 
		id="ident-frame" src="identity/start.php?compact=1"></iframe>
	</form> <?php
}
$http_io->transcludeScript(["oldver", "shared", "project-multibox"], "js");
?></body>
</html>
