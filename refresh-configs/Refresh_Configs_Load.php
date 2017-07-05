<?php
class Refresh_Configs_Load {
	const STATS_URL = "http://wikistats.wmflabs.org/display.php?t=\$key";
	private $abbreviations = ["wp" => "wikipedia", "wt" => "wiktionary", "ws" => "wikisource", 
		"wn" => "wikinews", "wb" => "wikibooks", "wq" => "wikiquote", "wv" => "wikiversity"];
	private $meta_configs = ["Wikivoyage/Table"];
	
	/**
	 *
	 * @var Http_Cache_Reader
	 */
	private $http_cache_reader;
	public function __construct() {
		$this->http_cache_reader = new Http_Cache_Reader(SECONDS_PER_DAY);
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function load() {
		return array_merge($this->load_from_stats(), $this->load_from_meta());
	}
	
	/**
	 *
	 * @return string[]
	 */
	private function load_from_stats() {
		return array_merge_all(
			array_map_pass_key($this->abbreviations, 
				function ($key, $project) {
					$url = replace_named_variables(self::STATS_URL, ["key" => $key]);
					
					$content = $this->http_cache_reader->get_and_store_url($url);
					
					$regex = "/<a href=\"https?:\/\/(([a-z\-]+)" .
						 "\.$project)\.org\/wiki\/\">\\2<\/a>/";
					
					preg_match_all($regex, $content, $matches);
					
					return $matches[1];
				}));
	}
	
	/**
	 *
	 * @throws IllegalStateException
	 * @return string[]
	 */
	private function load_from_meta() {
		global $logger, $wiki_interface;
		if ($this->meta_configs) {
			$logger->debug("Loading meta");
			$meta = (new Project_Data("meta.wikimedia"))->getWiki();
			$page_text_responses = $wiki_interface->get_text($meta, $this->meta_configs);
			
			return array_merge_all(
				array_map(
					function (Page_Text_Response $page_text_response) {
						if (!$page_text_response->exists) {
							throw new IllegalStateException("Page doesn't exist");
						}
						preg_match_all("/\[\/\/([a-z\-]+\.w[a-z]+)\.org\/wiki\/Special:Imagelist /", 
							$page_text_response->text, $matches);
						
						return $matches[1];
					}, $page_text_responses));
		}
		
		$logger->debug("Nothing to load from meta");
		return [];
	}
}