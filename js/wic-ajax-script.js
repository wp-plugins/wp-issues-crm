/*
*
* wic-ajax-script.js
*
*/

jQuery(document).ready(function($) {

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
		action: 'wp_issues_crm', 
		wic_ajax_nonce: wic_ajax_object.wic_ajax_nonce,
		sub_action: 'remap_columns',
		dog: 1,   
		cat: 2,
		fish: 3,
		cloud: 5
	};

	jQuery.post( wic_ajax_object.ajax_url, data, function(response) {
		alert('Dog value: ' + response);
	});


}

var columnMap;

function loadColumnMap() {
	
	var data = {
		action: 'wp_issues_crm', 
		wic_ajax_nonce: wic_ajax_object.wic_ajax_nonce,
		sub_action: 'get_column_map',
		id_requested: jQuery('#ID').val()
	}
	
	jQuery.post( wic_ajax_object.ajax_url, data, function(response) {
		columnMap = JSON.parse(response);
		alert ( typeof columnMap ) ;
		var columnList;
		for ( x in columnMap ) {
			console.log ( x ); 
		}
	});
	
}



/* outline:
1) when form loaded for remap, do ajax call to get array
2) when drop or undrop occurs, 
	do call to update array
	do ajax call to save change */

