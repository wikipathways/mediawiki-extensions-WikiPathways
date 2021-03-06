<?php

require_once('search.php');

//Get commandline options
$o = getopt("s:f:m:");

//Get requested species
$species = $o["s"]; //'Zea mays'; //$_REQUEST['species'];

//Get output format(tab or html)
$outputFormat = $o["f"]; //'html'; //$_REQUEST['output'];

//Get id mapping preference(on or off)
$mappingPref = $o["m"]; //'true';// $o->["mapping"]; // 'on'; //$_REQUEST['mapping'];

//Try to use a cached file if possible
//Always use cached if present. Updated by weekly cron script.
$cacheFile = WPI_CACHE_DIR . "/wikipathways_data_$species.$outputFormat";
if($mappingPref == "off"){$cacheFile = WPI_CACHE_DIR . "/wikipathways_native_data_$species.$outputFormat";}

// all else...
generateContent($species); //Update cache
returnCached();

function returnCached() {
	global $cacheFile;
	//Redirect to cached url
	$url = WPI_CACHE_PATH . '/' . basename($cacheFile);
	ob_start();
	ob_clean();
	header("Location: $url");
	exit();
}

function generateContent($species) {
	global $outputFormat, $cacheFile;

	$fh = fopen($cacheFile, 'w');

	error_reporting(0);

	//The datasources to list in output file
	$datasourceList = array(
		"Entrez Gene",
		"Ensembl", //SPECIAL CASE (see below)
		"Uniprot/TrEMBL",
		"UniGene",
		"RefSeq",
		"MOD", //SPECIAL CASE (see below)
		"PubChem",
		"CAS",
		"ChEBI",
	);


	// Print header
	//NOTE: Model Organism Databases = HUGO, MGI, RGD, ZFIN, FlyBase, WormBase, SGD
	if ($outputFormat =='html'){
		$sysCols = '';
		foreach($datasourceList as $s) {
			$sysCols .= "<TD>$s</TD>";
		}

		fwrite($fh, "<html><table border=1 cellpadding=3>
		<tr bgcolor=\"#CCCCFF\" font><td>Pathway Name</td><td>Organism</td><td>Gene Ontology</td><td>Url to WikiPathways</td><td>Last Changed</td><td>Last Revision</td><td>Author</td><td>Count</td>$sysCols</tr>\n");

	} elseif ($outputFormat == 'excel'){
		//TODO (see Pear module for spreadsheet writer)
		fwrite($fh, "Not available yet...\n");
	} else {
		$sysCols = '';
		foreach($datasourceList as $s) {
			$sysCols .= "\t$s";
		}
		//print header
		fwrite($fh, "Pathway Name\tOrganism\tGene Ontology\tUrl to WikiPathways\tLast Changed\tLast Revision\tAuthor\tCount$sysCols\n");
	}

	// Loop through all species, if requested
	// THIS DOESN'T WORK: TAKES TOO LONG?
	//if ($species == 'All') {
	//	foreach(Pathway::getAvailableSpecies() as $species) {
	//		processPathways($species, $fh, $datasourceList);
	//	}
	//} else {
	processPathways($species, $fh, $datasourceList);
	//}

	//Print footer
	if ($outputFormat =='html'){
		fwrite($fh, "</table></html>");
	} elseif ($outputFormat == 'excel'){
		//TODO
	} else {

	}

	fclose($fh);
}

function processPathways($species, $fh, $datasourceList) {
	global $outputFormat, $cacheFile, $mappingPref;

	$pathwayList = Pathway::getAllPathways($species);

	//Stores looked up user names (key is user id)
	$users = array();

	foreach ($pathwayList as $pathway) {

		//Exclude unwanted pathways
		$page_id = $pathway->getPageIdDB();
		if (in_array($page_id, CurationTag::getPagesForTag('Curation:Tutorial'))) continue;
		if (in_array($page_id, CurationTag::getPagesForTag('Curation:ProposedDeletion'))) continue;
		if (in_array($page_id, CurationTag::getPagesForTag('Curation:Stub'))) continue;
		if (in_array($page_id, CurationTag::getPagesForTag('Curation:InappropriateContent'))) continue;
		if (in_array($page_id, CurationTag::getPagesForTag('Curation:UnderConstruction'))) continue;
		//Exclude deleted and private pathways
		if($pathway->isDeleted() || !$pathway->isPublic()) continue;

		try {
			$modTime = $pathway->getGpmlModificationTime();
			$url = $pathway->getFullUrl();
			$pathwayName = $pathway->getName();
			$authorIds = MwUtils::getAuthors($pathway->getTitleObject()->getArticleID());
			$authors = array();
			foreach($authorIds as $id) {
				$name = $users[$id];
				if(!$name) {
					$name = User::newFromId($id)->getName();
					$users[$id] = $name;
				}
				$authors[] = $name;
			}
			$author = implode(', ', $authors);
			$lastRevision = $pathway->getLatestRevision();

			// Print pathways data
			if ($outputFormat =='html'){
				fwrite($fh, "<tr><td>".$pathwayName."</td><td>".$species."</td><td>&nbsp</td><td>".$url."</td><td>".$modTime."</td><td>".$lastRevision."</td><td>".$author."&nbsp</td><td>");
			}
			elseif ($outputFormat == 'excel'){
				//TODO
			}
			else {
				fwrite($fh, $pathwayName."\t".$species."\t\t".$url."\t".$modTime."\t".$lastRevision."\t".$author."\t");
			}

			//Count original datanodes
			try
				{
					$xrefs = $pathway->getUniqueXrefs();
				}
			catch (Exception $e)
				{
					// we can safely ignore exceptions
					// erroneous pathways simply won't get counted
				}
			$xrefCount = count($xrefs);

			// Print xref translations
			$datasourceXrefMap = array();
			$updatedDatasourceList = array();
			foreach($datasourceList as $s) {
				if($s === "Ensembl"){
					$s = DataSource::getEnsemblDatasource($species);
				}
				if($s === "MOD") {
					$list = DataSource::getModDatasources($species);
					if(count($list) > 0 ){
						//just take the first one here
						$s = $list[0];
					} else {
						//register a blank
						$datasourceXrefMap[$s] = ' ';
						continue;
					}
				}
				$code = DataSource::getCode($s);

				## id mapping = off
				if($mappingPref == "off"){
					try
						{
							$xrefs = $pathway->getUniqueXrefs();
						}
					catch (Exception $e)
						{
							// we can safely ignore exceptions
							// erroneous pathways simply won't get counted
						}

					$tmp = "";
					foreach ($xrefs as $xref){
						$id = $xref->getId();
						$db = $xref->getSystem(); # system name
						if ($db == $s){
							if ($id && $id != '' && $id != ' '){
								$tmp .= $id .',';
							}
						}
					}
					perlChop($tmp);
					$datasourceXrefMap[$s] = $tmp;
					$updatedDatasourceList[] = $s;
				} else {
					## id mapping = on
					try {
						$xrefList = PathwayIndex::listPathwayXrefs($pathway, $code, 'FALSE');
					} catch(Exception $e) {
						throw new WSFault("Receiver", "Unable to process request: " . $e);
					}

					$tmp = "";
					foreach($xrefList as $xref) {
						$tmp .= $xref . ',';
					}
					perlChop($tmp); //remove final comma from generated list
					$datasourceXrefMap[$s] = $tmp;
					$updatedDatasourceList[] = $s;
				}
			}
			//array_walk($datasourceXrefMap, 'perlChop');

			//Print gene content data
			if ($outputFormat =='html') {
				fwrite($fh, $xrefCount);
				foreach($updatedDatasourceList as $s) {
					//append with space character toprovide for empty cells in html table
					fwrite($fh, "<TD>{$datasourceXrefMap[$s]}&nbsp</TD>");
				}
				fwrite($fh, "</TR>");
			} elseif ($outputFormat == 'excel'){
				//TODO
			} else {
				fwrite($fh, $xrefCount);
				foreach($updatedDatasourceList as $s) {
					//append with space character toprovide for empty cells in html table
					fwrite($fh, "\t{$datasourceXrefMap[$s]}");
				}
				fwrite($fh, "\n");
			}

		} catch (Exception $e) {
			// we can safely ignore exceptions
			// erroneous pathways simply won't get processed
		}
	}

}

function perlChop(&$string){
	$endchar = substr("$string", strlen("$string") - 1, 1);
	$string = substr("$string", 0, -1);
	return $endchar;
}
