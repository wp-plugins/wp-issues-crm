/*
*
* wic-jquery-ui.js
*
*/

jQuery(document).ready(function($) {
	$( ".wic-draggable" ).draggable({
		// zIndex: 99999999,
		// revert: true,
	});
	$( ".wic-droppable" ).droppable({
	//	accept: ".wic-draggable",
		activeClass: "wic-droppable-highlight",
	/*	hoverClass: "wic-droppable-hover",
		tolerance: "pointer",
		drop: function( event, ui ) {
			$( this )
			.addClass( "wic-state-dropped" );
	//       var dropped = ui.draggable;
	//       var droppedOn = $(this);
   //		$(dropped).detach().css({top: 0,left: 10}).appendTo(droppedOn); 
		},
		over: function( event, ui ) {
			$( this )
			.addClass( "wic-droppable-hover" )
		},
		out: function( event, ui ) {
			$( this )
			.removeClass( "wic-state-dropped" )
			var dropped = ui.draggable;
		//	$(dropped).detach();
		//	$( "#wic-draggable-column").prepend ( dropped ); 
		}*/
	});

	if ( $("#wic-form-upload-map").length > 0 ) {
		loadColumnMap();
	};

	$("#wic-droppable-column").click(function(){
			dog_function();		
		}) ;
});

function dog_function () {

console.log('wtfe');

	var data = {
		dog: 1,   
		cat: 2,
		fish: 3,
		cloud: 9
	};

	 wpIssuesCRMAjaxPost ( 'test', 'remap_columns', 'test', data, function( decode ) {
		alert ( 'got this finally' + decode );	
	} );

	
}

var columnMap;

function loadColumnMap() {
	
	wpIssuesCRMAjaxPost( '', 'get_column_map',  jQuery('#ID').val(), '', function(response) {
		// calling parameters are: entity, action_requested, id_requested, data object, callback
		for ( x in response ) {
			console.log ( x ); 
		}
	});
	
}