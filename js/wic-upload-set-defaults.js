/*
*
* wic-upload-set-defaults.js
*
*/

// self-executing anonymous namespace
( function() {
	
	var uploadID, uploadParameters, chunkSize, chunkPlan, chunkCount, currentPassPointer, sortedIDs, startingMatchButtonText, matchingInProgress;

	jQuery(document).ready(function($) {
		
		uploadID	 			= 		jQuery( "#ID" ).val();
		uploadParameters 	=		JSON.parse ( jQuery( "#serialized_upload_parameters" ).val() )  
	
		$( "#wic-upload-progress-bar" ).progressbar({
			value: 0
		});

		$( "ul.wic-sortable" ).sortable ( {
			connectWith: "ul",
			dropOnEmpty: true, 
			change: function( event, ui ) { // if user changes match order, make available for rematch and hide previous results
				jQuery( "#match-button" ).prop( "disabled", false );
				$( "#match-button" ).text( startingMatchButtonText );
				matchingInProgress = 1; // set in progress indicator if have changed lineup (operational effect is only to make form dirty) 	
				chunkCount = 0;
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



})(); // end anonymous namespace enclosure