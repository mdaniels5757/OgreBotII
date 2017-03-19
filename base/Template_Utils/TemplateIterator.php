<?php

/**
 * 
 * @author magog
 *
 */
class TemplateIterator implements Iterator {

	/**
	 * 
	 * @var string
	 */
	private $origText;
	
	/**
	 * 
	 * @var string
	 */
	private $text;
	
	/**
	 *
	 * @var int
	 */
	private $index = 0;
	
	/**
	 * 
	 * @var Abstract_Template[]
	 */
	private $templates;

	/**
	 * 
	 * @var Template_Factory
	 */
	private $template_factory;

	/**
	 * 
	 * @param string $text
	 */
	public function __construct(&$text, Template_Factory $template_factory = null) {
		if ($template_factory === null) {
			$template_factory = Default_Template_Factory::get_singleton();
		}
		$this->template_factory = $template_factory;
		$this->text = &$text;
		$this->reset();
		$this->rewind();
	}

	/**
	 * @return void
	 */
	private function check() {
		if ($this->origText !== $this->text) {
			$this->reset();
		}
	}

	/**
	 * @return void
	 */
	private function reset() {
		$this->origText = $this->text;
		
		$this->templates = [];
		
		preg_match_all("/\{\{\s*([^\[\]\{\}\|\#\s][^\[\]\{\}\|\#]+?)\s*(?:\||\}\})/", $this->text, 
			$matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		
		foreach ($matches as $match) {
			$template = $this->template_factory->extract($this->text, $match[1][0], $match[0][1]);
			if ($template && strlen($template->__get("before")) == $match[0][1]) {
				$this->templates[] = $template;
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key() {
		$this->check();
		return $this->index;
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::next()
	 * @return Template
	 */
	public function next() {
		$this->check();
		$this->index++;
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::current()
	 * @return Abstract_Template
	 */
	public function current() {
		$this->check();
		return $this->templates[$this->index];
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		$this->index = 0;
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::valid()
	 * @return boolean
	 */
	public function valid() {
		return $this->index < count($this->templates);
	}
}