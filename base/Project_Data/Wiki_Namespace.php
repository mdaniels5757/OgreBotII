<?php

class Wiki_Namespace {
	
	/**
	 * 
	 * @var int
	 */
	private $id;
	
	/**
	 * 
	 * @var string
	 */
	private $name;
	
	/**
	 * 
	 * @var string[]
	 */
	private $aliases;
	
	/**
	 * Is this a talk namespace?
	 * @var bool
	 */
	private $talk;
	
	/**
	 * 
	 * @var int|null
	 */
	private $talk_namespace;
	
	
	/**
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 *
	 * @param int $id
	 * @return void
	 */
	public function set_id($id) {		
		$this->id = $id;
	}

	/**
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 *
	 * @param string $name
	 * @return void
	 */
	public function set_name($name) {
		$this->name = $name;
	}

	/**
	 *
	 * @return string[]
	 */
	public function get_aliases() {
		return $this->aliases;
	}

	/**
	 *
	 * @param string[] $aliases
	 * @return void
	 */
	public function set_aliases(array $aliases) {
		$this->aliases = $aliases;
	}

	/**
	 *
	 * @return bool
	 */
	public function get_talk() {
		return $this->talk;
	}

	/**
	 *
	 * @param bool $talk
	 * @return void
	 */
	public function set_talk($talk) {		
		$this->talk = $talk;
	}

	/**
	 *
	 * @return Wiki_Namespace|null
	 */
	public function get_talk_namespace() {
		return $this->talk_namespace;
	}

	/**
	 *
	 * @param int|null $talk_namespace
	 * @return void
	 */
	public function set_talk_namespace($talk_namespace) {
		$this->talk_namespace = $talk_namespace;
	}
}