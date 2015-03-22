/*
*
* wic-upload-validate.js
*
*/

// self-executing anonymous namespace
( function() {
	
	var uploadID, uploadParameters, chunkSize, chunkPlan, chunkCount, currentPassPointer, sortedIDs, startingMatchButtonText, matchingInProgress;

	jQuery(document).ready(function($) {
		
		uploadID	 			= 		jQuery( "#ID" ).val();
		uploadParameters 	=		JSON.parse ( jQuery( "#serialized_upload_parameters" ).val() )  
		// set chunk size at 1000 for larger files to avoid memory breaks; set lower in smaller files to achieve motion effect in the progress bar
		chunkSize 			= 		Math.min( 1000, Math.floor( uploadParameters.insert_count / 10 ) );	
		// make sure that chunksize doesn't floor to zero for small files
		chunkSize 			= 		Math.max( 1, chunkSize );
		// set chunkPlan = number of chunks to get
		chunkPlan 			= 		Math.ceil( uploadParameters.insert_count / chunkSize );
		// set a counter for number of times chunks called in recursion
		chunkCount			 = 	0;
		// pointer for array of match strategies 
		currentPassPointer = 	0;
		// array of sorted slugs (maintained only on button click);
		sortedIDs; // not initialized -- undefined
		// save starting translated text for main button 	
		startingMatchButtonText = 	$( "#match-button" ).text();
		// flag to test before unloading page
		matchingInProgress = 0;
	
		$( "#wic-upload-progress-bar" ).progressbar({
			value: 0
		});

		$( "ul.wic-sortable" ).sortable ( {
			connectWith: "ul",
			dropOnEmpty: true, 
			change: function( event, ui ) { // if user changes match order, make available for rematch and hide previous results
				jQuery( "#match-button" ).prop( "disabled", false );
				$( "#match-button" ).text( startingMatchButtonText );	
				chunkCount = 0
				currentPassPointer = 0;						
				jQuery( "#upload-results-table-wrapper" ).html( ' ' );
			} 
		});

  		$( "ul.wic-sortable" ).disableSelection();

		$("#match-button").click(function(){
			
			jQuery( "#match-button" ).prop( "disabled", true );
			jQuery( "ul.wic-sortable" ).sortable ( "disable" );
			sortedIDs = $( "#wic-match-list ul" ).sortable( "toArray" ); // populate the ID's array 
			matchingInProgress = 1;
			$( "#match-button" ).text( "Resetting . . ." );
			$( "#wic-upload-progress-bar" ).progressbar ( "value", false );
	  		$( "#wic-upload-progress-bar" ).show();
			jQuery( "#upload-results-table-wrapper" ).html( "<h3>Resetting match indicators. . . </h3>" );
			resetMatchIndicators( sortedIDs ); // note that reset callback includes invocation of validation 				  		
		}); 
		
    $(window).on('beforeunload', function() {
			if ( 1 == matchingInProgress ) {
				return ( 'initiated match run, but not completed');
			}   
      });
	});

	function resetMatchIndicators( sortedIDs ) {
		var data = {
			table: uploadParameters.staging_table_name,
			usedMatch: sortedIDs		
		}; 
		wpIssuesCRMAjaxPost( 'upload', 'reset_match',  uploadID, data, function( response ) {
			jQuery( "#upload-results-table-wrapper" ).html( "<h3>" + response +  "</h3>" );
			jQuery( "#match-button" ).text( "Matching . . ." );
			jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 0 );
	  		// start match at top of array ( undefined gets termination )
  			matchUpload();
		});
	}
	

	function matchUpload() {
		if ( undefined != sortedIDs[currentPassPointer] ) {
			// reset chunk count
			chunkCount			 = 0;
			// initiate next pass with offset 0
			matchUploadPass (0);
		} else { 
			// create the unmatched table
			jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", false );
			jQuery( "#match-button" ).text( "Analyzing . . ." );
			jQuery( "#upload-results-table-wrapper h3" ).text ( " . . . identifying unique values remaining unmatched after all passes." )
			analyzeUnmatched (); // after analysis, will close out processing;
		}
	}


	// function is recursive to keep going until all chunks processed
	// chunking match proces to support progress reporting and to limit array size on server
	function matchUploadPass ( offset ) {		

		var matchParameters = {
			"staging_table" : uploadParameters.staging_table_name,
			"offset" : offset,
			"chunk_size" : chunkSize,
			"working_pass" : 	sortedIDs[currentPassPointer] // always defined in this function	
		}
		
		wpIssuesCRMAjaxPost( 'upload', 'match_upload',  uploadID, matchParameters,  function( response ) {
			// calling parameters are: entity, action_requested, id_requested, data object, callback
			chunkCount++;
			jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 100 * chunkCount / chunkPlan );
			progressLegend = '<h3> . . . matched ' + ( chunkCount * chunkSize ).toLocaleString( 'en-US' )  + ' of ' + uploadParameters.insert_count.toLocaleString( 'en-IN' ) + ' records in current pass.</h3>'; 
			jQuery( "#upload-results-table-wrapper" ).html( progressLegend + response ); //  + progressLegend );
			if ( chunkCount < chunkPlan ) {
				matchUploadPass ( chunkCount * chunkSize );
			} else {
				// move to next pass or end
				currentPassPointer++;
				matchUpload();
			}
		});
	}
	
	function analyzeUnmatched () {		

		var matchParameters = {
			"staging_table" : uploadParameters.staging_table_name,
		}
		
		wpIssuesCRMAjaxPost( 'upload', 'create_unique_unmatched_table',  uploadID, matchParameters,  function( response ) {
			// calling parameters are: entity, action_requested, id_requested, data object, callback
			progressLegend = '<h3>Test match results for ' +  uploadParameters.insert_count.toLocaleString( 'en-US' ) + ' input records saved in staging table.</h3>';
			jQuery( "#upload-results-table-wrapper" ).html( progressLegend + response ); 
			// close out processing
			wpIssuesCRMAjaxPost( 'upload', 'update_upload_status',  uploadID, 'matched',  function( response ) {		
				jQuery( "#wic-upload-progress-bar" ).hide();
				jQuery( "ul.wic-sortable" ).sortable( "enable" );
				jQuery( "#match-button" ).text( "Matched" );	
				matchingInProgress = 0;
			});
		});
	}


})(); // end anonymous namespace enclosure