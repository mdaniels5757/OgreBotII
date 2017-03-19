<?php
class CategoryTree {
	
	/**
	 *
	 * @var string[][]
	 */
	private $affiliations = [];
	
	/**
	 *
	 * @param string $page        	
	 * @param string[] $supercategories        	
	 * @return void
	 */
	public function addSuperCategories($page, $supercategories) {		
		$this->affiliations[$page] = $supercategories;
	}
	
	/**
	 * Queries whether a page has had its categories added (not
	 * whether the category is a subcategory of another category
	 * 
	 * @param string $page        	
	 * @return bool
	 */
	public function isPageStored($page) {
		return isset($this->affiliations[$page]);
	}
	
	/**
	 *
	 * @param Category_Files_Category $category        	
	 * @param string[] $blacklist_cats        	
	 * @param string|null $gallery_name
	 *        	DEFAULT null
	 * @param CategoryTreeLogger|null $logback
	 *        	DEFAULT null
	 * @throws WikiDataException
	 * @return string[]
	 */
	public function pagesInTree(Category_Files_Category $category, $blacklist_cats, $gallery_name = null, 
		CategoryTreeLogger $logback = null) {
		
		$logger = Environment::get()->get_logger();
		
		$blacklist_cats = array_flip($blacklist_cats);
		
		$categories_already_run = array();
		
		$hit_cats = array($category->category => $category->category);
		$recurse_level = 0;
		
		do {
			if ($recurse_level++ > $category->max_depth && $category->max_depth !== null) {
				$logger->debug("Stopping at depth $category->max_depth.");
				break;
			}
			
			if ($recurse_level > 200) {
				throw new WikiDataException("Recurse level exceed maximum.");
			}
			
			$pos_hit = false;
			foreach ($this->affiliations as $cat => $supercats) {
				if (!isset($hit_cats[$cat]) && !isset($blacklist_cats[$cat])) {
					foreach ($supercats as $supercat) {
						if (isset($hit_cats[$supercat])) {
							$hit_cats[$cat] = "$cat|$hit_cats[$supercat]";
							$pos_hit = true;
						}
					}
				}
			}
		} while ($pos_hit);
		
		if ($logback) {
			$logback->log($gallery_name, $category->category, $hit_cats);
		}
		
		return array_keys($hit_cats);
	}
}

?>
