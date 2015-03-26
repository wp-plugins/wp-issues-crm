/*
*
* wic-upload-set-defaults.js
*
*/

// self-executing anonymous namespace
( function() {
	
	var uploadID, uploadParameters, columnMap, matchResults, defaultDecisions, saveHeaderMessage;

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
		// getting all form elements holding non-hidden values
		// note that other elements may become hidden as a result of logic, but array will remain constant
		controlsArray =  jQuery ( ":input" ).not( ":button, :hidden" );
		saveHeaderMessage = jQuery ( "#post-form-message-box" ).text();
	
		console.log ( 'uploadParameters:' );
		console.log ( uploadParameters );
		console.log ( 'columnMap' );
		console.log ( columnMap );
		console.log ( 'matchResults' );	
		console.log ( matchResults );
		console.log ( 'defaultDecisions' );
		console.log ( defaultDecisions );
		console.log ( controlsArray );
		populateForm();
		
		$( "#wic-upload-progress-bar" ).progressbar({
			value: 0
		});

  		$( "ul.wic-sortable" ).disableSelection();

		$("#settings-test-button").click(function(){
			recordDefaultDecisions()
			// jQuery( "#settings-test-button" ).prop( "disabled", true );
			$( "#wic-upload-progress-bar" ).progressbar ( "value", false );
	  		$( "#wic-upload-progress-bar" ).show();
		}); 
		
    $(window).on('beforeunload', function() {
      });
	});

	// populate displayed  orm values from hidden field;
	function populateForm() { 
		if ( ! jQuery.isEmptyObject ( defaultDecisions ) ) { 
			controlsArray.each ( function ( index ) {
				elementID 		= jQuery( this ).attr( "id" ) 
				// note that since selects do not have type attribute, cannot user ternary here (get undefined)
				if ( 'checkbox' == jQuery( this ).attr( "type" ) ) {
				 jQuery( this ).prop ( "checked", defaultDecisions[ jQuery( this ).attr( "id" ) ] )
				} else {
					jQuery( this ).val( defaultDecisions[ jQuery( this ).attr( "id" ) ]);
				}
			});
		}
	}
	
	function recordDefaultDecisions() {

		// first transfer form value to defaultDecisions object
		controlsArray.each ( function ( index ) {
			elementID 		= jQuery( this ).attr( "id" ) 
			// note that since selects do not have type attribute, cannot user ternary here (get undefined)
			if ( 'checkbox' == jQuery( this ).attr( "type" ) ) {
				elementValue 	= jQuery( this ).prop ( "checked" ); 
			} else {
				elementValue 	= jQuery( this ).val();
			}
			defaultDecisions[elementID] = elementValue;
		});

		jQuery ( "#post-form-message-box" ).text( saveHeaderMessage + " Saving . . . ")

		// update column map in browser

		// send column map on server
		wpIssuesCRMAjaxPost( 'upload', 'update_default_decisions',  jQuery('#ID').val(), defaultDecisions, function( response ) {
			jQuery ( "#post-form-message-box" ).text( saveHeaderMessage + " Saved.")
		});
	}		



})(); // end anonymous namespace enclosure