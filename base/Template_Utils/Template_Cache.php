<?php

/**
 *
 * @author magog
 *
 */
class Template_Cache {
	
	/**
	 * 
	 * @var string
	 */
	private $text;
	
	/**
	 * 
	 * @var multitype:Ambigous <false, Abstract_Template>
	 */
	private $cache;
	
	/**
	 * 
	 * @var Template_Factory
	 */
	private $template_factory;
	
	/**
	 * 
	 * @param Template_Factory $template_factory
	 */
	public function __construct(Template_Factory $template_factory) {
		$this->template_factory = $template_factory;
	}
	
	/**
	 * 
	 * @param string $text
	 * @param string $template_name
	 * @return Abstract_Template|false
	 */
	public function get_template($text, $template_name) {
		if ($text !== $this->text || count($this->cache) > 1024) {
			$this->cache = [];
		}
		
		if (isset($this->cache[$template_name])) {
			return $this->cache[$template_name];
		}
		
		$this->text = $text;
		
		$template = $this->template_factory->extract($text, $template_name);
		
		$this->cache[$template_name] = $template;
		
		return $template;
	}
}