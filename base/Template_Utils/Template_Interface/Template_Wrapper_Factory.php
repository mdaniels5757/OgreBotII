<?php
/**
 * 
 * @author magog
 *
 */
class Template_Wrapper_Factory implements Template_Factory {
	
	/**
	 * 
	 * @var Template_Logic[]
	 */
	private $template_logics = [];
	
	/**
	 * 
	 * @param Template_Logic[] $template_logics
	 */
	public function __construct(array $template_logics = []) {
		$this->template_logics = $template_logics;
	}
	
	/**
	 * @param Template_Logic $template_logic
	 * @return void
	 */
	public function add_logic(Template_Logic $template_logic) {
		$this->template_logics[] = $template_logic;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Template_Factory::extract()
	 */
	public function extract($text, $name, $offset=0) {
		$template = Template::extract($text, $name, $offset, false);
		
		if (!$template) {
			return $template;
		}
		
		return $this->_get($template);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Template_Factory::get()
	 */
	public function get(Abstract_Template $template) {
		return $this->_get($template->get_original_template());
		
	}
	
	/**
	 * 
	 * @param Template $template
	 * @return Abstract_Template
	 */
	private function _get(Template $template) {
		
		$template_logics = array_filter($this->template_logics,
			function (Template_Logic $template_logic) use($template) {
				return $template_logic->is_eligible($template);
			});
		
		
		//if there are no template logics, just return the original template for efficiency
		return $template_logics ? new Template_Wrapper($template, $template_logics) : $template;
	}
}