/*
*
* wic-main.js (functions supporting page wp-issues-crm-main)
*
*/

// self-executing anonymous namespace
( function() {
	
	var wicFinancialCodesArray;
	var wicUseActivityIssueAutocomplete = false;
	var wicUseNameAndAddressAutocomplete = false;
	var wicStandardInputBorder;
	var wicStandardCompareSet;
	var wicActivityFrozenDate;
	/*
	*
	*	First of two document ready instances (the second serves only advance search functions)
	*
	*/
	jQuery(document).ready(function($) {
		
		/*
		* initialize datepickers ( note that also initialize in moreFields ) 
		* use name*='row-template' as not selecter  instead of :hidden so that do add datepicker to fields in initially hidden form groups		
		*/
		// set date picker for non-template, updateable fields other than activity date without a minimum date
	  	$(".datepicker").not( ".activity-date").not("[name*='row-template']").not("[readonly='readonly']").datepicker({
	  		 dateFormat: "yy-mm-dd"
	  	});
		// get frozen date from hidden div (only in constituent save and update forms -- returns blank on other forms )
		wicActivityFrozenDate = $( "#wic-activity-frozen-date" ).text();
		// set datepicker for activity dates with minDate	  	
	  	$(".activity-date").not("[name*='row-template']").not("[readonly='readonly']").datepicker({
	  		 dateFormat: "yy-mm-dd",
	  		 minDate: wicActivityFrozenDate > '' ? wicActivityFrozenDate : null 
	  	});
		// set delegated listener to backup date picker in enforcing the frozen cutoff
		$( "form" ).on( "change", ".activity-date", function ( event ) {
			if ( $( this ).val() > '' && $( this ).val() < wicActivityFrozenDate ) {
				$( this ).val( wicActivityFrozenDate ) 
			}
		});

		/*
		*
		* basic row manipulation functions
		*
		*/

		// set listener for row add buttons in form
		$( ".row-add-button" ).on( "click", function ( event ) {
			var nextID = $( this ).next().attr("id")
			base = nextID.substring(0, nextID.indexOf( '[' ) );
			moreFields ( base );
		});
		
		// set delegated listener for row delete elements in form and which may be added to form
		$( "form" ).on( "change", ".wic-input-deleted", function ( event ) {
			var parentID = $( this ).parents( ".visible-templated-row" ).attr("id");
			hideSelf ( parentID );
		});

		// set delegated listener for show hide toggle buttons
		$( "form" ).on( "click", ".field-group-show-hide-button", function ( event ) {
			var sectionID = $( this ).next().attr("id");
			togglePostFormSection ( sectionID );
		});
		 
		// pick up standard border color to revert to in constituent highlights
		wicStandardInputBorder = $( "#first_name" ).css('border-top-color');
	
		/*
		*
		*  listeners for changes to certain special fields
		*
		*/
		// 
		// automatically set case_status to Open when Assigned
		$( "#case_assigned" ).on( "change", function() {
			if ( $ ( this ).val() > 0 && 0 == $( "#wic-form-constituent-search" ).length ) {
				$( "#case_status" ).val(1)
			}
		});
		// set issue follow_up_status to Open if Assigned	 
		$( "#issue_staff" ).on( "change", function()  {
			if ( $ ( this ).val() > '' && 0 == $( "#wic-form-issue-search" ).length ) {
				$( "#follow_up_status" ).val( "open" )
			}
		});		
		// changes the activity issue link on change of the selected issue		
		$( "#wic-form-constituent-save, #wic-form-constituent-update" ).on( "change", ".issue", function() {
			changeActivityIssueButtonDestination( this );
		});
		
		/*
		*
		* autocomplete logic
		*
		*/
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
		  	
		/*
		* search log support functions
		*/
		// set up favorite button on return to search list
		$( ".wic-favorite-button" ).click(function() {
			starSpan = $( this ).find("span").first();
			var buttonValueArray = $( this ).val().split(",");
			var searchID = buttonValueArray[2];
			var favorite = starSpan.hasClass ( "dashicons-star-filled" );
			// don't unfavorite named items
			if ( favorite && 1 == $( this ).next().find(".pl-search_log-is_named").text() ) {
				alert( 'Cannot unfavorite non-private searches ( those with a Share Name ).' );
				return;
			}
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
		// set up handler for name replacement on right click of list button
		$( ".wic-search-log-list-button" ).on("mousedown", function(e) {
			if ( 3 == e.which ) { 
				doSearchNameDialog( this ); 
			};
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
	*
	*	Utility Functions for basic row manipulations
	*
	*/
	
	/* pair of functions for sending a warning (non-error) message before restoring green light message */
	function sendErrorMessage ( messageText ) {
		var message = document.getElementById( 'post-form-message-box' );
		message.innerHTML = messageText;
		message.className = 'wic-form-errors-found';
		timeout = window.setTimeout ( restoreMessage, 4000 ); 
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
	
		// activate datepicker on child fields (except activity_date)
		jQuery( newFields ).find( ".datepicker" ).not( ".activity-date").datepicker({
				 dateFormat: "yy-mm-dd"
		}); 
		// set datepicker for child activity dates with minDate	  	
	  	jQuery( newFields ).find( ".activity-date" ).datepicker({
	  		 dateFormat: "yy-mm-dd",
	  		 minDate: wicActivityFrozenDate > '' ? wicActivityFrozenDate : null 
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
	
		// initialize fields and refresh contingent displays based on counts if serving the advanced search form
		var parentFormID = jQuery( newFields ).parents('form').attr('id');
		if ( "wic-form-advanced-search" ==  parentFormID  || "wic-form-advanced-search-again" ==  parentFormID  ) {
			wicSwapInAppropriateFields ( newFields );
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
				replaceInDescendants ( newField[i], oldValue, newValue, base )
			}
		}
	}


	// screen delete rows in multivalue fields
	function hideSelf( rowname ) {
		var row = document.getElementById ( rowname );
		var parentFormID = jQuery( row ).parents('form').attr('id')
		if ( "wic-form-advanced-search" !=  parentFormID && "wic-form-advanced-search-again" !=  parentFormID  ) {
			rowClass =row.className; 
			row.className = rowClass.replace( 'visible-templated-row', 'hidden-template' ) ;
			sendErrorMessage ( 'Row will be deleted when you save/update.' )
			window.nextWPIssuesCRMMessage = 'You can proceed.';
			jQuery('#wic-form-constituent-update').trigger('checkform.areYouSure');
		} else {
			jQuery( row ).remove();
			wicShowHideAdvancedSearchCombiners();
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


	/*
	*
	* doSearchNameDialog -- manages naming of searches
	*
	*/
	function doSearchNameDialog ( listButton ) {

		// get necessary values
		var searchID = listButton.value.split(',')[2] ;
		var searchNameElement = jQuery(listButton).find(".pl-search_log-share_name")
		var searchName = searchNameElement.text();
		searchName = 'private' == searchName ? '' : searchName;
		// define dialog box
		var divOpen = '<div title="Enter Search Name">';
		var nameInput = '<input id="new_search_name" type="text" value ="'  + searchName + '"></input>' ;
		var divClose = '<div>';
		dialog = jQuery.parseHTML( divOpen + nameInput + divClose );
		// kill the standard context menu (just for this button)
		var saveContextMenu = document.oncontextmenu 
		document.oncontextmenu = function() {return false;};
		// show the share name dialog instead
		dialogObject = jQuery( dialog );
  		dialogObject.dialog({
  			closeOnEscape: true,
  			close: function ( event, ui ) {
  				document.oncontextmenu = saveContextMenu; 	// restore context menu
  				dialogObject.remove();						// cleanup object
  				},
			position: { my: "left top", at: "left bottom", of: searchNameElement }, 	
			width: 180,
			buttons: [
				{	width: 50,
					text: "OK",
					click: function() {
						var newSearchName = dialogObject.find ( "#new_search_name" ).val();
						wpIssuesCRMAjaxPost( 'search_log', 'update_name', searchID, newSearchName, function ( response ) {
							if ( 1 == response[0] ) {
								searchNameElement.text( newSearchName > '' ? newSearchName : 'private' );
								if( newSearchName > '' ) {
									// when name > '', then database access will always set favorite
									// need to reflect this on client side
									starSpan = jQuery( listButton ).prev().find("span").first()
									starSpan.addClass ( "dashicons-star-filled" );
									starSpan.removeClass ( "dashicons-star-empty" );
									searchNameElement.text ( newSearchName );
									searchNameElement.parent().find(".pl-search_log-is_named").text("1");
								} else {
									searchNameElement.text ('private');
									searchNameElement.parent().find(".pl-search_log-is_named").text("0");
								}
							}
							jQuery( "#post-form-message-box" ).text(response[1]);
							dialogObject.dialog( "close" ); 
						});
					}
				},
				{
					width: 50,
					text: "Cancel",
					click: function() {
						dialogObject.dialog( "close" ); 
					}
				}
			],
  			modal: true,
  		});
	}

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
					changeActivityIssueButtonDestination( activityIssue.get(0) );
				}
		});
	}

	/*
	*
	* Changed Issue Function -- repoints link to view issue
	*
	*/
	function changeActivityIssueButtonDestination( changedIssue ) {	
		// no link on advanced search forms, but may invoke this routine
		if ( 0 < jQuery( "#wic-form-advanced-search, #wic-form-advanced-search-again" ).length ) {
			return;
		}
		// get the button we want to modify and repoint or remove it
		var currentActivityButton = jQuery( changedIssue ).parents( ".wic-multivalue-block.activity" ).find(".wic-activity-issue-link-button");
		if ( currentActivityButton.length > 0 ) {
			if ( changedIssue.value > 0 ) {
				currentActivityButton.val( 'issue,id_search,' + changedIssue.value );
			} else {
				currentActivityButton.remove();	
			}
		// or add the button
		} else if ( changedIssue.value > 0 ) {
			var btn = document.createElement("BUTTON");
			btn.innerHTML = 'View Issue';
			btn.className = 'wic-form-button wic-activity-issue-link-button';
			btn.value = 'issue,id_search,' + changedIssue.value;
			btn.name  = 'wic_form_button';	
			previousGroup = changedIssue.parentNode.previousSibling;
			previousGroup.appendChild(btn);								
		}	
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

	/*
	*
	* Advanced Query functions -- these functions support intuitive display of advanced search query terms
	*
	* Within search term rows:
	* 	(1) primary field selected determines control to be used for value input ( and type fields available )
	*		-- swap in new control via AJAX lookup from field selected (preserve values only if initializing)
	*		-- reset comparison options based on field selected (general, category, nonselect, nonquantitative)
	*		-- hide type option for constituent fields in constituent row
	*		-- hide compare and input options if primary field is a type field
	*		-- hide compare if have a checked field (select version of checked -- is, is not)
	*		-- fire items (2) and (3) for additional adjustments 
	*		-- fires on control change or initializaiton
	*	(2) if change comparison operator, may change control for value input
	*		-- on issues, will swap issue autocomplete in and out based on comparison
	*		-- on other fields, should hide input control if comparison does not require input (e.g., is null)
	*		-- fires on control change or initialization (1) and if change comparison
	*	(3) if change aggregator in having row, may override input field (show count field; hide field selector)
	*		-- fires on control change or initialization (1) or on change aggregator
	* Show/hide combination terms based on whether have >1 rows to combine
	* Show/hide having row depending on whether doing constituent or activity search
	*/
	jQuery(document).ready(function($) {
	
		// quit if not doing an advanced search form
		if ( 0 == $( "#wic-form-advanced-search, #wic-form-advanced-search-again" ).length ) {
			return;
		}
	
		// pick up like option set for restore on value change
		wicStandardCompareSet = $( "#advanced_search_constituent\\[row-template\\]\\[constituent_comparison\\]" ).html();
		/*
		* manage display of correct options for  fields in advanced search
		*/

		// initialize rows -- display correct value based on any previously selected fields
		$( ".wic-multivalue-block" ).each( function () {
			wicSwapInAppropriateFields( this, true );  // true preserve field values
		});

		// set up delegated event listener for changes to field selector in rows
		$( "#wic-form-main-groups" ).on( "change", "[id*='_field']", function ( event ) {
			var changedBlock = $( this ).parents( ".wic-multivalue-block" )[0];
			wicSwapInAppropriateFields( changedBlock, false ); // false says don't preserve values, but will anyway if input to input change 
		});

		// set up delegated event listener for comparison operators (does NOT get called through change fields or on Morefields b/c start /w show)
		$( "#wic-form-main-groups" ).on( "change", "[id*='_comparison']", function ( event ) {
			var changedBlock = $( this ).parents( ".wic-multivalue-block" )[0];
			wicSetFieldDisplay( changedBlock );
		});

		// set up delegated event listener for change to constituent having aggregator (also gets called through change fields)
		$( "#wic-control-advanced-search-constituent-having" ).on( "change", ".constituent-having-aggregator", function ( event ) {
			var changedBlock = $( this ).parents( ".wic-multivalue-block.advanced_search_constituent_having" )[0];
			wicReconcileHavingAggregateWithField( changedBlock );
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


	function wicSwapInAppropriateFields( advancedSearchMultivalueBlock, preserveFieldValues ) {
	
			// set up variables
			var currentBlock 			= jQuery( advancedSearchMultivalueBlock );
			var newLabel 				= currentBlock.find( "[id*='_field'] :selected").text();
			var newValue 				= currentBlock.find( "[id*='_field'] :selected").val();
			var valueField				= currentBlock.find( "[id*='_value']");
			var valueFieldID			= valueField.attr("id");
			var currentEntity			= valueFieldID.substring( valueFieldID.lastIndexOf( '[' ) + 1, valueFieldID.lastIndexOf( '_' ) )		 

			// identify and swap in appropriate types if changing a constituent block (pulling html from hidden template)
			if ( ! preserveFieldValues && "constituent" == currentEntity ){ // preserveFieldValues is true on document.ready; this field is properly set from server
				var fieldEntity 			= newLabel.substring( newLabel.lastIndexOf( ' ' ) + 1, newLabel.lastIndexOf( ':' ) );
				var targetTypeElement 	= jQuery( advancedSearchMultivalueBlock ).find( ".wic-input.constituent-entity-type");
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
			wicAdvancedSearchReplaceControl( advancedSearchMultivalueBlock, valueFieldID, newValue, currentEntity.replace('_','-') + '-value', preserveFieldValues );
		
	}


	// adjust show/hide of fields -- on change comparison or after replace fields (includes issue display logic
	function wicSetFieldDisplay( advancedSearchMultivalueBlock ) {
	
		rowComparisonOperator = jQuery( advancedSearchMultivalueBlock ).find( "[id*='_comparison']" ).val();
		// is the current activity field 'issue'? 
		if ( jQuery( advancedSearchMultivalueBlock ).find( ".issue").length > 0 ) {
			issueFieldObject = jQuery( advancedSearchMultivalueBlock ).find( ".issue"); // get the primary, not the autocomplete
			// change issue control only if needed to change
			if ( issueFieldObject.is("select") && '=' != rowComparisonOperator ) { // swap from autocomplete to category
				valueFieldID = jQuery( advancedSearchMultivalueBlock ).find( ".wic-input.activity-value" ).attr("id");
				wicAdvancedSearchReplaceControl( advancedSearchMultivalueBlock, valueFieldID, 'CATEGORY', 'activity-value', false  )
			} else if ( ! issueFieldObject.is("select") && '=' == rowComparisonOperator  ) { // swap from category to autocomplete 
				wicSwapInAppropriateFields ( advancedSearchMultivalueBlock, false ); // just do as if coming from another field
			}
		} else {
			if ( -1 < jQuery.inArray ( rowComparisonOperator, ["BLANK", "NOT_BLANK", "IS_NULL"] ) ) { 
				jQuery( advancedSearchMultivalueBlock ).find( "[id*='_value']").hide()
			} else {
				jQuery( advancedSearchMultivalueBlock ).find( "[id*='_value']" ).show()
			}
		}
	}


	// activate this when aggregator changes -- is also called on field change and startup through the having field swapper via wicAdvancedSearchReplaceControl
	function wicReconcileHavingAggregateWithField( constituentHavingFieldMultivalueBlock) {
		var currentBlock = constituentHavingFieldMultivalueBlock;
		aggregatorIsCount =  ( 'COUNT' == jQuery( constituentHavingFieldMultivalueBlock ).find( ".constituent-having-aggregator" ).val() );
		currentValueObject = jQuery( constituentHavingFieldMultivalueBlock ).find( ".constituent-having-value" );
		currentFieldObject = jQuery( constituentHavingFieldMultivalueBlock ).find( ".constituent-having-field" );
		currentValueID = currentValueObject.attr("id");
		currentValueValue = currentValueObject.val();

		if ( aggregatorIsCount ) {
			// hide field selector but leave in place for spacing
			currentFieldObject.css( "visibility" , "hidden" ) 
			// substitute a count input field for value field -- plain text, with class activity-amount
			tempCountInput = jQuery.parseHTML ( '<input type = "text" />'); 
			tempCountInputObject = jQuery ( tempCountInput );
			tempCountInputObject.attr( "id", currentValueID );
			tempCountInputObject.attr( "name", currentValueID );
			tempCountInputObject.attr( "class", "wic-input temporary-count-field constituent-having-value" )
			tempCountInputObject.attr( "placeholder", "Count" );
			tempCountInputObject.val( currentValueValue );
			currentValueObject.replaceWith( tempCountInput );
		} else {
			// show field selector
			currentFieldObject.css( "visibility" , "visible" ) 
			// no need to add back date picker -- field swap will do this
			// restore value input field consistent with current field selection
			if ( currentValueObject.hasClass( "temporary-count-field" ) ) {  
				// NB: swap function calls reconcile function, test for class temporary-count-field to prevent looping 
				wicSwapInAppropriateFields( constituentHavingFieldMultivalueBlock, false )
			}
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

			// preserve values on init 
			if ( preserveFieldValues ) { 
				// select option searched for may not be in current select list; add it to preserve integrity of search
				if ( newControlObject.is("select") ) {
					var findString = "option[value=\'" + oldValue + "\']";
					if ( 0 == newControlObject.find( findString ).length ) {
						jQuery('<option/>').attr('value',oldValue).text('added working').appendTo( newControlObject );
					}
				} 
				newControlObject.val( oldValue );
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
		
			/***
			*
			* adjust displays for user understanding
			*
			***/
			var currentBlock 				= jQuery( fieldMultivalueBlock );
			var nonQuantitativeOptionString = "option[value$=\'BLANK\'], option[value=\'IS_NULL\'], option[value=\'LIKE\'], option[value=\'SCAN\']"	
			var nonSelectOptionString 		= "option[value=\'>=\'], option[value=\'<=\'],option[value=\'IS_NULL\'], option[value=\'LIKE\'], option[value=\'SCAN\']"
			var newLabel 					= currentBlock.find( "[id*='_field'] :selected").text();
			var fieldEntity 				= newLabel.substring( newLabel.lastIndexOf( ' ' ) + 1, newLabel.lastIndexOf( ':' ) ) 
			var fieldFieldSlug 				= newLabel.substring( newLabel.lastIndexOf( ':' ) + 1 );
			var compareFieldObject			= currentBlock.find( "[id*='_comparison']");
			var saveCompareValue 			= compareFieldObject.val();
			var typeFieldObject				= currentBlock.find( "[id*='_type']" );

			/*
			* rebuild comparison option set based on field selection
			*/
			// reset comparison option set (will pair down in logic below)
			compareFieldObject.html( wicStandardCompareSet );
			if ( ! newControlObject.is ( "div" ) && ! keptOldMultiSelect && ! preserveFieldValues ) {
				compareFieldObject.val("=");
			} else {
				compareFieldObject.val( saveCompareValue );
			}
			// pair down comparison option set based on selected fields
			if ( newControlObject.hasClass( "activity-amount") || newControlObject.hasClass( "activity-date") || newControlObject.hasClass( "last-updated-time" ) ) { 
				compareFieldObject.find( nonQuantitativeOptionString ).remove();
				compareFieldObject.find( "option[value^='cat']" ).remove();
			} else if ( newControlObject.is ( "select" ) || newControlObject.is ( "div" )  ) {
				if ( newControlObject.hasClass( "issue") ) {  
					compareFieldObject.find( nonSelectOptionString ).remove();
					compareFieldObject.find( nonQuantitativeOptionString ).remove();
				} else {	
					compareFieldObject.find( nonSelectOptionString ).remove();
					compareFieldObject.find( "option[value^='cat']" ).remove();
				} 			
			} else {
				compareFieldObject.find( "option[value^='cat']" ).remove();			
			}	

			/*
			* show/hide
			*/
			// show issue autocomplete if issue and not the category multiselect version of issue
			// can't do this as part of wicSetFieldDisplay because need to have new field back from AJAX
			if ( newControlObject.hasClass( "issue" ) && ! newControlObject.hasClass( "wic_multi_select") ) { 
				newControlObject.hide(); 
				newControlObject.next().attr( "type", "text" );		
				setUpActivityIssueAutocomplete( newControlObject.parent() );
			// otherwise rehide autocomplete field which is always present, but does not emit search clauses because transient
			} 	else if ( 'activity-value' == newFieldClass ) { 
				newControlObject.next().attr( "type", "hidden" ); 
			}	
		
			// hide type selection (is set to blank and is irrelevant) if have a constituent field;
			if ( 'constituent' == fieldEntity ) {
				typeFieldObject.hide();		
			} else {
				typeFieldObject.show();
			}		
			// hide comparison and value if selected a type field
			if ( jQuery.inArray ( fieldFieldSlug, ["activity_type", "address_type", "email_type", "phone_type"] ) > -1 ) {
				compareFieldObject.hide(); 	// will not be incorporated into query
				newControlObject.hide();	// will not be incorporated into query
			// for non-type fields . . .
			} else {
				// show comparison except for checked
				if ( newControlObject.hasClass('advanced-search-checked-substitute') ) { 
					compareFieldObject.hide(); 	// can only be checked or not
				} else {
					compareFieldObject.show();
				}
				// handle final display of value based on aggregate selection
				if ( 'constituent-having-value' == newFieldClass ) {
					wicReconcileHavingAggregateWithField( fieldMultivalueBlock ); 
				// or based on comparison (in case of issue, would loop, but wicSetFieldDisplay tests for issue
				} else {
					wicSetFieldDisplay( fieldMultivalueBlock );
				}
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
			jQuery( "#constituent_and_or" ).val("and");
		}
	
		// combinations of activity conditions
		if ( jQuery( "#advanced_search_activity-control-set" ).children( ".visible-templated-row" ).length > 1 ) {
			jQuery( "#wic-control-activity-and-or" ).children().show();
		} else {
			jQuery( "#wic-control-activity-and-or" ).children().hide();
			jQuery( "#activity_and_or" ).val("and");
		}

		// combinations of constituent_having conditions
		if ( jQuery( "#advanced_search_constituent_having-control-set" ).children( ".visible-templated-row" ).length > 1 ) {
			jQuery( "#wic-control-constituent-having-and-or" ).children().show();
		} else {
			jQuery( "#wic-control-constituent-having-and-or" ).children().hide();
			jQuery( "#constituent_having_and_or" ).val("and");
		}

		// combinations of activity and constituent conditions
		if ( 	jQuery( "#advanced_search_activity-control-set" ).children( ".visible-templated-row" ).length > 0 && 
				jQuery( "#advanced_search_constituent-control-set" ).children( ".visible-templated-row" ).length > 0 ) {
			jQuery( "#wic-control-activity-and-or-constituent" ).children().show();
		} else {
			jQuery( "#wic-control-activity-and-or-constituent" ).children().hide();
			jQuery( "#activity_and_or_constituent" ).val("and");
		}

	}


	function wicShowHideHavingClauses() { 
		// only show having block if have chosen constituent as search mode
		if (  'constituent' == jQuery( "#activity_or_constituent" ).val() ) { 
			jQuery( "#wic-field-group-search_constituent_having" ).show();	
		} else {
			jQuery( "#wic-field-group-search_constituent_having" ).hide();
		}
	}
	
})(); // end anonymous namespace enclosure	