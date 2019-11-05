<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_File_Importer implements Cleanup_Module {

	/**
	 * 
	 * @var string
	 */
	private $dont_modify_links_regex;
	
	/**
	 * 
	 * @var string[]
	 */
	private $langlinks;
	
	/**
	 *
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		list(
			"langlinks_regex" => $langlinks, 
			"no_modify_interwiki_prefixes_regex" => $no_modify_prefixes,
			"langlinks" => $this->langlinks
		) = $cleanup_package->get_constants();
		
		
		$this->dont_modify_links_regex = "/\s*:?\s*([a-z]|$no_modify_prefixes|$langlinks)\s*\:\s*\S/";
	}

	/**
	 * 
	 * @param Cleanup_Instance $ci
	 * @return string|NULL
	 */
	private function get_project(Cleanup_Instance $ci): ?string {
		foreach ($ci->get_page_parser()->__get("elements") as $unparsed_element) {
			
			if (preg_match("/<\!\-\-\s*This file was moved here using FileImporter from (?:https:)?" . 
					"\/\/([\w+\-]+)\.wikipedia\./u", $unparsed_element, $match)) {
				return $match[1];
			}
		}
		
		return null;
	}
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$lang = $this->get_project($ci);
		if ($lang) {
			$template = $ci->get_template(Cleanup_Shared::INFORMATION);
			if ($template) {				
				foreach(["description", "source", "date", "author", "other_versions", 
						"location", "other_fields", "permission"] as $field) {
					//update interwikis work
					$value = $template->fieldvalue($field);
					if ($value && mb_trim($value) && strpbrk($value, "{}") === false) {
						$last_offset = 0;
						preg_match_all("/\[\[([^\[\]]+)\]\]/", $value, $matches,
								PREG_OFFSET_CAPTURE | PREG_SET_ORDER, $last_offset);
						foreach (array_reverse($matches) as list(list($whole_link, $offset), list($link))) {
							list($target, $text, $etc) = array_pad(explode("|", $link, 3), 3, null);
							if (!$etc && !preg_match($this->dont_modify_links_regex, $target)) {
								$text = $text ?? $target;
								$value = substr($value, 0, $offset) . "[[:$lang:$target|$text]]" .
								substr($value, $offset + strlen($whole_link));
							}
						}
						$template->updatefield($field, $value);
					}
				}
				
				$additional = $template->fieldvalue("additional_information");
				if ($additional !== false && !mb_trim($additional)) {
					$template->removefield("additional_information");
				}
				
				$description = $template->fieldvalue("description");
				if ($description) {
					$has_langlinks = false;
					foreach (new Template_Iterator($description) as $desc_template) {
						if (array_search(ucfirst_utf8($desc_template->getname()), $this->langlinks) !== false) {
							$has_langlinks = true;
							break;
						}
					}
					//add language wrapper to description
					if (!$has_langlinks && mb_trim($description) && strpbrk($description, "{}") === false) {
						$number_prefix = strpos($description, "=") ? "1=": "";
						$description = preg_replace("/^(\s*)([\s\S]+?)(\s*)$/", 
								"$1{{{$lang}|{$number_prefix}$2}}$3", $description);
					}
					$template->updatefield("description", $description);
					
				}
				$ci->set_text($template->wholePage());
				
			}
			//remove Category ordered by date
			while ($bad_template = $ci->get_template("Category ordered by date")) {
				$ci->set_text(Cleanup_shared::remove_template_and_trailing_newline($bad_template));				
			}
			
			//remove redundant migration 
			$self_template = $ci->get_template("self");
			if ($self_template) {
				if ($self_template->fieldvalue("migration") === "redundant") {
					if ($this->self_template_has_license($self_template, "/^gfdl/i") && 
							$this->self_template_has_license($self_template, "/^cc-by(-sa)?-(?:[34]\.0(?:-migrated)?|all)$/i")) {
							$self_template->removefield("migration");
						$ci->set_text($self_template->wholePage(), false);
					}
				}
			}
			//remove date from PD-self
			$pd_self = $ci->get_template("PD-self");
			if ($pd_self && $pd_self->fieldisset("date")) {
				$pd_self->removefield("date");
				$ci->set_text($pd_self->wholePage(), false);
			}
			
			//top spacing
			$ci->preg_replace("/^(%%%MTONOPARSE_COMMENT0%%%\s*?\n)(?:\s*\n)+(==\s*{{\s*int\s*:\s*filedesc\s*}}\s*==)/", "$1$2", false);
		}
	}
	
	/**
	 * 
	 * @param Abstract_Template $template
	 * @param string $regex
	 * @return bool
	 */
	private function self_template_has_license(Abstract_Template $template, string $regex): bool {
		foreach ([1, 2, 3] as $i) {
			if (preg_match($regex, $template->fieldvalue($i)??"")) {
				return true;
			}
		}
		return false;
	}
	
}