/*
*
* wic-field-customization.js
*
*/


// self-executing anonymous namespace
( function() {

	jQuery(document).ready(function($) {

		$( "#field_order" ).spinner( {
			min: 0,
			max: 5000
		});
		$( "#field_order" ).prop( "readonly", true );
		
		$( "#option_group" ).change( function() { 
			if ( '' == $( "#option_group" ).val() ) {
			 	$( "#field_type" ).val ("text")
			} else {
				$( "#field_type" ).val ("select")
			}
			// keep list formatter in synch;
			$( "#list_formatter" ).val( $( "#option_group" ).val() );
			
			// remove errors set regardless -- if change to a non-empty value then now consistent with select
			// -- if change to empty value, then resetting to text
			$( "#post-form-message-box" ).text ( 'Save/update field.' );
			$( "#post-form-message-box" ).removeClass ( 'wic-form-errors-found' )
			$( ".wic-form-button" ).prop( "disabled", false );				
		});
		
		// main purpose is to prevent select field from being saved without option group
		// underlying form validation logic doesn't support cross field validation
		// if field_type is select and no option_group, show error, otherwise clear error; also default search choice
		$( "#field_type" ).change ( function () { 
			if ( 'text' == $( "#field_type" ).val() ) {
				$( "#option_group" ).val( '' );
				$( "#like_search_enabled" ).val( 1 );	
				$( "#post-form-message-box" ).text ( 'Save/update field.' );		
				$( "#post-form-message-box" ).removeClass ( 'wic-form-errors-found' )
				$( ".wic-form-button" ).prop( "disabled", false );								
			} else if ( 'date' == $( "#field_type" ).val() ) {
				$( "#option_group" ).val( '' );
				$( "#like_search_enabled" ).val( 0 );
				$( "#post-form-message-box" ).text ( 'Save/update field.' );		
				$( "#post-form-message-box" ).removeClass ( 'wic-form-errors-found' )
				$( ".wic-form-button" ).prop( "disabled", false );			
			} else if ( 'select' == $( "#field_type" ).val() ) {
				if ( '' == $( "#option_group" ).val() ) {
					$( "#post-form-message-box" ).text ( 'Please specify an option group for your select field.' );
					$( "#post-form-message-box" ).addClass ( 'wic-form-errors-found' )
					$( ".wic-form-button" ).prop( "disabled", true );				
				}
				$( "#like_search_enabled" ).val( 0 );			
			}				
			
		});
 
	});

})(); // end anonymous namespace enclosure