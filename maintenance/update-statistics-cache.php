<?php
if ( isset( $argv[0] ) && $argv[0] == "StatisticsCache.php" ) { // Called from commandline, update cache
	ini_set( "memory_limit", "256M" );
	echo( "Updating statistics cache\n" );
	$start = microtime( true );

	StatisticsCache::updatePathwaysCache();
	foreach ( Pathway::getAvailableSpecies() as $species ) {
		StatisticsCache::updateUniqueGenesCache( $species );
	}

	$time = ( microtime( true ) - $start );
	echo( "\tUpdated in $time seconds\n" );
}
