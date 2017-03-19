<?PHP

/**
 *
 * @author magog
 *        
 */
class CommonsHelper_Factory {

	/**
	 *
	 * @var array[]
	 */
	private static $constants;
	
	/**
	 * 
	 */
	private static function _autoload() {
		self::$constants = load_property_file("transwiki");
	}
	
	/**
	 * @return array[]
	 */
	public static function get_constants() {
		return self::$constants;
	}
	
	/**
	 * 
	 * @return CommonsHelper_Dao
	 */
	public static function get_dao() {
		return new CommonsHelper_Dao_Impl();
	}


	/**
	 *
	 * @return CommonsHelper_Service
	 */
	public static function get_service() {
		return new CommonsHelper_Service_Impl();
	}
	
	/**
	 * 
	 * @return CommonsHelper_Debugger
	 */
	public static function get_debugger() {
		$debugger = Environment::prop("environment", "commonshelper.debugger");
		return new $debugger("new");
	}
}
?>

