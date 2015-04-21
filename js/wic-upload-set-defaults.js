/*
*
* wic-upload-set-defaults.js
*
* note that this routine plays role in enforcement 
*
*/

// self-executing anonymous namespace
( function() {
	
	var columnMap, defaultDecisions, initialUploadStatus, matchResults, saveHeaderMessage, uploadID, uploadParameters;

	jQuery(document).ready(function($) {
		
		initialUploadStatus = $( "#initial-upload-status" ).text();

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
		saveHeaderMessage = jQuery ( "#post-form-message-base" ).text();	
	
		// populate form with saved values or initialize
		populateForm();	

		// on first time through, after populating form, set database values
		// necesary in case good to without change
		recordDefaultDecisions(); // updates serialized_default_decisions			
		decideWhatToShow(); // updates upload_status after analyzing form

		
		// listen to save all updates to default options
		controlsArray.change ( function() {
			recordDefaultDecisions();
			decideWhatToShow();
		});	
		
		// add zip code validation for default field
		
		//
		$("#settings-test-button").click(function(){
			jQuery ( "#post-form-message-box" ).removeClass ( "wic-form-errors-found" );	
			// jQuery( "#settings-test-button" ).prop( "disabled", true );
			$( "#wic-upload-progress-bar" ).progressbar ( "value", false );
	  		$( "#wic-upload-progress-bar" ).show();
		}); 

    $(window).on('beforeunload', function() {
      });
	});

	// populate displayed  form values from hidden field;
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
		}
	}
	
	function decideWhatToShow() { 

		/*
		* first compute totals relevant for add/update fields
		*/
		var validMatched = 0;
		var validUnique  = 0;
		// 
		for ( var matchSlug in matchResults  ) { 
			validMatched +=  matchResults[matchSlug].matched_with_these_components;
			validUnique  +=  Number( matchResults[matchSlug].unmatched_unique_values_of_components ) ;
				// this field has type string because of ancestry as a literal ? for display purposes
		}	
		
		/*
		* next enable/set defaults for add update choices
		*/
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
		
		
		/*
		*
		* Prepare messages based on field mapping and form choices
		*
		*/
		// drop prior set of messages		
		// message box will always be red or green -- error or good news
		jQuery( "#upload-settings-need-attention" ).remove();
		jQuery( "#upload-settings-good-to-go" ).remove();
		var commentsArray = [];
		var errorsArray = [];
		
		// if both matched and unmatched are unchecked, nothing will be uploaded
		if ( false === jQuery ( "#update_matched" ).prop ( "checked" ) && false === jQuery ( "#add_unmatched" ).prop ( "checked" ) ) {
			errorsArray.push ( 'You have specified that no records will be updated or added -- nothing to upload.')		
		}

		// hide defaults for fields that have already been mapped		
		// hide phone or email default type if phone or email not supplied.
		// show logic unnecessary because nothing will cause them to show within the form
		// generate messages if field values needed		
		var addressMapped = false;
		var phoneMapped 	= false;
		var emailMapped 	= false;
		var issueMapped 	= false;
		var titleMapped 	= false;
		var activityMapped = false;
		var activityIssueMapped = false;
		var issueMapped	= false;
		var issueTitleColumn = '';
		var issueContentColumn = '';
		var str = '';
		var hidegroup = ''; 
		for ( var inputColumn in columnMap  ) {
			// don't show defaults for fields that have already been mapped
			var mappedField = columnMap[inputColumn].field; 
			if ( undefined != mappedField ) { 
				hideGroup =  '#wic-control-' + mappedField.replace ( '_', '-' ); 
				jQuery( hideGroup ).hide();
			}
			// check what fields are completed	
			if ( 'address' == columnMap[inputColumn].entity ) {
				addressMapped = true; // any address field			
			} 
			if ( 'phone_number' == columnMap[inputColumn].field ) {
				phoneMapped = true;			
			}
			if ( 'email_address' == columnMap[inputColumn].field ) {
				emailMapped = true;			
			}
			if ( 'activity' == columnMap[inputColumn].entity ) {
				activityMapped = true; // any activity	field 		
			} 
			if ( 'issue' == columnMap[inputColumn].entity ) {
				issueMapped = true; // any issue field 		
			} 
			if ( 'issue' == columnMap[inputColumn].field ) {
				activityIssueMapped = true;			
			}
			if ( 'post_title' == columnMap[inputColumn].field ) {
				titleMapped = true; 
				issueTitleColumn = inputColumn;			
			}
			if ( 'post_content' == columnMap[inputColumn].field ) {
				issueContentColumn = inputColumn;			
			}
		}

		// addresses can be added entirely with defaults so we don't hide the address_type field
		// just enforcing the required rules for address if some elements supplied either by mapping or default
		if ( 	addressMapped 							|| // street address or any of the defaultable values could be mapped
				jQuery ( "#address_type" ).val() > '' || 
				jQuery ( "#city" ).val() > ''		||
				jQuery ( "#state" ).val() > '' 	||	
				jQuery ( "#zip" ).val() > '' 		
			) { 
			// error if address type blank and not hidden as previously mapped
			if ( '' == jQuery( "#address_type" ).val() && jQuery( "#address_type" ).is(':visible') ) {
				errorsArray.push ( 'Set an address type to upload address data.' )		
			}	
			// error if address city blank and not hidden as previously mapped
			if ( '' == jQuery( "#city" ).val() && jQuery( "#city" ).is(':visible') ) {
				errorsArray.push ( 'Set a city to upload address data.' )		
			}
		}
		// if phone number number not mapped, hide phone type default
		if ( ! phoneMapped ) {
			jQuery ( "#wic-control-phone-type" ).hide();		
		// if phone number mapped, error if phone_type blank ( and not hidden previously as mapped )	
		} else if ( '' == jQuery( "#phone_type" ).val() && jQuery( "#phone_type" ).is(':visible') ) {
			errorsArray.push ( 'Set a phone type to upload phone numbers.' )		
		}

		// email . . . same as phone
		if ( ! emailMapped ) {
			jQuery ( "#wic-control-email-type" ).hide();			
		} else if ( '' == jQuery( "#email_type" ).val() && jQuery( "#email_type" ).is(':visible') ) {
			errorsArray.push ( 'Set an email type to upload email addresses.' )		
		}
		
		if ( 	activityMapped 							|| // any activity field
				titleMapped								||    // content alone will not force additions, just warning
				jQuery ( "#activity_date" ).val() > '' || 
				jQuery ( "#activity_type" ).val() > ''	||
				jQuery ( "#pro_con" ).val() > '' ||	
				jQuery ( "#issue" ).val() > '' 			
			) { 
			if ( '' == jQuery( "#activity_date" ).val() && jQuery( "#activity_date" ).is(':visible') ) {
				errorsArray.push ( 'Set an activity date to upload activity data.' )		
			}	
			if ( '' == jQuery( "#activity_type" ).val() && jQuery( "#activity_type" ).is(':visible') ) {
				errorsArray.push ( 'Set an activity type to upload activity data.' )		
			}	
			// can supply either issue selection or offer a non-blank title
			if (  ! titleMapped && ! activityIssueMapped &&
				 	'' == jQuery( "#issue" ).val() ) {
				errorsArray.push ( 'Choose an issue to upload activity data.' )		
			}
			// if have mapped title and not overwritten, must consent to additions
			if (  titleMapped && ! activityIssueMapped &&
				 	'' == jQuery( "#issue" ).val() && 	
					! jQuery ( "#create_issues" ).prop ( "checked" )				 	
				 	) {			// not overriden by mapped issue choice
				errorsArray.push ( 'You must affirmatively accept New Issue Titles or change other settings.' )	;		
			}			
		}				

		// warning about ignoring content without titles
		if ( issueMapped && ! titleMapped ) {
			commentsArray.push( 'You have mapped issue content without mapping issue title.  The issue content column will be ignored.')			
		}
		
		
		// show/hide title elements in constituent default field group based on whether all group inputs are hidden
		if ( 0 == jQuery( "#wic-field-group-constituent, #wic-field-group-address, #wic-field-group-email, #wic-field-group-phone" ).find( ":input" ).not( ":button, :hidden" ).length ) {
			jQuery( "#wic-inner-field-group-constituent-toggle-button" ).hide();	
			jQuery( "#wic-inner-field-group-constituent p" ).hide();			
		} else {
			jQuery( "#wic-inner-field-group-constituent-toggle-button" ).show();	
			jQuery( "#wic-inner-field-group-constituent p" ).show();			
		}
		
		// show the issue creation option under following conditions:
		// activity_issue default showing and blank (i.e., not mapped and controlling), but title also mapped, so title default not showing
		// in this condition, available title column will be controlling the update -- need to show results (is required in validation)
		if ( !activityIssueMapped &&
				'' == jQuery( "#issue" ).val() &&
				titleMapped 
				) {
			jQuery ( "#wic-field-group-new_issue_creation" ).show();
			addIssueTable( issueTitleColumn, issueContentColumn );		
		} else { 
			jQuery ( "#wic-field-group-new_issue_creation" ).hide();
			jQuery ( "#create_issues" ).prop ( "checked", false );
		}

		// having set up form appropriately, decide whether to allow updates and what messages to show
		// if have already started or completed upload, disable all input
		if ( 'completed' == initialUploadStatus || 'started' == initialUploadStatus || 'reversed' == initialUploadStatus ) { 
			jQuery ( "#wic-form-upload-set-defaults :input" ).prop( "disabled", true ); 
			jQuery ( "#upload-settings-good-to-go" ).hide();
			jQuery ( "#upload-settings-need-attention" ).hide();
			jQuery ( "#post-form-message-box" ).addClass ( "wic-form-errors-found" );
		// elseif didn't kill form for record count reasons, show messages and set status accordingly
		} 	else if ( ! jQuery ( ":input" ).prop ( "disabled" ) ) { 		
			
			if ( errorsArray.length > 0 ) { 
				jQuery( "#post-form-message-box" ).append( '<ul id="upload-settings-need-attention"></ul>' );
				for ( var i in errorsArray ) {
					jQuery( "#upload-settings-need-attention" ).append( '<li>' +  errorsArray[i]  + '</li>' );
				}
				// if errors, bust back to status matched as if haven't been to default setting
				wpIssuesCRMAjaxPost( 'upload', 'update_upload_status',  uploadID, 'matched',  function( response ) {});		
			} else {
				commentsArray.push('Good to go!');
				jQuery( "#post-form-message-box" ).append( '<ul id="upload-settings-good-to-go"></ul>' );
				for ( var i in commentsArray ) {
					jQuery( "#upload-settings-good-to-go" ).append( '<li>' +  commentsArray[i]  + '</li>' );
				}
				// if no errors good to go to next stage 			
				wpIssuesCRMAjaxPost( 'upload', 'update_upload_status',  uploadID, 'defaulted',  function( response ) {});
			}		
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

		// add new issue count to object 	
		defaultDecisions['new_issue_count'] = jQuery( "#new-issue-table tr" ).length - 1 ;
	
		// set saving message
		jQuery ( "#post-form-message-base" ).text( saveHeaderMessage + " Saving . . . ")

		// send object to server
		wpIssuesCRMAjaxPost( 'upload', 'update_default_decisions',  jQuery('#ID').val(), defaultDecisions, function( response ) {
			jQuery ( "#post-form-message-base" ).text( saveHeaderMessage + " Saved.")
		});
	}		

	function addIssueTable( issueTitleColumn, issueContentColumn ) { 
		// if exist issue table return ( don't recreate on form refersh)
		if ( 0 < jQuery ( "#new-issue-table" ).length ) {
			return;
		} 
		
		// create div to receive reults
		jQuery ( "#wic-inner-field-group-new_issue_creation" ).append ( '<div id = "new-issue-progress-bar-legend"> . . . looking up issues . . . </div>' ); 
		jQuery ( "#wic-inner-field-group-new_issue_creation" ).append ( '<div id = "new-issue-progress-bar"></div>' );			
		jQuery ( "#wic-inner-field-group-new_issue_creation" ).append ( '<div id = "new-issue-table"></div>' );	
		jQuery ( "#new-issue-progress-bar" ).progressbar({
			value: false
		});
		 
		// set up AJAX call and go	
		var data = {
			staging_table : uploadParameters.staging_table_name,
			issue_title_column : issueTitleColumn,
			issue_content_column : issueContentColumn  		
		}
		wpIssuesCRMAjaxPost( 'upload', 'get_unmatched_issue_table',  jQuery('#ID').val(), data, function( response ) {
			jQuery ( "#new-issue-table" ).html(response); // show table results
			jQuery ( "#new-issue-progress-bar-legend" ).remove();
			jQuery ( "#new-issue-progress-bar" ).remove();
			recordDefaultDecisions();  // need to set the number of new issues among the default decisions
		});
	}

})(); // end anonymous namespace enclosure