<?php
class Cleanup_Auto_Editor {

	/**
	 *
	 * @var Cleanup_Base
	 */
	private $cleanup_base;

	/**
	 *
	 * @var Page
	 */
	private $noref_pg;

	/**
	 *
	 * @var Page
	 */
	private $nootrs_pg;

	/**
	 *
	 * @var Page
	 */
	private $del_pg;

	/**
	 *
	 * @var Wiki
	 */
	private $co2;

	/**
	 *
	 * @var string
	 */
	private $edit_summary_all_files_desc;

	/**
	 *
	 * @param Wiki $co2
	 * @param string $edit_summary_all_files_desc
	 */
	public function __construct(Wiki $co2, $edit_summary_all_files_desc) {
		global $logger, $wiki_interface;

		$logger->debug("Starting up...");
		$this->cleanup_base = new Cleanup_Base();

		$this->noref_pg = $wiki_interface->new_page($co2,
			"User:MDanielsBot/Pages moved to Commons with missing information");
		$this->nootrs_pg = $wiki_interface->new_page($co2,
			"User:MDanielsBot/Pages moved to Commons with missing OTRS templates");
		$this->del_pg = $wiki_interface->new_page($co2,
			"User:MDanielsBot/Pages moved to Commons with possible licensing issues");
		$this->co2 = $co2;
		$this->edit_summary_all_files_desc = $edit_summary_all_files_desc;

		$logger->debug("Startup finished");
	}

	/**
	 *
	 * @param string $title
	 * @param string $text
	 * @param mixed $categories
	 *        	currently unused
	 * @param string $upload_date_millis
	 * @return boolean
	 */
	public function process($title, $text, $categories, $upload_date_millis) {
		global $logger, $wiki_interface;

		$made_change = false;
		do {
			$thistime = time();
			$logger->debug("Autoprocessing $title");
			$cleanup_response = $this->cleanup_base->super_cleanup($text,
				date("YmdHis", $upload_date_millis), false);
			$newtext = $cleanup_response->get_text();

			$time_to_process = time() - $thistime;

			if ($time_to_process > 5) {
				$logger->error("$title took $time_to_process seconds to process.");
			}
			$editsummary = "";
			if ($cleanup_response->get_significant_changes()) {
				$editsummary = "[[User:MDanielsBot/2b|clean up]]";
			}

			if (0) { // !$categories) {
				if (!$editsummary && preg_replace("/(?<!\r)\n/u", "\r\n", $text) !==
					 preg_replace("/(?<!\r)\n/u", "\r\n", $newtext)) {
					$editsummary = "minor cleanup";
				}
				if ($editsummary) {
					$editsummary .= " and ";
				}
				$editsummary .= "marking as [[:Category:Media needing categories|" .
					 "uncategorized]]";
				$newtext .= "\r\n\r\n{{Uncategorized|year=" . date("Y", $upload_date_millis) .
					 "|month=" . date("F", $upload_date_millis) . "|day=" .
					 date("j", $upload_date_millis) . "}}";
			}

			if ($editsummary !== "") {
				$pg = $wiki_interface->new_page($this->co2, $title);
				if ($text !== $wiki_interface->get_page_text($pg, true)) {
					// edit conflict?
					$text = $wiki_interface->get_page_text($pg, false);
					$logger->debug("(EDIT CONFLICT)");
					continue;
				}
				$revisions = $pg->history(5000);
				array_shift($revisions);

				$logger->info("Editing $title");
				$wiki_interface->edit_suppress_exceptions($pg, $newtext,
					"([[Commons:Bots/Requests/MDanielsBot_2|Bot Test]]): $editsummary of " . $this->edit_summary_all_files_desc, true, true,
					false, false, false);
				$made_change = true;
			}
			$warnings = $cleanup_response->get_warnings();

			if ((array_remove($warnings, Cleanup_Shared::OTRS_REMOVED))) {
				$logger->info("($title - logging no OTRS transclusion... ");
				$newlink = "\n*[[:$title]]";
				$text = $wiki_interface->get_page_text($this->nootrs_pg);
				if (strpos($text, $newlink) === false) {
					$wiki_interface->edit_suppress_exceptions($this->nootrs_pg, "\n*[[:$title]]",
						"(BOT): adding [[$title]]", true, true, false, "ap");
				} else {
					$logger->info("already logged, skipped)");
				}
			}
			if ((array_remove($warnings, Cleanup_Shared::LOCAL_DELETION))) {
				$logger->info("($title - logging possibly deleteable transclusion... ");

				$newlink = "\n*[[:$title]]";
				$text = $wiki_interface->get_page_text($this->del_pg);
				if (strpos($text, $newlink) === false) {
					$wiki_interface->edit_suppress_exceptions($this->del_pg, "\n*[[:$title]]",
						"(BOT): adding [[$title]]", true, true, false, "ap");
				} else {
					$logger->info("already logged, skipped)");
				}
			}
			if ($warnings) {
				$logger->info("($title - logging no cite replacement error)... ");

				$message = "\n*[[:$title]] - " .
					 implode("; ", $cleanup_response->get_formatted_warnings(true));
				$text = $wiki_interface->get_page_text($this->noref_pg);
				if (strpos($text, $message) === false) {
					$wiki_interface->edit_suppress_exceptions($this->noref_pg, $message,
						"(BOT): adding [[$title]]", true, true, false, "ap");
				} else {
					$logger->info("already logged, skipped)");
				}
			}
		} while (0);

		return $made_change;
	}
}
