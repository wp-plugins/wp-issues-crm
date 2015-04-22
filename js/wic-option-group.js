/*
*
* wic-option-group.js
*
*/


// self-executing anonymous namespace
( function() {

	jQuery(document).ready(function($) {

		$( ":input.value-order" ).spinner( {
			min: 0,
			max: 5000
		});
		$( "#field_order" ).prop( "readonly", true );
 
	});

})(); // end anonymous namespace enclosure