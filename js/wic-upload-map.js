/*
*
* wic-upload-map.js
*
*/

jQuery(document).ready(function($) {
	
	// make field labels draggable
	$( ".wic-draggable" ).draggable({
		revert: "invalid", // note that change this to false once dropped, so no auto move back 
		stack: ".wic-draggable"
	});
	
	// set up target fields as droppable
	$( ".wic-droppable" ).droppable({
		activeClass: "wic-droppable-highlight",
		hoverClass: "wic-droppable-hover",
		tolerance: "fit",
		// drop function should change look and update array
		// should update draggable with a class or other indicator so that it knows it has been dropped		
		drop: function( event, ui ) {
			// show this droppable as occupied
			$( this )
				.addClass( "wic-state-dropped" );
			// associate this draggable with this droppable by adding the droppable's ID as an additional class
			var dropped = ui.draggable;
			marker = $( this ).attr( "id" );
			dropped.addClass( marker );
			dropped.draggable( "option", "revert", false );
			wicUpdateColumnMap ( wicParseIdentifier( dropped.attr( "id" ) ),  wicParseIdentifier ( marker ) );			
			// update array
			console.log ( marker );

		},
		out: function( event, ui ) {
			var movingOut = ui.draggable;
			marker = $( this ).attr( "id" );
			// if moving out of droppable that have been dropped into, 	
			if ( movingOut.hasClass ( marker ) ) {
				// remove marker from draggable
				movingOut.removeClass ( marker )
				// mark the droppable as open
				$( this )
					.removeClass ( 'wic-state-dropped ');
				// update the array to show unassigned
				wicUpdateColumnMap ( wicParseIdentifier( movingOut.attr( "id" ) ),  '' );		
			} // else do nothing -- just passing over
		} 
	});
	
	// if showing column map form, load columns -- belt and suspenders -- also testing for the page when loading script
	if ( $("#wic-form-upload-map").length > 0 ) {
		loadColumnMap();
	};
});

/*
*
* keep column_map object synchronized to both  database and screen
*
*/
var wicColumnMap;

// on initial load, get column from database and move draggables into place
function loadColumnMap() {
	
	wpIssuesCRMAjaxPost( '', 'get_column_map',  jQuery('#ID').val(), '', function( response ) {
		// calling parameters are: entity, action_requested, id_requested, data object, callback
		wicColumnMap = response;
		wicShowColumnMap();
	});
}

// based on a drag action, update column map, both in browser and on server
function wicUpdateColumnMap ( upload_field, entity_field_object ) {
	// update column map in browser
	wicColumnMap[upload_field] = entity_field_object;
	// send column map on server
	wpIssuesCRMAjaxPost( 'upload', 'update_column_map',  jQuery('#ID').val(), wicColumnMap, function( response ) {
		console.log ( response );
		
		// update message as saved?
	});	

}

// show matched fields as matched on form setup  
function wicShowColumnMap() {
	for ( x in wicColumnMap ) {
		if ( wicColumnMap[x] > '' ) {
			// upload field headers have already been loaded -- identify the one associated with x in column map
			xDraggable = jQuery( "#wic-draggable___" + x );
			// mark it with the associated target database field class
			xDraggable.addClass( "wic-droppable" + '___' + wicColumnMap[x].entity + '___' + wicColumnMap[x].field );
			// make it so won't revert to already dropped location if seek to move
			xDraggable.draggable( "option", "revert", false );
			// the target database fields have also been loaded as divs
			yDroppable = jQuery ( "#wic-droppable" + '___' + wicColumnMap[x].entity + '___' + wicColumnMap[x].field );
			// put the upload field into the target div 
			yDroppable.append ( xDraggable );
			// mark the target div as occupied
			yDroppable.addClass ( "wic-state-dropped" );
		}
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