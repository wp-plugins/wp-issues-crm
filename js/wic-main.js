/*
*
* wic-main.js (functions supporting page wp-issues-crm-main)
*
*/
var wicFinancialCodesArray;
var wicUseActivityIssueAutocomplete = false;
var wicUseNameAndAddressAutocomplete = false;
var wicStandardInputBorder;

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
		$( "#last_name, #first_name, #middle_name, :visible.address-line, :visible.email-address " ).each ( function () {
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

	// set up favorite button on return to search list
	$( ".wic-favorite-button" ).click(function() {
		starSpan = $( this ).find("span").first();
		var buttonValueArray = $( this ).val().split(",");
		var searchID = buttonValueArray[2];
		var favorite = starSpan.hasClass ( "dashicons-star-filled" );
		var data = { favorite : !favorite };
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
 		
}); // document ready



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
				// test for possibility that user selects the not-found message
				if ( ui.item.value > -1 ) {
					activityIssueAutocomplete.val( cleanLabel );
					// add the selected option to the hidden select (no harm if added twice -- just need to have it there to successfully assign value)
					activityIssue.append( jQuery("<option></option>").val( ui.item.value ).text( cleanLabel ) ) ;  
					// assign post id as value of the hidden select 
					activityIssue.val( ui.item.value );
					// reset border color upon a selection 
					activityIssueAutocomplete.css( 'border-color', wicStandardInputBorder );
				// if user selected not found message, reset phrase and hidden search value
				} else {
					activityIssueAutocomplete.val( '' );
					activityIssue.val( '' );
				}
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
* Name and Address Autocomplete
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
			select: function ( event, ui ) {
				event.preventDefault();
				if ( '. . .' != ui.item.value ) {
					this.value = ui.item.value; 				
				}
			}		
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


/*
*
* Advanced Query functions
*
*/
jQuery(document).ready(function($) {
	/*
	* manage display of correct options for  fields in advanced search
	*/
	// set up delegated event listener for changes to constituent field
 	$( "#wic-control-advanced-search-constituent" ).on( "change", ".constituent-field", function ( event ) {
 		var changedBlock = $( this ).parents( ".wic-multivalue-block.advanced_search_constituent" )[0];
 		wicSwapInAppropriateFields( changedBlock, false ); // false says don't preserve values, but will anyway if input to input change 
 	});
	// initialize constituent fields
	$( ".wic-multivalue-block.advanced_search_constituent" ).each( function () {
		wicSwapInAppropriateFields( this, true );  // true preserve field values
	});
	
	// set up delegated event listener for changes to activity field 
 	$( "#wic-control-advanced-search-activity" ).on( "change", ".activity-field", function ( event ) {
 		var changedBlock = $( this ).parents( ".wic-multivalue-block.advanced_search_activity" )[0];
 		wicSwapInActivityAppropriateFields( changedBlock, false );
 	});
	// initialize activity fields
	$( ".wic-multivalue-block.advanced_search_activity" ).each( function () {
		wicSwapInActivityAppropriateFields( this, true );  // true preserve field values
	});

	// set up delegated event listener for changes to constituent having field 
 	$( "#wic-control-advanced-search-constituent-having" ).on( "change", ".constituent-having-field", function ( event ) {
 		var changedBlock = $( this ).parents( ".wic-multivalue-block.advanced_search_constituent_having" )[0];
 		wicSwapInHavingAppropriateFields( changedBlock, false );
 	});
	// initialize constituent having fields
	$( ".wic-multivalue-block.advanced_search_constituent_having" ).each( function () {
		wicSwapInHavingAppropriateFields( this, true );  // true preserve field values
	});
	
	// set up delegated event listener for change to constituent having aggregator 
 	$( "#wic-control-advanced-search-constituent-having" ).on( "change", ".constituent-having-aggregator", function ( event ) {
 		var changedBlock = $( this ).parents( ".wic-multivalue-block.advanced_search_constituent_having" )[0];
 		wicReconcileHavingAggregateWithField( changedBlock );
 	});

	// set up delegated event listener for changes to activity comparison field 
 	$( "#wic-control-advanced-search-activity" ).on( "change", ".activity-comparison", function ( event ) { 
 		var changedBlock = $( this ).parents( ".wic-multivalue-block.advanced_search_activity" )[0];
 		wicSwapInIssueAppropriateFields( changedBlock ); // never preserves values 
 	});  
 	// note that this does not need to be initialized because generate appropriate issue field on server side
 	
 	// remove unused options for having comparison value -- do only on initialization and include template
 	$( ".wic-multivalue-block.advanced_search_constituent_having" ).each( function() { 
 		wicPrepareConstituentHavingFields( this );
 	}); 		

	
	// manage display of combination options in advanced search -- show only if multiple selected
	wicShowHideAdvancedSearchCombiners(); // initialize
	// set up delegated event listener for changes to constituent block
 	$( ".wic-multivalue-control-set" ).on( "change", function ( event ) {
		wicShowHideAdvancedSearchCombiners();
 	});

	// set up delegated event listener for changes to constituent or activity choice
 	$( "#activity_or_constituent" ).on( "change", function ( event ) {
			wicShowHideHavingClauses();
 	});
 		
}); // document ready

/*
* function wicSwapInAppropriateFields
* expects a constituent field multivalue block -- swaps select control options
*/ 


function wicSwapInAppropriateFields( constituentFieldMultivalueBlock, preserveFieldValues ) {
	
		// set up variables
		var currentBlock 			= jQuery( constituentFieldMultivalueBlock );
 		var newLabel 				= currentBlock.find( ".constituent-field :selected").text();
 		var newValue 				= currentBlock.find( ".constituent-field :selected").val();
 		var valueField				= currentBlock.find( ".constituent-value");
		var valueFieldID			= valueField.attr("id");

 		// identify and swap in appropriate types 
 		if ( ! preserveFieldValues ) { // preserveFieldValues is true on document.ready; this field is properly set from server
 			var fieldEntity 			= newLabel.substring( newLabel.lastIndexOf( ' ' ) + 1, newLabel.lastIndexOf( ':' ) );
			var targetTypeElement 	= jQuery( constituentFieldMultivalueBlock ).find( ".wic-input.constituent-entity-type");
			var newTemplate;
 			if ( '' != fieldEntity && 'constituent' != fieldEntity ) {
 				// have to escape brackets  in jquery with \\ to cause them to be treated as literal
				var newTemplateIDString = "#" + fieldEntity + '\\[control-template\\]\\[' + fieldEntity + '_type\\]';
				newTemplate = jQuery( newTemplateIDString );
 			} else {
				newTemplate = jQuery( "#advanced_search_constituent\\[row-template\\]\\[constituent_entity_type\\]" ); 
 			}
 			// for a select element, the html is just the options list, so swapping in the options:
 			targetTypeElement.html( newTemplate.html() );
 		}
 		
		//now swap in control for values ( this AJAX function will also set other values based on new control)
		wicAdvancedSearchReplaceControl( constituentFieldMultivalueBlock, valueFieldID, newValue, "constituent-value", preserveFieldValues );
 		
}

// parallel function for activity fields 
function wicSwapInActivityAppropriateFields( activityFieldMultivalueBlock, preserveFieldValues ) {
 		var newValue = jQuery( activityFieldMultivalueBlock ).find( ".wic-input.activity-field :selected").val();
		valueFieldID = jQuery( activityFieldMultivalueBlock ).find( ".wic-input.activity-value, .wic_multi_select" ).attr("id");
		wicAdvancedSearchReplaceControl( activityFieldMultivalueBlock, valueFieldID, newValue, "activity-value", preserveFieldValues );
}
// special listener for activity-comparison field on issues
function wicSwapInIssueAppropriateFields( activityFieldMultivalueBlock ) {
	// is the current activity field 'issue'? 
	if ( jQuery( activityFieldMultivalueBlock ).find( ".issue").length > 0 ) {
		issueFieldObject = jQuery( activityFieldMultivalueBlock ).find( ".issue"); // get the primary, not the autocomplete
		issueComparisonObject = jQuery( activityFieldMultivalueBlock ).find( ".activity-comparison");
		// change issue control only if needed to change
		if ( issueFieldObject.is("select") && issueComparisonObject.val() != '=' ) { // swap from autocomplete to category
			valueFieldID = jQuery( activityFieldMultivalueBlock ).find( ".wic-input.activity-value" ).attr("id");
			wicAdvancedSearchReplaceControl( activityFieldMultivalueBlock, valueFieldID, 'CATEGORY', 'activity-value', false  )
		} else if ( ! issueFieldObject.is("select") && '=' == issueComparisonObject.val()  ) { // swap from category to autocomplete 
			wicSwapInActivityAppropriateFields ( activityFieldMultivalueBlock, false ); // just do as if coming from another field
		}
	}
}
function wicSwapInHavingAppropriateFields( constituentHavingFieldMultivalueBlock, preserveFieldValues ) {
 	var newValue = jQuery( constituentHavingFieldMultivalueBlock ).find( ".wic-input.constituent-having-field :selected").val();
	valueFieldID = jQuery( constituentHavingFieldMultivalueBlock ).find( ".wic-input.constituent-having-value, .wic_multi_select" ).attr("id");
	wicAdvancedSearchReplaceControl( constituentHavingFieldMultivalueBlock, valueFieldID, newValue, "constituent-having-value", preserveFieldValues );
}

// activate this when aggregator changes -- is also called on field change and startup through the having field swapper via wicAdvancedSearchReplaceControl
function wicReconcileHavingAggregateWithField( constituentHavingFieldMultivalueBlock) {
	var currentBlock = constituentHavingFieldMultivalueBlock;
	aggregatorIsCount = 	( 'COUNT' == jQuery( constituentHavingFieldMultivalueBlock ).find( ".constituent-having-aggregator" ).val() );
	currentFieldIsDate = ( jQuery( constituentHavingFieldMultivalueBlock ).find( ".constituent-having-field option:selected" ).text().indexOf('date') > -1 );
	currentValueHasDatepicker = jQuery( constituentHavingFieldMultivalueBlock ).find( ".constituent-having-value" ).hasClass("hasDatepicker");
	if ( aggregatorIsCount && currentValueHasDatepicker ) {
		jQuery( constituentHavingFieldMultivalueBlock ).find( ".constituent-having-value" ).datepicker("destroy");
	} else if ( ! aggregatorIsCount && currentFieldIsDate && ! currentValueHasDatepicker ) {
		jQuery( constituentHavingFieldMultivalueBlock ).find( ".constituent-having-value" ).datepicker({
	  			 dateFormat: "yy-mm-dd"
	  		});	
	} 
}

function wicAdvancedSearchReplaceControl( fieldMultivalueBlock, valueFieldID, newFieldID, newFieldClass, preserveFieldValues  ) { 
	wpIssuesCRMAjaxPost( 'advanced_search', 'make_blank_control',  newFieldID, '', function( response ) {

		// make a control in the current document context from the response
		var newControl = jQuery.parseHTML( response );
		var newControlObject = jQuery( newControl );

		// save oldControl value
		var oldControl =  document.getElementById( valueFieldID ); // valueFieldID contains brackets
		var oldControlObject = jQuery( oldControl );
		var oldValue = oldControlObject.val();

		// set the name and ID to of new control to same as old control; add appropriate class
		newControlObject.attr( "id", valueFieldID ) ;
		newControlObject.attr( "name", valueFieldID ) ;
		newControlObject.addClass ( newFieldClass );
 
		// in case of category control (really an array of controls), need to individually identify the individual category controls
		// 	id so far only names the div that wraps the array (which will not appear in $_POST ); div name matters only in this js
		if ('CATEGORY' == newFieldID ) {
			newControlObject.find("p").each( function (){
				var idString = valueFieldID + this.firstChild.htmlFor.substr(48); // stripping out template string, adding back field id
				this.firstChild.htmlFor = idString;
				this.lastChild.id = idString;
				this.lastChild.name = idString;
			});
			newControlObject.addClass( " issue " );
		}
		// preserve values on init (constituent-field input to whatever) and on all input to input changes
		if ( preserveFieldValues || ( oldControlObject.is("input") && newControlObject.is("input") ) ) { 
			if ( newControlObject.hasClass('wic-input-checked') ) { 
				if ( newControlObject.val() == oldValue ) { 
					newControlObject.prop( 'checked', true );					
				}
			} else {
				newControlObject.val( oldValue );
			}
		} 
		// already have a multiselect and are in init, don't execute the replace, instead, keep the old field
		// happens is doing search again on an issue category search -- class-wic-form-advanced-search-activity-update.php
		if ( oldControlObject.hasClass("wic_multi_select") && preserveFieldValues ) {  
			var keptOldMultiSelect = true;
		// otherwise do the replace -- this is the usual case	
		} else {		
			oldControlObject.replaceWith ( newControl );  
		}
		
		// add date picker if appropriate
		if ( newControlObject.hasClass( "datepicker" ) ) {
			newControlObject.datepicker({
	  		 dateFormat: "yy-mm-dd"
	  	}); }
 		
		/*
		* show hide compare fields and options based on relevance
		*/ 
		var currentBlock 			= jQuery( fieldMultivalueBlock );
		var nonQuantitativeOptionString = "option[value$=\'BLANK\'], option[value=\'IS_NULL\'], option[value=\'LIKE\'], option[value=\'SCAN\']"	
		var nonSelectOptionString = "option[value=\'>=\'], option[value=\'<=\'],option[value=\'IS_NULL\'], option[value=\'LIKE\'], option[value=\'SCAN\']"
		// logic specific to constituent fields
		if ( 'constituent-value' == newFieldClass ) {
			var newLabel 				= currentBlock.find( ".constituent-field :selected").text();
		 	var fieldEntity 			= newLabel.substring( newLabel.lastIndexOf( ' ' ) + 1, newLabel.lastIndexOf( ':' ) ) 
	 		var fieldFieldSlug 		=  newLabel.substring( newLabel.lastIndexOf( ':' ) + 1 );
 			var compareFieldObject	= currentBlock.find( ".constituent-comparison" );
 			var typeFieldObject		= currentBlock.find( ".constituent-entity-type" );

			// moving from left to right, configuring parameter fields based on selected search field
			// first do show/hides, then define comparison options 
			
			// hide type selection (is set to blank and is irrelevant) if have a constituent field;
			if ( 'constituent' == fieldEntity ) {
				typeFieldObject.hide();		
			} else {
				typeFieldObject.show();
			}

			// hide comparison and value fields if selecting by type ( will drive off type field, not value field showing type)
			if ( jQuery.inArray ( fieldFieldSlug, ["address_type", "email_type", "phone_type"] ) > -1 ) {
				compareFieldObject.hide(); 	// will not be incorporated into query
				newControlObject.hide();		// will not be incorporated into query
			} else {
				// if not type, show comparison except for checked
				if ( newControlObject.hasClass('wic-input-checked') ) { 
					compareFieldObject.hide(); 	// can only be checked or not
					compareFieldObject.val( "=" ); // set in case previous value was set to something else
				} else {
					compareFieldObject.show();
				}
				// and show value				
				newControlObject.show();	
			}

			// managing options, start with show all, then hide as appropriate
			compareFieldObject.find( "option").show()
			// reset comparison after field change unless redo
			if ( ! preserveFieldValues ) { 
				compareFieldObject.val("=");
			}
			// for constituents, always hide issue only comparison options 
			compareFieldObject.find( "option[value^='cat']" ).hide();			

			// for last updated time fields limit options
			// for other date fields, leave all possibilities
			if (  newControlObject.hasClass( "last-updated-time" ) ) {
				compareFieldObject.find( nonQuantitativeOptionString ).hide(); 			
			}	else if ( newControlObject.is( "select" ) ) {
				compareFieldObject.find( nonSelectOptionString ).hide(); 			
			}
		// same sequence as above activity row if swapped an activity field
		} else if ( 'activity-value' == newFieldClass ) { 
	  		var compareFieldObject	= currentBlock.find( ".activity-comparison" );
 			var typeFieldObject		= currentBlock.find( ".activity-type" );
			var newLabel 				= currentBlock.find( ".activity-field :selected").text();
	 		var fieldFieldSlug 		=  newLabel.substring( newLabel.lastIndexOf( ':' ) + 1 );

			// hide comparison and value fields if selecting by type ( will drive off type field, not value field showing type)
			if ( 'activity_type' == fieldFieldSlug ) {
				compareFieldObject.hide(); 	// will not be incorporated into query
				newControlObject.hide();		// will not be incorporated into query
			} else {
				compareFieldObject.show();
				newControlObject.show();	
			}

		  	// show issue autocomplete if issue and not the category multiselect version of issue
	 		if ( newControlObject.hasClass( "issue" ) && ! newControlObject.hasClass( "wic_multi_select") ) { 
				newControlObject.hide(); 
				newControlObject.next().attr( "type", "text" );		
				setUpActivityIssueAutocomplete( newControlObject.parent() );
			} 	else { // rehide autocomplete field which is always present, but does not emit search clauses because transient
				newControlObject.next().attr( "type", "hidden" ); 
			}	
			
			// now do comparison options -- as above, start with show all
			compareFieldObject.find( "option").show();
			// amounts and dates, only show ordinal comparisons
			// note that activity_date is required, so blank/non-blank/null are irrelevant
			if ( newControlObject.hasClass( "activity-amount") || newControlObject.hasClass( "activity-date") || newControlObject.hasClass( "last-updated-time")) { 
				compareFieldObject.find( nonQuantitativeOptionString ).hide();
				compareFieldObject.find( "option[value^='cat']" ).hide(); 			
			} else if ( newControlObject.is ( "select" ) || newControlObject.is ( "div" )  ) {
				if ( newControlObject.hasClass( "issue") ) {  
					compareFieldObject.find( nonSelectOptionString ).hide();
					compareFieldObject.find( nonQuantitativeOptionString ).hide();
				} else {	
					compareFieldObject.find( nonSelectOptionString ).hide();
					compareFieldObject.find( "option[value^='cat']" ).hide();
				} 			
			}	
			// reset comparison after field change unless was to a multiselect or on redo search
			if ( ! newControlObject.is ( "div" ) && ! keptOldMultiSelect && ! preserveFieldValues ) {
				compareFieldObject.val("=");
			}
		} else if ( 'constituent-having-value' == newFieldClass ) {
			// no special rules for constituent-having row -- no type selection, no fields other than from dictionary, no variation in comparison values
			// except -- datepicker vs count
			wicReconcileHavingAggregateWithField( fieldMultivalueBlock ); 
		}
   });

}

// show hide combination options as appropriate -- this is aesthetics ( server parses appropriately regardless )
function wicShowHideAdvancedSearchCombiners() {
	
	// combinations of constituent conditions
	if ( jQuery( "#advanced_search_constituent-control-set" ).children( ".visible-templated-row" ).length > 1 ) {
		jQuery( "#wic-control-constituent-and-or" ).children().show();
	} else {
		jQuery( "#wic-control-constituent-and-or" ).children().hide();
	}
	
	// combinations of activity conditions
	if ( jQuery( "#advanced_search_activity-control-set" ).children( ".visible-templated-row" ).length > 1 ) {
		jQuery( "#wic-control-activity-and-or" ).children().show();
	} else {
		jQuery( "#wic-control-activity-and-or" ).children().hide();
	}

	// combinations of constituent_having conditions
	if ( jQuery( "#advanced_search_constituent_having-control-set" ).children( ".visible-templated-row" ).length > 1 ) {
		jQuery( "#wic-control-constituent-having-and-or" ).children().show();
	} else {
		jQuery( "#wic-control-constituent-having-and-or" ).children().hide();
	}

	// combinations of activity and constituent conditions
	if ( 	jQuery( "#advanced_search_activity-control-set" ).children( ".visible-templated-row" ).length > 0 && 
			jQuery( "#advanced_search_constituent-control-set" ).children( ".visible-templated-row" ).length > 0 ) {
		jQuery( "#wic-control-activity-and-or-constituent" ).children().show();
	} else {
		jQuery( "#wic-control-activity-and-or-constituent" ).children().hide();
	}

}

function wicPrepareConstituentHavingFields( havingBlock ) { 
/*	
	var havingBlockObject = jQuery( havingBlock ); 
	// trim field options
	var removeString = "option[value$=\'BLANK\'], option[value=\'IS_NULL\'], option[value=\'LIKE\'], option[value=\'SCAN\'], option[value^='cat']"
	havingBlockObject.find( ".constituent_having_field" ).find ( ) 


	// trim comparison options
	removeString = "option[value$=\'BLANK\'], option[value=\'IS_NULL\'], option[value=\'LIKE\'], option[value=\'SCAN\'], option[value^='cat']"
	havingBlockObject.find( ".constituent-having-comparison" ).find( removeString ).remove();
*/
}

function wicShowHideHavingClauses() { 
	// only show having block if have chosen constituent as search mode
	if (  'constituent' == jQuery( "#activity_or_constituent" ).val() ) { 
		jQuery( "#wic-field-group-search_constituent_having" ).show();	
	} else {
		jQuery( "#wic-field-group-search_constituent_having" ).hide();
	}
}