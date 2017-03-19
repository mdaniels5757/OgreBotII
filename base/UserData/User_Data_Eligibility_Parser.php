<?php

class User_Data_Eligibility_Parser {

	/**
	 * 
	 * @param ProjectData $projectData
	 * @param string $username
	 * @return bool
	 */
	public static function is_elevated_cleanup_rights(ProjectData $projectData, $username) {
		global $constants, $logger, $validator, $wiki_interface;
		
		$project_title = $projectData->getSubproject() . "." . $projectData->getProject();
		$logger->debug("tusc_user_is_elevated_cleanup_rights($project_title, $username)");
		
		$wiki = $projectData->getWiki();
		$user_data = UserData::getUserData($wiki, [$username], SECONDS_PER_DAY);
		
		$validator->validate_args_condition($user_data, "Array of size 1", count($user_data) === 1);
		
		/* @var $user_datum UserData */
		$user_datum = current($user_data);
		
		if ($user_datum->isAdmin()) {
			$logger->debug("Is administrator. Is eligible.");
			return true;
		}
		
		$eligiblity_page_name = array_key_or_exception($constants, "eligibility.page.cleanup.$project_title");
		
		$text = $wiki_interface->get_text($wiki, $eligiblity_page_name, true)->text;
		//$text = file_get_contents_ensure(ARTIFACTS_DIRECTORY. "/filestuff.txt");

		$override_usernames = read_configuration_page_lines($text);
		
		//Normalized username
		$username = $user_datum->getUsername();
		return in_array($username, $override_usernames);
	}	
}