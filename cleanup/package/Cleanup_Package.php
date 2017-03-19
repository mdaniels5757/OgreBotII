<?php
/**
 * 
 * @author magog
 *
 */
interface Cleanup_Package {
	
	/**
	 * @return array
	 */
	public function get_constants();
	
	/**
	 * @return Template_Factory
	 */
	public function get_infobox_template_factory();
	
	/**
	 * @return Template_Factory
	 */
	public function get_migration_template_factory();
	
	
	/**
	 * @return TemplateUtils
	 */
	public function get_template_utils();
	
	/**
	 * @return Template_Cache
	 */
	public function get_template_cache();
	
	
}