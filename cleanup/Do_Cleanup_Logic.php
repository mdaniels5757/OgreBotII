<?php

class Do_Cleanup_Logic {
	
	/**
	 *
	 * @param array $post_data.
	 *        	Should have the following indices:
	 *        	'image' => the file name to cleanup
	 *        	'text' => the text for the filename. If not defined,
	 *        	then the logic will fetch the text of the page.
	 *        	
	 * @param callable $encodeLinkCallback
	 *        	a callback which returns a string
	 *        	when the link is passed to it.
	 *        	
	 * @return array an array with the following indices
	 *         'error" => (array of strings) if there was a retrival error. If there
	 *         are no errors, this field will not be set.
	 *         'warnings' => (array of strings) if there are warnings emitted by the
	 *         cleanup method. Will not be set if there are errors.
	 *         'text' => (string) the new text emitted by the cleanup method.
	 *         Will not be set if there are errors.
	 *         'edittime' => (string) Mediawiki timestamp for the most recent edit
	 *         to this file. Will not be set if there are errors.
	 *         'changes' => (string) indicates if the cleanup method has made any
	 *         changes whatsoever to the text. Will not be set if there are
	 *         errors. Has the following possible values: array
	 *         * "major"
	 *         * "minor"
	 *         * "none"
	 *        
	 */
	public static function get_cleanup_data($post_data, $encodeLinkCallback) {
		global $co, $logger, $messages, $validator, $wiki_interface;
		
		try {
			$validator->validate_arg_array($post_data, "string");
			$validator->validate_arg($encodeLinkCallback, "function");
			
			$logger->debug("get_cleanup_data");
			$logger->debug($post_data);
			$logger->debug($encodeLinkCallback);
			
			if (!array_key_exists('image', $post_data)) {
				return array("error" => "No file name specified");
			} else {
				if ($co === null) {
					$co = $wiki_interface->new_wiki("OgreBotCommons");
				}
				
				$title = $post_data['image'];
				$image = $wiki_interface->new_image($co, $title);
				$page = $image->get_page();
				if (@$post_data['text']) {
					$text = $post_data['text'];
				} else {
					$text = $wiki_interface->get_page_text($page);
					$categories_query = $wiki_interface->query_pages($co, array($title), 
						"categoriesnohidden");
					$categories_query_thispage = array_pop($categories_query);
					if (!array_key_exists('categories', $categories_query_thispage) ||
						 count($categories_query_thispage['categories']) === 0) {
						$text .= "\r\n\r\n{{subst:unc}}";
					}
				}
				
				$history = $wiki_interface->get_upload_history($image);
				if (count($history) > 0 && preg_match("/^\d{4}\-\d{2}\-\d{2}T\d{2}:\d{2}:\d{2}Z$/", 
					$history[count($history) - 1]['timestamp'], $earliest_timestamp)) {
					
					$cleanup_response = (new Cleanup_Base())->super_cleanup($text, 
						date("YmdHis", strtotime($earliest_timestamp[0])), TRUE);
					
					$cleanup_text = $cleanup_response->get_text();
					
					$warnings = $cleanup_response->get_formatted_warnings(false);
					
					if ($cleanup_response->get_significant_changes()) {
						$changes = "major";
					} else {
						$any_changes = str_replace("\r\n", "\n", $cleanup_text) !==
							 str_replace("\r\n", "\n", $text);
						$changes = $any_changes ? "minor" : "none";
					}
					$edit_time = preg_replace(
						"/^(\d{4})\-(\d{2})\-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/", "$1$2$3$4$5$6", 
						$page->get_lastedit());
					
					$default_summary = array_key_or_exception($messages, 'do_cleanup.editsummary');
					$summary = @$post_data['summary'];
					
					if ($changes !== "none") {
						if ($summary) {
							$summary = "$summary +$default_summary";
						} else {
							$summary = $default_summary;
						}
					}
					
					return ["changes" => $changes, "warnings" => $warnings, 
						"text" => $cleanup_text, "edittime" => $edit_time, "summary" => $summary];
				} else {
					$encodedTitle = $encodeLinkCallback($title);
					$validator->validate_arg($encodedTitle, "string");
					
					return [
						"error" => replace_named_variables("\$title not found!", 
							["title" => $encodedTitle])];
				}
			}
		} catch (Exception $e) {
			ogrebotMail($e);
			return ["error" => "Unknown error. The bot owner has been notified."];
		}
	}
}