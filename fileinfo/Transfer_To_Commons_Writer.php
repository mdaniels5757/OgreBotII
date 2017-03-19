<?php
class Transfer_To_Commons_Writer {
	
	/**
	 *
	 * @var int
	 */
	const INCLUDE_AUTHOR_DATE = 1;
	
	/**
	 *
	 * @var int
	 */
	const INCLUDE_LICENSE = 2;
	
	/**
	 *
	 * @var int
	 */
	const INCLUDE_FIELDS = 4;
	/**
	 *
	 * @var ProjectData
	 */
	private $project_data;
	
	/**
	 *
	 * @var string
	 */
	private $title;
	
	/**
	 *
	 * @var string
	 */
	private $text;
	
	/**
	 *
	 * @var array
	 */
	private $imageinfos;
	
	/**
	 *
	 * @param ProjectData $project_data        	
	 * @param string $title        	
	 * @param string $text        	
	 * @param array $imageinfos        	
	 */
	public function __construct(ProjectData $project_data, $title, $text, array $imageinfos) {
		global $logger;
		
		$logger->info(
			"__construct($project_data, $title, string(" . strlen($text) . "), array[" .
				 count($imageinfos) . "])");
		
		$this->project_data = $project_data;
		$this->title = $title;
		$this->text = $text;
		$this->imageinfos = $imageinfos;
	}
	
	/**
	 *
	 * @param string $text        	
	 * @return string
	 */
	private function relink($text) {
		global $MB_WS_RE_OPT;
		
		/* parse wikilinks */
		preg_match_all("/\[\[$MB_WS_RE_OPT:?$MB_WS_RE_OPT(.+?)$MB_WS_RE_OPT\]\]/u", $text, $matches, 
			PREG_OFFSET_CAPTURE);
		
		for($i = count($matches[0]) - 1; $i >= 0; $i--) {
			$linktext = $matches[1][$i][0];
			
			// relink
			if (preg_match("/^(.+?)$MB_WS_RE_OPT\|$MB_WS_RE_OPT(.+?)$/u", $linktext, $match)) {
				$linktext = $this->project_data->formatPageLink($match[1], $match[2]);
			} else {
				$linktext = $this->project_data->formatPageLinkAuto($linktext);
			}
			
			// save results to string
			$text = substr($text, 0, $matches[0][$i][1]) . $linktext .
				 substr($text, $matches[0][$i][1] + strlen($matches[0][$i][0]));
		}
		
		return $text;
	}
	
	/**
	 *
	 * @param Template $tmp        	
	 * @param string $fieldname        	
	 * @return string
	 */
	private function information_template_field(Template $tmp, $fieldname) {
		$value = $tmp->information_style_fieldvalue($fieldname);
		if ($value === null) {
			$value = "";
		}
		$value = filter_templates($value);
		$value = $this->relink($value);
		$value = mb_trim($value);
		return $value;
	}
	
	/**
	 *
	 * @param int $options        	
	 * @return string
	 */
	public function write($options = 0) {
		global $MB_WS_RE_OPT, $logger, $validator;
		
		$validator->validate_arg($options, "int");
		
		$logger->info("write($options)");
		
		$authordate = !!($options & self::INCLUDE_AUTHOR_DATE);
		$license = !!($options & self::INCLUDE_LICENSE);
		$fields = !!($options & self::INCLUDE_FIELDS);
		
		$text = "";
		
		$project = $this->project_data->getProject();
		$lang = $this->project_data->getSubproject();
		if ($authordate || $license || $fields) {
			$uploadhistory = end($this->imageinfos);
			$user = sanitize($uploadhistory['user']);
			$authortext_anchor = "{{user at project|$user|$project|$lang}}";
			$authortext = $authordate ? $authortext_anchor : "";
		} else {
			$uploadhistory = [];
			$user = "";
			$authortext = "";
		}
		
		$licensetext = "";
		if ($license && $lang === "en" && $project === "wikipedia") {
			if (preg_match(
				"/\{\{\s*(?:[Gg](?:FDL|fdl)\-[Ss]elf|[Gg]FDL-self-no-disclaimers)\s*(?:\|\s*[Mm]igration\s*\=\s*([A-Za-z\-]+)\s*)?\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{GFDL-user-en-no-disclaimers|$user|migration=$match[1]}}";
			}
			if (preg_match(
				"/\{\{\s*(?:[Gg]FDL\-self\-(?:en|with\-disclaimers))\s*(?:\|\s*[Mm]igration\s*\=\s*([A-Za-z\-]+)\s*)?\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{GFDL-user-en-with-disclaimers|$user|migration=$match[1]}}";
			}
			if (preg_match(
				"/\{\{\s*(?:[Pp]D\-(?:[Ss]elf|SELF)|[Pp]d\-self|[Pp]dself)\s*(?:\|\s*[Dd]ate\s*\=\s*[^\|\}]+)?\}\}/", 
				$this->text)) {
				$licensetext .= "\n{{PD-user-en|$user}}";
			}
			if (preg_match("/\{\{\s*([Cc][Cc]\-(?:by\-[^\|\}]+|0|zero|sa))\}\}/", $this->text, 
				$match)) {
				$licensetext .= "\n{{" . "$match[1]}}";
			}
			if (preg_match(
				"/\{\{\s*([Gg]FDL(?:\-(?:no|with)\-disclaimers|\-en)?|[Gg]fdl)\s*(?:\|\s*[Mm]igration\s*\=\s*([A-Za-z\-]+)\s*)?\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{" . "$match[1]|migration=$match[2]}}";
			}
			if (preg_match("/\{\{\s*(?:[Pp]d\-old|[Pp]D\-old|[Pp]D\-old\-100)\s*\}\}/", $this->text)) {
				$licensetext .= "\n{{PD-old-100}}";
			}
			if (preg_match("/\{\{\s*[Pp][Dd]\-(?:[Oo]ld\-70|UK)\s*(\|[\S\s]*?)?\}\}/", $this->text)) {
				$licensetext .= "\n{{PD-old-70$match[1]}}";
			}
			if (preg_match("/\{\{\s*(?:[Pp][Dd]\-(?:US|us))\s*\}\}/", $this->text)) {
				$licensetext .= "\n{{PD-US}}";
			}
			if (preg_match(
				"/\{\{\s*(pd\-(?:us[^\}]+?|old\-(?!70|100)[^\}]+?|(?!us|uk|old|self)[\S\s]+?))\}\}/i", 
				$this->text, $match)) {
				$licensetext .= "\n{{" . "$match[1]}}";
			}
			if (preg_match(
				"/\{\{\s*(?:[Nn]oRightsReserved|[Nn]orightsreserved|[Nn]o[ _]+rights[ _]+reserved)\s*(\|[\S\s]*?)?\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{No rights reserved$match[1]}}";
			}
			if (preg_match(
				"/\{\{\s*(?:[Cc]opyrighted[ _]+free[ _]+use|[Cc]opyrightedFreeUse)\s*(\|[\S\s]*?)?\s*\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{Copyrighted free use$match[1]}}";
			}
			if (preg_match(
				"/\{\{\s*[Mm]ultilicense[ _]+replacing[ _]+placeholder\s*(\|[\S\s]*?)?\s*\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{Multilicense replacing placeholder$match[1]}}";
			}
			if (preg_match(
				"/\{\{\s*[Mm]ultilicense[ _]+replacing[ _]+placeholder[ _]+new\s*(\|[\S\s]*?)?\s*\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{Multilicense[ _]+replacing[ _]+placeholder[ _]+new$match[1]}}";
			}
			if (preg_match(
				"/\{\{\s*(?:[Cc]opyrighted[ _]+free[ _]+use[ _]+provided[ _]+that|[Cc]opyrightedFreeUseProvidedThat)\s*(\|[\S\s]*?)?\s*\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{Copyrighted free use provided that$match[1]}}";
			}
			if (preg_match(
				"/\{\{\s*(?:[Aa]ttribution|[Cc]opyrightedFreeUseProvided)\s*(\|[\S\s]*?)?\s*\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{Copyrighted free use provided that$match[1]}}";
			}
			if (preg_match("/\{\{\s*(?:[Cc]opyrightedFreeUse\-Link)\s*(\|[\S\s]*?)?\s*\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{CopyrightedFreeUse-Link$match[1]}}";
			}
			if (preg_match("/\{\{\s*[Ff]ree\s+(?:software\s+)?screenshot\s*(\|[\S\s]*?)?\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{Free screenshot$match[1]}}";
			}
			if (preg_match("/\{\{\s*(?:[Ww]ikipedia(?:\-|[ _]+)screenshot)\s*(\|[\S\s]*?)?\s*\}\}/", 
				$this->text, $match)) {
				$licensetext .= "\n{{Wikipedia-screenshot$match[1]}}";
			}
			if (preg_match("/\{\{\s*(?:[Ss]elf2?)\s*(\|[^\}]+)\}\}/", $this->text, $match)) {
				$licensetext .= "\n{{self|author=$authortext_anchor$match[1]}}";
			}
			
			/* header */
			if ($licensetext != "") {
				$licensetext = "\n\n== {{int:license-header}} ==$licensetext";
			}
		}
		
		$descriptiontext = "";
		$descriptionfield = "";
		if ($fields) {
			
			$tmp = new Template(
				"{{" . (preg_replace("/\|" . $MB_WS_RE_OPT . "[Oo]ther\s+versions\s*\=/u", 
					"other_versions", $this->text)) . "}}", "information");
			
			$descriptionfield = $this->information_template_field($tmp, "description");
			$sourcefield = $this->information_template_field($tmp, "source");
			$datefield = $this->information_template_field($tmp, "date");
			$authorfield = $this->information_template_field($tmp, "author");
			$permissionfield = $this->information_template_field($tmp, "permission");
			$otherversionsfield = $this->information_template_field($tmp, "other_versions");
			$locationfield = $this->information_template_field($tmp, "location");
			if ($locationfield) {
				$descriptionfield = $descriptionfield ? "$locationfield\n$descriptionfield" : $locationfield;
			}
			
			// grab everything that isn't a header, category, or in a template
			$descriptiontext = filter_templates($this->text); // templates
			$descriptiontext = preg_replace(
				"/\[\[" . $MB_WS_RE_OPT . "category$MB_WS_RE_OPT:[\s\S]*?\]\]/ui", "", 
				$descriptiontext); // categories 1
			$descriptiontext = preg_replace(
				"/\{\{" . $MB_WS_RE_OPT . "defaultsort$MB_WS_RE_OPT:[\s\S]*?\}\}/ui", "", 
				$descriptiontext); // categories 2
			$descriptiontext = preg_replace("/^\=.*\=$MB_WS_RE_OPT$/umi", "", $descriptiontext); // headers
			$descriptiontext = $this->relink($descriptiontext);
			$descriptiontext = mb_trim($descriptiontext);
		}
		
		/* description */
		if ($descriptiontext && $descriptionfield) {
			$descriptiontext = "$descriptiontext\n$descriptionfield";
		} else {
			$descriptiontext = "$descriptiontext$descriptionfield";
		}
		if (stripos($descriptiontext, "=") !== FALSE) {
			$descriptiontext = "1=$descriptiontext";
		}
		if ($descriptiontext) {
			$descriptiontext = "{{" . "$lang|$descriptiontext}}";
		}
		
		/* source */
		if (@$sourcefield) {
			$sourcetext = $sourcefield;
		} else {
			$sourcetext = "{{transferred from|$lang.$project}}";
		}
		
		/* date */
		if (!is_empty(@$datefield)) {
			$datetext = $datefield;
		} else if ($authordate) {
			$datetext = "{{original upload date|" . substr($uploadhistory['timestamp'], 0, 10) . "}}";
		} else {
			$datetext = "";
		}
		
		/* author */
		if (!is_empty(@$authorfield)) {
			$authortext = $authorfield;
		}
		
		/* permission */
		$permissiontext = @$permissionfield;
		
		/* other_versions */
		$otherversionstext = @$otherversionsfield;
		
		$text = "== {{int:filedesc}} ==\n" . "{{Information\n" . "|Description=$descriptiontext\n" .
			 "|Source=$sourcetext\n" . "|Date=$datetext\n" . "|Author=$authortext\n" .
			 "|Permission=$permissiontext\n" . "|other_versions=$otherversionstext\n" .
			 "}}$licensetext";
		
		return $text;
	}
}