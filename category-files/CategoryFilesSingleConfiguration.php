<?php
class CategoryFilesSingleConfiguration {
	
	/**
	 *
	 * @var array
	 */
	private static $GALLERY_DEFAULTS = ["subpage" => true, "warning" => false, "noIndex" => false, 
		"mode" => null, "daysPerGallery" => 11, "showFilename" => false];
	
	/**
	 *
	 * @var string
	 */
	private $hash;
	
	/**
	 *
	 * @var Category_Files_Category[]
	 */
	private $categories;
	
	/**
	 *
	 * @var string
	 */
	private $galleryname;
	
	/**
	 *
	 * @var int|null
	 */
	private $width;
	
	/**
	 *
	 * @var int|null
	 */
	private $height;
	
	/**
	 *
	 * @var boolean
	 */
	private $subpage;
	
	/**
	 *
	 * @var int|null
	 */
	private $daysPerGallery;
	
	/**
	 *
	 * @var string[]
	 */
	private $ignoredSubcats;
	
	/**
	 *
	 * @var boolean
	 */
	private $warning;
	
	/**
	 *
	 * @var boolean
	 */
	private $noIndex;
	
	/**
	 *
	 * @var string|null
	 */
	private $mode;
	
	/**
	 *
	 * @var bool
	 */
	private $show_filename;
	
	/**
	 *
	 * @param Category_Files_Category[] $categories        	
	 * @param string $galleryname        	
	 * @param int|null $width        	
	 * @param int|null $height        	
	 * @param boolean $subpage        	
	 * @param int|null $daysPerGallery        	
	 * @param string[] $ignoredSubcats        	
	 * @param boolean $warning        	
	 * @param boolean $noIndex        	
	 * @param string|null $mode        	
	 * @param boolean $showFilename
	 */
	public function __construct(array $categories, $galleryname, $width, $height,
			$subpage, $daysPerGallery, array $ignoredSubcats, $warning, $noIndex, $mode, $showFilename) {
		
		$validator = Environment::get()->get_validator();
		
		$validator->validate_arg_array($categories, Category_Files_Category::class);
		$validator->validate_arg($galleryname, "string");
		$validator->validate_arg($width, "numeric", true);
		$validator->validate_arg($height, "numeric", true);
		$validator->validate_arg($daysPerGallery, "integer", true);
		$validator->validate_arg_array($ignoredSubcats, "string");
		$validator->validate_arg($mode, "string", true);
		$validator->validate_args("bool", $subpage, $warning, $noIndex, $showFilename);
		
		$this->categories = $categories;
		$this->galleryname = $galleryname;
		$this->width = $width;
		$this->height = $height;
		$this->subpage = $subpage;
		$this->daysPerGallery = $daysPerGallery;
		$this->ignoredSubcats = $ignoredSubcats;
		$this->warning = $warning;
		$this->noIndex = $noIndex;
		$this->mode = $mode;
		$this->show_filename = $showFilename;
		
		$this->hash = hash("md5", print_r($galleryname, true));
	}
	
	/**
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}
	
	/**
	 *
	 * @return Category_Files_Category[]
	 */
	public function getCategories() {
		return $this->categories;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getGalleryname() {
		return $this->galleryname;
	}
	
	/**
	 *
	 * @return number|null
	 */
	public function getWidth() {
		return $this->width;
	}
	
	/**
	 *
	 * @return number|null
	 */
	public function getHeight() {
		return $this->height;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function getSubpage() {
		return $this->subpage;
	}
	
	/**
	 *
	 * @return number|null
	 */
	public function getDaysPerGallery() {
		return $this->$daysPerGallery;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function getIgnoredSubcats() {
		return $this->ignoredSubcats;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isWarning() {
		return $this->warning;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isNoIndex() {
		return $this->noIndex;
	}
	
	/**
	 *
	 * @return string|null
	 */
	public function getMode() {
		return $this->mode;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isShowFilename() {
		return $this->show_filename;
	}
	
	/**
	 * 
	 * @param int $date
	 * @return string
	 */
	public function get_subpage_for_date($date, $include_name = true) {
		if (!preg_match("/^(\d{4})(\d{2})(\d{2})/", $date, $match)) {
			throw new IllegalArgumentException("Can't match date: $date");
		}
		list(, $year, $month, $date) = $match;
		
		$subpage = $include_name ? $this->galleryname : "";
		if ($this->subpage) {
			$subpage .= "/" . $this->rangeText($year, $month, $date);
		}
		return $subpage;
	}
	
	/**
	 * 
	 * @param integer $year
	 * @param integer $month
	 * @param integer $date
	 * @param integer $count
	 * @return string
	 */
	public function get_subpage_overflow_for_date($year, $month, $date, $count) {
		Environment::get()->get_validator()->validate_args("positive", $year, $month, $date, $count);		
		
		$monthname = date('F', mktime(0, 0, 0, $month));		
		return "/$year $monthname " . ((int)$date) . "/Overflow $count";
	}
	
	/**
	 *
	 * @param number $year        	
	 * @param number $month        	
	 * @param number $date        	
	 * @return string
	 */
	private function rangeText($year, $month, $date) {
		global $logger, $validator;
		
		$year = intval($year);
		$month = intval($month);
		$date = intval($date);
		
		$validator->validate_args("positive", $year, $month, $date);
		
		/* range text */
		$monthname = date('F', mktime(0, 0, 0, $month));
		$daysPerGallery = $this->daysPerGallery;
		$daysThisMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$galleriesPerMonth = ceil($daysThisMonth / $daysPerGallery);
		
		$minDays = floor($daysThisMonth / $galleriesPerMonth);
		$maxDays = ceil($daysThisMonth / $galleriesPerMonth);
		
		$rangeEnd = 0;
		do {
			$rangeStart = $rangeEnd + 1;
			if (($daysThisMonth - $rangeEnd) % $maxDays == 0) {
				$rangeEnd += $maxDays;
			} else {
				$rangeEnd += $minDays;
			}
		} while ($rangeEnd < $date);
		
		$rangeText = ($rangeStart == 1 && $rangeEnd == $daysThisMonth) ? "" : (($rangeStart ==
			 $rangeEnd) ? " $rangeStart" : " $rangeStart-$rangeEnd");
		
		$rangeText = "$year $monthname$rangeText";
		$logger->debug("\$rangeText($daysPerGallery) => \"$rangeText\"");
		
		return $rangeText;
	}
	
	/**
	 *
	 * @param $line string        	
	 * @return CategoryFilesSingleConfiguration|null returns the CategoryFilesSingleConfiguration, or null if it can't be parsed
	 */
	private static function parse_line($line) {
		global $logger;
		
		$logger->trace($line);
		
		preg_match(
			"/^(\[\[\s*\:?\s*[^\[\]\|]*?\s*\]\](?:\s*\(depth\s*\=\s*\d+\))?\s*(?:,\s*\[\[(?:[^\[\]\|]*?)\]\]" .
				 "(?:\s*\(depth\s*\=\s*\d+\))?\s*)*)\|\s*\[\[\s*\:?\s*([^\[\]\|]*?)\s*\]\]\s*" .
				 "\|\s*(?:(\d*)\s*x\s*(\d*)(?![A-Z_]))?(\s*[A-Z_\-\"\(\)\d]+(?:\,\s*[A-Z_\-\"\(\)" .
				 "\d]+)*)?\s*((?:\|\s*\[\[(?:[^\[\]\|]*?)\]\]\s*)*)\|?\s*$/i", 
				$line, $cat);
		
		if ($cat) {
			preg_match_all("/\[\[\s*\:?\s*([^\[\]\|]*?)\s*\]\](?:\s*\(depth\s*\=\s*(\d+)\))?/", 
					$cat[1], $these_cats_matches, PREG_SET_ORDER);
			
			$these_cats = array_map(function(array $match) {
				$obj = new Category_Files_Category();
				$obj->category = $match[1];
				$obj->max_depth = @$match[2];				
				return $obj;
			}, $these_cats_matches);
			
			$ignored_subcats = preg_replace("/^\s*\[\[\s*\:?\s*([^\[\]\|]*?)\s*\]\]\s*/", "$1", 
				explode("|", $cat[6]));
			array_shift($ignored_subcats); /* remove first null element (PHP is weird) */
			
			$width = $cat[3];
			$height = $cat[4];
			
			/* unlike Javascript, it doesn't seem PHP will let me point to a method directly */
			$flags_by_key = map_array_function_keys(explode(",", $cat[5]), 
				(new ReflectionMethod(self::class, "parse_flag"))->getClosure());
			
			$flags_by_key = array_replace(self::$GALLERY_DEFAULTS, $flags_by_key);
			list($subpage, $warning, $noIndex, $mode, $daysPerGallery, $showFilename) = extract_array_params(
				$flags_by_key, array_keys(self::$GALLERY_DEFAULTS));
		}
		
		if (@$these_cats) {
			return new CategoryFilesSingleConfiguration($these_cats, $cat[2], $width ? $width : null, 
				$height ? $height : null, $subpage, $daysPerGallery, $ignored_subcats, $warning, 
				$noIndex, $mode, $showFilename);
		}
		
		ogrebotMail("Can't parse line: $line");
	}
	
	/**
	 *
	 * @param string $flag        	
	 * @return CategoryFilesSingleConfigurationp[]|null 
	 * 	If parseable, returns the first value is the string key, the second is the value being set.
	 *         If not parseable, returns null.
	 */
	private static function parse_flag($flag) {
		$flag = mb_trim($flag);
		if ($flag === "WARNING") {
			return ["warning", true];
		} else if ($flag === "NO_SUBPAGE") {
			return ["subpage", false];
		} else if ($flag == "NO_INDEX") {
			return ["noIndex", true];
		} else if (preg_match("/DAYS_PER_GALLERY\((\d+)\)/", $flag, $daysPerGalleryParse)) {
			$daysPerGallery = intval($daysPerGalleryParse[1]);
			return ["daysPerGallery", $daysPerGallery ? $daysPerGallery : 1];
		} else if (preg_match("/MODE\((\"?)([A-Za-z\-]+)\\1\)/", $flag, $modeParse)) {
			return ["mode", mb_strtolower($modeParse[2])];
		} else if ($flag === "SHOW_FILENAME") {
			return ["showFilename", true];
		} else if ($flag !== "") {
			ogrebotMail("Unrecognized gallery option: $flag");
		}
	}
	
	/**
	 *
	 * @param string $gallery_vars_txt        	
	 * @throws ParseException
	 * @return CategoryFilesSingleConfiguration[]
	 */
	private static function initFromText($gallery_vars_txt) {
		global $logger, $validator;
		
		$validator->validate_arg($gallery_vars_txt, "string");
		
		$allLines = read_configuration_page_lines($gallery_vars_txt);
		
		/* unlike Javascript, it doesn't seem PHP will let me point to a method directly */
		$affiliations = array_map_filter($allLines, 
			(new ReflectionMethod(self::class, "parse_line"))->getClosure());
		
		$logger->info(count($affiliations) . " galleries found.");
		$logger->debug($affiliations);
		
		$validator->validate_arg_array($affiliations, "CategoryFilesSingleConfiguration");
		return $affiliations;
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param string $pagename        	
	 * @return CategoryFilesSingleConfiguration[]
	 */
	public static function initCategoryFilesConfigurationsFromProperties(Wiki $wiki, $pagename) {
		global $logger, $validator, $wiki_interface;
		$validator->validate_arg($pagename, "string");
		
		$logger->info("Querying variables page: " . $wiki->get_base_url() . " " . $pagename);
		
		$gallery_vars_txt = $wiki_interface->get_text($wiki, $pagename)->text;
		
		return CategoryFilesSingleConfiguration::initFromText($gallery_vars_txt);
	}
	
	/**
	 *
	 * @param string $filename        	
	 * @throws CantOpenFileException
	 * @return CategoryFilesSingleConfiguration[]
	 */
	public static function initCategoryFilesConfigurationsFromLocalFile($filename) {
		global $logger, $validator;
		$validator->validate_arg($filename, "string");
		
		$logger->info("Initiating configuration from local properties: $filename");
		
		$text = file_get_contents_ensure($filename);
		
		return CategoryFilesSingleConfiguration::initFromText($text);
	}
}
?>
