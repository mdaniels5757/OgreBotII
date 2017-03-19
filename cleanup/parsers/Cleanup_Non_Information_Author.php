<?php
/**
 * @author magog
 *
 */
class Cleanup_Non_Information_Author implements Cleanup_Module {
	
	/**
	 * 
	 * @var stirng
	 */
	private $langlink_regex;
	
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->langlink_regex = $cleanup_package->get_constants()["langlinks_regex"];
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$ci->iter_replace(
			"/(\|[Aa]uthor\s*\=\s*)\[\[\s*\:\s*(?:w[a-z]+\s*\:\s*)?($this->langlink_regex" .
				 ")\s*:User\s*:\s*([^\|\}\{\[\]]+)\s*\|\s*\\3\s*\]\] at \[(?:https?\:)?\/\/\\2\.(w[a-z]+)\.org\/? \\2\.\\4\]/u", 
				"$1{{user at project|1=$3|2=$4|3=$2}}");
		$ci->iter_replace(
			"/(\|[Aa]uthor\s*\=\s*{{\s*[Uu]ser[ _]+at[ _]+project\s*\|)\s*(?:1\s*\=\s*)?\s*\|/u", 
			"$1", false); /* not a major change (I think) */
	}
}