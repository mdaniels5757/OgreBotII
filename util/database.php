<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $logger, $validator;

class DatabaseUtils {

	private static $connections;
	private static $properties;
	private static $mycnf;
	
	private static function init() {
		global $logger, $validator;
		
		$logger->debug("DatabaseUtils::init");
		self::$mycnf = parse_ini_file(BASE_DIRECTORY."/replica.my.cnf");
		
		log_property_data(self::$mycnf);
		
		$validator->assert(self::$mycnf);
		
		self::$properties = parse_ini_file(BASE_DIRECTORY."/properties/databases.properties");
		
		log_property_data(self::$properties);
	}
	
	private static function connect($db) {
		global $logger;
		
		$logger->debug("DatabaseUtils::connect($db)");	
		
		if (!self::$properties) {
			self::init();
		}
		
		$db_info = array_key_or_exception(self::$properties, $db);
		$connection_string = array_key_or_exception($db_info, 0);
		$db_string = array_key_or_exception($db_info, 1);
		
		$logger->trace("Connection: $connection_string; db: $db_string");
		
		self::$connections[$db] = 
			new mysqli($connection_string, 
						array_key_or_exception(self::$mycnf, 'user'),
						array_key_or_exception(self::$mycnf, 'password'),
						$db_string
					);	
	}
	
	public static function closeConnection($db) {
		global $logger;
		$logger->debug("DatabaseUtils::closeConnection($db)");
		
		self::$connections[$db]->close();
		unset(self::$connections[$db]);		
	}
	
	public static function call($db, $statement) {
		global $logger;
		
		if (!@self::$connections[$db]) {
			self::connect($db);
		}
		$db = self::$connections[$db];

		$result = $db->query($statement);
		
		$logger->debug($result->num_rows." rows found.");
		
		return $result;
	} 
}

$cc_by_40_db = DatabaseUtils::call("commons.wikimedia", 
				"select page_namespace, page_title 
					from categorylinks 
					left join page on cl_from = page_id 
						where cl_to =\"CC-BY-4.0\""); //by ref...
$cc_by_sa_40 = array();
while ($next = $cc_by_40_db->fetch_array()) {
	if ($next["page_namespace"] !== 6) {
		$cc_by_40[]= $next["page_title"];
	}
}

$cc_by_sa_40_db = DatabaseUtils::call("commons.wikimedia", 
				"select page_namespace, page_title 
					from categorylinks 
					left join page on cl_from = page_id 
						where cl_to =\"CC-BY-SA-4.0\"");

$cc_by_sa_40 = array();
while ($next = $cc_by_sa_40_db->fetch_array()) {
	if ($next["page_namespace"] !== 6) {
		$cc_by_sa_40[]= $next["page_title"];
	}
}

$all_cc_40 = array_unique(array_merge($cc_by_40, $cc_by_sa_40));

$logger->debug("\$all_cc_40 size: ".count($all_cc_40));

$migration_db = DatabaseUtils::call("commons.wikimedia",
		"select page_namespace, page_title
		from categorylinks
		left join page on cl_from = page_id
		where cl_to =\"License_migration_candidates\"");
$migration = array();
while ($next = $migration_db->fetch_array()) {
	if ($next["page_namespace"] !== 6) {
		$migration[]= $next["page_title"];
	}
}
$intersection = array_intersect($all_cc_40, $migration);
asort($intersection);

$logger->debug("\$intersection: ".count($intersection));

foreach ($intersection as $page) {
	echo "$page\n";
}