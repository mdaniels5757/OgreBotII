<?php

class Now_Commons_Image {

	/**
	 *
	 * @var string
	 */
	public $commons_cleanup_link;

	/**
	 *
	 * @var string
	 */
	public $commons_edit_link;

	/**
	 *
	 * @var bool
	 */
	public $commons_exists;

	/**
	 *
	 * @var string
	 */
	public $commons_formatted_text;

	/**
	 *
	 * @var string
	 */
	public $commons_hash;

	/**
	 *
	 * @var number
	 */
	public $commons_height;

	/**
	 *
	 * @var string
	 */
	public $commons_listed_name;
	
	/**
	 * 
	 * @var number
	 */
	public $commons_preview_height;
	
	/**
	 * 
	 * @var number
	 */
	public $commons_preview_width;
	
	/**
	 *
	 * @var string
	 */
	public $commons_mime;

	/**
	 *
	 * @var number
	 */
	public $commons_size;

	/**
	 *
	 * @var string
	 */
	public $commons_text;

	/**
	 *
	 * @var string
	 */
	public $commons_thumb;

	/**
	 *
	 * @var string
	 */
	public $commons_title;

	/**
	 *
	 * @var Upload_History_Instance[]
	 */
	public $commons_upload_history;

	/**
	 *
	 * @var string
	 */
	public $commons_url;

	/**
	 *
	 * @var number
	 */
	public $commons_width;

	/**
	 *
	 * @var string[]
	 */
	public $errors = array();

	/**
	 *
	 * @var string
	*/
	public $local_delete_link;

	/**
	 *
	 * @var string
	*/
	public $local_delete_reason;

	/**
	 *
	 * @var string
	 */
	public $local_edit_link;

	/**
	 *
	 * @var bool
	 */
	public $local_exists;

	/**
	 *
	 * @var string
	 */
	public $local_fileinfo_link;

	/**
	 *
	 * @var string
	 */
	public $local_formatted_text;
	/**
	 *
	 * @var string
	 */
	public $local_hash;

	/**
	 * Only set if not the same name
	 * @var string[]
	 */
	public $local_links;

	/**
	 *
	 * @var number
	 */
	public $local_height;
	/**
	 *
	 * @var string
	 */
	public $local_mime;

	/**
	 * Only set if the local talk page exists and not the same name
	 * @var string
	 */
	public $local_move_talk_link;

	/**
	 *
	 * @var string
	 */
	public $local_now_commons_link;
	
	/**
	 * 
	 * @var number
	 */
	public $local_preview_height;
	
	/**
	 * 
	 * @var number
	 */
	public $local_preview_width;

	/**
	 *
	 * @var number
	 */
	public $local_size;

	/**
	 *
	 * @var string|null
	 */
	public $local_talk_link;

	/**
	 *
	 * @var string
	 */
	public $local_text;

	/**
	 *
	 * @var string
	 */
	public $local_title;

	/**
	 *
	 * @var string
	 */
	public $local_thumb;

	/**
	 *
	 * @var Upload_History_Instance[]
	 */
	public $local_upload_history;

	/**
	 *
	 * @var string
	 */
	public $local_uploaders;

	/**
	 *
	 * @var string
	 */
	public $local_url;

	/**
	 * Only set if the local talk page exists and the same name
	 * @var string
	 */
	public $local_view_talk_link;

	/**
	 *
	 * @var number
	 */
	public $local_width;

	/**
	 *
	 * @var string
	 */
	public $old_versions_link;

	/**
	 *
	 * @var string[]
	 */
	public $warnings = array();

}