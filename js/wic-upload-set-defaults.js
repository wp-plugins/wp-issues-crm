/*
*
* wic-upload-set-defaults.js
*
* note that this routine plays role in enforcement of required rules at the column level
*
*/

// self-executing anonymous namespace
( function() {

	var columnMap, defaultDecisions, initialUploadStatus, matchResults, saveHeaderMessage, uploadID, uploadParameters,
		validMatched, validUnique, commentsArray, errorsArray;

	// set up presumptions that fields not mapped 		
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
	
	// for use with us zip code validation if set
	var regPostalCode = new RegExp("^\\d{5}(-\\d{4})?$");
	
	// initialize in progress flag for Ajax call
	var newIssuesInProgress = 0;
	/*
	*
	* in ready function, make all choices and all setup possible to make without knowing user input
	*
	*/
	jQuery(document).ready(function($) {
		
		// if error is showing at ready stage, it is because mapping, validation and/or matching steps have not been completed 
		// therefore no need to further prepare form
		if ( jQuery ( "#post-form-message-box" ).hasClass ( "wic-form-errors-found" )	) {
			return;	
		}

		// set up window close listener for new issue creation
		$(window).on('beforeunload', function() {
			if ( 1 == newIssuesInProgess ) {
				return ( 'started new issue creation, but not completed');
			}   
      });

		// otherwise, initial status is something other than 'staged', 'mapped' or 'validated'
		// php has defined an appropriate initial message based on status			
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
		// listen to save all updates to default options
		controlsArray.change ( function() {
			recordDefaultDecisions();
			decideWhatToShow();
		});

		saveHeaderMessage = jQuery ( "#post-form-message-base" ).text();	

		// get and save totals
		validMatched = 0;
		validUnique  = 0;
		for ( var matchSlug in matchResults  ) { 
			validMatched +=  matchResults[matchSlug].matched_with_these_components;
			validUnique  +=  Number( matchResults[matchSlug].unmatched_unique_values_of_components ) ;
				// this field has type string because of ancestry as a literal ? for display purposes
		}	

		/*
		* process column map and hide fields already mapped
		* set up flags for entities and fields that are mapped for use in validating
		*/
		for ( var inputColumn in columnMap  ) {
			
			// hide controls for fields that have already been mapped
			var mappedField = columnMap[inputColumn].field; 
			if ( undefined != mappedField ) { 
				hideGroup =  '#wic-control-' + mappedField.replace ( '_', '-' ); 
				jQuery( hideGroup ).hide();
			}
			
			// check what entities/fields are mapped	
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

		// populate form with saved values or initialize with some defaults
		// if user has already set defaults, retrieve values 
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
		// if user has not previously set defaults, make some recommendations
		} else {
			jQuery ( "#update_matched" ).prop ( "checked", true );
			jQuery ( "#add_unmatched" ).prop ( "checked", true );
			jQuery ( "#protect_identity" ).prop ( "checked", true );
			jQuery ( "#protect_blank_overwrite" ).prop ( "checked", true );
		}	

		// enable/set necessary values for add update choices (may change previous recommendations)
		// the null case -- kill the form
		if ( 0 == validMatched && 0 == validUnique ) {
			jQuery ( "#post-form-message-box" ).text ( "No new or matched records to upload -- revisit prior steps." );
			jQuery ( "#post-form-message-box" ).addClass ( "wic-form-errors-found" );			
			jQuery ( ":input" ).prop ( "disabled", true );
		} else {
			// if no matched constituents, disable update choices
			if ( 0 == validMatched ) {
				jQuery ( "#update_matched" ).prop ( "checked", false );
				jQuery ( "#protect_identity" ).prop ( "checked", true );
				jQuery ( "#protect_blank_overwrite" ).prop ( "checked", true );
				jQuery ( "#update_matched" ).prop ( "disabled", true );
				jQuery ( "#protect_identity" ).prop ( "disabled", true );
				jQuery ( "#protect_blank_overwrite" ).prop ( "disabled", true );
			// if nothing to add, disable the add choice
			} else if ( 0 == validUnique ) {
				jQuery ( "#add_unmatched" ).prop ( "checked", false );
				jQuery ( "#add_unmatched" ).prop ( "disabled", true );
			}
		}

		// if title mapped not overridden by issue, create new issue table -- 
		// will be hidden later if no new issues or if default issue is set
		if ( titleMapped && ! activityIssueMapped ) {

			// create div to receive results
			jQuery ( "#wic-inner-field-group-new_issue_creation" ).append ( '<div id = "new-issue-progress-bar-legend"> . . . looking up issues . . . </div>' ); 
			jQuery ( "#wic-inner-field-group-new_issue_creation" ).append ( '<div id = "new-issue-progress-bar"></div>' );			
			jQuery ( "#wic-inner-field-group-new_issue_creation" ).append ( '<div id = "new-issue-table"></div>' );	
			jQuery ( "#new-issue-progress-bar" ).progressbar({
				value: false
			});

			newIssuesInProgress = 1;
			 
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
				newIssuesInProgress = 0;				
				// do these form set up items as callbacks.
				recordDefaultDecisions();  // need to set the number of new issues among the default decisions
				decideWhatToShow();
				// have to do this in the callback on first run through to have the new issue count to test
	
			});
	
		} else {
			// on ready, after populating form, set database values from form
			// necessary in case good to go without change and database values have not been saved
			// done in the callback above as well
			recordDefaultDecisions();
			decideWhatToShow();
		}
	});

	
	function decideWhatToShow() { 
	
		// enable disable protect identity, depending on whether doing updates to matched.
		if ( jQuery ( "#update_matched" ).prop( "checked" ) ){
			jQuery ( "#protect_identity" ).prop ( "disabled", false );
			jQuery ( "#protect_blank_overwrite" ).prop ( "disabled", false );
		} else {
			jQuery ( "#protect_identity" ).prop ( "disabled", true );
			jQuery ( "#protect_blank_overwrite" ).prop ( "disabled", true );					
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
		commentsArray = [];
		errorsArray = [];
		
		// if both matched and unmatched are unchecked, nothing will be uploaded
		if ( false === jQuery ( "#update_matched" ).prop ( "checked" ) && false === jQuery ( "#add_unmatched" ).prop ( "checked" ) ) {
			errorsArray.push ( 'You have specified that no records will be updated or added -- nothing to upload.')		
		}

		// addresses can be added entirely with defaults so we don't hide the address_type field even if no address values mapped
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
			// this div is present only if the zip check option is set for WP Issues CRM
			if ( 1 == jQuery ( "#do_zip_code_format_check" ).length && jQuery ( "#zip" ).val() > '' ) {
				if ( false == regPostalCode.test( jQuery ( "#zip" ).val() ) ) {
					errorsArray.push ( 'If postal code is supplied, it must be in 5 digit or 5-4 digit format.' )				
				}
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

		/*
		*	Activity more complicated . . . if any field mapped or defaulted, need date, type and issue or title
		*	Title mapping is, in effect, an activity field, but don't allow default titling 
		*/
	
		if ( 	activityMapped 								|| // any activity field mapped
				titleMapped										|| // note: content cannot be mapped without title
				jQuery ( "#activity_date" ).val() > '' || 
				jQuery ( "#activity_type" ).val() > ''	||
				jQuery ( "#pro_con" ).val() > '' 		||	
				jQuery ( "#issue" ).val() > '' 			
			) { 

			if ( '' == jQuery( "#activity_date" ).val() && jQuery( "#activity_date" ).is(':visible') ) {
				errorsArray.push ( 'Set an activity date to upload activity data.' )		
			}	
			if ( '' == jQuery( "#activity_type" ).val() && jQuery( "#activity_type" ).is(':visible') ) {
				errorsArray.push ( 'Set an activity type to upload activity data.' )		
			}	
			// must supply either map activity issue (a numeric link to post on the activity record), default the issue or map title
			// if haven't mapped or defaulted issue, look for title and act accordingly
			if ( '' == jQuery( "#issue" ).val() && jQuery( "#issue" ).is(':visible') ) {
				// if haven't mapped title, must default or supply issue
				if ( ! titleMapped ) { 				
					errorsArray.push ( 'Choose an issue to upload activity data.' )		
				} else { 
					if ( defaultDecisions.new_issue_count > 0 ) {	
						jQuery ( "#wic-field-group-new_issue_creation" ).show();
						if ( ! jQuery ( "#create_issues" ).prop ( "checked" ) ) {		
							errorsArray.push ( 'You must affirmatively accept New Issue Titles or change other settings.' )	;
						}
					} else {
						jQuery ( "#wic-field-group-new_issue_creation" ).hide();		
						// new issues = 0, then should advise user that titles will be used, although no new issues
						commentsArray.push ( "Activities will be created based on the issue titles column. " +
							"All titles have previously been saved as issues." );				
					}
				} 
			// if mapped or supplied an issue, then hide the whole issue creation section
			} else {
				jQuery ( "#wic-field-group-new_issue_creation" ).hide();
			}		
		}				

		// show/hide title elements in constituent default field group based on whether all group inputs are hidden
		if ( 0 == jQuery( "#wic-field-group-constituent, #wic-field-group-address, #wic-field-group-email, #wic-field-group-phone" ).find( ":input" ).not( ":button, :hidden" ).length ) {
			jQuery( "#wic-inner-field-group-constituent-toggle-button" ).hide();	
			jQuery( "#wic-inner-field-group-constituent p" ).hide();			
		} else {
			jQuery( "#wic-inner-field-group-constituent-toggle-button" ).show();	
			jQuery( "#wic-inner-field-group-constituent p" ).show();			
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





})(); // end anonymous namespace enclosure