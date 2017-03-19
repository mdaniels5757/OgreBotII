<?php

/**
 * 
 * @author magog
 *
 */
class Simple_Lazy_Load_Storage implements Lazy_Load_Storage {
	
	/**
	 *
	 * @var array
	 */
	private $storage;
	
	/**
	 *
	 * @var callable
	 */
	private $get_object;
	
	/**
	 *
	 * @var callable
	 */
	private $on_init;
	
	/**
	 *
	 * @param callable $get_object        	
	 * @param callable|null $on_init        	
	 */
	public function __construct(callable $get_object) {
		$this->storage = [];
		$this->get_object = $get_object;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset) {
		if (isset($this->storage[$offset])) {
			return true;
		}
		
		$val = call_user_func($this->get_object, $offset);
		if ($val === null) {
			return false;
		}			
		$this->storage[$offset] = $val;
		return $val;
		
	}
	
	/**
	 *
	 * {@inheritDoc}	 *
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset) {
		if (!isset($this->storage[$offset])) {
			$val = call_user_func($this->get_object, $offset);
			if ($val === null) {
				throw new ArrayIndexNotFoundException($offset);
			}
			
			$this->storage[$offset] = $val;
			return $val;
		}
		return $this->storage[$offset];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value) {
		$this->storage[$offset] = $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset) {
		unset($this->storage[$offset]);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Lazy_Load_Storage::get_storage()
	 */
	public function get_storage() {
		return $this->storage;
	}
}