/*
*
* wic-jquery-ui.js
*
*/

jQuery(document).ready(function($) {
	
	// make field labels draggable
	$( ".wic-draggable" ).draggable({
		stack: ".wic-draggable",
		stop: function ( event, ui ) {
			alert ('stopped');
			}
	});
	
	// make their home div droppable on return
	$( ".wic-draggable-column" ).droppable({
		deactivate: function( event, ui ) {
			alert ( 'column deactivated' );
	  	} 	
	});	
	
	// set up target fields as droppable
	$( ".wic-droppable" ).droppable({
		activeClass: "wic-droppable-highlight",
		hoverClass: "wic-droppable-hover",
		tolerance: "fit",
		drop: function( event, ui ) {
			$( this )
			.addClass( "wic-state-dropped" );
			alert ( 'dropped');	      
			var dropped = ui.draggable;
	      var droppedOn = $(this);
   		$( dropped )
   			.detach()
   			.css( { 
   				"bottom": "0",
   				"right": "0" ,
   				"top": "0",
   				"left": "0"
   				} )
   			.appendTo(droppedOn); 
		},
		over: function( event, ui ) {
			$( this )
			.addClass( "wic-droppable-hover" )
		},
		out: function( event, ui ) {
			$( this )
			.removeClass( "wic-state-dropped" );
		/*	var dropped = ui.draggable;
	  		$( dropped )
   			.detach()
   			.css( { 
   				"bottom": "initial",
   				"right": "initial" ,
   				"top": "initial",
   				"left": "initial"
   				} )
				.appendTo ( "#wic-draggable-column" ); */
				// alert ( 'out' ); 
		} 
	});

	
	// if showing column map form, load columns
	if ( $("#wic-form-upload-map").length > 0 ) {
		loadColumnMap();
	};
});

/*
*
* column map synchronized between database and screen
* 
*
*/
var columnMap;

function loadColumnMap() {
	
	wpIssuesCRMAjaxPost( '', 'get_column_map',  jQuery('#ID').val(), '', function(response) {
		// calling parameters are: entity, action_requested, id_requested, data object, callback
		columnMap = response;
		showColumnMap();
	});
}

function showColumnMap() {
	console.log ( JSON.stringify ( columnMap['first_name'] ) );
	// show matched fields as matched
	for ( x in columnMap ) {
		if ( columnMap[x] > '' ) {
			xDraggable = jQuery( "#wic-draggable-" + x );
			yDroppable = jQuery ( "#wic-droppable-" + columnMap[x].field );
			yDroppable.append ( xDraggable );
			yDroppable.addClass ( "wic-state-dropped" );
		}
	}	
}