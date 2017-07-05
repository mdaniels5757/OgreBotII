<?php

class Cleanup_Base {
	
	/**
	 * 
	 * @var Cleanup_Package
	 */
	private $cleanup_package;
	
	/**
	 * 
	 * @var Cleanup_Module[]
	 */
	private $modules;
	
	/**
	 * 
	 * @var Cleanup_Module[]
	 */
	private $pre_parse_modules;
	
	/**
	 * 
	 * @var Cleanup_Module[]
	 */
	private $post_parse_modules;
	
	/**
	 * 
	 */
	public function __construct() {
		$template_factory = new Template_Wrapper_Factory([new Infobox_Template_Logic()]);
		
		$this->cleanup_package = new Cleanup_Package_Impl((new Cleanup_Shared())->get_constants(), 
			$template_factory, new Template_Wrapper_Factory([new Migration_Template_Logic()]), 
			new Template_Utils(), new Template_Cache($template_factory));
		
		$all_modules = map_array_function_keys(
			Classloader::get_all_class_names_of_type(Cleanup_Module::class), 
			function ($class_name) {
				$key_name = strtolower(preg_replace("/^Cleanup_/", "", $class_name));
				$module = new $class_name($this->cleanup_package);
				
				return [$key_name, $module];
			});
		
		$this->pre_parse_modules = array_sort_custom($all_modules, ["fastily_xml"]);
		
		$this->modules = array_sort_custom($all_modules, 
			["license_mangle", "duplicate_author", "bad_template_close", "magnus_date_bugs", 
				"magnus_author_bugs", "original_description_page", "multiple_information", 
				"nested_information", "wikivoyage", "duplicate_gfdl", "remove_empty_templates", 
				"headers", "mangled_transfer", "duplicate_licenses", "langlinks", 
				"license_templates_to_information_field", "uploader", "location_field", 
				"remove_redundant_fields", "dates", "transferred_from_jan_luca_bot", 
				"non_information_author", "prettify", "move_transferred_from", 
				"remove_extra_description_verbiage", "unicode", "known_author_types", 
				"remove_duplicate_otrs", "remove_original_text", "own", "old_description_page_link", 
				"otrs", "remove_incorrect_trailing_license", "license_migration", "kettos", 
				"add_source", "remove_duplicates", "by_author_type", "self_migration_redundant", 
				"remove_original_uploader", "duplicate_transfer_text", "transferred_from", "fastily", 
				"remove_notoc", "remove_information_extra_bar", "pd_user", "warn_citation_removed", 
				"human_changes"]);
			
		$this->post_parse_modules = array_sort_custom($all_modules, 
			["remove_local_missing_template"]);
	}
	
	
	/**
	 *
	 * @param string $text
	 * @param string $uploadtime
	 * @param bool $human
	 * @return Cleanup_Instance
	 */	
	public function super_cleanup($text, $uploadtime, $human = FALSE) {
		global $logger;
		
		try {
			$pre = $this->do_pre_cleanup($text, $human);
			$text = $pre->get_text();
			
			$post = $this->do_cleanup($text, $uploadtime, $human);
			
			$pre->merge($post);
			
			return $pre;
		} catch (XmlError $e) {
			$logger->warn($e);
			
			$cleanup_instance = new Simple_Cleanup_Instance($text, $human);
			$cleanup_instance->add_warning(Cleanup_Shared::UNCLOSED_XML);
			return $cleanup_instance;
		} catch (Cleanup_Abort_Exception $e) {
			$logger->warn("Cleanup aborted!");
			$logger->warn($e);
			
			return new Simple_Cleanup_Instance($text, $human);
		} catch (TemplateParseException $e) {
			$cleanup_instance = new Simple_Cleanup_Instance($text, $human);
			$cleanup_instance->add_warning(Cleanup_Shared::TEMPLATE_UNCLOSED);
			return $cleanup_instance;
		} 
	}
	

	/**
	 *
	 * @param string $text
	 * @param string $uploadtime
	 * @param bool $human
	 * @return Cleanup_Instance
	 */
	private function do_pre_cleanup($text, $human) {
		$ci = new Simple_Cleanup_Instance($text, $human);
		array_walk($this->pre_parse_modules,
			function (Cleanup_Module $module) use ($ci) {
				$module->cleanup($ci);
			});
		$text = $ci->get_text();
		return $ci;
	}
	
	/**
	 *
	 * @param string $text        	
	 * @param string $uploadtime        	
	 * @param bool $human        	
	 * @return Cleanup_Instance
	 */
	private function do_cleanup($text, $uploadtime, $human) {
		global $logger, $validator;
		
		/**
		 * Isolate Comments and nowikis, which we shouldn't touch
		 */
		$parser = new Page_Parser($text);
		$text = $parser->get_text();
		
		$ci = new Full_Cleanup_Instance($text, $human, $parser, $uploadtime, 
			$this->cleanup_package->get_template_cache());
		array_walk($this->modules, 
			function (Cleanup_Module $module) use($ci) {
				$module->cleanup($ci);
			});
		
		/**
		 * Restore Comments and nowikis
		 */
		$parser->set_text($ci->get_text());
		$parser->unparse();
		$ci->set_text($parser->get_text(), false);		
		array_walk($this->post_parse_modules,
			function (Cleanup_Module $module) use($ci) {
				$module->cleanup($ci);
			});
		
		
		// seems to happen exclusively in Windows due to PCRE UTF-8 incompatibilities
		if (!$ci->get_text()) {
			$logger->error("Text was blanked by PHP! Text: " . $ci->get_text());
			return new Simple_Cleanup_Instance($ci->get_text(), $human);
		}
		
		// whitespace cleanup
		$ci->preg_replace("/(?<!\\r)\\n/u", "\r\n", false);
		
		return $ci;
	}
}