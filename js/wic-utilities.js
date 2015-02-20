/*
*
* wic-utilities.js
*
*/

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

// in field customization, set field options according to type
function  setFieldTypeDefaults() {
	// change values of 	 
	if( document.getElementById('field_type') != undefined ) {
		
		var fieldType = document.getElementById('field_type');
		var optionGroup = document.getElementById('option_group');				
		var likeSearch = document.getElementById('like_search_enabled');
		var listFormatter = document.getElementById('list_formatter');	
			
		var selectedFieldType = fieldType.options[fieldType.selectedIndex].value;
		if ( selectedFieldType == 'text' ) {
			optionGroup.value = '';	
			likeSearch.value = 1;	
		} else if ( selectedFieldType == 'select' ) {
			if ( '' == optionGroup.value ) {
				alert ( 'Now set option group for your drowdown field.' );			
			}			
			likeSearch.value = 0;			
		} else if ( selectedFieldType == 'date' ) {
			optionGroup.value = '';	
			likeSearch.value = 0;	
		}
	}
}

// in field customization, warn if need to set field type
function  setFieldType() {
	// alert to change text value 	 
	if( document.getElementById('option_group') != undefined ) {
		var optionGroup = document.getElementById('option_group');
		var selectedOptionGroup = optionGroup.options[optionGroup.selectedIndex].value;
		var fieldType = document.getElementById('field_type');				
		var selectedFieldType = fieldType.options[fieldType.selectedIndex].value;
		if ( selectedOptionGroup != '' && selectedFieldType != 'select' ) {
			alert ( 'Set field type as dropdown to use option groups' );
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

function sendErrorMessage ( messageText ) {
	var message = document.getElementById( 'post-form-message-box' );
	permMessage = message.innerHTML
	message.innerHTML = messageText;
	message.className = 'wic-form-errors-found';

	timeout = window.setTimeout ( restoreMessage, 12000 ); 
}

function restoreMessage (  ) {
	var message = document.getElementById( 'post-form-message-box' );
	message.innerHTML = window.nextWPIssuesCRMMessage;
	message.className = 'wic-form-good-news';
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

// test for dup values among option values in option group edit
function testForDupOptionValues () {

	var optionValues = document.getElementsByClassName( 'wic-input option-value' );

	valuesValues = [];
	
	for ( var i = 0; i < optionValues.length; i++ ) {
		var dbVal = optionValues[i].value;
		if ( null !== optionValues[i].offsetParent ) {
			valuesValues.push( optionValues[i].value.trim() );	
		}
	} 
	var sortedValues = valuesValues.sort();
	
	var results = [];

	for (var j = 0;  j < sortedValues.length - 1; j++) {
   	 if (sortedValues[j + 1] == sortedValues[j]) {
   	 	var displayValue; 
   	 	if ( '' == sortedValues[j].trim() ) {
   	 		displayValue = '|BLANK|';
   	 	} else {
				displayValue = '"' + sortedValues[j] + '"';   	 	
   	 	}
   	 	var errorMessage = 'The database value of each option must be unique.  The value ' + displayValue + ' appears more once.  Delete an extra row using the red <span id="" class="dashicons dashicons-dismiss"></span> next to it.'
   	 	sendErrorMessage ( errorMessage  )
			window.nextWPIssuesCRMMessage = 'You can proceed after making values unique.';	
      	return false;
    	}
	}	
	
	return true;
	
}