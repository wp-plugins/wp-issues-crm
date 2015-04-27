/*
*
* wic-jquery-ui.js
*
*/

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

});

