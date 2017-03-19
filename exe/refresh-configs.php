<?php
require_once __DIR__. "/../base/bootstrap.php";

$reader = new Refresh_Configs_Load();
$writer = new Refresh_Configs_Write();

$configs = $reader->load();
sort($configs);
$writer->delete();
$writer->write($configs);
