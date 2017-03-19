<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_License_Mangle implements Cleanup_Module {
		
	/**
	 *
	 * @var string[]
	 */
	private $self_re;
	
	/**
	 * 
	 * @var string[]
	 */
	private $self_template_names;
	
	/**
	 * 
	 * @var Template_Factory
	 */
	private $template_factory;
	
	
	/**
	 *
	 * @param Cleanup_Package $cleanup_package        	
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->template_factory = $cleanup_package->get_infobox_template_factory();
		$this->self_template_names = ["self"];
		$this->self_re = "/" . Cleanup_Shared::LICENSE_HEADER . "(\{\{\s*[Ss]elf\s*" .
			 "\|\s*[Aa]uthor\s*=\s*\{\{\s*[Uu]ser[_\s]+at[_\s]+project\s*\|[^\[\]\|]+\|" .
			 "\s*[Ww][A-Za-z]*\s*\|\s*(" . $cleanup_package->get_constants()["langlinks_regex"] .
			 ")\s*\}\})\s*\n/";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		
		$logger = Environment::get()->get_logger();
		$logger->debug("Entering " . Cleanup_License_Mangle::class . "::cleanup()");
		
		$text = $ci->get_text();
		if (!preg_match($this->self_re, $text, $mangled_self, PREG_OFFSET_CAPTURE)) {
			$logger->trace("regex not found, exiting.");
			return;
		}

		$logger->trace("self regex found.");
		
		foreach ($this->self_template_names as $template_name) {
			if ($ci->get_template($template_name)) {
				$logger->warn("Template $template_name found, aborting");
				return;
			}
		}
		
		$information = $ci->get_template(Cleanup_Shared::INFORMATION);
		if (!$information) {
			$logger->warn("Information not found, aborting");
			return;
		}
		
		$description = $information->fieldvalue(Cleanup_Shared::DESCRIPTION);
		if ($description === false) {
			$logger->warn("No description, aborting");
			return;
		}
		
		$langlink = $this->template_factory->extract($description, $mangled_self[4][0]);

		if (!$langlink) {
			$logger->warn("Can't find language template, aborting.");
			return;
		}
		
				
		$fields = $langlink->__get("fields");
		
		if (!$fields) {
			$logger->warn("Fields is empty, aborting.");
			return;
		}
		
		switch (key($fields)) {
			case "migration":
			case "Migration": 
			$logger->warn("Bad migration, aborting.");
			return;
				
		}
		unset($fields[key($fields)]);
		
		$self = $mangled_self[3][0] . "|" . join("|", $fields) . "}}";
		
		$text = substr($text, 0, $mangled_self[3][1]) . $self .
			 substr($text, $mangled_self[3][1] + strlen($mangled_self[3][0]));
		
		$before_templates_removed = $this->remove_templates($mangled_self[1][0], $fields);
		
		$text = substr($text, 0, $mangled_self[1][1]) . $before_templates_removed .
			 substr($text, $mangled_self[1][1] + strlen($mangled_self[1][0]));
		
		
		$information = $this->template_factory->extract($text, Cleanup_Shared::INFORMATION);
		if (!$information) {
			$logger->warn("Our text has mangled the page. Abort. :(");
			return;
		}

		$description = $information->fieldvalue(Cleanup_Shared::DESCRIPTION);
		if ($description === false) {
			$logger->warn("Our text has mangled the page. Aborting :(");
			return;
		}
		
		$langlink = $this->template_factory->extract($description, $mangled_self[4][0]);

		if (!$langlink) {
			$logger->warn("Our text has mangled the page. Aborting :(");
			return;
		}
		
		$field_keys = array_keys($fields);
		array_walk($field_keys, [$langlink, "removefield"]);		
		$information->updatefield(Cleanup_Shared::DESCRIPTION, $langlink->wholePage());		
		$ci->set_text($information->wholePage());

	}
	
	/**
	 * 
	 * @param string $text
	 * @param string[] $fields
	 */
	private function remove_templates($text, array $fields) {
		foreach ($fields as $key => $name) {
			unset($fields[$key]);
			if (is_numeric($key)) {
				$template = $this->template_factory->extract($text, $name);
				if ($template) {
					return $this->remove_templates(
						Cleanup_Shared::remove_template_and_trailing_newline($template), $fields);
				}
			}
		}
		return $text;
	}
	
}