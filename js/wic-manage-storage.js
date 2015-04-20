/*
*
* wic-manage-storage.js
*
*/

// self-executing anonymous namespace
( function() {
	
	var controlsArray, showHideButtons, constituentSubFields;

	jQuery(document).ready(function($) { 

		$( "#wic-purge-progress-bar" ).progressbar({
			value: 0
		});
		
		// all the check boxes should change messages
		controlsArray =  jQuery ( ":input" ).not( ":button, :hidden" );		
		controlsArray.change ( function() {
			decideWhatToShow();
		});
		
		// set keep search when keep all changes
		jQuery ( "#keep_all" ).change ( function () {
			jQuery ( "#keep_search" ).prop( "checked" , jQuery ( "#keep_all" ).prop( "checked" ) )
		});
		
		// continue to show the section buttons, but knockout their onclick show/hide toggle function
		showHideButtons = jQuery ( ".field-group-show-hide-button" );
		showHideButtons.prop( "onclick", '' );
		
		constituentSubFields = jQuery ( "#keep_activity, #keep_phone, #keep_email, #keep_address" );
		constituentSubFields.prop( "disabled", true );		
		
		$( "#wic-form-manage-storage" ).submit( function( event ){ 							
			// on submit test if still showing stats -- if submit is good, will hide stats, so recursive call to submit will just go through
			if ( "none" != $( "#wp-issues-crm-stats" ).css( "display" ) ) {
				event.preventDefault();
				// if nothing unchecked, alert and go no further
				if ( jQuery ( "#keep_all" ).prop( "checked" ) && 
					jQuery ( "#keep_staging" ).prop( "checked" ) &&
					jQuery ( "#keep_search" ).prop( "checked" ) ) {
					alert ( 'Nothing selected to purge.' );	
				} else { 
					// if hasn't confirmed constituent purge, go no further
					if ( ! jQuery ( "#keep_all" ).prop ( "checked" ) && 'PURGE CONSTITUENT DATA' != jQuery( "#confirm" ).val().trim() ) {
						alert ( 'To delete constituent data, you must type out "PURGE CONSTITUENT DATA" in the confirmation field. Use all caps.' )									
					} else {
						// require confirmation to go further
						if ( confirm ( "Click OK to purge data.  This action cannot be undone." ) ) {
							$( "#wic-purge-progress-bar" ).progressbar ( "value", false );
							$( "#wp-issues-crm-stats" ).hide();
							$( "#manage_storage_button" ).text( "Purging . . ." );
							// need to spoof the button back into the form
							tempElement = $("<input type='hidden'/>");
							tempElement
								.attr ("name", "wic_form_button" )
								.val ( $( "#manage_storage_button" ).val() )
								.appendTo( "#wic-form-manage-storage" ); 
							console.log (tempElement); 
				  			$( "#wic-purge-progress-bar" ).show();
			  				$( "#wic-form-manage-storage" ).submit();
				  			tempElement.remove(); 
				  		}
			  		}
		  		}
		  	}
		}); 


	});

	
	function decideWhatToShow() { 
	
		var uploadMessage, searchMessage, allMessage, subFieldsMessage, fullMessage;

		if ( jQuery ( "#keep_all" ).prop( "checked" ) ) {
			// always show subfields as kept if keep all checked
			constituentSubFields.prop( "disabled", true );	
			constituentSubFields.prop( "checked", true );
			// allow modification of search purge if not purging constituents
			jQuery ( "#keep_search" ).prop( "disabled", false );
			constituentMessage = 'No constituents will be purged.';	
			jQuery ( "#post-form-message-box" ).removeClass( 'wic-form-errors-found' )
		} else {
			// if purging any constituents, always purge search log
			jQuery ( "#keep_search" ).prop( "disabled", true );
			jQuery ( "#keep_search" ).prop( "checked", false );
			
			// allow setting of constituent purge criteria and format message
			constituentSubFields.prop( "disabled", false );
			jQuery ( "#post-form-message-box" ).addClass( 'wic-form-errors-found' )
			subFieldsMessage = '';
			if ( jQuery ( "#keep_activity" ).prop( "checked" ) ) {
				subFieldsMessage	= " activity history"		
			}	
			if ( jQuery ( "#keep_email" ).prop( "checked" ) ) {
				if ( '' != subFieldsMessage ) {
					subFieldsMessage = subFieldsMessage + ' OR ';				
				} 
				subFieldsMessage	= subFieldsMessage  + " an email address";		
			}		
			if ( jQuery ( "#keep_phone" ).prop( "checked" ) ) {
				if ( '' != subFieldsMessage ) {
					subFieldsMessage = subFieldsMessage + ' OR ';				
				} 
				subFieldsMessage	= subFieldsMessage  + " a phone number";		
			}		
			if ( jQuery ( "#keep_address" ).prop( "checked" ) ) {
				if ( '' != subFieldsMessage ) {
					subFieldsMessage = subFieldsMessage + ' OR ';				
				} 
				subFieldsMessage	= subFieldsMessage  + "  some physical address information";		
			}
			
			if ( '' != subFieldsMessage ) {
				constituentMessage= 'Purge will keep constituents that have ' + subFieldsMessage + ', but will purge ALL other constituents.';
			} else {
				constituentMessage = 'All constituents are selected and will be purged.';			
			} 
					
		}

		uploadMessage = jQuery ( "#keep_staging" ).prop( "checked" ) ? " keep upload history " : " purge upload history "
		searchMessage = jQuery ( "#keep_search" ).prop( "checked" ) ? " and keep search history. " : " and purge search history. "
		fullMessage = constituentMessage + ' Purge will ' + uploadMessage + searchMessage ;
		
		jQuery ( "#post-form-message-box" ).text( fullMessage );
	}		

})(); // end anonymous namespace enclosure