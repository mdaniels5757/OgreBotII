<?php
require_once __DIR__ . "/../base/bootstrap.php";
$http_io = new Http_Io();
$http_io->ob_start();
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<?php 
	$http_io->transcludeScriptRemote(['bootstrap.css.url', 'bootstrap.css.theme.url']);
	$http_io->transcludeScript("category-files-blame", "css");
	?>
	<title>Category Files Blamer</title>
</head>
<body ng-app="app" ng-controller="ctrl">
	<form ng-cloak>
		<div class="_table">
			<div class="table-row">
				<div class="table-cell">
					<label for="date">Date</label>
				</div>
				<div class="table-cell">
					<select class="form-control" ng-model="selectedDate" name='date'>
						<option ng-repeat="date in dates" value="{{date}}">{{date}}</option>
					</select>
				</div>
			</div>
			<div class="table-row">
				<div class="table-cell">
					<label for="name">Gallery page (optional)</label>
				</div>
				<div class="table-cell">
					<select class="form-control" ng-model="selectedGallery" name="gallery">
						<option value="">&lt;All galleries&gt;</option>
						<option ng-repeat="gallery in galleries" value="{{gallery}}">{{gallery}}</option>
					</select>
				</div>
			</div>
			<div class="table-row">
				<div class="table-cell">
					<label for="name">Limit (0 for unlimited)</label>
				</div>
				<div class="table-cell">
					<input type="number" min="0" ng-model="limit"/>
				</div>
			</div>
		</div>
		<input class="btn btn-primary" type="button" value="Load" ng-click="loadData()"
			ng-disabled="loading" />
	</form>
	
	<div ng-show="loading">Please wait one moment while the data is loading...</div>
	<table ng-cloak class="mainTable" ng-show="entries.length">
		<tr>
			<th>Gallery name</th>
			<th>Category name</th>
			<th>Path</th>
		</tr>
		<tr ng-repeat="entry in entries" >
			<td><a target="_blank" href="https://commons.wikimedia.org/wiki/{{entry.gallery | escape}}">{{entry.gallery}}</a></td>
			<td><a target="_blank" href="https://commons.wikimedia.org/wiki/{{entry.category | escape}}">{{entry.category}}</a></td>
			<td>
				<span ng-repeat="leaf in entry.tree">
					<span ng-show="!$first">=&gt;</span>
					<a target="_blank" href="https://commons.wikimedia.org/wiki/{{leaf | escape}}">{{leaf}}</a>
				</span>
			</td>
		</tr>
	</table>
<?php $http_io->transcludeScriptRemote('angular.js.url'); 
$http_io->transcludeScript("category-files-blame", "js");
?>
</body>
</html>