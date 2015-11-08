/*
*
* wic-ajax-script.js
*
*
* standardizing call -- data is the object to be passed, returns an object
* action is always wp_issues_crm -- posting to Wordpress admin ajax (localized in WIC_Admin_Setup)
* sub_action is the action requested for WP Issues CRM
*  
* note must manipulate the return object within the callback function b/c AJAX post is a synchronous --
*	this function (and so any function calling it) will return before completion of the call.
*
*/
var wpIssuesCRMAjaxPost = function( entity, action, idRequested, data, callback ) {

	var postData = {
		action: 'wp_issues_crm', 
		wic_ajax_nonce: wic_ajax_object.wic_ajax_nonce,
		entity: entity,
		sub_action: action,
		id_requested: idRequested,
		wic_data: JSON.stringify( data )
	};

	// note: cloning an element with ID is bad form, but if clone by class, show two copies; also if have to requests pending only show 1
	// allow first to close spinner even while second pending; in our usage second is secondary (update learning table)
	 if ( 0 == jQuery("#post-form-message-box").children("#ajax-loader" ).length ) {
		ajaxLoader = jQuery("#ajax-loader").clone().appendTo("#post-form-message-box").css("display", "inline");
	 } 

	jQuery.post( wic_ajax_object.ajax_url, postData, function( response ) {
		try {	
			var decoded_response = JSON.parse ( response );
			callback ( decoded_response );
		} catch( err ) {
			if ( 'JSON' == err.message.substring( 0, 4 ) ) {
				console.log ( 'Apparent server side error message (JSON could not parse): ' + response );
				alert ( 'A server side error occurred.  Retry with console log open for details.' );
			} 
			console.log ( 'AJAX post error message at wic-axaj-script.js, line 39 = ' + err.message );
		}
		ajaxLoader.remove();		
	});
 
}



