<?php 
$all_maps = array_keys(Latitude_Longitude_Svg::load());
sort($all_maps);
$cpd = new ProjectData("commons.wikimedia");

?><div class="sharedHeader">
	<div>
		<div class="toolTitle">
			Branchmapper
			<a href="#" id="click-help" class="tooltip-div" data-tooltip-class="tooltip-15"></a>
		</div>
	</div>
</div>
<div class="newuser-instructions">
	<h2>What is this tool?</h2>
	<ul>
		<li>This is a tool which takes a list of user-entered locations and maps them.</li>
		<li>You can see examples of its usage <a href="https://commons.wikimedia.org/wiki/Category:Branch_footprint_maps_of_the_United_States"
			target="_blank">here</a>.</li>
	</ul>
	
	<h2>What you will need to use this tool</h2>
	<ul>
		<li>High technical profiency (to be able to input data).</li>
		<li>Understanding of how to attribute <a 
			href="https://commons.wikimedia.org/wiki/Commons:Derivative_work"
			target="_blank">derivative works</a> (full details are on the second page).</li>
		<li>A good image editor to crop the SVG and/or convert it to PNG (I
			recommend <a href="http://www.gimp.org/" target="_blank">GIMP</a>).</li>
	</ul>
	
	<h2>Which maps are currently supported?</h2>
	<span>[<a href="#" id="all-map-links-toggle">+</a>]</span>
	<div class="all-map-links">
		<ul><?php 
			foreach ($all_maps as $map) {
				?><li><a href="<?= $cpd->getRawLink("File:$map.svg") ?>" target="_blank"><?= 
					str_replace("_", " ", $map) ?></a></li><?php
			}
		?></ul>
	</div> 
	
	<h2>Other notes</h2>
	<ul>
		<li>For questions or feature requests, please contact the <a 
			href="<?= $constants['branchmapper.report_bug_link'] ?>"
			target="_blank">bot maintainer</a>.</li>
	</ul> 
</div>
<span class="tooltip-text" data-for="click-help">Click for help</span>