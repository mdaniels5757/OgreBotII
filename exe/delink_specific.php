<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $argv, $logger, $validator, $wiki_interface;


/* errorcode variable is just to keep myself honest: assert I don't commit if calling function has outstanding errors */
function commit($page, $newtext, $english_name, $commons_name, $errorcode) {
	global $logger, $wiki_interface;
	if ($errorcode) {
		/* just to be sure */
		return $errorcode;
	}

	$english_name = preg_replace("/_/", " ", $english_name);
	if (strlen($english_name)+strlen($commons_name)>151) {
		if (strlen($english_name)*2>151) {
			/* too long edit summary */
			$english_name = "image or file ";
		} else {
			$english_name = "[[File:$english_name]] ";
		}

		if (strlen($commons_name)*2>151) {
			$commons_name = "";
		} else {
			$commons_name = "[[File:$commons_name]] ";
		}
	} else {
		$english_name = "[[File:$english_name]] ";
		$commons_name = "[[File:$commons_name]] ";
	}

	try {
		$summary = "replacing $english_name with commons equivalent $commons_name"
			."([[User talk:Magog the Ogre|report errors here]])";
		$wiki_interface->edit($page, $newtext, $summary, EDIT_MINOR|EDIT_NO_CREATE);
	} catch (Exception $e) {
		$logger->error($e);
	}

	return $err_indicator?$err_indicator:0;
}

/* function to replace the images on Wikipedia, but with a specialized regular expression for images which are hard to reach (e.g., ones in a template).
 If finding one and only one instance, it will verify that the image is no longer transcluded, and make the change. If finding more than one instance,
it will verify each change on the command line, and then verify the image is no longer transcluded.
$imagename string
$re string the regular expression for which to search.
$replacer, what to replace the regular expression with */
function replace_image_special_regex($imagename, $re, $replacer) {
	global $en, $co, $logger, $validator, $wiki_interface;

	$img = $wiki_interface->new_image($en, $imagename);
	$imgpg = &$img->get_page();
	$imgstr = $imgpg -> get_title(true); /* normalize title */
	$logger->info("\n********************************* $imgstr\n");
	$commons_name = get_listed_commons_image($wiki_interface->get_page_text($imgpg), $imgstr);
	$newimg = $wiki_interface->new_image($co, $commons_name);
	$commons_name = $newimg -> get_page() -> get_title(true); /* normalize title */
	$logger->info("=> $commons_name\n(\"$re\", \"$replacer\")\n");

	$str_old_image_underscore = substr(str_replace(" ", "_", $imgstr), 5);
	$str_new_image_underscore = substr(str_replace(" ", "_", $commons_name), 5);

	$transclusions=$wiki_interface->run_mediawiki_query(function() use ($img) {
		return $img->get_usage();
	}, $en);
	$count_transclusions_i = count($transclusions);
	foreach ($transclusions as $transclusion_i_no => $pagename)	{
		$logger->info(($transclusion_i_no+1)." of $count_transclusions_i - $pagename");
		$validator->assert(strlen($pagename)!=0);
		$newpage = $wiki_interface->new_page($en, $pagename, null, false, false);
		$validator->assert(strlen($newpage->get_title())!=0);

		$text = $wiki_interface->get_page_text($newpage);
		$matches;
		$count = preg_match_all($re, $text, $matches, PREG_OFFSET_CAPTURE|PREG_SET_ORDER);
		$newtext;
		if ($count==0) {
			$logger->info(" ...0 matches");
			continue;
		}
		if ($count==1) {
			$logger->info(" ...1 match ");
			$newtext=preg_replace($re, $replacer, $text, 1, $count);
			$validator->assert($count==1);
		} else {
			$logger->info(" ...$count matches ");
			$php_likes_cunt_rather_than_count = $count;
			for($i=0; $i<$count; $i++) {
				$startpos = $matches[$i][0][1]-30;
				if ($startpos<0) {
					$startpos=0;
				}
				$strlen = $matches[$i][0][1]+strlen($matches[$i][0][0])+30-$startpos;
				if ($startpos+$strlen>strlen($text)) {
					$strlen=strlen($text)-$startpos;
				}
				$string = substr($text, $startpos, $strlen);
				$string = str_replace("\n", "\\n", $string);

				stderr_print("\n    \"$string\" (y/n)?");
				$chr = strtolower(substr(stdin_get(2), 0, 1));
				if ($chr=='n')
				{
					stderr_print ("\n ...aborted\n");
					continue 2;
				} else if ($chr!='y') {
					stderr_print ("\n ...unrecognized character\n");
					$i--;
					continue;
				}

				/* else: it's 'y', so continue on */
				$newtext=preg_replace($re, $replacer, $text, -1);
			}
		}

		/* if we get this far, we're good to make the changes. now test it */
		$preview = $wiki_interface->api_query(
				$en,
				array(
						'action' => 'parse',
						'title' => $pagename,
						'text'=>$newtext,
						'prop'=>'images'
				),
				true
		);
		if (!isset($preview['parse']['images'])) {
			throw new Exception("Server returned error: $pagename: $newtext");
		}
		$prev_images = $preview['parse']['images'];

		if (in_array($str_new_image_underscore, $prev_images) &&
				!in_array($str_old_image_underscore, $prev_images)) {
			commit($newpage, $newtext, substr($imgstr, 5), substr($commons_name, 5), 0);
			$logger->info("done");
		}
		else {
			$logger->info("...change failed to find all instances and properly replace");
		}

	} /* end foreach */
}


$en = $wiki_interface->new_wiki( "MDanielsBot" );
$co = $wiki_interface->new_wiki( "MDanielsBotCommons" );
if (count($argv)!=4) {
	stderr_print("Usage: [program name] [file to delink] [regular expression for which to".
			" search] [string with which to replace it] $1 notation is allowed. Make sure".
			" to escape characters not allowed by your native environment (e.g., in Linux".
			", $1 -> \\$1).\n");
} else {
	replace_image_special_regex($argv[1], $argv[2], $argv[3]);
}
?>
