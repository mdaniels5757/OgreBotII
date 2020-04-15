<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $argv, $logger, $validator, $wiki_interface;

$ignored_cats = array("Category:Wikipedia files with a different name on Wikimedia Commons as of unknown date",
		"Category:Wikipedia files with the same name on Wikimedia Commons as of unknown date",
		"Category:All Wikipedia files with a different name on Wikimedia Commons",
		"Category:All Wikipedia files with the same name on Wikimedia Commons");
$emptycats_str = " <nowiki>[[WP:CSD#G6|G6]]: this temporary maintenance category is ready for deletion</nowiki>\n";
$today = mktime(0, 0, 0, date("n"), date("j"), date("Y"), -1);


$en = $wiki_interface->new_wiki( "MDanielsBot" );

$dcats = $wiki_interface->new_category_traverse($en, "Category:Wikipedia files with a different name on Wikimedia Commons", false, 14, NULL, $ignored_cats);
$scats = $wiki_interface->new_category_traverse($en, "Category:Wikipedia files with the same name on Wikimedia Commons", false, 14, NULL, $ignored_cats);

$cats = array();
foreach ($dcats as $i => $dcat) {
	if (isset($argv[1]) && $i>intval($argv[1])) {
		break;
	}
	$cats[]=$dcat;
}
foreach ($scats as $i => $scat) {
	if (isset($argv[2]) && $i>intval($argv[2])) {
		break;
	}
	$cats[]=$scat;
}

foreach ($cats as $i => $cat) {
	$validator->assert($cat['ns']==14);
	foreach ($ignored_cats as $_igncat) {
		if ($cat['title']==$_igncat) {
			continue 2;
		}
	}
	$cat_name = $cat['title'];

	/* only print out empty categories dated before today */
	preg_match("/(\d{1,2} [A-Za-z]+ \d{4})$/", $cat_name, $matches) ||
		$validator->assert(false, "Unrecognized category name structure; $cat_name");
	$catdate = strtotime($matches[1]);
	if ((!array_key_exists('categoryinfo', $cat) || !array_key_exists(
			'size', $cat['categoryinfo']) || $cat['categoryinfo']['size']==0) &&
			 $today-$catdate>0) {
		$emptycats_str.="*[[:$cat_name]]\n";
	}
}

$logger->debug("Updating empty cat list on wiki...");
$pg = $wiki_interface->new_page($en, "User:MDanielsBot/emptycats");
$wiki_interface->edit_throw_exceptions(
		$pg,
		$emptycats_str, "
		BOT: updating list of empty dated nowcommons categories ".
		"([[User talk:Mdaniels5757|report errors here]])"
);
