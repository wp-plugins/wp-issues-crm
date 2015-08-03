/*
*
* wic-main.js (functions supporting page wp-issues-crm-main)
*
*/

// self-executing anonymous namespace
( function() {
	
	jQuery(document).ready(function($) {

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
						starSpan.addClass ( "dashicons-star-empty" )
					} else {
						starSpan.addClass ( "dashicons-star-filled" );
						starSpan.removeClass ( "dashicons-star-empty" )
					}
				});						
		});

		jsonPassedValues = document.getElementById( "financial_activity_types" );
		if ( null !== jsonPassedValues ) { // only present in constituent search/save/update forms
			financialCodesArray = JSON.parse( jsonPassedValues.innerHTML );		
			if ( 1 == financialCodesArray.length && '' == financialCodesArray[0] ) { // no financial types set
				$( "#wic-form-constituent-search .activity-amount" ).hide();
			}
	 		setChangedActivityTypeListeners();
 		}
	});

})(); // end anonymous namespace enclosure


function setChangedActivityTypeListeners() {
		jQuery( ".wic-input" ).change(function() {
			financialCodesArray = JSON.parse( document.getElementById( "financial_activity_types" ).innerHTML );
			// note financialCodesArray is always an array; if no financial activity codes, array of length 1 with empty string as only value
			found = testVal.indexOf('cp');
			if ( '' == testVal[0] ) { alert ( 'indexOfcp:' + found + ' length: ' + testVal.length + ' 0index: ' + testVal[0] ) };
			console.log ( 'ns' );
			/* starSpan = $( this ).find("span").first();
			var buttonValueArray = $( this ).val().split(",");
			var searchID = buttonValueArray[2];
			var favorite = starSpan.hasClass ( "dashicons-star-filled" );
			var data = { favorite : !favorite };
			console.log ( 'ns' );
			wpIssuesCRMAjaxPost( 'search_log', 'set_favorite', searchID, data, function( response ) {
					if ( favorite ) { 
						starSpan.removeClass ( "dashicons-star-filled" );
						starSpan.addClass ( "dashicons-star-empty" )
					} else {
						starSpan.addClass ( "dashicons-star-filled" );
						starSpan.removeClass ( "dashicons-star-empty" )
					}
				}); */						
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

// add new visible rows by copying hidden template
function moreFields( base ) {

	// counter always unique since gets incremented on add, but not decremented on delete
	var counter = document.getElementById( base + '-row-counter' ).innerHTML;
	counter++;
	document.getElementById( base + '-row-counter' ).innerHTML = counter;
	
	var newFields = document.getElementById( base + '[row-template]' ).cloneNode(true);
	
	/* set up row paragraph with  id and class */
	newFields.id = base + '[' + counter + ']' ;
	newFieldsClass = newFields.className; 
	newFields.className = newFieldsClass.replace('hidden-template', 'visible-templated-row') ;

	/* walk child nodes of template and insert current counter value as index*/
	replaceInDescendants ( newFields, 'row-template', counter, base);	

	var insertBase = document.getElementById( base + '[row-template]' );
	var insertHere = insertBase.nextSibling;
	insertHere.parentNode.insertBefore( newFields, insertHere );
	jQuery('#wic-form-constituent-update').trigger('checkform.areYouSure'); /* must also set 'addRemoveFieldsMarksDirty' : true in Are you sure*/
	jQuery('#wic-form-constituent-save').trigger('checkform.areYouSure');
	// activate datepicker on child fields (in wic-jquery-ui.js do not activate datepicker unless visible ) 
 	jQuery( newFields ).find( ".datepicker" ).datepicker({
			 dateFormat: "yy-mm-dd"
	}); 
	setChangedActivityTypeListeners();
}

// supports moreFields by walking node tree for whole multi-value group to copy in new name/ID values
function replaceInDescendants ( template, oldValue, newValue, base  ) {
	var newField = template.childNodes;
	if ( newField.length > 0 ) {
		for ( var i = 0; i < newField.length; i++ ) {
			var theName = newField[i].name;
			if ( undefined != theName) {
				newField[i].name = theName.replace( oldValue, newValue );
			}
			var theID = newField[i].id;
			if ( undefined != theID)  {
				newField[i].id = theID.replace( oldValue, newValue );
			} 
			var theFor = newField[i].htmlFor;
			if ( undefined != theFor)  {
				newField[i].htmlFor = theFor.replace( oldValue, newValue );
			} 
			var theOnClick = newField[i].onclick;
			if ( undefined != theOnClick)  {
				newClickVal = 'hideSelf(\'' + base + '[' + newValue + ']' + '\')' ;
				newField[i].setAttribute( "onClick", newClickVal );
			} 
			replaceInDescendants ( newField[i], oldValue, newValue, base )
		}
	}
}

