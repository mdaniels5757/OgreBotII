<?php
/**
 * Move the Transferred From text to the top of the original upload log
 * @author magog
 *
 */
class Cleanup_License_Migration implements Cleanup_Module {
	
	/**
	 *
	 * @var Template_Cache
	 */
	private $migration_template_cache;

	/**
	 * 
	 * @var Template_Factory
	 */
	private $template_factory;
	
	/**
	 * 
	 * @var Template_Utils
	 */
	private $template_utils;
	
	/**
	 * 
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->template_utils = $cleanup_package->get_template_utils();
		$this->template_factory = $cleanup_package->get_migration_template_factory();
		$this->migration_template_cache = new Template_Cache($this->template_factory);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		
		/**
		 * GFDL with a cc-3.0 is always redundant if not yet marked
		 */
		$cc_30 = $this->template_utils->getPredefinedClassTypesMulti($ci->get_text(), "cc_migration", 1);
		if ($cc_30) {
			$gfdl_templates = $this->template_utils->getPredefinedClassTypesMulti($ci->get_text(), "GFDL");
			foreach ($gfdl_templates as $template) {
				$template = $this->template_factory->get($template);
				if ($template->fieldisset("migration")) {
					$template->addfield("redundant", "migration");
					$ci->set_text($template->wholePage());
				}
			}
		}
		
		do {
			$orig_text = $ci->get_text();
			$this->license_migration_cleanup_internal($ci, "relicense", "Cc-by-sa-3.0-migrated");
			$this->license_migration_cleanup_internal($ci, "not-eligible", 
				"License migration not eligible");
			$this->license_migration_cleanup_internal($ci, "review", "License migration review");
		} while ($orig_text !== $ci->get_text());
	}
	
	/**
	 *
	 * @param Cleanup_Instance $ci        	
	 * @param string $migration_text        	
	 * @param string $migration_template_text        	
	 * @return void
	 */
	private function license_migration_cleanup_internal(Cleanup_Instance $ci, $migration_text, 
		$migration_template_text) {
		
		$migration_template = $ci->get_template($migration_template_text);
		
		if ($migration_template) {
			$_text = $migration_template->__get("before") . preg_replace("/^[^\n\S]*\n/u", "", 
				$migration_template->__get("after"));
			
			$gfdl_re = "(?i:gfdl[\w\.\-]*)";
			preg_match_all(
				"/\{\{\s*($gfdl_re|[Ww]ik(?:ipedia|tionary|inews|iquote|isource|imedia[ _]+project|iversity)[- _]+screenshot)\s*(?:\||\}\})/u", 
				$_text, $gfdl, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
			preg_match_all(
				"/\{\{\s*([Ss]elf2?|[Mm]ultilicense[ _]+replacing[ _]+placeholder([ _]+new)?)\s*(?:\||\}\})/u", 
				$_text, $self, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
			
			/* parse a self template ONLY if it has a GFDL variable within */
			foreach ($self as $self_next) {
				/* iterate through self template, see if GFDL is present */
				$template = $this->migration_template_cache->get_template(
					substr($_text, $self_next[0][1]), $self_next[1][0]);
				if ($template) {
					/*
					 * in case of a false positive i.e., unterminated template,
					 * it might not parse
					 */
					$fields = $template->__get("fields");
					if (preg_match("/^[Mm]ultilicense[ _]+replacing[ _]+placeholder([ _]+new)?$/iu", 
						$template->__get("name"))) {
						$gfdl[] = $self_next;
					} else {
						foreach ($fields as $field_next_key => $field_next) {
							if (is_numeric($field_next_key)) {
								if (preg_match("/^\s*$gfdl_re\s*$/u", $field_next)) {
									/* match */
									$gfdl[] = $self_next;
									break;
								}
							}
						}
					}
				}
			}
			
			$relicense_present = false;
			foreach ($gfdl as $gfdl_next) {
				$template = $this->migration_template_cache->get_template(
					substr($_text, $gfdl_next[0][1]), $gfdl_next[1][0]);

				/*
				 * in case of a false positive i.e., unterminated template,
				 * it might not parse
				 */
				if ($template) {
					$local_val = $template->fieldvalue("migration");
					$make_switch = $migration_text === $local_val || !in_array($local_val, 
						["opt-out", "not-eligible", "relicense"]);
					
					if ($make_switch) {
						if ($migration_text !== $local_val) {
							$template->updatefield("migration", $migration_text);
						}
						
						$ci->set_text(substr($_text, 0, $gfdl_next[0][1]) . $template->wholePage());
						$this->license_migration_cleanup_internal($ci, $migration_text, 
							$migration_template_text);
						return;
					}
				}
			}
		}
	}
}