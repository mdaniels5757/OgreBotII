<?php
class CategoryFilesRunner {
	private $wiki;
	
	/**
	 *
	 * @param Wiki $wiki        	
	 */
	public function __construct(Wiki $wiki) {
		$this->wiki = $wiki;
	}
	
	/**
	 *
	 * @param string[] $pageNames        	
	 * @return CategoryTree
	 */
	public function runFromPageNames(array $pageNames) {
		global $logger, $validator;
		
		$validator->validate_arg_array($pageNames, "string");
		if (count($pageNames) == 0) {
			$logger->warn("Empty \$pageNames array");
		}
		
		$categoryTree = new CategoryTree();
		$this->run($pageNames, $categoryTree, 0);
		return $categoryTree;
	}
	
	/**
	 *
	 * @param number $starttime        	
	 * @param number $endtime        	
	 * @return CategoryTree
	 */
	public function runFromFileUploadDates($starttime, $endtime) {
		global $logger, $validator, $wiki_interface;
		
		$logger->info("runFromFileUploadDates $starttime $endtime");
		$validator->validate_arg($starttime, "numeric");
		$validator->validate_arg($endtime, "numeric");
		
		$all_uploads = $wiki_interface->get_recent_uploads_single_array($this->wiki, $starttime, 
			$endtime);
		// validate_arg_array($all_uploads, "array");
		if (count($all_uploads) == 0) {
			$logger->warn("Empty \$all_uploads array");
		}
		
		$fileNames = array_keys($all_uploads);
		
		return $this->runFromPageNames($fileNames);
	}
	
	/**
	 *
	 * @param string[] $pages        	
	 * @param CategoryTree $categoryTree        	
	 * @param int $recurse_level        	
	 * @throws WikiDataException
	 * @return void
	 */
	private function run(array $pages, CategoryTree &$categoryTree, $recurse_level) {
		global $logger, $constants, $wiki_interface;
		
		$max_category_query = array_key_or_exception($constants, 'maxcategoryquery');
		
		$count = count($pages);
		if (count($pages) === 0) {
			return;
		}
		
		/* graceful termination on bug. We will never reach this level unless there is an error. */
		if ($recurse_level === 85) {
			$logger->error(
				"Unable to complete full run. The following categories are still around.");
			$logger->error($pages);
			return;
		}
		
		/*
		 * place all pages into a giant query, so we can do it all at once.
		 * It will probably be more than $max_category_query, so break into multiple queries
		 */
		$nxt_query = array();
		for($pages_ptr = 0; $pages_ptr < $count; $pages_ptr += $max_category_query) {
			$logger->debug(
				"$pages_ptr $count $recurse_level." . ($pages_ptr / $max_category_query + 1) . " of " .
					 intval(($count - 1) / $max_category_query + 1));
			
			$page_str = implode("|", array_slice($pages, $pages_ptr, $max_category_query));
			$clcontinue = NULL;
			do {
				$vars = array('action' => 'query', 'titles' => $page_str, 'prop' => 'categories', 
					'cllimit' => 'max', 'redirects' => '');
				if ($clcontinue !== NULL) {
					$vars['clcontinue'] = $clcontinue;
				}
				
				$query = $wiki_interface->api_query($this->wiki, $vars, true);
				
				if (!is_array($query) || !array_key_exists("query", $query) ||
					 !array_key_exists("pages", $query['query'])) {
					throw new WikiDataException(
						"Bad API values: " . print_r($vars, true) . "=>" . print_r($query, true));
				}
				
				$logger->debug(".");
				
				foreach ($query['query']['pages'] as $pageid) {
					
					if (!array_key_exists('title', $pageid) || !is_string($pageid['title'])) {
						throw new WikiDataException(
							"Bad API values: " . print_r($vars, true) . "=>" . print_r($pageid, 
								true));
					}
					$page_title = $pageid['title'];
					
					/* uncategorized or missing pages have no categories */
					if (!array_key_exists("categories", $pageid) || !is_array($pageid["categories"])) {
						continue;
					}
					
					$cat_titles = array();
					foreach ($pageid["categories"] as $cat) {
						if (!array_key_exists("title", $cat)) {
							throw new WikiDataException("Bad API values: " . print_r($cat, true));
						}
						$cat_title = $cat['title'];
						
						if (!is_string($cat_title)) {
							throw new WikiDataException("Bad API value: " . print_r($cat_title, 
								true));
						}
						
						$cat_titles[] = $cat_title;
						
						if ($cat_title != $page_title && !$categoryTree->isPageStored($cat_title)) {
							$nxt_query[] = $cat_title;
						}
					}
					
					$categoryTree->addSuperCategories($page_title, $cat_titles);
				}
				$clcontinue = array_key_or_null($query, 'query-continue', 'clcontinue');
			} while ($clcontinue !== null);
		}
		
		$this->run(array_unique($nxt_query), $categoryTree, $recurse_level + 1);
	}
}
?>
