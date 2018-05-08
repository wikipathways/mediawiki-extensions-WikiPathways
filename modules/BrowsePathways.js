function browsePathwaysSubmit() {
	return function() { $("#browsePathwayForm").submit(); };
}

$("#browseSelection select").change( browsePathwaysSubmit() );
$("#tagSelection select").change( browsePathwaysSubmit() );
$("#viewSelection select").change( browsePathwaysSubmit() );
