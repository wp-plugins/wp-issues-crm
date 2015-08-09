/*
*
* wic-main.js (functions supporting page wp-issues-crm-main)
*
*/
var wicFinancialCodesArray;
var wicUseActivityIssueAutocomplete = false;
var wicUseNameAndAddressAutocomplete = false;
var wicStandardInputBorder;
// self-executing anonymous namespace
( function() {
	
	
	jQuery(document).ready(function($) {

		// pick up standard border color to revert to
		wicStandardInputBorder = $( "#first_name" ).css('border-top-color');

		// set global use autocomplete flags
		var useActivityIssueAutocompleteFlag = document.getElementById( "use_activity_issue_autocomplete" ); // if have one have both flags
		if ( null !== useActivityIssueAutocompleteFlag ) { 		// only present in constituent search/save/update forms
			wicUseActivityIssueAutocomplete = ( 'yes' == useActivityIssueAutocompleteFlag.innerHTML ) ;
			// presence, but not value, of useNameAndAddressAutocompleteFlag is determined by useActivityIssueAutocompleteFlag
			var useNameAndAddressAutocompleteFlag = document.getElementById( "use_name_and_address_autocomplete" );
			wicUseNameAndAddressAutocomplete = ( 'yes' == useNameAndAddressAutocompleteFlag.innerHTML ); 
	 	} 

		// test the set flag and call autocomplete function for activities showing in form (will need to call again when adding activities)
		if ( wicUseActivityIssueAutocomplete ) {
			// covering update form initialization -- must also call this function when adding activity rows to save or update forms)
			$(":visible.wic-multivalue-block.activity" ).each( function() {
				setUpActivityIssueAutocomplete( this );
			})
			// covering search and search again forms		
			$("#wic-field-subgroup-activity_issue" ).each( function() {
				setUpActivityIssueAutocomplete( this );
			})			
		}

		// same for name and addresses
		if ( wicUseNameAndAddressAutocomplete ) {
			$( "#last_name, #first_name, :visible.address-line, :visible.email-address " ).each ( function () {
				setUpNameAndAddressAutocomplete( this );			
			});		
		}

		// set up post export button as selectmenu that submits form it is on change
		$( "#wic-post-export-button" ).selectmenu();  	
	
		$( "#wic-post-export-button" ).on( "selectmenuselect", function( event, ui ) {
			myThis = $( this );
			if ( myThis.val() > '' )  {
				$( "#wic_constituent_list_form" ).submit();
			}
		});  	
	
		$( ".wic-favorite-button" ).click(function() {
			starSpan = $( this ).find("span").first();
			var buttonValueArray = $( this ).val().split(",");
			var searchID = buttonValueArray[2];
			var favorite = starSpan.hasClass ( "dashicons-star-filled" );
			var data = { favorite : !favorite };
			console.log ( data );
			wpIssuesCRMAjaxPost( 'search_log', 'set_favorite', searchID, data, function( response ) {
					if ( favorite ) { 
						starSpan.removeClass ( "dashicons-star-filled" );
						starSpan.addClass ( "dashicons-star-empty" );
					} else {
						starSpan.addClass ( "dashicons-star-filled" );
						starSpan.removeClass ( "dashicons-star-empty" );
					}
				});						
		});

		// manage financial activity types on update forms
		jsonPassedValues = document.getElementById( "financial_activity_types" );
		if ( null !== jsonPassedValues ) { // only present in constituent search/save/update forms
			wicFinancialCodesArray = JSON.parse( jsonPassedValues.innerHTML );		
			if ( '' < wicFinancialCodesArray[0] ) { // financial types set (min length is one with a blank element)
	 			// set up delegated event listener for changes to activity type
	 			$( "#wic-control-activity" ).on( "change", ".activity-type", function ( event ) {
	 				var changedBlock = $( this ).parents( ".wic-multivalue-block.activity" )[0];
	 				showHideFinancialActivityType( changedBlock );
	 			});
				// set up delegated event listener for changes to activity amount -- alert user of non-numeric value
	 			$( "#wic-control-activity" ).on( "blur", ".wic-input.activity-amount", function ( event ) {
					this.value = this.value.replace('$','') // drop dollar signs (convenience for $users)
	 				if ( isNaN( this.value ) ) { 
							alert ( "Non-numeric amount -- " + this.value + " -- will be set to zero." );
							this.value = "0.00";
	 				} else {
	 					this.value = ( this.value == '' ? '' : Number( this.value ).toFixed(2) ) ; 
	 				} 
	 			});		 					
			}
 		} 
 		
	});

})(); // end anonymous namespace enclosure


/*
* function showHideFinancialActivityType
* expects an activity multivalue block -- tests activity type and shows/hides activity-amount 
* (visibility comes right from server; need function only on change of activity_type)
*/ 
function showHideFinancialActivityType( activityMultivalueBlock ) {
 		var activityType = jQuery( activityMultivalueBlock ).find( ".wic-input.activity-type").val()
		var isFinancial = ( wicFinancialCodesArray.indexOf( activityType ) > -1 );
		if ( ! isFinancial ) {
			jQuery( activityMultivalueBlock ).find( ".wic-input.activity-amount").hide();
		} else {
			jQuery( activityMultivalueBlock ).find( ".wic-input.activity-amount").show();			
		}

}


/*
*
* Activity Issue Autocomplete setup
*
*/

function setUpActivityIssueAutocomplete( activityMultivalueBlock ) {
	var activityIssue = jQuery( activityMultivalueBlock ).find( ".wic-input.issue");
	var activityIssueAutocomplete = jQuery( activityMultivalueBlock ).find( ".wic-input.issue-autocomplete");
	activityIssueAutocomplete.autocomplete( {
			delay: 300, 	// default = 300
			minLength: 3,	// default = 1
		  	source: function( request, responseAC ) { 
		  		// note that this call uses arguments in ways inconsistent with their labeling in wpIssuesCRMAjaxPost (but no type violations
		  		wpIssuesCRMAjaxPost( 'autocomplete', 'db_pass_through',  'activity_issue', request.term, function( response ) {
						responseAC ( response );		          
		      })
			},
			focus: function( event, ui ) {
				event.preventDefault();
			},
			select: function ( event, ui ) {
				event.preventDefault();
				// show the selected item in the visible input (strip the informational add ons)
				cleanLabel = ui.item.label.substring(0, ui.item.label.lastIndexOf('(') - 1 );
				//
				activityIssueAutocomplete.val( cleanLabel );
				// add the selected option to the hidden select (no harm if added twice -- just need to have it there to successfully assign value)
				activityIssue.append( jQuery("<option></option>").val( ui.item.value ).text( cleanLabel ) ) ;  
				// assign post id as value of the hidden select 
				activityIssue.val( ui.item.value );
				// reset border color upon a selection 
				activityIssueAutocomplete.css( 'border-color', wicStandardInputBorder );
			},
			change: function ( event, ui ) {
				/* 
				Note on the change logic:  When user leaves the autocomplete input, there are the following possibilities:
				(1) No change -- so nothing to do;
				(2) User has validly selected from search results and has not altered selected result -- change is clean -- nothing to do;
						Test for this by finding value and label matching in hidden select field.
				(3) Other alternatives:
					+ User has blanked out the value -- this is OK (although may fail edit if so submitted. 
						Just make sure hidden value is also blank and restore border OK.
					+ User has left non-blank value that does not match a label in the select array or does match label, but not also value
						Alert of need to select; set warning border color.
						Wipe out hidden value so that will not affect search or will generate error on save/update submit.
						This is conservative, but should rarely be annoying.
				*/
				// if user ended up leaving issue field blank, make sure hidden value is also blank
				var foundValidMatchingIssue = false
				if ( '' == activityIssueAutocomplete.val() ){
					activityIssue.val('');
					foundValidMatchingIssue = true;
				// otherwise check that result of user's change of field is still valid				
				} else {
					activityIssue.find("option").each(function(){
						if ( this.text == activityIssueAutocomplete.val() && this.value == activityIssue.val() )  {
							foundValidMatchingIssue = true;
							return ( false );
						}	
					});
				}				
				if ( foundValidMatchingIssue ) {
					activityIssueAutocomplete.css( 'border-color', wicStandardInputBorder );	
				} else {
					alert( 'Please enter a search phrase (at least 3 characters) and choose from the drop down.' );
					activityIssueAutocomplete.css( 'border-color', 'red' );
					activityIssue.val('');					
				}	
				changeActivityIssueButtonDestination();
			}
	});
}


/*
*
* Name and Address Autocompete
*
*/
function setUpNameAndAddressAutocomplete ( element ) {
	acElement = jQuery ( element );
	acElement.autocomplete ({
			delay: 200, 	// default = 200
			minLength: 3,	// default = 1
		  	source: function( request, responseAC ) { 
		  		// note that this call uses arguments in ways inconsistent with their labeling in wpIssuesCRMAjaxPost (but no type violations
		  		wpIssuesCRMAjaxPost( 'autocomplete', 'db_pass_through',  element.id, request.term, function( response ) {
						responseAC ( response );		          
		      })
			},		
	});

}

// automatically set case_status to Open when Assigned
function changeCaseStatus() {
	 
	if( document.getElementById('case_assigned') != undefined && undefined == document.getElementById('wic-form-constituent-search') ) {
		var assigned = document.getElementById('case_assigned');				
		var assignedStaff = assigned.options[assigned.selectedIndex].value;
		
		if ( assignedStaff > 0 ) {
			var caseStatus = document.getElementById('case_status');	
				caseStatus.value = '1';	
		}
	}
}

// set issue follow_up_status to Open if Assigned
function changeFollowUpStatus() {
	if( document.getElementById('issue_staff') != undefined && undefined == document.getElementById('wic-form-issue-search') ) {
		var assigned = document.getElementById('issue_staff');				
		var assignedStaff = assigned.options[assigned.selectedIndex].value;
		if ( assignedStaff > '' ) {
			var caseStatus = document.getElementById('follow_up_status');	
				caseStatus.value = 'open';	
		}
	}
}

// changes the activity issue link on change of the selected issue
function changeActivityIssueButtonDestination() {
	// since can't point to button that should change, just update them all
	var activities = document.getElementsByClassName( 'wic-multivalue-block activity' );
	var numActivities = activities.length; 
	for ( i = 0; i < numActivities; i++ ) {
		var activity_with_button = activities[i].getElementsByClassName ( 'wic-form-button wic-activity-issue-link-button');
		var activity_with_issue = activities[i].getElementsByClassName ( 'wic-input issue');
		var activity_issue = activity_with_issue[0];
		if ( activity_with_button.length > 0 ) {
			var activity_link_button = activity_with_button[0];
			if ( activity_issue.value > '' ) {		
				activity_link_button.value = 'issue,id_search,' + activity_issue.value;
			} else {
				activity_link_button.parentNode.removeChild( activity_link_button );			
			}		
		} else { // no link button -- create one if have an issue value
			if ( activity_issue.value > '' ) {	
				var btn = document.createElement("BUTTON");
				btn.innerHTML = 'View Issue';
				btn.className = 'wic-form-button wic-activity-issue-link-button';
				btn.value = 'issue,id_search,' + activity_issue.value;
				btn.name  = 'wic_form_button';
				previousGroup = activity_issue.parentNode.previousSibling;
				previousGroup.appendChild(btn);	
				// activity_link_button.value = 'issue,id_search,' + activity_issue.value;
			} 
		}
	}
}

// show/hide form sections
function togglePostFormSection( section ) { 
	var constituentFormSection = document.getElementById ( section );
	var display = constituentFormSection.style.display;
	if ('' == display) {
		display = window.getComputedStyle(constituentFormSection, null).getPropertyValue('display');
	}
	var toggleButton	= document.getElementById ( section + "-show-hide-legend" );
	if ( "block" == display ) {
		constituentFormSection.style.display = "none";
		toggleButton.innerHTML = "Show";
	} else {
		constituentFormSection.style.display = "block";
		toggleButton.innerHTML = "Hide";
	}
}

// screen delete rows in multivalue fields
function hideSelf( rowname ) {
	var row = document.getElementById ( rowname );
	rowClass =row.className; 
	row.className = rowClass.replace( 'visible-templated-row', 'hidden-template' ) ;
	sendErrorMessage ( 'Row will be deleted when you save/update.' )
	window.nextWPIssuesCRMMessage = 'You can proceed.';
	jQuery('#wic-form-constituent-update').trigger('checkform.areYouSure');
}

