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

		$( ".wic-form-button" ).on( "click", function ( event ) {
			return ( testForDupOptionValues () );
		});
 
	});

	// test for dup values among option values in option group edit
	function testForDupOptionValues () {

		var optionValues = document.getElementsByClassName( 'wic-input option-value' );

		valuesValues = [];
	
		for ( var i = 0; i < optionValues.length; i++ ) {
			var dbVal = optionValues[i].value;
			if ( null !== optionValues[i].offsetParent ) {
				valuesValues.push( optionValues[i].value.trim() );	
			}
		} 
		var sortedValues = valuesValues.sort();
	
		var results = [];

		for (var j = 0;  j < sortedValues.length - 1; j++) {
		 if (sortedValues[j + 1] == sortedValues[j]) {
			var displayValue; 
			if ( '' == sortedValues[j].trim() ) {
				displayValue = '|BLANK|';
			} else {
					displayValue = '"' + sortedValues[j] + '"';   	 	
			}
			alert ( 'The database value of each option must be unique.  The value ' + displayValue + ' appears more once. ' )
			return false;
			}
		}	
		return true;
	}

})(); // end anonymous namespace enclosure