/*
*
* wic-upload-complete.js
*
*/

// self-executing anonymous namespace
( function() {
	
	var 	uploadID, uploadParameters, columnMap, matchResults, defaultDecision, finalResults, 
		 	chunkSize, chunkPlan, chunkCount, currentPhasePointer, currentPhaseArray, currentPhase, 
		 	totalMatched, totalUnmatched, uploadInProgress;

	jQuery(document).ready(function($) {
		

		// uploadID, upload parameters and column map populated on upload
		uploadID	 			= 		jQuery( "#ID" ).val();
		uploadParameters 	=		JSON.parse ( jQuery( "#serialized_upload_parameters" ).val() ) ; 
		columnMap			=		JSON.parse ( jQuery( "#serialized_column_map" ).val() ) ; 
		// match result should be populated at this stage if have already matched, 
		// but js error not prevented by form php logic if jump to this stage, so test for empty to avoid throwing error
		matchResults		=		jQuery( "#serialized_match_results" ).val() 		> '' ? 
			JSON.parse ( jQuery( "#serialized_match_results" ).val() ) : '' ;
		// will be unpopulated on first time through this stage -- need to handle further below
		defaultDecisions	= 		jQuery( "#serialized_default_decisions" ).val() > '' ? 
			JSON.parse ( jQuery( "#serialized_default_decisions" ).val() ) : {};	
		finalResults	= 		jQuery( "#serialized_final_results" ).val() > '' ? 
			JSON.parse ( jQuery( "#serialized_final_results" ).val() ) : {};					
		currentPhasePointer = 0;
		currentPhaseArray = [ 'save_new_issues', 'save_new_constituents', 'update_constituents' ];
		uploadInProgress = 0;

		// get totals for later use
		var totalMatched = 0;
		var totalUnmatched = 0;
		var insertCount = uploadParameters->insert_count;
		for ( var phase in matchResults ) {
			totalMatched += matchResults[phase].matched_with_these_components;
			totalUnmatched += matchResults[phase].unmatched_unique_values_of_components;					
		}


	
		$( "#wic-upload-progress-bar" ).progressbar({
			value: 0
		});

		$("#upload-button").click(function(){
			jQuery( "#upload-button" ).prop( "disabled", true );
			uploadInProgress = 1;
			$( "#upload-button" ).text( "Uploading . . ." );
			$( "#upload-game-plan").remove();
	  		$( "#wic-upload-progress-bar" ).show();
			jQuery( "#upload-results-table-wrapper" ).html( "<h3>Starting upload . . . </h3>" );
		}); 
		
    $(window).on('beforeunload', function() {
			if ( 1 == uploadInProgress ) {
				return ( 'upload in progress');
			}   
      });
	});


	function doUpload() {
		currentPhase = currentPhaseArray[currentPhasePointer];
		if ( undefined != currentPhase ) {
			// reset chunk count
			switch ( currentPhase ) {
				case "save_new_issues":
					$( "#wic-upload-progress-bar" ).progressbar ( "value", false );
					break;
				case "save_new_constituents":
					$( "#wic-upload-progress-bar" ).progressbar ( "value", 0 );			
					resetChunkParms ( totalUnmatched );
					break;
				case "update_constituents":	 			
					$( "#wic-upload-progress-bar" ).progressbar ( "value", 0 );
					resetChunkParms ( totalMatched + finalResults.matched_to_new_inserts );
					break;					
			}
			// initiate the recursion
			finalUploadPhase ( 0 );
		} else { 
			// wrap up!
			jQuery( "#wic-upload-progress-bar" ).hide();
			jQuery( "#upload-button" ).text( "Done" );	
			uploadInProgress = 0;
		}
	}


	// function is recursive to keep going until all chunks processed
	// chunking match proces to support progress reporting and to limit array size on server
	function finalUploadPhase ( offset ) {		

		var completeParameters = {
			"staging_table" : uploadParameters.staging_table_name,
			"offset" : offset,
			"chunk_size" : chunkSize,
			"phase" : 	currentPhase // always defined in this function	
		}
		var progressLegend = '';
		var totalToShow = '';
		
		wpIssuesCRMAjaxPost( 'upload', 'complete_upload',  uploadID, completeParameters,  function( response ) {
			// calling parameters are: entity, action_requested, id_requested, data object, callback
			// update global final results object			
			finalResults = response;
			switch ( currentPhase ) {
				case "save_new_issues":
					currentPassPointer++;
					doUpload();
					progressLegend = '<h3>Completed new issue insertion phase.</h3>'; 
					jQuery( "#upload-results-table-wrapper" ).html( progressLegend + layoutFinalResults()  ); 
					break;
				case "save_new_constituents":
				case "update_constituents":	 			
					chunkCount++;
					jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 100 * chunkCount / chunkPlan );
					totalToShow = ( currentPhase == 'save_new_constituents' ) ? totalUnmatched : finalResults.matched_to_new_inserts ;
					progressLegend = '<h3> . . . processed ' + ( chunkCount * chunkSize ).toLocaleString( 'en-US' )  
						+ ' of ' + totalToShow.toLocaleString( 'en-US' ) + ' records in ' + currentPhase + ' phase.</h3>'; 
					jQuery( "#upload-results-table-wrapper" ).html( progressLegend + layoutFinalResults() ); 
					if ( chunkCount < chunkPlan ) {
						finalUploadPhase ( chunkCount * chunkSize );
					} else {
						// move to next phase
						currentPassPointer++;
						doUpload();
					}
					break;
			}
		});
	}
	


	// set/reset global variables for new phase based on table size
	function resetChunkParms( totalRecords ){
		// set chunk size at 1000 for larger files to avoid memory breaks; set lower in smaller files to achieve motion effect in the progress bar
		chunkSize 			= 		Math.min( 1000, Math.floor( totalRecords / 10 ) );	
		// make sure that chunksize doesn't floor to zero for small files
		chunkSize 			= 		Math.max( 1, chunkSize );
		// set chunkPlan = number of chunks to get
		chunkPlan 			= 		Math.ceil( totalRecords / chunkSize );
		// set a counter for number of times chunks called in recursion
		chunkCount			 = 	0;
	}
	
	
	function layoutFinalResults () {
		var finalResultTable = '<table>';		
		for ( var finalResult in finalResults  ) { // the global final results array
			finalResultTable += '<tr><td class="wic-statistic-text">' + finalResult + '</td><td class = "wic-statistic">' + finalResults[finalResult] + ' </td></tr>'; 		
		}
		finalResultTable += '</table>';
		return ( finalResultTable );	
	}


})(); // end anonymous namespace enclosure