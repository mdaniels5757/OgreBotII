<?php
include_once(__DIR__ . "/base/bootstrap.php");
global $validator, $wiki_interface;

function relink($text)
{
	global $MB_WS_RE_OPT, $validator;
	$wl_re = "/\[\[$MB_WS_RE_OPT:?$MB_WS_RE_OPT(.+?)$MB_WS_RE_OPT\]\]/u";
	$pointer_link_re = "/(.+?)$MB_WS_RE_OPT\|$MB_WS_RE_OPT(.+?)/u";
	$file_re = "/\[\[${MB_WS_RE_OPT}file|image|media$MB_WS_RE_OPT\:/ui";

	/* reg ex can't handle sublinks (e.g., [[File:hello.jpg|this is a [[test]] line]]), and they won't show properly anyway; filter them out */
	$end_brackets; $file_sublinks=array();
	while (preg_match($file_re, $text, $file_sublinks_match, PREG_OFFSET_CAPTURE))
	{
		$num_brackets=1;
		$next_brackets = $file_sublinks_match[0][1];
		$end_brackets = $next_brackets + 7;
		while($num_brackets>0)
		{
			$tmp_next_brackets = stripos($text, "[[", $end_brackets);
			$end_brackets = stripos($text, "]]", $end_brackets);
			/* Unterminated bracket */
			if ($end_brackets===false) {
				break 2;
			}

			/* Jump out by a level */
			if ($tmp_next_brackets===false || $tmp_next_brackets>$end_brackets)
			{
				$end_brackets = $end_brackets + 2;
				$num_brackets--;
				continue;
			}

			/* Go deeper by a level */
			else if ($tmp_next_brackets<$end_brackets)
			{
				$end_brackets = $tmp_next_brackets + 2;
				$num_brackets++;
				continue;
			}
			$validator->assert(false, "unable to parse links; function: relink(\$text) wherein \$text == $text"); //how the hell did we get here?
		}
		$filetext = substr($text, $file_sublinks_match[0][1], $end_brackets-2 - $file_sublinks_match[0][1]);
		$text = substr($text, 0, $file_sublinks_match[0][1])."%MTOFILELINK%".count($file_sublinks)."%".substr($text, $end_brackets);
		$file_sublinks[]= substr($filetext, 0, stripos($filetext, "|")===FALSE?strlen($filetext):stripos($filetext, "|"));
	}
	/* parse wikilinks */
	$matches;
	preg_match_all($wl_re, $text, $matches, PREG_OFFSET_CAPTURE);
	for ($i=count($matches[0])-1; $i>=0; $i--)
	{
		$linktext = $matches[1][$i][0];

		//relink
		if (preg_match($pointer_link_re, $linktext)) {
			$linktext = preg_replace($pointer_link_re, ":en:$1|$2", $linktext);
		}
		else {$linktext = ":en:$linktext|$linktext";
		}

		//save results to string
		$text = substr($text, 0, $matches[0][$i][1])."[[$linktext]]".substr($text, $matches[0][$i][1] + strlen($matches[0][$i][0]));
	}

	/* rebuild file links */
	foreach ($file_sublinks as $i => $val) {
		$text = str_replace("%MTOFILELINK%$i%", "[[:en:".mb_trim($val)."]]", $text);
	}
	return $text;
}


$en = $wiki_interface->new_wiki( "OgreBot" );

/* grab as much information about files as we can */
echo "Step 1a: Gathering list of deleted pages...";
$deletion_query=array();
$nxt_deletion_query;
do
{
	echo ".";
	$query_vars = array('action' => 'query', 'list' => 'logevents','letype' => 'delete', 'lelimit' => 5000, 'leuser' => 'Magog_the_Ogre');
	if (isset($nxt_deletion_query))
	{
		$nxt = $nxt_deletion_query['query-continue']['logevents']['lestart'];
		echo "\n$nxt.";
		$query_vars['lestart']=$nxt;
	}
	$nxt_deletion_query=apiquery($en, $query_vars);
	$deletion_query=array_merge($deletion_query, $nxt_deletion_query['query']['logevents']);
}while(array_key_exists('query-continue', $nxt_deletion_query));

echo "done\nStep 1b: Parsing information";
$file_talk_names=array();
foreach ($deletion_query as $event)
{
	if($event['ns']!==6) {
		continue;
	} /* not a file */
	if($event['action']!=='delete') {
		continue;
	} /* restoration, not deletion */
	$file_talk_names[]="File talk:".substr($event['title'], 5);
}

echo "done\nStep 2a: Gathering list of talk pages, and their content";
$talk_content = $wiki_interface->query_pages(
		$en,
		$file_talk_names,
		'revisions'
);
echo "done\nStep 2b: Parsing information";
$file_talk_content=array();
foreach ($talk_content as $title => $page)
{
	if(array_key_exists('missing', $page)) {
		continue;
	} /* file talk doesn't exist */
	$text = sanitize($page['text']);

	$template_indices=array();
	$template_texts=array();
	$file_talk_content[$title]['commons'] = relink(filter_templates($text, $template_texts, $template_indices));

	//highlight templates
	for ($i=count($template_indices)-1; $i>=0; $i--)
	{
		$text = mb_substr($text, 0, $template_indices[$i])."<font style=\"background-color:green\">".mb_substr($text, $template_indices[$i], mb_strlen($template_texts[$i])+4).
		"</font>".mb_substr($text, $template_indices[$i]+mb_strlen($template_texts[$i])+4);
	}
	$file_talk_content[$title]['text'] = $text;
};

echo "done\nStep 3: Outputting information";
$i=0;
$total = count($file_talk_content);

$f = fopen("filetalk.htm", 'w');
function write($text) {
	global $f; fwrite($f, $text);
}
write("<HTML>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n<BODY><TABLE border=\"3\" width=\"100%\" style=\"table-layout:fixed\">\n");

foreach ($file_talk_content as $title => $content)
{
	echo "$title: ".(++$i)." of $total\n";
	/**********************************
	 * We have our data: start output *
	**********************************/
	write("En:<TR>\n<TD><small>".wikilink($title, "en.wikipedia", true, true)."</small></TD>\n"); /* en name */
	write("<br/>Commons:<TD><small>".wikilink($title, "commons.wikipedia", true, true)."</small></TD>\n"); /* commons name */

	/* en text */
	write("<TD style=\"valign:top; align:center;\ word-wrap:break-word\" colspan=\"3\">");
	write("<TABLE><TR style=\"height:20px\"><TD><code>$content[text]</code></TD></TR></TABLE>");
	write("</TD>\n");

	/* commons text: if it fits the upload bot (magnus) format, we simply will put a positive checkmark */
	write("<TD style=\"valign:top; align:center;\ word-wrap:break-word\" colspan=\"3\">");
	write("<TABLE><TR style=\"height:20px\"><TD><code>$content[commons]</code></TD></TR></TABLE>");
	write("</TD></TR>\n");
}
debug_print("done\n");

write("\n</TABLE>\n<form name=\"mfw\"><TEXTAREA cols=220 rows=8 name=\"delink\" READONLY>php -f delink.php </TEXTAREA></form><br>\n</BODY>\n</HTML>");
fclose($f);
?>
