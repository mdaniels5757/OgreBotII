<?php
/**
 * A class for reading and writing migration-eligible templates
 * @author magog
 *
 */
class Migration_Template_Logic extends Infobox_Style_Template_Logic {
	

	/**
	 * 
	 * @var string[]
	 */
	private static $to_canonical;
	
	public function __construct() {
		if (self::$to_canonical === null) {
			$from_canonical = [
					"relicense" => ["complete"],
					"opt-out" => ["opt out"],
					"not-eligible" => ["not eligible"],
					"redundant" => [],
					"needs-review" => ["review", "needs review"]
			];
			
			self::$to_canonical = [];
			foreach ($from_canonical as $from => $to_array) {
				self::$to_canonical = array_merge(self::$to_canonical, 
					array_fill_keys($to_array, $from));
				self::$to_canonical[$from] = $from;
			}
		}
	}
	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Template_Logic::is_eligible()
	 */
	public function is_eligible(Template $template) {
		foreach ($template->__get("fields") as $field_name => $field_val) {
			if ($this->field_is_eligible($field_name)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Template_Logic::get_field_name_map()
	 */
	public function get_field_name_map($name, Template $template) {
		if ($this->field_is_eligible($name)) {
			return ["Migration", "migration"];
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Whitespace_Preserve_Template_Logic::read_value()
	 */
	public function read_value($value, $original_name, Template $template) {
		$val = $this->canonicalize(parent::read_value($value, $original_name, $template));
		return $val !== null ? $val : false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Whitespace_Preserve_Template_Logic::write_value()
	 */
	public function write_value($value, $original_name, Template $template) {
		global $logger;
	
		$value = $this->canonicalize(mb_trim(mb_strtolower($value)));
	
		if ($value === null) {
			$logger->warn(
				"Trying to set migration to $value; in value: $value; original name: $original_name");
			$value = "";
		}
		
		return parent::write_value($value, $original_name, $template);
	}
	
	private function canonicalize($in) {
		return @self::$to_canonical[mb_strtolower($in)];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Whitespace_Preserve_Template_Logic::is_add_carriage_return_new_field()
	 */
	protected function is_add_carriage_return_new_field() {
		return false;
	}
	
	/**
	 * 
	 * @param string $name
	 * @return boolean
	 */
	private function field_is_eligible($name) {
		return ucfirst(mb_trim($name)) === "Migration";
	}
}