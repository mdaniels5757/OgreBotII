<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Multiple_Information implements Cleanup_Module {
	
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
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		/**
		 * More than one information template; the script can't handle this; abandon ship!
		 */
		$first_information_template = $ci->get_template(Cleanup_Shared::INFORMATION);
		if ($first_information_template) {
			$after = $first_information_template->__get("after");
			if ($this->template_factory->extract($after, Cleanup_Shared::INFORMATION)) {
				throw new Cleanup_Abort_Exception();
			}
		}
	}
}