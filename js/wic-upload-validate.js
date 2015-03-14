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
		console.log( 'chunkPlan:' + chunkPlan );
		chunkCount = 0;
	
		$( "#wic-upload-validate-progress-bar" ).progressbar({
			value: 0
		});

		$("#validate-button").click(function(){
			$( "#wic-upload-validate-progress-bar" ).progressbar ( "value", 0 );
	  		$( "#wic-upload-validate-progress-bar" ).show();
	  		// start validation at 0 offset
	  		validateUpload( 0 );
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
		console.log (validationParameters);
		wpIssuesCRMAjaxPost( '', 'validate_upload',  uploadID, validationParameters,  function( response ) {
			// calling parameters are: entity, action_requested, id_requested, data object, callback
			jQuery( "#validation-results-table" ).html( response );
			chunkCount++;
			console.log( 'chunkCount:' + chunkCount )
			jQuery( "#wic-upload-validate-progress-bar" ).progressbar ( "value", 100 * chunkCount / chunkPlan );
			if ( chunkCount < chunkPlan ) {
				validateUpload ( chunkCount * chunkSize );
			} else {
				jQuery( "#wic-upload-validate-progress-bar" ).hide();
				jQuery( "#validate-button" ).prop( "disabled", true );	
			}
			
		});
		
	};
	


})(); // end anonymous namespace enclosure