<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Package_Impl implements Cleanup_Package {
	
	/**
	 * 
	 * @var array 
	 */
	private $constants;
	
	/**
	 * 
	 * @var Template_Factory
	 */
	private $infobox_template_factory;
	
	/**
	 * 
	 * @var Template_Factory
	 */
	private $migration_template_factory;
	
	/**
	 * 
	 * @var Template_Utils
	 */
	private $template_utils;
	
	/**
	 * 
	 * @var Template_Cache
	 */
	private $template_cache;
	
	/**
	 *
	 * @param array $constants        	
	 * @param Template_Factory $infobox_template_factory        	
	 * @param Template_Factory $migration_template_factory        	
	 * @param TemplateUtils $template_utils        	
	 */
	public function __construct(array $constants, Template_Factory $infobox_template_factory, 
		Template_Factory $migration_template_factory, TemplateUtils $template_utils, 
		Template_Cache $template_cache) {
		$this->constants = $constants;
		$this->infobox_template_factory = $infobox_template_factory;
		$this->migration_template_factory = $migration_template_factory;
		$this->template_utils = $template_utils;
		$this->template_cache = $template_cache;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Package::get_constants()
	 */
	public function get_constants() {
		return $this->constants;
	}


	/**
	 * @return Template_Factory
	 */
	public function get_infobox_template_factory() {
		return $this->infobox_template_factory;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Package::get_template_factories()
	 */
	public function get_migration_template_factory() {
		return $this->migration_template_factory;
	}
	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Package::get_template_utils()
	 */
	public function get_template_utils() {
		return $this->template_utils;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Package::get_template_cache()
	 */
	public function get_template_cache() {
		return $this->template_cache;
	}
	
}