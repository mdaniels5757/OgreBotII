<?php
class Upload_History_Instance {

	/**
	 *
	 * @var string
	 */
	public $comment;
	
	/**
	 * 
	 * @var string
	 */
	public $hash;
	
	/**
	 * 
	 * @var int|null
	 */
	public $height;

	/**
	 *
	 * @var numeric|null
	 */
	public $revert;

	/**
	 * 
	 * @var int
	 */
	public $size;
	
	/**
	 * Human-readable timestamp
	 *
	 * @var string
	 */
	public $timestamp;

	/**
	 *
	 * @var bool
	 */
	public $unchanged;

	/**
	 *
	 * @var string
	 */
	public $url;

	/**
	 *
	 * @var string
	 */
	public $user;
	
	/**
	 * 
	 * @var int|null
	 */
	public $width;
	
	/**
	 *
	 * @param string[][] $image_info
	 * @return  Upload_History_Instance[]
	 */
	public static function read_from_wiki_image_info($image_infos) {
		$instances = array();
	
		$previous_sha = "Invalid SHA string";
		$image_infos = array_reverse($image_infos, false);
	
		foreach ($image_infos as $i => $image_info) {
			$instance = new Upload_History_Instance();
				
			$instance->comment = @$image_info["comment"];
			$instance->user = @$image_info["user"];
			$instance->timestamp = @$image_info["timestamp"];
			$instance->url = @$image_info["url"];
			$instance->size = @$image_info["size"];
			$instance->height = @$image_info["height"];
			$instance->width = @$image_info["width"];
			$instance->hash = @$image_info["sha1"];
				
			$instance->revert = false;
			if ($i > 0 && $instance->hash !== null && @$image_infos[$i - 1]['sha1'] === $instance->hash) {
				$instance->unchanged = true;
			} else {
				$instance->unchanged = false;
				for ($j = 0; $j < $i; $j++) {
					if ($instance->hash !== null && @$image_infos[$j]['sha1'] === $instance->hash) {
						$instance->revert = $j;
						break;
					}
				}
			}
				
			$instances[] = $instance;
		}
		return $instances;
	}
}