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
	
		// populate form with saved values or initialize
		populateForm();	

		// hide controls that don't make a difference and replace with messages	
		decideWhatToShow();	
	
		// listen to save all updates to default options
		controlsArray.change ( function() {
			recordDefaultDecisions();
			decideWhatToShow()		
		});	
	
		
		$( "#wic-upload-progress-bar" ).progressbar({
			value: 0
		});

		$("#settings-test-button").click(function(){
			jQuery ( "#post-form-message-box" ).removeClass ( "wic-form-errors-found" );	
			// jQuery( "#settings-test-button" ).prop( "disabled", true );
			$( "#wic-upload-progress-bar" ).progressbar ( "value", false );
	  		$( "#wic-upload-progress-bar" ).show();
		}); 
		
    $(window).on('beforeunload', function() {
      });
	});

	// populate displayed  orm values from hidden field;
	function populateForm() {
		// if user has already set defaults . . . 
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
		// if user has not set defaults, make some recommendations
		} else {
			jQuery ( "#update_matched" ).prop ( "checked", true );
			jQuery ( "#add_unmatched" ).prop ( "checked", true );
			jQuery ( "#protect_identity" ).prop ( "checked", true );
			jQuery ( "#activity_date" ).val ( "2011-01-01" );
		}
	}
	
	function decideWhatToShow() { 

		// first compute totals relevant for add/update fields
		var validMatched = 0;
		var validUnique  = 0;
		// 
		for ( var matchSlug in matchResults  ) { 
			validMatched +=  matchResults[matchSlug].matched_with_these_components;
			validUnique  +=  Number( matchResults[matchSlug].unmatched_unique_values_of_components ) ;
				// this field has type string because of ancestry as a literal ? for display purposes
		}	
		// the null case -- kill the form
		if ( 0 == validMatched && 0 == validUnique ) {
			jQuery ( "#post-form-message-box" ).text ( "No new or matched records to upload -- revisit prior steps." );
			jQuery ( "#post-form-message-box" ).addClass ( "wic-form-errors-found" );			
			jQuery ( ":input" ).prop ( "disabled", true );
		// if no matched constituents, disable update choices
		} else if ( 0 == validMatched ) {
			jQuery ( "#update_matched" ).prop ( "checked", false );
			jQuery ( "#protect_identity" ).prop ( "checked", false );
			jQuery ( "#update_matched" ).prop ( "disabled", true );
			jQuery ( "#protect_identity" ).prop ( "disabled", true );
		// if nothing to add, disable the add choice
		} else if ( 0 == validUnique ) {
			jQuery ( "#add_unmatched" ).prop ( "checked", false );
			jQuery ( "#add_unmatched" ).prop ( "disabled", true );
		}
		
		// enable disable protect identity, depending on whether doing updates to matched.
		if ( jQuery ( "#update_matched" ).prop( "checked" ) ){
			jQuery ( "#protect_identity" ).prop ( "disabled", false );
		} else {
			jQuery ( "#protect_identity" ).prop ( "disabled", true );		
		}		
		
		// hide defaults for fields that have already been mapped		
		// hide phone or email default type if phone or email not supplied.
		// show logic unnecessary because nothing will cause them to show within the form
		var phonePresent = false;
		var emailPresent = false;
		var issuePresent = false;
		var str = '';
		var hidegroup = ''; 
		for ( var inputColumn in columnMap  ) {
			str = columnMap[inputColumn].field;
			if ( undefined != str ) { 
				hideGroup =  '#wic-control-' + str.replace ( '-', '_' ); 
				jQuery( hideGroup ).hide();
			}	 
			if ( 'email_address' == columnMap[inputColumn].field ) {
				emailPresent = true;			
			}
			if ( 'phone_number' == columnMap[inputColumn].field ) {
				phonePresent = true;			
			}
			if ( 'issue' == columnMap[inputColumn].field ) {
				phonePresent = true;			
			}
		}
		if ( ! phonePresent ) {
			jQuery ( "#wic-control-phone-type" ).hide();			
		}
		if ( ! emailPresent ) {
			jQuery ( "#wic-control-email-type" ).hide();			
		}
		
		// show/hide title elements in constituent default field group based on whether inputs are hidden
		if ( 0 == jQuery( "#wic-field-group-constituent_default" ).find( ":input" ).not( ":button, :hidden" ).length ) {
			jQuery( "#wic-inner-field-group-constituent_default-toggle-button" ).hide();	
			jQuery( "#wic-inner-field-group-constituent_default p" ).hide();			
		} else {
			jQuery( "#wic-inner-field-group-constituent_default-toggle-button" ).show();	
			jQuery( "#wic-inner-field-group-constituent_default p" ).show();			
		}
		
		if ( '' < jQuery( "#issue" ).val() ) {
			jQuery( "#wic-control-post-title" ).hide() 		
		} else if ( ! issuePresent ) {
			jQuery( "#wic-control-post-title" ).show()
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