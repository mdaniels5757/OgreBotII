<?php

/**
 * 
 * @param string $link
 * @param bool $error_badname
 * @throws IllegalWikiNameException
 */
function cleanup_wikilink($link, $error_badname = false) {
	global $MB_WS_RE_OPT, $constants;
	$decoded = ucfirst_utf8(
		preg_replace("/^$MB_WS_RE_OPT(.*?)$MB_WS_RE_OPT$/u", "$1", 
			preg_replace("/[_ ]+/", " ", rawurldecode($link))));
	
	/* filter blacklisted illegal characters */
	if ($error_badname && preg_match($constants["illegal_pagename_re"], $decoded)) {
		throw new IllegalWikiNameException("\$decoded: $decoded, has an illegal character");
	}
	
	/* filter out-of-range illegal characters */
	for($i = 0; $i < strlen($decoded); $i++) {
		$code = ord($decoded{$i});
		
		// no PHP bugs, or features which later mess up our scheme!
		assert(($code >= 0x01 && $code <= 0xFF) || $code === 0x00);
		
		if ($error_badname && $code < 0x20) {
			throw new IllegalWikiNameException(
				"\$decoded: illegal character U+00" . ($code > 0xF ? "" : "0") .
					 strtoupper(dechex($code)));
		}
	}
	/* leaving out empty titles right now, as we flatly just don't care (implement later as needed!) */
	return $decoded;
}

/**
 *
 * @param string $origtext        	
 * @param string $subwiki        	
 * @return string
 */
function wikivoyage_link_to_commons_link($origtext, $subwiki) {
	global $validator;
	
	$validator->validate_arg($subwiki, "string");
	
	if ($subwiki == 'wts') {
		$base_url = "http://wts.wikivoyage-old.org/wiki/";
		$voy_interwikis = "de|fr|it|nl|ru|sv";
	} else {
		$base_url = "http://www.wikivoyage-old.org/$subwiki/";
		$voy_interwikis = "fr|nl|ru|sv";
	}
	
	$origtext = preg_replace_callback(
		"/\[\[(?:\s*\:+)?(?!\s*(?:\s*\:+)?(?:file|image|commons|wmc|$voy_interwikis|en)\s*\:)([^\|]+?)\]\]/i", 
		function ($matches) use($base_url, $origtext) {
			$urlencode = wiki_url_encode_underbar($matches[1]);
			return "[$base_url$urlencode $matches[1]]";
		}, $origtext);
	$origtext = preg_replace_callback(
		"/\[\[(?:\s*\:+)?(?!\s*(?:\s*\:+)?(?:file|image|commons|wmc|$voy_interwikis|en)\s*\:)([^\|]+?)\s*\|\s*([^\[]+?)\]\]/i", 
		function ($matches) use($base_url, $origtext) {
			$urlencode = wiki_url_encode_underbar($matches[1]);
			return "[$base_url$urlencode $matches[2]]";
		}, $origtext);
	
	$origtext = preg_replace("/\[\[\s*(?:\:+\s*)?($voy_interwikis)\s*\:+\s*([^\|]+?)\]\]" . "/i", 
		"[[voy:$1:$2]]", $origtext);
	$origtext = preg_replace(
		"/\[\[\s*(?:\:+\s*)?($voy_interwikis)\s*\:+\s*([^\|]+?)\s*" . "\|([^\[]+?)\]\]/i", 
		"[[voy:$1:$2|$3]]", $origtext);
	$origtext = preg_replace("/\[\[\s*(?:\:+\s*)?en\s*\:+\s*([^\|]+?)\]\]/i", "[[voy:$1]]", 
		$origtext);
	$origtext = preg_replace("/\[\[\s*(?:\:+\s*)?en\s*\:+\s*([^\|]+?)\s*\|([^\[]+?)\]\]" . "/i", 
		"[[voy:$1|$2]]", $origtext);
	$origtext = preg_replace("/\[\[\s*(?:\:+\s*)?(?:commons|wmc)\s*\:+\s*([^\|]+?)\]\]/i", 
		"[[:$1]]", $origtext);
	$origtext = preg_replace(
		"/\[\[\s*(?:\:+\s*)?(?:commons|wmc)\s*\:+\s*([^\|]+?)\s*\|" . "([^\[]+?)\]\]/i", 
		"[[:$1|$2]]", $origtext);
	return $origtext;
}

/**
 * load list of projects from disk
 * 
 * @throws AssertionFailureException
 * @return string[]
 */
function get_all_project_names() {
	global $validator;
	$PROJECTS = [];
	
	$projects_handle = opendir(BASE_DIRECTORY . "/REL1_0/Configs");
	
	if (!$projects_handle) {
		throw new AssertionFailureException("Unable to open configurations directory.");
	}
	
	while (($projects_file = readdir($projects_handle)) !== false) {
		if (preg_match("/OgreBot\-([a-z\-]+)\.cfg/", $projects_file, $match)) {
			array_push($PROJECTS, "$match[1].wikipedia");
		} else if (preg_match("/OgreBot\-([a-z\-]+)\.(wik[a-z\-]+)\.cfg/", $projects_file, $match)) {
			array_push($PROJECTS, "$match[1].$match[2]");
		}
	}
	closedir($projects_handle);
	sort($PROJECTS, SORT_STRING);
	
	$validator->validate_arg_array($PROJECTS, "string", false, false, false);
	return $PROJECTS;
}

/**
 * Filter templates; removes all template instances from a wikipage.
 * 
 * @param string $pagetext
 *        	String of page in which to filter templates
 * @param string $template_text
 *        	Parameter in which the text of the templates is passed back
 *        	to the caller. Subtemplates are ignored and passed back as
 *        	{{subtemplate_text}}
 * @param int[] $template_text_indices
 *        	Parameter in which the index of the string removed.
 *        	Index is calculated forward, after removal. Thus "hello {{there}} {{world}}"
 *        	is calculated returned as [6, 7].
 * @return string wikitext without any templates
 */
function filter_templates($pagetext, &$template_text = NULL, &$template_text_indices = NULL) {
	global $validator;
	
	if (!is_array($template_text)) {
		$template_text = array();
	}
	if (!is_array($template_text_indices)) {
		$template_text_indices = array();
	}
	$validator->assert(count($template_text) == count($template_text_indices), 
		"Bad data passed to filter_templates(): count(\$template_text)" .
			 "!=count(\$template_text_indices)");
	
	while (($next_brackets = stripos($pagetext, "{{")) !== FALSE) {
		$num_brackets = 1;
		$end_brackets = $next_brackets + 2;
		while ($num_brackets > 0) {
			$tmp_next_brackets = stripos($pagetext, "{{", $end_brackets);
			$end_brackets = stripos($pagetext, "}}", $end_brackets);
			if ($end_brackets === false) {
				/* Unterminated parentheses */
				return $pagetext;
			}
			
			if ($tmp_next_brackets === false || $tmp_next_brackets > $end_brackets) {
				/* Jump out by a level */
				$end_brackets = $end_brackets + 2;
				$num_brackets--;
				continue;
			} else if ($tmp_next_brackets < $end_brackets) {
				/* Go deeper by a level */
				$end_brackets = $tmp_next_brackets + 2;
				$num_brackets++;
				continue;
			}
		}
		
		$count = count($template_text);
		$validator->assert($next_brackets + 4 <= $end_brackets, 
			"Internal coding error in " .
				 "filter_templates, report to Magog the Ogre along with inputted data");
		$template_text[$count] = substr($pagetext, $next_brackets + 2, 
			$end_brackets - $next_brackets - 4);
		$template_text_indices[$count] = $next_brackets;
		$pagetext = substr($pagetext, 0, $next_brackets) . substr($pagetext, $end_brackets);
	}
	return $pagetext;
}

/**
 *
 * @param string $pagetext        	
 * @param string $pagetitle        	
 * @param bool $errorflag        	
 * @return NULL|string The name of the link in string form; if unable to find or if it's an
 *         illegal name, it will return NULL. Any errors will be output in $errorflag.
 */
function get_listed_commons_image($pagetext, $pagetitle, &$errorflag = NULL) {
	global $logger, $MB_WS_RE_OPT, $validator;
	
	$commons_now_re = "/\{\{\s*([Dd]b\-commons|[Cc]ommons[Nn]ow|NC|[Dd]b\-(nowcommons|[Ff]8)|[Mm]oved[ _]to" .
		 "[ _]commons|[Nn]CT|[Nn]ct|[Nn]ow[ _]at[ _]commons|[Nn]ow[ _][Cc]ommons|[Nn]ow[Cc]" .
		 "ommons(?:this|\/Mängel)?|[Nn]owcommons2|Nach[ _]Commons[ _]verschieben[ _]" .
		 "\(bestätigt\))\s*(?:\|[\s\S]*?)?\}\}/um";
	$no_matches = preg_match_all($commons_now_re, $pagetext, $matches, PREG_SET_ORDER);
	$errorflag = 0;
	if ($no_matches == 0) {
		$errorflag |= COMMONS_LISTED_ZERO;
		return NULL;
	}
	if ($no_matches > 1) {
		$commons_listed_name = NULL;
		foreach ($matches as $instance) {
			$next_listed_name = get_listed_commons_image($instance[0], $pagetitle, $tmperrorflag);
			$errorflag |= ($tmperrorflag & ~COMMONS_LISTED_ZERO);
			if ($commons_listed_name === NULL) {
				$commons_listed_name = $next_listed_name;
			} else if ($next_listed_name != $commons_listed_name) {
				$errorflag |= COMMONS_LISTED_MULTIPLE;
				$logger->debug(
					"Warning: more than one {{nowcommons}} tag; with " .
						 "different images listed on commons: $pagetitle, " .
						 "'$next_listed_name' != '$commons_listed_name'.");
				return $commons_listed_name; /* return first */
			}
		}
		return $commons_listed_name;
	}
	
	$temp = new Template($matches[0][0], $matches[0][1]);
	$tempfieldvalue1 = $temp->first_field_value(["1", "filename"]);
	$commons_listed_name = ($tempfieldvalue1 == NULL) ? $pagetitle : "File:" .
		 basepagename_ns6(preg_replace("/^${MB_WS_RE_OPT}1\=/u", "", $tempfieldvalue1));
	
	$commons_listed_name = html_entity_decode($commons_listed_name, ENT_QUOTES, "UTF-8");
	
	/* assertions; often a PHP bug (regex that hates unicode) */
	$regexassert = "/^$MB_WS_RE_OPT([\s\S]+?)$MB_WS_RE_OPT$/u";
	$stripped = preg_replace($regexassert, "$1", $commons_listed_name);
	$validator->assert($commons_listed_name != "File:" && $commons_listed_name != "Datei:", 
		"$commons_listed_name=\"$commons_listed_name\"");
	$validator->assert($stripped !== NULL, 
		"PHP's unicode regex hates you: report this to them:" .
			 " preg_replace(\"$regexassert\" , \"$1\", \"$commons_listed_name\")===NULL)");
	$validator->assert($commons_listed_name == $stripped, 
		"str{" . strlen($commons_listed_name) . "}" . " \"$commons_listed_name\"\n!==\nstr{" .
			 strlen($stripped) . "} \"$stripped\"");
	
	if (preg_match("/\[\]{}\#/", $commons_listed_name)) {
		$errorflag |= COMMONS_LISTED_ILLEGAL_CHAR;
		$logger->debug("Illegal character in filename; one of the following: [[{}#");
		return NULL;
	}
	assert(stripos("|", $commons_listed_name) == 0);
	
	return $commons_listed_name;
}