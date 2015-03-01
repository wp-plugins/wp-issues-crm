/*
*
* wic-jquery-ui.js
*
*/

jQuery(document).ready(function($) {
	$( ".wic-draggable" ).draggable({
		stack: ".wic-draggable",
		revert: true,
		scroll: true
	});
	$( ".wic-droppable" ).droppable({
		accept: ".wic-draggable",
		activeClass: "wic-droppable-highlight",
		hoverClass: "wic-droppable-hover",
		tolerance: "pointer",
		drop: function( event, ui ) {
			$( this )
			.addClass( "wic-state-dropped" );
	       var dropped = ui.draggable;
	       var droppedOn = $(this);
   		$(dropped).detach().css({top: 0,left: 10}).appendTo(droppedOn); 
		},
		over: function( event, ui ) {
			$( this )
			.addClass( "wic-droppable-hover" )
		},
		out: function( event, ui ) {
			$( this )
			.removeClass( "wic-state-dropped" )
			var dropped = ui.draggable;
			$(dropped).detach();
			$( "#wic-draggable-column").prepend ( dropped ); 
		}
	});
});

/*
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
	
	
	newFields.id = base + '[' + counter + ']' ;
	newFieldsClass = newFields.className; 
	newFields.className = newFieldsClass.replace('hidden-template', 'visible-templated-row') ;

	
	replaceInDescendants ( newFields, 'row-template', counter, base);	

	var insertBase = document.getElementById( base + '[row-template]' );
	var insertHere = insertBase.nextSibling;
	insertHere.parentNode.insertBefore( newFields, insertHere );
	
*/