<?php 
global $constants, $msg;
?><div class="sharedHeader">
	<div>
		<div class="toolTitle">
			<a href="oldver.php"><?= $msg['header.title'] ?></a>
		</div>
		<div class="toolDescription" ><?= $msg['header.description'] ?><br/>&nbsp;</div>
		<div class="toolLinks">
			<a href="oldverlog.php"><?= $msg['header.view_log'] ?></a><br />
			<a href='<?= $constants["oldver.report_bug_link"] ?>'><?= $msg['header.report_bug'] ?></a>
		</div>
	</div>
</div>