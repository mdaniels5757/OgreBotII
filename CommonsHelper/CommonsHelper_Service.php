<?PHP

/**
 *
 * @author magog
 *        
 */
interface CommonsHelper_Service {
	
	/**
	 *
	 * @param string $image        	
	 * @param string $language        	
	 * @param string $project        	
	 * @param bool $remove_categories        	
	 * @return CommonsHelper_Get_Upload_Text_Response
	 */
	public function get_upload_text($image, $language, $project, $remove_categories);
}
