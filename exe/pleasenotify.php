<?php
echo "Step 0: Initializing...";
require_once __DIR__ . "/base/bootstrap.php";
global $wiki_interface;

/* grab as much information about files as we can */
debug_print(" done\nStep 1: Gathering list of talk pages...");
debug_print(".");
$co = $wiki_interface->new_wiki( "OgreBotCommons" );
$query_vars = array('action' => 'query', 'list' => 'usercontribs','ucuser' => 'Magog the Ogre', 'uclimit' => 5000, 'ucdir' => 'older', 'ucnamespace' => 7);
$result=$wiki_interface->api_query($co, $query_vars);
$query=$result['query']['usercontribs'];
$pagenames=array();

foreach ($query as $query_this)
 {$pagenames[]=$query_this['title'];}

debug_print("done\nStep 2: Gathering content of talk pages");
$talk_content = $wiki_interface->query_pages(
		$co, 
		$pagenames, 
		'revisions'
);

debug_print("done\nStep 3: Parsing and outputting");
$final_pages=array();
foreach ($talk_content as $title => $content)
{
  if(!array_key_exists('text', $content))
   {debug_print("Warning, ignoring $title due to error in Mediawiki queries.");}
  else if (stripos($content['text'], "== Free status ==\n")!==false)
   {$final_pages[] = substr($title, 10);}
}
sort($final_pages, SORT_STRING);
foreach($final_pages as $final_page)
 {echo "\n*{{lf|$final_page}}";}

?>
