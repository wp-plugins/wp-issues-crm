/*
*
* wic-upload-validate.js
*
*/

// self-executing anonymous namespace
( function() {
	
	var uploadID, uploadParameters, chunkSize, chunkPlan, chunkCount;

	jQuery(document).ready(function($) {
		
		uploadID	 			= 		jQuery( "#ID" ).val();
		uploadParameters 	=		JSON.parse ( jQuery( "#serialized_upload_parameters" ).val() )  
		// set chunk size at 1000 for larger files to avoid memory breaks; set lower in smaller files to achieve motion effect in the progress bar
		chunkSize = Math.min( 1000, Math.floor( uploadParameters.insert_count / 10 ) );	
		// make sure that chunksize doesn't floor to zero for small files
		chunkSize = Math.max( 1, chunkSize );
		// set chunkPlan = number of chunks to get
		chunkPlan = Math.ceil( uploadParameters.insert_count / chunkSize );
		// set a counter for number of times chunks called in recursion
		chunkCount = 0;
	
		$( "#wic-upload-progress-bar" ).progressbar({
			value: 0
		});

		$("#validate-button").click(function(){
			$( "#validate-button" ).text( "Resetting . . ." );
			$( "#wic-upload-progress-bar" ).progressbar ( "value", false );
	  		$( "#wic-upload-progress-bar" ).show();
			jQuery( "#upload-results-table-wrapper" ).html( "<h3>Resetting validation indicators. . . </h3>" );
			resetValidationIndicators(); // note that reset callback includes invokation of validation				  		
		}); 
	});

	
	// function is recursive to keep going until all chunks processed
	// chunking validation to support progress reporting and to limit array size on server
	function validateUpload( offset ){

		var validationParameters = {
			"staging_table" : uploadParameters.staging_table_name,
			"offset" : offset,
			"chunk_size" : chunkSize		
		}
		wpIssuesCRMAjaxPost( 'upload', 'validate_upload',  uploadID, validationParameters,  function( response ) {
			// calling parameters are: entity, action_requested, id_requested, data object, callback
			chunkCount++;
			jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 100 * chunkCount / chunkPlan );
			if ( chunkCount < chunkPlan ) {
				progressLegend = '<h3> . . . validated ' + ( chunkCount * chunkSize ).toLocaleString( 'en-IN' )  + ' of ' + uploadParameters.insert_count.toLocaleString( 'en-IN' ) + ' records.</h3>'; 
				jQuery( "#upload-results-table-wrapper" ).html( response + progressLegend );
				validateUpload ( chunkCount * chunkSize );
			} else {
				progressLegend = '<h3> Validated ' + uploadParameters.insert_count.toLocaleString( 'en-IN' ) + ' records -- done.</h3>';
				jQuery( "#upload-results-table-wrapper" ).html( response + progressLegend );
				wpIssuesCRMAjaxPost( 'upload', 'update_upload_status',  uploadID, 'validated',  function( response ) {		
					jQuery( "#wic-upload-progress-bar" ).hide();
					jQuery( "#validate-button" ).prop( "disabled", true );
					jQuery( "#validate-button" ).text( "Validated" );
				});		
			}
		});
	}
	
	function resetValidationIndicators() { 
		wpIssuesCRMAjaxPost( 'upload', 'reset_validation',  uploadID, uploadParameters.staging_table_name,  function( response ) {
			jQuery( "#upload-results-table-wrapper" ).html( "<h3>" + response +  "</h3>" );
			jQuery( "#validate-button" ).text( "Validating . . ." );
			jQuery( "#wic-upload-progress-bar" ).progressbar ( "value", 0 );
	  		// start validation at 0 offset
	  		validateUpload( 0 );
		});
	}


})(); // end anonymous namespace enclosure