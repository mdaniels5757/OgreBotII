<?php
abstract class Relink {
	/**
	 *
	 * @var Wiki
	 */
	private $local;
	
	/**
	 *
	 * @var Wiki
	 */
	private $shared;
	
	/**
	 *
	 * @var array()
	 */
	private $properties;
	
	/**
	 * 
	 * @var Relink_Text_Replacer[]
	 */
	private $relink_text_replacers;
	
	/**
	 * 
	 * @var bool
	 */
	private $test_conflicts = true;
	
	/**
	 *
	 * @param Wiki $local        	
	 * @param Wiki $shared        	
	 * @return void
	 */
	public function __construct(Wiki &$local, Wiki &$shared) {
		$this->local = $local;
		$this->shared = $shared;
		$this->relink_text_replacers = [new Kit_Standard_Text_Replacer(), 
			new Kit_Football_Kit_Pattern_Template_Replacer(), new Map_Replacer()];
		load_property_file_into_variable($this->properties, "relink");
	}
	
	/**
	 *
	 * @return void
	 */
	public function run() {
		global $logger, $constants, $wiki_interface;
		
		$all_same = $this->normalize($this->get_files_same_name());
		$all_different = $this->get_files_different_name();
		
		$all_local_pages = array_unique(
			array_merge(array_values($all_same), array_keys($all_different)));
		
		$all_shared_pages = array_unique(
			array_merge(array_values($all_same), array_values($all_different)));
		
		$all_local_redirects = $wiki_interface->get_all_redirects($this->local, $all_local_pages);
		
		$all_local_info = $wiki_interface->new_query_pages($this->local, $all_local_pages, 
			'imageinfo|revisions|templates');
		
		$all_shared_info = $wiki_interface->new_query_pages($this->shared, $all_shared_pages, 
			'imageinfo|revisions|templates');
		
		if ($all_same) {
			$logger->info("Delinking same name... ");
			$i = 0;
			$count = count($all_same);
			foreach ($all_same as $page) {
				$i++;
				$logger->info("======== $page ($i of $count)");
				
				try {
					$relink_success = $this->relink_redirects(@$all_local_redirects[$page], $page, 
						$page);
					
					if ($relink_success) {
						$this->post_delink($page, true);
					}
				} catch (Exception $e) {
					$logger->error($e);
					continue;
				}
			}
		} else {
			$logger->info("No same name files to delink.");
		}
		
		if ($all_different) {
			$logger->info("Delinking different name... ");
			
			$logger->info("Checking Commons shadow");
			
			$shadows = $wiki_interface->get_shadows($this->local, $all_different);
			
			list($links_by_page, $all_links) = $this->get_and_prune_usage(
				array_keys($all_different));
			
			$i = 0;
			$count = count($all_different);
			foreach ($all_different as $from => $to) {
				$i++;
				$logger->info("======== $from => $to ($i of $count)");
				
				try {
					$local_info = array_key_or_exception($all_local_info, $from);
					$shared_info = array_key_or_exception($all_shared_info, $to);
					
					$file_scope_failure = null;
					if ($this->has_keep_local($local_info)) {
						$file_scope_failure = "keep_local";
					} else if (@$shadows[$to]) {
						$file_scope_failure = "shadows";
					} else if ($this->is_verify_mime() &&
						 $this->is_different_mime($local_info, $shared_info)) {
						$file_scope_failure = "mime";
					} else {
						$redirects = array_key_or_null($all_local_redirects, $from);
						
						$success = $this->relink_redirects($redirects, $from, $to);
						
						$links = array_key_or_exception($links_by_page, $from);
						
						$success &= $this->relink_all($from, $to, $links);
						
						if ($success) {
							$this->post_delink($from, false);
						}
					}
					
					if ($file_scope_failure) {
						$failure = new Relink_Failure();
						$failure->status_code = $file_scope_failure;
						$failure->file_name = $from;
						$failure->dest_name = $to;
						$this->handle_failure($failure);
					}
				} catch (Exception $e) {
					$logger->error($e);
					continue;
				}
			}
		} else {
			$logger->info("No different name files to delink.");
		}
	}
	
	/**
	 *
	 * @param string[] $links        	
	 */
	private function prune_usage($from, $links) {
		global $constants, $logger;
		
		$all_prefixes_to_ignore = array_key_or_empty($constants, 
			"image.replacement.global.delink.optoutprefix");
		$new_links = array();
		
		foreach ($links as $link) {
			$skip = false;
			foreach ($all_prefixes_to_ignore as $prefix) {
				if (str_starts_with($link, $prefix)) {
					$logger->trace("Skipping $link (ignored prefix).");
					$skip = true;
					break;
				} else if ($from === $link) {
					$logger->warn("Skipping $link not updating the same page.");
					$skip = true;
					break;
				}
			}
			
			if (!$skip) {
				$new_links[] = $link;
			}
		}
		
		return $new_links;
	}
	
	/**
	 *
	 * @param string $from_name        	
	 * @param string $to_name        	
	 * @param string $link        	
	 * @param string $new_text        	
	 */
	private function edit_link($from_name, $to_name, $link, $new_text) {
		global $wiki_interface;
		
		$link_properties = array("local" => $from_name, "shared" => $to_name);
		
		$summary = replace_named_variables($this->properties['relink_summary_long'], 
			$link_properties);
		if (mb_strlen($summary) > 255) {
			$summary = replace_named_variables($this->properties['relink_summary_short_1'], 
				$link_properties);
			if (mb_strlen($summary) > 255) {
				$summary = replace_named_variables($this->properties['relink_summary_short_2'], 
					$link_properties);
				if (mb_strlen($summary) > 255) {
					$summary = replace_named_variables($this->properties['relink_summary_short'], 
						$link_properties);
				}
			}
		}
		$page = $wiki_interface->new_page($this->local, $link);
		try {
			$wiki_interface->edit($page, $new_text, $summary, EDIT_MINOR);
		} catch (PermissionsError $e) {
			throw new CantDelinkException("protected");
		}
	}
	/**
	 *
	 * @param string $file_name        	
	 * @return array -
	 *         index 0 : links by page (string[][])
	 *         index 1 : links for all pages (unique)
	 */
	private function get_and_prune_usage($file_name) {
		global $logger, $wiki_interface;
		
		static $props = array('iufilterredir' => 'nonredirects');
		
		$i = 0;
		$count = count($file_name);
		$all_links = array();
		$links_by_page = array();
		foreach ($file_name as $name) {
			$i++;
			$logger->info("Getting links for $name ($i of $count)");
			$links = $wiki_interface->get_usage($this->local, $name, USAGE_NON_REDIRECTS_ONLY);
			$links = $this->prune_usage($name, $links);
			$links_by_page[$name] = $links;
			$all_links = array_merge($all_links, $links);
		}
		
		$all_links = array_unique($all_links);
		
		return array($links_by_page, $all_links);
	}
	
	/**
	 *
	 * @param array $templates        	
	 * @return bool
	 */
	private function has_keep_local($imageinfo) {
		global $constants;
		static $keep_local_templates = null;
		
		if (!$keep_local_templates) {
			$keep_local_templates = array_key_or_empty($constants, 
				"image.replacement.global.template.ignore");
			foreach ($keep_local_templates as $index => $template) {
				$keep_local_templates[$index] = "Template:$template";
			}
		}
		
		$local_templates = array_key_or_empty($imageinfo, 'templates');
		foreach ($local_templates as $template_array) {
			$template = array_key_or_exception($template_array, "title");
			if (in_array($template, $keep_local_templates)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 *
	 * @param array $local        	
	 * @param array $shared        	
	 * @return bool
	 */
	private function is_different_mime($local, $shared) {
		$local_mime = array_key_or_exception($local, "imageinfo", 0, "mime");
		$shared_mime = array_key_or_exception($shared, "imageinfo", 0, "mime");
		return $local_mime !== $shared_mime;
	}
	
	/**
	 *
	 * @param string[] $file_names        	
	 * @return string[]
	 */
	private function normalize($file_names) {
		global $wiki_interface;
		
		$normalized = $wiki_interface->query_generic($this->shared, "titles", $file_names, array());
		return array_keys($normalized);
	}
	
	/**
	 *
	 * @param string[] $redirects        	
	 * @param array $local_info        	
	 * @param array $shared_info        	
	 * @return boolean if the edits were made without error
	 */
	private function relink_redirects($redirects, $from, $to) {
		global $logger, $wiki_interface;
		
		$global_success = true;
		if ($redirects) {
			list($redirect_links) = $this->get_and_prune_usage($redirects);
			
			$count = count($redirects);
			$i = 0;
			foreach ($redirects as $redirect) {
				$i++;
				$logger->info("Redirect $redirect: $i of $count.");
				if ($redirect !== $to) {
					$usage_error = !$this->relink_all($redirect, $to, 
						array_key_or_exception($redirect_links, $redirect), 0, true);
				} else {
					$usage_error = false;
				}
				
				if (!$usage_error) {
					$page = $wiki_interface->new_page($this->local, $redirect, null, false);
					$summary = array_key_or_exception($this->properties, "redirect_delete");
					try {
						$wiki_interface->edit_throw_exceptions($page, "{{Db-redirnone}}\n", 
							$summary, EDIT_PREPEND);
					} catch (Exception $e) {
						$global_success = false;
						$failure = new Relink_Failure();
						$failure->file_name = $from;
						$failure->dest_name = $to;
						$failure->linked_page = $redirect;
						if ($e instanceof PermissionsError) {
							$failure->status_code = "protected";
						} else {
							$failure->status_code = "unknown";
						}
						$this->handle_failure($failure);
					}
				} else {
					$global_success = false;
				}
			}
		} else {
			$logger->debug("No redirects found for this file.");
		}
		
		return $global_success;
	}
	
	/**
	 *
	 * @param string $from        	
	 * @param string $to        	
	 * @param string[] $links        	
	 * @param int $previous_count
	 *        	- only used internally by this function - do not set.
	 * @param bool $redirect
	 * @return bool if all links were successfully updated
	 */
	private function relink_all($from, $to, $links, $previous_count = null, $redirect = false) {
		global $logger, $validator, $wiki_interface;
		
		$validator->validate_arg($from, "string");
		$validator->validate_arg($to, "string");
		$validator->validate_arg_array($links, "string");
		$validator->validate_arg($previous_count, "int", true);
		
		if ($previous_count !== null) {
			if ($previous_count === count($links)) {
				// it failed... break
				foreach ($links as $page) {
					$failure = new Relink_Failure();
					$failure->status_code = "unknown";
					$failure->file_name = $from;
					$failure->dest_name = $to;
					$failure->linked_page = $page;
					$this->handle_failure($failure);
				}
				return false;
			}
			// purge each page
			$from_underscore = $this->remove_namespace(str_replace(" ", "_", $from));
			foreach ($links as $index => $page) {
				$logger->info("Relinking $page");
				try {
					$newpage = $wiki_interface->new_page($this->local, $page);
					$content = $wiki_interface->get_page_text($newpage);
					$logger->info("Purging cache, retesting...");
					$wiki_interface->edit($newpage, $content, "purging page cache", EDIT_MINOR);
					
					list($included) = $this->test_image_inclusion($page, $content, 
						array($from_underscore));
					if (!$included) {
						unset($links[$index]);
					}
				} catch (Exception $e) {
					$logger->error($e);
				}
			}
			$previous_count = count($links);
		} else {
			$previous_count = 0;
		}
		
		$all_content = $wiki_interface->new_query_pages($this->local, $links, "revisions");
		
		$global_success = true;
		$retries = array();
		$i = 0;
		$count = count($all_content);
		foreach ($all_content as $page => $content) {
			$i++;
			$logger->info("Relinking $page... ($i of $count)");
			$text = array_key_or_exception($content, "revisions", 0, "*");
			
			try {
				$new_text = $this->relink_text($page, $text, $from, $to, !$redirect);
				
				// perform the edit
				$this->edit_link($from, $to, $page, $new_text);
			} catch (CantDelinkException $e) {
				$status = $e->get_status_code();
				if ($status === "unknown" || $status === "not_linked") {
					$logger->debug("Status: $status; retrying...");
					$retries[] = $page;
				} else {
					$logger->error($e);
					$global_success = false;
					$failure = new Relink_Failure();
					$failure->status_code = $status;
					$failure->file_name = $from;
					$failure->dest_name = $to;
					$failure->linked_page = $page;
					$this->handle_failure($failure);
				}
			}
		}
		
		if ($retries) {
			$global_success = $global_success &
				 $this->relink_all($from, $to, $retries, $previous_count, $redirect);
		}
		
		return $global_success;
	}
	
	/**
	 *
	 * @param string $page_name        	
	 * @param string $page_text        	
	 * @param string $str_old_image        	
	 * @param string $str_new_image
	 * @param bool $test_conflicts
	 * @throws CantDelinkException
	 * @throws Exception
	 * @return string
	 */
	protected function relink_text($page_name, $page_text, $str_old_image, $str_new_image,
			$test_conflicts) {
		global $MB_WS_RE, $MB_WS_RE, $MB_WS_RE_OPT, $logger, $validator, $wiki_interface;
		
		/* pre-function assertions */
		$validator->validate_arg($page_name, "string");
		$validator->validate_arg($page_text, "string");
		$validator->validate_arg($str_old_image, "string");
		$validator->validate_arg($str_new_image, "string");
		
		$str_old_image = $this->remove_namespace($str_old_image);
		$str_new_image = $this->remove_namespace($str_new_image);
		
		$relink_text = new Relink_Text($this->local, $str_old_image, $str_new_image, $page_text, 
			$page_name, $this->test_conflicts && $test_conflicts);
		
		$pixel_change_tolerance = .9;
		
		$str_old_image_underscore = str_replace(" ", "_", $str_old_image);
		$str_new_image_underscore = str_replace(" ", "_", $str_new_image);
		
		/* generate search regex; also allow for encoded characters */
		$re = "";
		$j = 0;
		while ($j < strlen($str_old_image)) {
			$chr = mb_substr(substr($str_old_image, $j, 4), 0, 1);
			$re .= "(?:";
			/* first character in name is optionally uppercase or lowercase */
			if ($j == 0 && preg_match("/[A-Z]/i", $chr)) {
				$validator->assert(strlen($chr) == 1);
				$upper = strtoupper($chr);
				$_hexval = "%" . dechex(ord($upper));
				if (preg_match("/[a-f]/", $_hexval)) {
					$_hexval = "(?i:$_hexval)";
				}
				$re .= "$upper|$_hexval|";
				$chr = strtolower($chr);
			} 			/* spaces can be replaced with underscores */
			else if ($chr == " ") {
				$re .= "_|%5[Ff]|";
			}
			
			/* account for unicode chars */
			$hexval = "";
			$validator->assert(strlen($chr) > 0);
			for($k = 0; $k < strlen($chr); $k++) {
				$nxtchr = $str_old_image[$j++];
				$ord = ord($nxtchr);
				$dechex = $ord > 0xf ? dechex($ord) : "0" . dechex($ord);
				$hexval .= "%$dechex";
			}
			
			$validator->assert(preg_match("/^(?:%[a-f\d]{2}){1,4}$/", $hexval), 
				"\$hexval ($hexval) is an illegal value");
			$hexval_caseinsensitive = "";
			for($k = 0; $k < strlen($hexval); $k++) {
				$nxt_chr = $hexval[$k];
				if (preg_match("/^[a-f]$/", $nxt_chr)) {
					$hexval_caseinsensitive .= "[" . strtoupper($nxt_chr) . $nxt_chr . "]";
				} else {
					$validator->assert(preg_match("/^[\d%]$/", $nxt_chr), 
						"$nxt_chr != a digit or parenthesis mark");
					$hexval_caseinsensitive .= $nxt_chr;
				}
			}
			
			$quote = preg_quote($chr);
			$re .= "$quote|$hexval_caseinsensitive)";
			if ($chr == " ") {
				/*
				 * multiple spaces become one space; at least on English Wikipedia, which is
				 * what I'm using
				 */
				$re .= "+";
			}
		}
		
		$first_re = "/\[\[$MB_WS_RE_OPT(?i:file|image)$MB_WS_RE_OPT:$MB_WS_RE_OPT$re" .
			 "$MB_WS_RE_OPT(\|.*?)?\]\]/ums";
		
		/* prepare to check for pixelage issues if necessary */
		$additional_pixel_change_text = array();
		$require_replace = false;
		// if ($pixel_change_tolerance!=NULL) {
		// $dh = $old_image->get_height();
		// $dw = $old_image->get_width ();
		// $ih = $new_image->get_height();
		// $iw = $new_image->get_width ();
		
		// /* incompatible resolutions */
		// if (!$ignore_nonfatal && ($dh * $pixel_change_tolerance > $ih ||
		// $dw * $pixel_change_tolerance > $iw)) {
		// $errors|=REPLACE_IMAGE_LOWER_RESOLUTION;
		// }
		
		// if ($dh==$ih && $dw==$iw) {
		// /* identical height/width: can completely ignore replacing pixelage*/
		// $pixel_change_tolerance=NULL;
		// } else if (!$ignore_nonfatal && ($dh/$ih*$pixel_change_tolerance > $dw/$iw
		// /* incompatible dimensions */
		// || $dh/$ih/$pixel_change_tolerance < $dw/$iw)) {
		// $errors |= REPLACE_IMAGE_INCOMPATIBLE_RESOLUTION;
		// } else {
		// $require_replace = $dh*$pixel_change_tolerance>$ih ||
		// $dh/$pixel_change_tolerance<$ih ||
		// $dw*$pixel_change_tolerance>$iw || $dw/$pixel_change_tolerance<$iw;
		
		// preg_match_all($first_re, $page_text, $matches, PREG_SET_ORDER);
		// foreach ($matches as $i => $instance) {
		// $height=NULL; $width=NULL;
		// $comment = $instance[1];
		
		// if (preg_match("/\|".$MB_WS_RE_OPT."frame$MB_WS_RE_OPT(?:\||$)/us", $comment,
		// $match)) {
		// /* frames */
		// $errors|=REPLACE_IMAGE_FRAME;
		// } else if (preg_match("/\|$MB_WS_RE_OPT(\d*$MB_WS_RE_OPT(?:x$MB_WS_RE_OPT".
		// "\d+)?".$MB_WS_RE_OPT."px|thumb(?:nail)?|frameless)$MB_WS_RE_OPT".
		// "(?:\||$)/us", $comment, $match)) {
		// /* pixelage is specified exactly, so the mediawiki software will
		// * handle it */
		// $additional_pixel_change_text[$i]="";
		// } else {
		// /* pixelage isn't handled on old image, so we must handle it */
		// $additional_pixel_change_text[$i] = "|$dw"."px";
		// }
		// }
		// }
		// }
				
		if ($relink_text->test_pre() !== Relink_Text::SUCCESS) {
			throw new CantDelinkException("not_linked");
		}
		

		foreach ($this->relink_text_replacers as $replacer) {
			$new_text = $replacer->replace($relink_text);
			if ($new_text !== null && $relink_text->test_post($new_text) === Relink_Text::SUCCESS) {
				return $new_text;
			}
		}
		
		/*
		 * Safest: replacing the image with Image/File/Media in front, and brackets.
		 * If pixelage change is specified, do it here
		 */
		$count = 0;
		do {
			$next_pixel_text = (count($additional_pixel_change_text) <= $count) ? "" : ($additional_pixel_change_text[$count]);
			$page_text_backup = $page_text;
			$page_text = preg_replace($first_re, "[[File:$str_new_image$next_pixel_text$1]]", 
				$page_text, 1, $tmpcount);
			
			/* PHP regex bug that hates unicode */
			$validator->assert(strlen($page_text) != 0, 
				"PHP regex bug: offending regular expession; " .
					 "preg_replace(\"$first_re\", \"[[File:$str_new_image$next_pixel_text" .
					 "$1]]\")");
			$count += $tmpcount;
		} while ($tmpcount);
		
		$page_text = preg_replace(
			"/\[\[$MB_WS_RE_OPT(?i:media)$MB_WS_RE_OPT\:" .
				 "$MB_WS_RE$re$MB_WS_RE_OPT(\|.*?)?\]\]/ums", "[[Media:$str_new_image$1]]", 
				$page_text, -1, $count2);
		/* PHP regex bug that hates unicode */
		$validator->assert(strlen($page_text) != 0, "either PHP regex bug, or your regex sucks");
		$count += $count2;
		if ($count > 0) {
			if ($relink_text->test_post($page_text) === Relink_Text::SUCCESS) {
				return $page_text;
			}
		}
		
		/*
		 * if we've gotten this far, and we must worry about a change in pixelage, then it's
		 * unclear how to proceed. The only way to proceed is to check if a transcluded
		 * template is automatically setting the pixelage, or if it's in a gallery (also
		 * sets pixelage). If neither is true, then it will be too difficult to change; it
		 * will have to be done by hand
		 */
		if ($require_replace) {
			/* test for pixelage argument first */
			$validator->assert(strlen($page_text) != 0, "unknown error");
			$expand_array = $wiki_interface->api_query($this->local, 
				array('action' => 'expandtemplates', 'text' => $page_text, 'title' => $page_name), 
				true);
			$expanded = $expand_array["expandtemplates"]["*"];
			
			$validator->assert($expanded !== NULL && strlen($expanded) != 0, "unknown error");
			preg_match_all($first_re, $expanded, $matches, PREG_SET_ORDER);
			$validator->assert(strlen($expanded) != 0, "PHP regex failure");
			foreach ($matches as $i => $instance) {
				$height = NULL;
				$width = NULL;
				$comment = $instance[1];
				
				/* frames */
				if (preg_match("/\|" . $MB_WS_RE_OPT . "frame$MB_WS_RE_OPT(?:\||$)/us", $comment, 
					$match)) {
					throw new CantDelinkException("pixelage");
				} 				

				/*
				 * pixelage is specified exactly, so the mediawiki software will
				 * pixel changes
				 */
				else if (preg_match(
					"/\|$MB_WS_RE_OPT(\d*$MB_WS_RE_OPT(?:x$MB_WS_RE_OPT" . "\d+)?" . $MB_WS_RE_OPT .
						 "px|thumb(?:nail)?|frameless)$MB_WS_RE_OPT" . "(?:\||$)/us", $comment, 
						$match)) {
					$additional_pixel_change_text[$i] = "";
					$expanded = preg_replace($first_re, "<-- image removed -->", $expanded, 1);
					
					/* unpredictable PHP regex bug */
					$validator->assert(strlen($expanded) != 0, 
						"either PHP regex bug, or your regex sucks");
				} 				

				/* pixelage isn't handled on old image. Die. */
				else {
					throw new CantDelinkException("pixelage");
				}
			}
			
			/*
			 * test for image inside galleries; these have automatic pixelage.
			 * The parsing isn't perfect, but it's good enough for our purposes.
			 */
			$validator->assert(strlen($expanded) != 0, "unknown error");
			
			// can't do WS_RE_OPT for ws, as PHP is buggy
			$expanded = preg_replace("/<gallery.*?>.+?<\/gallery$MB_WS_RE_OPT>/ums", 
				"<!-- gallery removed -->", $expanded);
			// unpredictable PHP regex bug
			$validator->assert(strlen($expanded) != 0, "either PHP regex bug, or your regex sucks");
			
			/*
			 * we've gotten rid of galleries and images with pixelage syntax, and the image
			 * is still on there. Meaning its pixelage isn't specified and has to be changed
			 * from somewhere deep inside a template. Die
			 */
			if ($relink_text->test_post($page_text) === Relink_Text::OLD_NOT_REMOVED) {
				throw new CantDelinkException("pixelage");
			}
			
			/*
			 * image has pixelage arguments or is in gallery; no need to worry about size
			 * change; proceed as normal below.
			 */
		}
		
		/*
		 * a little less safe: replacing the image with Image/File/Media in front, but no
		 * brackets
		 */
		$page_text = preg_replace(
			"/([>$MB_WS_RE\|\=])(?i:file|image)$MB_WS_RE_OPT\:" .
				 "$MB_WS_RE_OPT$re([$MB_WS_RE\|}<])/ums", "$1File:$str_new_image$2", $page_text, -1, 
				$count);
		// PHP regex bug that hates unicode
		$validator->assert(strlen($page_text) != 0, "either PHP regex bug, or your regex sucks");
		$page_text = preg_replace(
			"/([>$MB_WS_RE\|\=])(?i:media)$MB_WS_RE_OPT\:$MB_WS_RE_OPT" . "$re([$MB_WS_RE\|}<])/ums", 
			"$1Media:$str_new_image$2", $page_text, -1, $count2);
		// PHP regex bug that hates unicode
		$validator->assert(strlen($page_text) != 0, "either PHP regex bug, or your regex sucks");
		$count += $count2;
		
		if ($count > 0) {			
			$result = $relink_text->test_post($page_text);
			
			if ($result === Relink_Text::SUCCESS) {
				return $page_text;
			}
		}
		
		/* least safe: replacing the image without Image/File/Media in front */
		$page_text = preg_replace("/([$MB_WS_RE>\|\={}])$re([$MB_WS_RE<\|{}])/ums", 
			'${1}' . escape_preg_replacement($str_new_image) . "$2", $page_text, -1, $count);
		
		// PHP regex bug that hates unicode
		$validator->assert(strlen($page_text) != 0, "either PHP regex bug, or your regex sucks");
		
		if ($count != 0) {
			if ($relink_text->test_post($page_text) === Relink_Text::SUCCESS) {
				return $page_text;
			}
		}
		throw new CantDelinkException("unknown");
	}
	
	/**
	 * 
	 * @param string $str
	 * @return string
	 */
	protected static final function remove_namespace($str) {
		return preg_replace("/^.+?\:(.+)$/", "$1", $str);
	}
	
	/**
	 *
	 * @param string $page_name        	
	 * @param string $page_text        	
	 * @param string[] $images_names_underscore        	
	 * @return bool[]
	 */
	protected final function test_image_inclusion($page_name, $page_text, $images_names_underscore) {
		global $wiki_interface;
		
		$test_old_image_inclusion = $wiki_interface->api_query($this->local, 
			array('action' => 'parse', 'title' => $page_name, 'text' => $page_text, 
				'prop' => 'images'), true);
		
		$images = array_key_or_exception($test_old_image_inclusion, 'parse', 'images');
		
		$return = array();
		foreach ($images_names_underscore as $key => $name) {
			$return[$key] = in_array($name, $images);
		}
		
		return $return;
	}
	/**
	 *
	 * @param string $from        	
	 * @param boolean $same        	
	 */
	protected abstract function post_delink($from, $same);
	
	/**
	 *
	 * @return string[] - array of file names without the namespace
	 */
	protected abstract function get_files_same_name();
	
	/**
	 *
	 * @return string[] - index of string is File being delinked
	 *         without the namespace. The value is the destination file
	 *         without the namespace
	 */
	protected abstract function get_files_different_name();
	
	/**
	 * Override as needed
	 * 
	 * @param Relink_Failure $failure        	
	 * @return void
	 */
	protected function handle_failure(Relink_Failure $failure) {
		global $logger;
		
		$logger->error($failure);
	}
	
	/**
	 * Override as needed
	 * 
	 * @return boolean
	 */
	protected function is_verify_mime() {
		return true;
	}
	
	/**
	 *
	 * @return Wiki
	 */
	protected function get_local() {
		return $this->local;
	}
	
	/**
	 *
	 * @return array
	 */
	public function get_properties() {
		return $this->properties;
	}
	
	/**
	 *
	 * @return Wiki
	 */
	protected function get_shared() {
		return $this->shared;
	}
	
	/**
	 * @return bool
	 */
	public function get_test_conflicts() {
		return $this->test_conflicts;
	}
	
	/**
	 * 
	 * @param bool $test_conflicts
	 * @return bool
	 */
	public function set_test_conflicts($test_conflicts) {
		$this->test_conflicts = $test_conflicts;
	}
}