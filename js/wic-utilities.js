/*
*
* wic-utilities.js (generally available ui services and functions)
*
*/


// self-executing anonymous namespace
( function() {
	
	jQuery(document).ready(function($) {
		
		// note that have to reinitialize datepickers for hidden-template fields anyway and cleaner to skip them here (additional initialization in wic-main.js)
		// alt approach was remove class for datepicker -- jQuery( newFields ).find( ".datepicker" ).removeClass('hasDatepicker');		
		
		// set date picker
	  $(".datepicker").not(":hidden").datepicker({
	  		 dateFormat: "yy-mm-dd"
	  	});

	});

})(); // end anonymous namespace enclosure


/* pair of functions for sending a temporary warning message before restoring green light message */
function sendErrorMessage ( messageText ) {
	var message = document.getElementById( 'post-form-message-box' );
	permMessage = message.innerHTML
	message.innerHTML = messageText;
	message.className = 'wic-form-errors-found';

	timeout = window.setTimeout ( restoreMessage, 12000 ); 
}

function restoreMessage (  ) {
	var message = document.getElementById( 'post-form-message-box' );
	message.innerHTML = window.nextWPIssuesCRMMessage;
	message.className = 'wic-form-good-news';
}


