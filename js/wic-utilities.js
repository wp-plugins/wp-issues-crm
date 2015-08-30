/*
*
* wic-utilities.js (generally available ui services and functions)
*
*/


// self-executing anonymous namespace
( function() {
	
	jQuery(document).ready(function($) {
		
		// note that have to reinitialize datepickers for hidden-template fields anyway 
		// cleaner to skip them here (additional initialization in wic-main.js)
		// alt approach was remove class for datepicker -- jQuery( newFields ).find( ".datepicker" ).removeClass('hasDatepicker');
		// use name*='row-template' as not selecter  instead of :hidden so that do add datepicker to fields in initially hidden form groups		
		
		// set date picker
	  $(".datepicker").not("[name*='row-template']").datepicker({
	  		 dateFormat: "yy-mm-dd"
	  	});

	});

})(); // end anonymous namespace enclosure


/* pair of functions for sending a temporary warning message before restoring green light message */
function sendErrorMessage ( messageText ) {
	var message = document.getElementById( 'post-form-message-box' );
	permMessage = message.innerHTML
	message.innerHTML = messageText;
	message.className = 'wic-form-errors-found';

	timeout = window.setTimeout ( restoreMessage, 10000 ); 
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
	// activate datepicker on child fields (in wic-jquery-ui.js do not activate datepicker unless visible ) 
 	jQuery( newFields ).find( ".datepicker" ).datepicker({
			 dateFormat: "yy-mm-dd"
	}); 

	// if have the boolean autocomplete flags set (i.e., wic-main.js is loaded) do the autocomplete listeners; 
	// if not (as in Options page), don't need them
 	if ( 'boolean' == ( typeof wicUseActivityIssueAutocomplete ) ) {  
	 	if ( wicUseActivityIssueAutocomplete ) {
	 		setUpActivityIssueAutocomplete( jQuery( newFields ).find( ".wic-multivalue-block.activity" )[0] );
		}
	
		if ( wicUseNameAndAddressAutocomplete ) {
			jQuery( newFields ).find (".address-line, .email-address" ).each ( function () {
				setUpNameAndAddressAutocomplete( this );			
			});		
		}
	} 
	
	// initialize type and refresh contingent displays based on counts if serving the advanced search form
	if ( "wic-form-advanced-search" ==  jQuery( newFields ).parents('form').attr('id') ) {
		swapInSubEntityTypes ( newFields );
		wicShowHideAdvancedSearchCombiners();
	}
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


// screen delete rows in multivalue fields
function hideSelf( rowname ) {
	var row = document.getElementById ( rowname );
	if ( "wic-form-advanced-search" !=  jQuery( row ).parents('form').attr('id') ) {
		rowClass =row.className; 
		row.className = rowClass.replace( 'visible-templated-row', 'hidden-template' ) ;
		sendErrorMessage ( 'Row will be deleted when you save/update.' )
		window.nextWPIssuesCRMMessage = 'You can proceed.';
		jQuery('#wic-form-constituent-update').trigger('checkform.areYouSure');
	} else {
		jQuery( row ).remove();
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
