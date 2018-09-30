<?php

/**
 * 
 * @author magog
 *
 */
class Cached_Lazy_Load_Storage implements Lazy_Load_Storage {
	
	/**
	 * 
	 * @var Lazy_Load_Storage
	 */
	private $storage;
	
	/**
	 * 
	 * @var int|null
	 */
	private $expiry;
	
	/**
	 * 
	 * @var string
	 */
	private $file;
	/**
	 * 
	 * @param callable $get_object
	 * @param string $file
	 * @param int|null $expiry
	 */
	public function __construct(callable $get_object, $file, $expiry = null) {
		$this->storage = new Simple_Lazy_Load_Storage(
			function ($offset) use ($get_object) {
				return $this->get_timed_data($offset, $get_object($offset));
			});
		$this->file = $file;
		$this->expiry = $expiry;
		$this->lazy_load();
	}
	
	
	/**
	 * 
	 * @param string $offset
	 * @param mixed $val
	 * @return Timed_Storage_Data
	 */
	private function get_timed_data($offset, $val) {
		$timed_data = new Timed_Storage_Data();
		$timed_data->data = $val;
		$timed_data->last_update = time();
		$this->storage->offsetSet($offset, $timed_data);
		$this->serialize_data();
		return $timed_data;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset) {
		return $this->storage->offsetExists($offset);		
	}	
	
	/**
	 *
	 * {@inheritDoc}	 
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset) {
		return $this->storage->offsetGet($offset)->data;
	}
	
	
	/**
	 *
	 * {@inheritDoc}
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value) {
		$this->get_timed_data($offset, $value);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset) {
		$this->storage->offsetUnset($offset);
		$this->serialize_data();
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see Lazy_Load_Storage::get_storage()
	 */
	public function get_storage() {
		throw new IllegalStateException("Not implemented");
	}
	
	/**
	 * @return void
	 */
	private function lazy_load() {
		global $logger;
		
		$file_content = file_exists($this->file) ? file_get_contents($this->file) : null;
			
		if ($file_content) {
			try {
				foreach (unserialize($file_content) as $key => $data) {
					if ($this->expiry === null || $data->last_update + $this->expiry > time()) {
						$this->storage->offsetSet($key, $data);
					}
				}
			} catch (Exception $e) {
				$logger->error($e);
			}
		}
	}
	
	/**
	 * @return void
	 */
	private function serialize_data() {
		global $logger;
	
		$serialized = serialize($this->storage->get_storage());
		$logger->debug("Writing timed data to $this->file: length " . strlen($serialized));
		try {
			file_put_contents_ensure($this->file, $serialized);
		} catch (CantOpenFileException $e) {
			$logger->error($e);
		}
	}
	
}