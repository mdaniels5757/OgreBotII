<?php
interface CategoryTreeLogger {
	
	/**
	 * Called once before each run
	 * @param numeric $date
	 * @param ProjectData $project_data
	 * @return void
	 */
	public function init($date, ProjectData $project_data);
	
	/**
	 * Called once for each category.
	 * @param string $gallery_name
	 * @param string $category_name
	 * @param string[] $pages
	 * @return void
	 */
	public function log($gallery_name, $category_name, $pages);
	
	/**
	 * Called once after each run
	 * @return void
	 */
	public function complete();
}