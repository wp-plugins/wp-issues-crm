/*
*
* wic-main.js (functions supporting page wp-issues-crm-main)
*
*/
var wicFinancialCodesArray;
var wicUseActivityIssueAutocomplete = false;
var wicUseNameAndAddressAutocomplete = false;
// self-executing anonymous namespace
( function() {
	
	
	jQuery(document).ready(function($) {

		// set global use autocomplete flags
		var useActivityIssueAutocompleteFlag = document.getElementById( "use_activity_issue_autocomplete" ); // if have one have both flags
		if ( null !== useActivityIssueAutocompleteFlag ) { 		// only present in constituent search/save/update forms
			wicUseActivityIssueAutocomplete = ( 'yes' == useActivityIssueAutocompleteFlag.innerHTML ) ;
			var useNameAndAddressAutocompleteFlag = document.getElementById( "use_name_and_address_autocomplete" );
			wicUseNameAndAddressAutocomplete = ( 'yes' == useNameAndAddressAutocompleteFlag.innerHTML ); 
	 	} 

		// test the set flag and call function for activities showing in form (will need to call again when adding activities)
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


/* work in progres here */
function setUpActivityIssueAutocomplete( activityMultivalueBlock ) {
	var activityIssue = jQuery( activityMultivalueBlock ).find( ".wic-input.issue");
	var activityIssueAutocomplete = jQuery( activityMultivalueBlock ).find( ".wic-input.issue-autocomplete");
	activityIssueAutocomplete.autocomplete({
  		source: [ "c++", "java", "php", "coldfusion", "javascript", "asp", "ruby" ]
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

