/*
*
* wic-jquery-ui.js
*
* jquery for wp-issues-crm-main and other pages
*
*/
// self-executing anonymous namespace
( function() {
	
	jQuery(document).ready(function($) {
		
		// set date picker
	  $(".datepicker").datepicker({
	  		 dateFormat: "yy-mm-dd"
	  	});
		
		// set up post export button as selectmenu that submits form it is on change
		$( "#wic-post-export-button" ).selectmenu();  	
	
		$( "#wic-post-export-button" ).on( "selectmenuselect", function( event, ui ) {
			myThis = $( this );
			if ( myThis.val() > '' )  {
				$( "#wic_constituent_list_form" ).submit();
			}
		});  	
	
		$( ".wic-favorite-button" ).click(function() {
			starSpan = $( this ).find("span").first();
			var buttonValueArray = $( this ).val().split(",");
			var searchID = buttonValueArray[2];
			var favorite = starSpan.hasClass ( "dashicons-star-filled" );
			var data = { favorite : !favorite };
			console.log ( data );
			wpIssuesCRMAjaxPost( 'search_log', 'set_favorite', searchID, data, function( response ) {
					if ( favorite ) { 
						starSpan.removeClass ( "dashicons-star-filled" );
						starSpan.addClass ( "dashicons-star-empty" )
					} else {
						starSpan.addClass ( "dashicons-star-filled" );
						starSpan.removeClass ( "dashicons-star-empty" )
					}
				});						
		});

	});

})(); // end anonymous namespace enclosure