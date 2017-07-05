<?php
require_once __DIR__ . "/../base/bootstrap.php";

$page = ARTIFACTS_DIRECTORY . "/filestuff.txt";
$text = file_get_contents_ensure($page);

$project_data = new Project_Data("en.wikipedia", null, false);
$project_data->setDefaultHostWiki("commons.wikimedia");

$page_parser = new Page_Parser($text);
$page_parser->modify_links_for_project($project_data);
$page_parser->unparse();
$text = $page_parser->get_text();

file_put_contents_ensure($page, $text);

echo $text;
