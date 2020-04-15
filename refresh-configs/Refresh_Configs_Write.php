<?php
class Refresh_Configs_Write {
	const DIRECTORY = "REL1_0/Configs";
	const EXTENSION = "cfg";
	private static $no_overwrite = ["de.wikipedia"];
	private static $no_delete = ["MDanielsBot-meta.wikimedia", "MDanielsBot",
		"MDanielsBotCommons", "MDanielsBotTest"
	];

	public function write($projects) {
		$projects = array_diff($projects, self::$no_overwrite);
		array_walk($projects,
			function ($project) {
				$data = <<<EOF
[config]
baseurl = "https://$project.org/w/api.php"
verbose = ""
EOF;
				if (preg_match("/^(.+)\.wikipedia$/", $project, $match)) {
					file_put_contents_ensure(
						BASE_DIRECTORY . "/" . self::DIRECTORY . "/MDanielsBot-$match[1].cfg", $data);
				} else {
					file_put_contents_ensure(
						BASE_DIRECTORY . "/" . self::DIRECTORY . "/MDanielsBot-$project.cfg", $data);
				}
			});
	}

	public function delete() {
		$files = array_filter(get_all_files_in_directory(BASE_DIRECTORY . "/" . self::DIRECTORY),
			function ($file) {
				$extension_with_dot = "." . self::EXTENSION;
				if (!str_ends_with($file, $extension_with_dot)) {
					return false;
				}

				$file = substr($file, 0, strlen($file) - strlen($extension_with_dot));
				if (array_search_callback(self::$no_delete, function ($no_delete) use ($file) {
					if ($no_delete[0] === "/") {
						return preg_match($no_delete, $file);
					}
					return str_ends_with($no_delete, $file);
				}, false)) {
					return false;
				}

				return true;
			});

		array_walk($files, function($file) {
			unlink(BASE_DIRECTORY . "/" . self::DIRECTORY . "/" . $file);
		});
	}
}
