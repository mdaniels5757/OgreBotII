<?php
define("LOGGER_NAME", "ident-start");
require_once __DIR__ . "/../../base/bootstrap.php";
global $constants, $string_utils;

$username = (new Identity_Verifier_Impl())->get_username();

$http_io = new Http_Io();
$http_io->ob_start();
$http_io->set_no_cache_headers();
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Tool Labs identity verifier</title>
<?php 
$http_io->transclude_no_cache_tags();
$http_io->transcludeScriptRemote(['bootstrap.css.url', 'bootstrap.css.theme.url']);
$http_io->transcludeScript('ident', 'css', '../');
?>
</head>
<body ng-cloak="ng-cloak" ng-app="app" ng-controller="ctrl" ng-class="{compact: compact, expanded: !compact}" 
	ng-init='username=<?= $string_utils->encode_json_for_html($username) ?>;
			 compact=<?= @$_REQUEST['compact'] ? "true" : "false" ?>'>
	<div class="body-wrapper">
		<header ng-show="!compact" class="sharedHeader">
			<div class="toolTitle">
				Identity verifier
			</div>
		</header>
		<main class="newuser-instructions">
			<div ng-show="cookie">
				<p ng-show="username">
					Currently logged in as: <a target="_blank" 
						href="http://meta.wikimedia.org/wiki/User:{{username|escape}}">{{username}}</a>.
				</p>
				<button class="btn btn-warning" ng-disabled="ajax" type="button" ng-click="logout()">
					Log out</button>				
			</div>
			<div ng-show="!cookie">
				<p>Currently logged out.</p>
				<button class="btn btn-primary" ng-show="!compact" ng-disabled="ajax" type="button" 
					ng-click="authorize()">Click here to authorize this tool</button>
				<button class="btn btn-primary" ng-show="compact" ng-disabled="ajax" type="button"
					ng-click="open()">Click here to continue</button>
			</div>
			<div ng-show="!compact">
				<h2>What is this tool?</h2>
				<ul>
					<li>This is a tool which can be used by <em>other tools</em> on Wikimedia Labs to verify your identity.</li>
					<li>This tool is meant as a replacement for <a target="_blank" href="http://tools.wmflabs.org/tusc/">TUSC</a>.</li>
				</ul>
				
				<h2>Is it safe to use?</h2>
				<ul>
					<li>Yes.</li>
				</ul>
				
				<h2>Who will be able to see my identity?</h2>
				<ul>
					<li>Currently, only bots maintained by Magog the Ogre.</li>
					<li>Other frameworks may be added in the future.</li>
				</ul>
				
				<h2>How long do I remain logged in?</h2>
				<ul>
					<li>30 days. If you wish to log out, you can either clear your cookies or return to this page.</li>
					<li>Due to technical limitations, if you log out of Wikimedia, you will not be logged out on Tool Labs, and vice versa.</li>
				</ul>
						
				<h2>I am a bot owner. Can I use this framework?</h2>
				<ul>
					<li>Yes. Please contact the <a 
						href="<?= $constants['branchmapper.report_bug_link'] ?>"
						target="_blank">bot maintainer</a>.</li>
				</ul> 
						
				<h2>Other notes</h2>
				<ul>
					<li>For questions or feature requests, please contact the <a 
						href="<?= $constants['branchmapper.report_bug_link'] ?>"
						target="_blank">bot maintainer</a>.</li>
				</ul> 
				
				<button class="btn btn-primary" ng-show="!cookie" ng-disabled="ajax"
					type="button" ng-click="authorize()">Click here to authorize this tool</button>
			</div>
		</main>	
	</div>
	<?php 
	$http_io->transcludeScriptRemote(['angular.js.url', 'angular.cookies.js.url']);
	$http_io->transcludeScript('ident', 'js', '../');
	?>
</body>
</html>
