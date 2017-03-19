<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Self_Migration_Redundant implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$self = $ci->get_template("Self");
		if (!$self) {
			$self = $ci->get_template("Self2");
		} else if (!$self) {
			$self = $ci->get_template("Multilicense replacing placeholder");
		} else if (!$self) {
			$self = $ci->get_template("Multilicense replacing placeholder new");
		}
		
		if ($self) {
			$self_parameters = $self->__get("fields");
			$migration = $this->get_migration($self);
			if ($migration === null) {
				$migration = "";
			} else {
				$migration = trim($migration);
			}
			
			switch ($migration === "") {
				case "review" :
				case "needs-review" :
				case "needs review" :
				case "complete" :
				case "relicense" :
				case "" :
					
					$has_cc_30 = false;
					$has_gfdl = false;
					foreach ($self_parameters as $param => $value) {
						if (is_numeric($param)) {
							if (preg_match("/^\s*(?i:gfdl)[\-\d\,\.a-z]*\s*$/u", $value)) {
								$has_gfdl = true;
								if ($has_cc_30) {
									break;
								}
							} else if (preg_match(
								"/^\s*(?i:cc\-by(?:\-sa)?[\- _]+" .
									 "(?:[34]\.0|all|any))[\-\w\,\.\s]*$/u", $value)) {
								$has_cc_30 = true;
								if ($has_gfdl) {
									break;
								}
							}
						}
					}
					if ($has_cc_30 && $has_gfdl) {
						switch ($migration) {
							case "redundant" :
							case "" :
								return;
							
							default :
								$this->set_migration($self, "redundant");
								$ci->set_text($self->wholePage());
						}
					} else if ($has_gfdl && ($migration === null || trim($migration) === "")) {
						$this->set_migration($self, "review");
						$ci->set_text($self->wholePage());
					}
			}
		}
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
		return $template->updatefield($field, $val);
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
}