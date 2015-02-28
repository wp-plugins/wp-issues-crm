<?php
/*
*
*  class-wic-form-upload-save.php
*
*	-- changes little in extending Update form
*/

class WIC_Form_Upload_Save extends WIC_Form_Upload_Update  {

	// buttons	
	protected function get_the_buttons ( &$data_array ) {
		$button_args_main = array(
			'entity_requested'			=> 'upload',
			'action_requested'			=> 'form_save',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Upload', 'wp-issues-crm')
		);	
		return ( $this->create_wic_form_button ( $button_args_main ) ) ;
	}
	
	// group screen
	protected function group_screen ( $group ) {
		// upload parameters is the post upload results report, not the upload parameters settings
		return ( 'upload_parameters' != $group->group_slug  );
	}	

	// special group handling for the comment group
	protected function group_special ( $group ) {
			return ( 'upload_tips' == $group );
	}	
	
	protected function group_special_upload_tips ( &$doa ) {
		$output = '<ul id = "upload-tips" >' .
			'<li>' . __( 'WP Issues CRM expects upload files to be in .csv or .txt format or a similar format 
					that includes rows of values separated by a delimiter.', 'wp-issues-crm' ) . '</li>' .
			'<li>' . __( 'With .csv files, you probably will have smooth sailing without touching any settings.', 'wp-issues-crm' ) . '</li>' .
			'<li>' . __( 'Set the delimiter to "Tab" for a .txt file.', 'wp-issues-crm' ) . '</li>' .
			'<li>' . __( 'In this initial upload step, you should not need to worry about any field formatting issues.  WP Issues CRM
					load all data to an initial staging file in which all fields are treated as plain text with a maximum length of 65,535.', 'wp-issues-crm' ) . '</li>' .
			'<li>' . __( 'In case of doubt, view your file in any text reader to identify the delimiter (character between fields) and 
					enclosure (character surrounding some multi-word fields).', 'wp-issues-crm' ) . '</li>' . 
			'<li>' . __( 'You will rarely need to change the escape character.  That is the character used to indicate that 
					a character that would otherwise be taken as an enclosure should be read as literal.  Back-slash is standard.  An example would be when
					the delimiter is a single quote and a name has an apostrophe in it (also represented by a single quote).', 'wp-issues-crm' ) . '</li>' .					
			'<li>' . __( 'You will rarely need to change the line length setting.  Increase it to accommodate large text fields (for example the body of an email).', 'wp-issues-crm' ) . '</li>' .
			'<li>' . __( 'Check the systems settings inventory further below if an upload is crashing.  Larger uploads
					can exceed several different system settings and you may need help from an administrator to change your system settings.', 'wp-issues-crm' ) . '</li>' .
			'<li>' . __( 'WP Issues CRM is economical in the use of memory for uploads, so your most likely problems 
					are upload_max_filesize and post_max_size (which must exceed upload_max_filesize ).', 'wp-issues-crm' ) . '</li>' .
			'<li>' . __( 'WP Issues CRM sets the maximum execution time to 20 minutes (1200 seconds) -- only a huge file or a very weak server will approach this limit.
					Files up to 100 megabytes in size should load in under 3 minutes (plus transmission time if over a remote connection).', 'wp-issues-crm' ) . '</li>'	.
		'<ul>';			
		
		return ( $output ); 					
	}	
	
		// legends
	protected function get_the_legends( $sql = '' ) {
		// report configuration settings related to upload capacity;
		$legend = '<p>' . __( 'The system settings below can be adjusted with the assistance of your hosting provider.  To upload 
				successfully, file_uploads must be "on" and size parameters must exceed your file size.
				Input time relates to your connection speed.  Execution time relates to the work done in storing
				the uploaded file to the database ( in preliminary form on upload or in final form after mapping ). WP Issues
				CRM is able to alter max_execution_time dynamically in many installations. Generally, you should not have 
				problems with your memory_limit while uploading files with WP Issues CRM. Your system settings are shown below:', 
				'wp-issues-crm' ) . '</p>' .
			'<ul>' .
				'<li>' . 'file_uploads: ' 				. ( 1 == ini_get ( 'file_uploads' ) ? 'on' : 'off' ) 	. '</li>' .	
				'<li>' . 'upload_max_filesize: ' 	. ini_get ( 'upload_max_filesize' ) 			. '</li>' .			
				'<li>' . 'post_max_size: ' 			. ini_get ( 'post_max_size' ) 					. '</li>' .
				'<li>' . 'memory_limit: ' 				. ini_get ( 'memory_limit' ) 				. '</li>' .
				'<li>' . 'max_input_time: ' 			. ini_get ( 'max_input_time' ) . ' seconds' 	. '</li>' .		
				'<li>' . 'max_execution_time: ' 		. ini_get ( 'max_execution_time' ) . ' seconds' 		. '</li>' .
				'<li>' . 'session.gc_maxlifetime: ' . ini_get ( 'session.gc_maxlifetime' ) . 'seconds' . '</li>' .

			'<ul>';
		$legend = '<div class = "wic-upload-legend">' . $legend . '</div>';					
		/*** 
		*	ini_system can only be set in php.ini or httpd.conf -- the others can be set in  php.ini, .htaccess, httpd.conf or .user.ini  	
		*
		* file_uploads 	  			"1" 		PHP_INI_SYSTEM 	
		* max_file_uploads 			20 		PHP_INI_SYSTEM 	
		* upload_max_filesize 		"2M" 		PHP_INI_PERDIR
		* post_max_size 				"8M" 		PHP_INI_PERDIR ( must be greater than upload_max_filesize )
		* memory_limit 				"128M" 	PHP_INI_ALL  	( must be greater than post_max_size -- for this script, s/b 2+ times upload file size attempted )
		* max_input_time 				"-1" 		PHP_INI_PERDIR ( time from invocation of php at server to when execution begins -- parsing of input [and xmit?]  )
		* max_execution_time 		"30" 		PHP_INI_ALL  	( can be altered by set_time_limit ))
		* session.gc_maxlifetime 	"1440" 	PHP_INI_ALL  	( seconds to garbage clean up -- not likely the limiting factor )
		* 
		* Note that memory limit may also be set in Wordpress at 256 -- override wp_config: define( 'WP_MAX_MEMORY_LIMIT' , '999M' );
		*
		* http://www.ewhathow.com/2013/09/how-to-temporarily-set-memory-limit-to-unlimited-in-php/
		*
		* see short hand http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
		***********/	
		return ( $legend );
	}

	// message
	protected function format_message ( &$data_array, $message ) {
		$formatted_message =  __('Upload a file. ' , 'wp-issues-crm') . $message;
		return $formatted_message; 
	}

	// use save controls
	protected function get_the_formatted_control ( $control ) {
		return ( $control->save_control() ); 
	}

	// add attributes to the form tag -- here to include a file
	protected function supplemental_attributes() {
		echo 'enctype="multipart/form-data"';
	}
	
}