<?php

//If run from command line, update cache
if(isset($argv[0]) && $argv[0] == "DataSourcesCache.php") {
	echo("Updating datasources cache\n");
	$start = microtime(true);

	DataSourcesCache::update();

	$time = (microtime(true) - $start);
	echo("\tUpdated in $time seconds\n");
}
