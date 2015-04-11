/*
*
* wic-upload-complete.js
*
*/

// self-executing anonymous namespace
( function() {
	
	var 	uploadID, uploadParameters, columnMap, matchResults, defaultDecision, finalResults, 
		 	chunkSize, chunkPlan, chunkCount, currentPhasePointer, currentPhaseArray, currentPhase, 
		 	insertCount, totalMatched, totalUnmatched, uploadInProgress;

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
		uploadInProgress = 0;
		
		// set up work phase array based on defaultDecisions
		currentPhaseArray = [];
		// if selected, plan to save new issues
		if ( defaultDecisions.create_issues ) { currentPhaseArray.push( 'save_new_issues' ); }		
		// note that default settings will not allow no save and no update -- otherwise, no action taken: will be doing one, the other or both
		// in case of saves, need to do updates afterwards so check whether doing straight updates or not in the update routine
		if ( defaultDecisions.add_unmatched ) { currentPhaseArray.push( 'save_new_constituents' ); }
 		// always do updates to complete saves; logic excludes non-saves		
		currentPhaseArray.push( 'update_constituents' );
		currentPhasePointer = 0;


		// get totals for later use
		totalMatched = 0;
		totalUnmatched = 0;
		insertCount = uploadParameters.insert_count;
		for ( var phase in matchResults ) {
			totalMatched += Number( matchResults[phase].matched_with_these_components );
			totalUnmatched += Number( matchResults[phase].unmatched_unique_values_of_components );
		}

		$( "#wic-upload-progress-bar" ).progressbar({
			value: 0
		});

		$("#upload-button").click(function(){
			jQuery( "#upload-button" ).prop( "disabled", true );
			// wpIssuesCRMAjaxPost( 'upload', 'update_upload_status',  uploadID, 'started',  function( response ) {});
			uploadInProgress = 1;
			$( "#upload-button" ).text( "Uploading . . ." );
			$( "#upload-game-plan").remove();
	  		$( "#wic-upload-progress-bar" ).show();
			jQuery( "#upload-results-table-wrapper" ).html( "<h3>Starting upload . . . </h3>" );
			doUpload();
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
					jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", false );
					// resetting chunk parms arbitrarily just so variables are defined -- these values are ignored in this phase, not chunked
					resetChunkParms ( 1000 );
					break;
				case "save_new_constituents":
					jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 0 );	
					// totalUnmatched should equal total number of records on unmatched version of staging_table
					resetChunkParms ( totalUnmatched );
					break;
				case "update_constituents":	
					jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 0 );
					// the update_constituents routine processes the staging table without any where clauses
					// so, need to go with the insertCount in setting the chunk plan, even if will bypass some records
					resetChunkParms ( insertCount );
					break;					
			}
			// initiate the recursion
			console.log ( 'doUpload initiating phase: ' + currentPhase );
			finalUploadPhase ( 0 );
		} else { 
			// wrap up!
			wpIssuesCRMAjaxPost( 'upload', 'update_upload_status',  uploadID, 'completed',  function( response ) {
				jQuery( "#wic-upload-progress-bar" ).hide();
				jQuery( "#upload-button" ).text( "Upload Completed" );	
				uploadInProgress = 0;
			});
		}
	}


	// function is recursive to keep going until all chunks processed
	// chunking match process to support progress reporting and to limit array size on server
	function finalUploadPhase ( offset ) {		

		var completeParameters = {
			"staging_table" : uploadParameters.staging_table_name,
			"offset" : offset,
			"chunk_size" : chunkSize,
			"phase" : 	currentPhase // always defined in this function	-- suffix of method name in class-wic-db-access-upload.php
		}

		var progressLegend = '';
		var totalToShow = 0;

		// calling parameters are: entity, action_requested, id_requested, data object, callback
		wpIssuesCRMAjaxPost( 'upload', 'complete_upload',  uploadID, completeParameters,  function( response ) {
			// update final results object with response			
			finalResults = response;
			// essentially doing three different flavors of the call back function, one for each phase; 
			switch ( currentPhase ) {
				case "save_new_issues":
					// update progress legend and partial results
					progressLegend = '<h3>Completed new issue insertion phase.</h3>'; 
					jQuery( "#upload-results-table-wrapper" ).html( progressLegend + layoutFinalResults()  ); 
					// new issues is single pass process, so move pointer to next phase when done
					currentPhasePointer++;
					doUpload();
					break;
				case "save_new_constituents":
					// always move chunk count and progress bar
					chunkCount++;
					jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 100 * chunkCount / chunkPlan );
					// if more to do, show interim legend and do recursion
					if ( chunkCount < chunkPlan ) {
						progressLegend = '<h3> . . . added ' + ( chunkCount * chunkSize ).toLocaleString( 'en-US' )  
							+ ' of ' + totalUnmatched.toLocaleString( 'en-US' ) + ' unique records in add new constituents phase.</h3>'; 
						jQuery( "#upload-results-table-wrapper" ).html( progressLegend + layoutFinalResults() ); 
						finalUploadPhase ( chunkCount * chunkSize );
					// otherwise show done legend and go to next phase
					} else {
						progressLegend = '<h3> Completed add of ' + totalUnmatched.toLocaleString( 'en-US' ) + ' unique records in add new constituents phase.</h3>'; 
						jQuery( "#upload-results-table-wrapper" ).html( progressLegend + layoutFinalResults() ); 
						// move to next phase
						currentPhasePointer++;
						doUpload();
					}	
					break;				
				case "update_constituents":	 			
					// always move chunk count and progress bar
					chunkCount++;
					jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 100 * chunkCount / chunkPlan );
					// if more to do, show interim legend and do recursion 
					if ( chunkCount < chunkPlan ) {
						progressLegend = '<h3> . . . processed ' + ( chunkCount * chunkSize ).toLocaleString( 'en-US' )  
							+ ' of ' + insertCount.toLocaleString( 'en-US' ) + ' records in staging table for possible updates.</h3>'; 
						jQuery( "#upload-results-table-wrapper" ).html( progressLegend + layoutFinalResults() );
						finalUploadPhase ( chunkCount * chunkSize );
					// otherwise show done legend and go to next phase						
					} else {
						progressLegend = '<h3>Completed all upload processing.</h3>'; 
						jQuery( "#upload-results-table-wrapper" ).html( progressLegend + layoutFinalResults() );
						// move to next phase
						currentPhasePointer++;
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