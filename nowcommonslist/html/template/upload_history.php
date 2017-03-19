<?php
function cleanup_timestamp(Upload_History_Instance $instance) {
	return preg_replace("/^([\d\-]+)T(\d\d\:\d\d)\:\d\dZ/", "$1&nbsp;$2", $instance->timestamp);
}

/**
 *
 * @param ProjectData $project_data
 * @param Upload_History_Instance $instance
 * @param int $revision
 * @return void
 */
function upload_template(ProjectData $project_data, Upload_History_Instance $instance, $revision) {

	$user_href = $project_data->getRawLink("User:$instance->user");
	$user_html = sanitize($instance->user);
	$writeable_comment = strlen($instance->comment) > 100 ?
	(substr($instance->comment, 0, 97)."...") : $instance->comment;

	if ($revision !== 1) {
		?><br /><?php
	}
	?><em>(<?= $revision ?>)</em>
		<a class="revision" href="<?= $instance->url?>"><?= cleanup_timestamp($instance) ?></a><br/>
		<a class="user" href="<?= $user_href ?>"><?= $user_html ?></a>
<code><?php 
	if ($instance->revert) {
		?><font color="red">(Rv to <em>(<?= $instance->revert ?>)</em>)
	</font><?php
	} else if ($instance->unchanged) {
		?><font color="red">(unchanged)</font><?php
	}
	?><em><?= sanitize($writeable_comment, false) ?></em>
</code><?php
}