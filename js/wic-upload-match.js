/*
*
* wic-upload-validate.js
*
*/

// self-executing anonymous namespace
( function() {
	
	var uploadID, uploadParameters, chunkSize, chunkPlan, chunkCount, currentPassPointer, sortedIDs;

	jQuery(document).ready(function($) {
		
		uploadID	 			= 		jQuery( "#ID" ).val();
		uploadParameters 	=		JSON.parse ( jQuery( "#serialized_upload_parameters" ).val() )  
		// set chunk size at 1000 for larger files to avoid memory breaks; set lower in smaller files to achieve motion effect in the progress bar
		chunkSize 			= Math.min( 1000, Math.floor( uploadParameters.insert_count / 10 ) );	
		// make sure that chunksize doesn't floor to zero for small files
		chunkSize 			= Math.max( 1, chunkSize );
		// set chunkPlan = number of chunks to get
		chunkPlan 			= Math.ceil( uploadParameters.insert_count / chunkSize );
		// set a counter for number of times chunks called in recursion
		chunkCount			 = 0;
		// pointer for array of match strategies 
		currentPassPointer = 0;
		// array of sorted slugs (maintained only on button click);
		sortedIDs; // not initialized -- undefined
	
		$( "#wic-upload-progress-bar" ).progressbar({
			value: 0
		});

		$( "ul.wic-sortable" ).sortable ( {
			connectWith: "ul",
			dropOnEmpty: true 
		});

  		$( "ul.wic-sortable" ).disableSelection();

		$("#match-button").click(function(){
			sortedIDs = $( "#wic-match-list ul" ).sortable( "toArray" ); // populate the ID's array 
			console.log ( sortedIDs ); 
			$( "#match-button" ).text( "Resetting . . ." );
			$( "#wic-upload-progress-bar" ).progressbar ( "value", false );
	  		$( "#wic-upload-progress-bar" ).show();
			jQuery( "#upload-results-table-wrapper" ).html( "<h3>Resetting validation indicators. . . </h3>" );
			resetMatchIndicators( sortedIDs ); // note that reset callback includes invocation of validation 				  		
		}); 
	});

	function resetMatchIndicators( sortedIDs ) {
		data = {
			table: uploadParameters.staging_table_name,
			usedMatch: sortedIDs		
		} 
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
			// close out processing
			progressLegend = '<h3> Mapped ' + uploadParameters.insert_count.toLocaleString( 'en-IN' ) + ' records -- done.</h3>';
			jQuery( "#upload-results-table-wrapper" ).html( response + progressLegend );
			wpIssuesCRMAjaxPost( '', 'update_upload_status',  uploadID, 'matched',  function( response ) {		
				jQuery( "#wic-upload-progress-bar" ).hide();
				jQuery( "#match-button" ).prop( "disabled", true );
				jQuery( "#match-button" ).text( "Matched" );	
			});
		}
	}


	// function is recursive to keep going until all chunks processed
	// chunking validation to support progress reporting and to limit array size on server
	function matchUploadPass ( offset ) {		

		var matchParameters = {
			"staging_table" : uploadParameters.staging_table_name,
			"offset" : offset,
			"chunk_size" : chunkSize,
			"working_pass" : 	sortedIDs[currentPassPointer] // always defined in this function	
		}
		
		wpIssuesCRMAjaxPost( '', 'match_upload',  uploadID, matchParameters,  function( response ) {
			// calling parameters are: entity, action_requested, id_requested, data object, callback
			chunkCount++;
			jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 100 * chunkCount / chunkPlan );
			if ( chunkCount < chunkPlan ) {
				progressLegend = '<h3> . . . validated ' + ( chunkCount * chunkSize ).toLocaleString( 'en-IN' )  + ' of ' + uploadParameters.insert_count.toLocaleString( 'en-IN' ) + ' records.</h3>'; 
				jQuery( "#upload-results-table-wrapper" ).html( response + progressLegend );
				validateUpload ( chunkCount * chunkSize );
			} else {
				// move to next pass or end
				currentPassPointer++;
				matchUpload();
			}
		});
	}
	


})(); // end anonymous namespace enclosure