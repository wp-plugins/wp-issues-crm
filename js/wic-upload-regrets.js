/*
*
* wic-upload-regrets.js
*
*/

// self-executing anonymous namespace
( function() {
	
	var uploadID, backoutInProgress, uploadParameters;

	jQuery(document).ready(function($) {
		
	
		uploadID	 			= 		jQuery( "#ID" ).val();
		uploadParameters 	=		JSON.parse ( jQuery( "#serialized_upload_parameters" ).val() )  	
	
		$( "#wic-upload-progress-bar" ).progressbar({
			value: 0
		});

		$("#wic-backout-button").click(function(){
			jQuery( "#wic-backout-button" ).prop( "disabled", true );
			backoutInProgress = 1;
			$( "#wic-backout-button" ).text( "Backing out . . ." );
			$( "#wic-upload-progress-bar" ).progressbar ( "value", false );
	  		$( "#wic-upload-progress-bar" ).show();
			var data = {
				table: uploadParameters.staging_table_name
			}; 
			wpIssuesCRMAjaxPost( 'upload', 'backout_new_constituents', uploadID, data,  function( response ) {		
				jQuery( "#wic-upload-progress-bar" ).hide();
				jQuery( "#wic-backout-button" ).text( "Backout Complete" );	
				jQuery( "#backout_new_legend" ).text( "Backout of new constituents completed." );
				backoutInProgress = 0;
		  	});	
		}); 
		
	   $(window).on('beforeunload', function() {
			if ( 1 == backoutInProgress ) {
				return ( 'initiated backout, but not completed');
			}   
	   });
	});

})(); // end anonymous namespace enclosure