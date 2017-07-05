<?php
/**
 * 
 * @author magog
 *
 */
class CommonsHelper_Dao_Impl implements CommonsHelper_Dao {
	
	const INTERFACE_URL = "User:Magnus Manske/Commonshelper interface";
	
	/**
	 * 
	 * @var array[]
	 */
	private $constants;

	/**
	 *
	 * @var Project_Data
	 */
	private $commons_project_data;
	
	/**
	 * 
	 */
	public function __construct() {
		$this->constants = CommonsHelper_Factory::get_constants();
		$this->commons_project_data = Project_Data::load("commons.wikimedia");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see CommonsHelper_Dao::load_interface()
	 */
	public function load_interface() {
		$text = Environment::get()->get_wiki_interface()->get_text(
			$this->commons_project_data->getWiki(), self::INTERFACE_URL)->text;
		return map_array_function_keys(
			Regex_Util::match_all("/^\=\=\s*([A-Za-z\-]+)\s*\=\=\s*\n\{\|.+([\s\S]+?)\|\}$/m", 
				$text), 
			function ($table) {
				global $logger;
				
				list(, $language, $fields) = $table;
				$language = strtolower($language);
				$interface = new CommonsHelper_Interface();
				$interface->dir = in_array($language, ["ar", "fa", "he"]) ? "rtl" : "ltr";
				foreach (Regex_Util::match_all("/^\!(\w+)\s*^\|(.+?)$/m", $fields) as $field) {
					list(, $name, $value) = $field;
					if (property_exists($interface, $name)) {
						$value = mb_trim($value);
						if ($value) {
							$interface->$name = $value;
						}
					} else {
						$logger->warn("Unrecognized field: $name");
					}
				}
				$interface->id = $language;
				return [$language, $interface];
			});
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see CommonsHelper_Dao::get_templates_on_commons($names)
	 */
	public function get_templates_on_commons($names) {
		$page_responses = Environment::get()->get_wiki_interface()->get_text(
			$this->commons_project_data->getWiki(), $names, false);
		return array_keys(
			array_filter($page_responses, 
				function (Page_Text_Response $page_text_response) {
					return $page_text_response->exists;
				}));
	}
}