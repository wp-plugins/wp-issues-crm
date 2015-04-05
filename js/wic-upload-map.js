/*
*
* wic-upload-map.js
*
*/


// enclose mapping variables and functions in self-executing anonymous namespace wrapper function 
( function () {

	var wicColumnMap; // master object synched to screen and database
	var wicSaveMapMessage; // save slot for the welcome message so it can be restored easily after save information shown

	jQuery(document).ready(function($) {
		
		// make field labels draggable
		$( ".wic-draggable" ).draggable({
			disabled: true, 						// do not enable until columns loaded by AJAX;
			revert: "invalid", 					// revert to start column if not dropped; change to false on dropped, so no revert back to droppable 
			stack: ".wic-draggable", 			// keep the moving item on top
			start: function ( event, ui ) { 	// restore original message -- drop any "saved" notation from previous drags
				jQuery ( "#post-form-message-box" ).text( wicSaveMapMessage );	
				$( this ).addClass ( 'moving-draggable' );	
			},
			stop: function ( event, ui ) {		// effect revert to starting column for dropped items that are undropped
				// if not dropped ( i.e., in an invalid position ) and also net set to revert anyway, 
				// animate it slowly back to top of column of pending items 
				// note that revert is true only when starting from initial position in column of pending items			
				if ( ! $( this ).hasClass ( 'wic-draggable-dropped') && false === $( this ).draggable( "option", "revert" ) ) {
					// note where it is and where it is going
					var draggableOffset = ui.offset;	
					var destinationOffset  = $( "#wic-draggable-column" ).offset() ;
					// attach it back to column, where it is going (mirrors actions 4 through 6 of drop event )
					$( this ).detach();
					$( this ).prependTo( "#wic-draggable-column" );
					// but smooth the perceived motion -- keep the actual offset the same and then animate back into place  
					$( this ).css( "top" , draggableOffset.top - destinationOffset.top );
					$( this ).css( "left" , draggableOffset.left - destinationOffset.left );
					$( this ).animate ( {
						top: 0,
						left: 0
					});
				} 		
				$( this ).removeClass ( 'moving-draggable' );
			}
		});
		
		// set up target fields as droppable
		$( ".wic-droppable" ).droppable({
			activeClass: 	"wic-droppable-highlight",
			hoverClass: 	"wic-droppable-hover",
			accept:			".wic-draggable",
			tolerance: 		"pointer",
			drop: function( event, ui ) {
				var dropped = ui.draggable;
				draggableID = dropped.attr( "id" );
				droppableID = $( this ).attr( "id" );
				// function effects 6 additional changes to the draggable and 2 to the droppable 
				wicDropEventDetails ( draggableID, droppableID ); 
				// update array
				wicUpdateColumnMap ( wicParseIdentifier( draggableID ),  wicParseIdentifier ( droppableID ) );
			},
			out: function( event, ui ) {
				var movingOut = ui.draggable;
				marker = $( this ).attr( "id" );
				// if moving out of droppable that have been dropped into, reverse actions taken by function wicDropEventDetails	
				if ( movingOut.hasClass ( marker ) ) {
					// (1) remove marker from draggable
					movingOut.removeClass ( marker )
					// (2) remove dropped state indicator from draggable
					movingOut.removeClass ( "wic-draggable-dropped" );
					// no need to repeat drop action (3) -- revert stays false; 
					// don't know where we are headed -- reverse drop actions (4)-(6) when dropped or if not dropped, when stopped
					$( this )
						// show droppable as open
						.removeClass ( 'wic-state-dropped ')
						// accept any draggable
						.droppable ( "option", "accept", ".wic-draggable" ); 
					// update the array to show unassigned
					wicUpdateColumnMap ( wicParseIdentifier( movingOut.attr( "id" ) ),  '' );		
				} // else do nothing -- just passing over
			} 
		});
		
		// if showing column map form, load columns -- belt and suspenders -- also testing for the page when loading script
		if ( $( "#wic-form-upload-map" ).length > 0 ) {
			loadColumnMap();
		};
	});
	
	/*
	*
	* keep column_map object synchronized to both  database and screen
	*
	*/
	
	// on initial load, get column from database and move draggables into place
	function loadColumnMap() {
		
		wpIssuesCRMAjaxPost( 'upload', 'get_column_map',  jQuery('#ID').val(), '', function( response ) {
			// calling parameters are: entity, action_requested, id_requested, data object, callback
			wicColumnMap = response;
			// loop through the response dropping upload-fields into targets
			for ( x in wicColumnMap ) {
				if ( wicColumnMap[x] > '' ) {
					draggableID = "wic-draggable___" + x ;
					droppableID =  "wic-droppable" + '___' + wicColumnMap[x].entity + '___' + wicColumnMap[x].field ;
					// drop the draggable upload field into the droppable
					wicDropEventDetails ( draggableID, droppableID ) ;
				}
			}	
			// enable the draggables
			jQuery( ".wic-draggable" ).draggable( "enable" );
		});
	
		wicSaveMapMessage = jQuery ( "#post-form-message-box" ).text()
	
	}
	
	// based on a drag action, update column map, both in browser and on server
	function wicUpdateColumnMap ( upload_field, entity_field_object ) {
		// in possible excess of caution, disable draggable during update process; it does not disable the moving item
		// this should be blindingly fast, but in server outage, this might let user know of problem 		
		jQuery ( "#post-form-message-box" ).text( wicSaveMapMessage + " Saving field map . . . ")
		jQuery( ".wic-draggable" ).draggable( "disable" );	
		// update column map in browser
		wicColumnMap[upload_field] = entity_field_object;
		// send column map on server
		wpIssuesCRMAjaxPost( 'upload', 'update_column_map',  jQuery('#ID').val(), wicColumnMap, function( response ) {
			// reenable draggables after update complete -		
			jQuery( ".wic-draggable" ).draggable( "enable" );
			jQuery ( "#post-form-message-box" ).text( wicSaveMapMessage + " Field map saved.")
		});
		// also send the particular map update to the server for learning purposes, but only for non-generic column titles
		if ( upload_field.slice( 0 , 7 ) != 'COLUMN_' || upload_field.length < 7 ) {
				wpIssuesCRMAjaxPost( 'upload', 'update_interface_table',  upload_field, entity_field_object, function( response ) {
				});  
		}	
	
	}
	
	// extract field and entity from droppable entity OR extract upload_field from draggable entity
	function wicParseIdentifier( identifier ) {
		// three underscores is separator
		var first___ 	= identifier.indexOf( '___' );
		var second___ 	= identifier.lastIndexOf ( '___');
		if ( second___ === first___ ) {  // can handle upload field (draggable) identifiers with one separator 
			var uploadField 		= identifier.slice ( second___ + 3 );
			return ( uploadField );		
		} else {									 // can also handle database field with two which return as objects
			var entity 		= identifier.slice ( first___ + 3, second___ );
			var field 		= identifier.slice ( second___ + 3 );
			return_object 	= {
				'entity' : entity,
				'field'	: field
			}  
			return ( return_object ) ;
		}	
	}
	
	// used in dropEvent and also in initial load
	function wicDropEventDetails ( draggableID, droppableID ) {
		// get the objects from the identifiers
		wicDropped = jQuery( "#" + draggableID );
		wicDroppable = jQuery( "#" + droppableID );
		// no take six actions as to the dropped item
		// (1) mark dropped with an identifier from the droppable
		wicDropped.addClass( droppableID );
		// (2) mark dropped as in the dropped state
		wicDropped.addClass( "wic-draggable-dropped" );
		// (3) prevent dropped from reverting to here 
		wicDropped.draggable( "option", "revert", false ); // if remains true will revert to this place on next move
		// (4) detach dropped from draggable column or from previous droppable
		wicDropped.detach();
		// (5) append dropped to the droppable
		wicDropped.appendTo( wicDroppable );
		// (6) reset css to pre-drag relative position, zero
		wicDropped.css( "top" , "0" );
		wicDropped.css( "left" , "0" );
		// now take two actions as to the droppable
		wicDroppable
			// show this droppable as occupied
			.addClass( "wic-state-dropped" ) // change css
			// change accept parameter to only the current draggable (note that can't just accept nothing b/c won't register the out event)				
			.droppable ( "option", "accept", "." + droppableID ); 
	}

})(); // close wicUploadMap namespace wrapper



