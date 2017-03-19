<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Duplicate_GFDL implements Cleanup_Module {

	/**
	 *
	 * @var Template_Cache
	 */
	private $migration_template_cache;

	/**
	 *
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->migration_template_cache = new Template_Cache(
			$cleanup_package->get_migration_template_factory());
	}
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		global $validator;
		
		$nxt = null;
		
		// {{Bild-GFDL-Neu}} => {{GFDL|migration=not-eligible}}
		$template = $this->migration_template_cache->get_template($ci->get_text(), "Bild-GFDL-Neu");
		if ($template) {
			$template->updatefield("migration", "not-eligible");
			$ci->set_text($template->wholePage());
		}
		
		$ci->preg_replace("/\{\{\s*Гфдл\s*(\||\}\})/u", "{{GFDL-sr$1", false); // not a major change
		$ci->preg_replace("/\{\{\s*ГЛСД\-без одрицања\s*(\||\}\})/u", "{{GFDL$1", false); // not a major change
		$text2 = $ci->get_text();
		$all_templates_names = array();
		$all_templates_text = array();
		$madechange = false;
		do {
			$nxt = false;
			preg_match_all(
				"/\{\{\s*((?:bild\-)?gfdl.*?|multilicense[ _]+replacing[ _]+placeholder(?:[ _]+new)?|cc|cc\-by(?:\-sa)?\-3\.0(?:\-rs)?|cc\-by\-sa\-(?:3\.0\,)??2\.5\,2\.0\,1\.0)(?:\||\}\})/iu", 
				$text2, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
			// ideally, we should find the gfdl on the first match, but in the case of a false positive (e.g., unterminated template), we might not.
			foreach ($matches as $match) {
				$nxt = Template::extract($text2, $match[1][0]);
				if ($nxt) {
					break;
				}
			}
			if ($nxt) {
				$text2 = $nxt->__get("before") . "%%%MTO_TEMPLATE_" . count($all_templates_names) .
					 "%%%" . $nxt->__get("after");
				$all_templates_names[] = ucfirst_utf8($nxt->__get("name"));
				$all_templates_text[] = $nxt->__toString();
			}
		} while ($nxt);
		if ($this->fix_duplicate_gfdl_internal($text2, $all_templates_names, $all_templates_text, 
			"GFDL-user-en-no-disclaimers", "GFDL-self-no-disclaimers") | $this->fix_duplicate_gfdl_internal(
			$text2, $all_templates_names, $all_templates_text, "GFDL-user-en-no-disclaimers", 
			"GFDL-self") | $this->fix_duplicate_gfdl_internal($text2, $all_templates_names, 
			$all_templates_text, "GFDL-user-en-no-disclaimers", "GFDL") | $this->fix_duplicate_gfdl_internal(
			$text2, $all_templates_names, $all_templates_text, "GFDL-user-en-with-disclaimers", 
			"GFDL-self-en") | $this->fix_duplicate_gfdl_internal($text2, $all_templates_names, 
			$all_templates_text, "GFDL-en", "GFDL-with-disclaimers") | $this->fix_duplicate_gfdl_internal(
			$text2, $all_templates_names, $all_templates_text, "GFDL-self-en", "GFDL") | $this->fix_duplicate_gfdl_internal(
			$text2, $all_templates_names, $all_templates_text, "GFDL-self", "GFDL") | $this->fix_duplicate_gfdl_internal(
			$text2, $all_templates_names, $all_templates_text, "GFDL-no-disclaimers", "GFDL") | $this->fix_duplicate_gfdl_internal(
			$text2, $all_templates_names, $all_templates_text, "GFDL-sr", "GFDL") | $this->fix_duplicate_gfdl_internal(
			$text2, $all_templates_names, $all_templates_text, "Multilicense replacing placeholder", 
			"GFDL") |
			 $this->fix_duplicate_gfdl_internal($text2, $all_templates_names, $all_templates_text, 
				"Multilicense replacing placeholder", "Cc-by-sa-2.5,2.0,1.0") | $this->fix_duplicate_gfdl_internal(
				$text2, $all_templates_names, $all_templates_text, 
				"Multilicense replacing placeholder new", "GFDL") | $this->fix_duplicate_gfdl_internal(
				$text2, $all_templates_names, $all_templates_text, 
				"Multilicense replacing placeholder new", "Cc-by-sa-3.0,2.5,2.0,1.0") | $this->fix_duplicate_gfdl_internal(
				$text2, $all_templates_names, $all_templates_text, "GFDL", "Bild-GFDL-Neu") | $this->fix_duplicate_gfdl_internal(
				$text2, $all_templates_names, $all_templates_text, "Cc-by-sa-3.0", "Cc") | $this->fix_duplicate_gfdl_internal(
				$text2, $all_templates_names, $all_templates_text, "Cc-by-3.0", "Cc") | $this->fix_duplicate_gfdl_internal(
				$text2, $all_templates_names, $all_templates_text, "Cc-by-3.0-rs", "Cc-by-sa-3.0-rs") | $this->fix_duplicate_gfdl_internal(
				$text2, $all_templates_names, $all_templates_text, "Cc-by-sa-3.0,2.5,2.0,1.0", "Cc") | $this->fix_duplicate_gfdl_internal(
				$text2, $all_templates_names, $all_templates_text, "Cc-by-sa-2.5,2.0,1.0", "Cc")) {
			$ci->set_text($text2);
			while (preg_match("/%%%MTO_TEMPLATE_(\d+)%%%/u", $ci->get_text(), $match, 
				PREG_OFFSET_CAPTURE)) {
				$validator->assert($all_templates_names[$match[1][0]] != null);
				$ci->set_text(
					substr($ci->get_text(), 0, $match[0][1]) . $all_templates_text[$match[1][0]] .
						 substr($ci->get_text(), $match[0][1] + strlen($match[0][0])));
			}
		}
	}
	
	/**
	 * 
	 * @param string $text
	 * @param string[] $all_templates_names
	 * @param string[] $all_templates_text
	 * @param string $keep_license
	 * @param string $replace_license
	 * @return boolean
	 */
	private function fix_duplicate_gfdl_internal(&$text, $all_templates_names, $all_templates_text, 
		$keep_license, $replace_license) {
		global $logger, $validator;
		
		$first_gfdl = null;
		$second_gfdl = null;
		foreach ($all_templates_names as $i => $name) {
			if ($name === $keep_license) {
				$first_gfdl = $i;
			} else if ($name === $replace_license) {
				$second_gfdl = $i;
			}
			if ($first_gfdl !== null && $second_gfdl !== null) {
				$tmpl1 = Template::extract($all_templates_text[$first_gfdl], 
					$all_templates_names[$first_gfdl]);
				$tmpl2 = Template::extract($all_templates_text[$second_gfdl], 
					$all_templates_names[$second_gfdl]);
				$validator->assert($tmpl1 !== false);
				$validator->assert($tmpl2 !== false);
				foreach ($tmpl2->__get("fields") as $key => $field) {
					$field = $tmpl2->fieldvalue($key);
					if ($key === "Migration" || $key === "migration") {
						$migration1 = $this->get_migration($tmpl1);
						switch (trim($migration1)) {
							case "" :
							case "review" :
							case "needs-review" :
							case "needs review" :
							case "redundant" :
								$nodefer1 = false;
								break;
							
							case "opt out" :
							case "opt-out" :
								$nodefer1 = "optout";
								break;
							
							case "not-eligible" :
							case "not eligible" :
								$nodefer1 = "noteligible";
								break;
							
							case "relicense" :
							case "complete" :
								$nodefer1 = "relicense";
								break;
							
							default :
								$nodefer1 = "unknown";
								break;
						}
						switch (trim($field)) {
							case "" :
							case "review" :
							case "needs-review" :
							case "needs review" :
							case "redundant" :
								$nodefer2 = false;
								break;
							
							case "opt out" :
							case "opt-out" :
								$nodefer2 = "optout";
								break;
							
							case "not-eligible" :
							case "not eligible" :
								$nodefer2 = "noteligible";
								break;
							
							case "relicense" :
							case "complete" :
								$nodefer2 = "relicense";
								break;
							
							default :
								$nodefer2 = "unknown";
								break;
						}
						if (!$nodefer1) {
							$this->set_migration($tmpl1, $field);
						} else if ($nodefer2 && $nodefer2 !== $nodefer1) {
							$logger->error(
								"Incompatible relicensing, $nodefer1, $nodefer2: $text\n\n");
						}
						/* end if == "migration" */
					} else {
						$old = $this->information_field_get($tmpl1, $key);
						if (trim($old) !== trim($field)) {
							$this->information_field_set($tmpl1, $key, $old . $field, false);
						}
					}
					/* end foreach ($tmpl2->__get("fields") as $key => $field) */
				}
				$text = str_replace("%%%MTO_TEMPLATE_$first_gfdl%%%", $tmpl1, $text);
				$text = preg_replace("/%%%MTO_TEMPLATE_$second_gfdl%%%(?:\s*?\\n)?/u", "", $text);
				$all_templates_names[$second_gfdl] = null;
				$this->fix_duplicate_gfdl_internal($text, $all_templates_names, $all_templates_text, 
					$keep_license, $replace_license);
				return true;
				/* end if ($first_gfdl!==null && $second_gfdl!==null) */
			}
			/* end foreach ($all_templates_names as $i => $name) */
		}
		return false;
	}
	
	/**
	 *
	 * @param Abstract_Template $template        	
	 * @return string
	 */
	private function get_migration(&$template) {
		$migration = $this->information_field_get($template, "migration");
		if ($migration !== null) {
			$migration = trim(strtolower($migration));
		}
		return $migration;
	}
	
	/**
	 *
	 * @param Abstract_Template $template        	
	 * @param mixed $val        	
	 * @return
	 *
	 */
	private function set_migration(Abstract_Template $template, $val) {
		global $validator;
		
		$validator->assert(is_string($val) && strlen(trim($val)) !== 0);
		if ($template->fieldisset("migration")) {
			$field = "migration";
		} else if ($template->fieldisset("Migration")) {
			$field = "Migration";
		} else {
			$field = "migration";
		}
		return $template->updatefield($field, mb_trim($val));
	}
	
	/**
	 *
	 * @param Template $template        	
	 * @param string|int $fieldname        	
	 * @return string|null
	 */
	public static function information_field_get(Abstract_Template $template, $fieldname) {
		$field = null;
		$fieldname = str_replace("_", " ", $fieldname);
		$underscore_uc = ucfirst(str_replace(" ", "_", $fieldname));
		$underscore_lc = lcfirst(str_replace(" ", "_", $fieldname));
		$uc = ucfirst($fieldname);
		$lc = lcfirst($fieldname);
		
		if ($template->fieldisset($underscore_lc)) {
			$field = $underscore_lc;
		} else if ($template->fieldisset($underscore_uc)) {
			$field = $underscore_uc;
		} else if ($template->fieldisset($lc)) {
			$field = $lc;
		} else if ($template->fieldisset($uc)) {
			$field = $uc;
		}
		
		if ($field !== null) {
			return $template->fieldvalue($field);
		}
		return null;
	}
	
	/**
	 *
	 * @param Template $template        	
	 * @param string|int $fieldname        	
	 * @param string $val        	
	 * @param bool $manipulate_newline
	 *        	[OPTIONAL] default true
	 */
	private function information_field_set(Abstract_Template $template, $fieldname, $val, 
		$manipulate_newline = true) {
		$field = null;
		$fieldname = str_replace("_", " ", $fieldname);
		$underscore_uc = ucfirst(str_replace(" ", "_", $fieldname));
		$underscore_lc = lcfirst(str_replace(" ", "_", $fieldname));
		$uc = ucfirst($fieldname);
		$lc = lcfirst($fieldname);
		
		if ($template->fieldisset($underscore_lc)) {
			$field = $underscore_lc;
		} else if ($template->fieldisset($underscore_uc)) {
			$field = $underscore_uc;
		} else if ($template->fieldisset($lc)) {
			$field = $lc;
		} else if ($template->fieldisset($uc)) {
			$field = $uc;
		} else {
			if ($manipulate_newline)
				$val = preg_replace("/(?:\s*?\\n)?$/u", "\r\n", $val);
			$field = $uc;
		}
		
		if (trim($val) === "" && !$template->fieldisset($field)) {
			return "";
		}
		
		return $template->updatefield($field, $val);
	}
}