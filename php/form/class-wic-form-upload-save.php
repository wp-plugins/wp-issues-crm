<?php
/*
*
*  class-wic-form-upload-save.php
*
*	-- changes little in extending Update form
*/

class WIC_Form_Upload_Save extends WIC_Form_Upload_Update  {

	// no header tabs
	protected function format_tab_titles( &$data_array ){}
	
	// buttons	
	protected function get_the_buttons ( &$data_array ) {

		$button_args_main = array(
			'entity_requested'			=> 'upload',
			'action_requested'			=> 'form_save',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Upload', 'wp-issues-crm')
		);	
		
		$buttons = $this->create_wic_form_button ( $button_args_main );
		
		$buttons .= '<a href="/wp-admin/admin.php?page=wp-issues-crm-uploads">' . __( 'Back to Uploads List', 'wp-issues-crm' ) . '</a>';
		
		return ( $buttons ) ;

	}
	
	// group screen
	protected function group_screen ( $group ) {
		// upload parameters is the post upload results report, not the upload parameters settings
		return ( 'save_options' == $group->group_slug ||   
					'initial' == $group->group_slug ||
					'upload_tips' == $group->group_slug || 
				   'upload_settings' == $group->group_slug  
					);
	}	

	// special group handling for the comment group
	protected function group_special ( $group ) {
			return ( 'upload_tips' == $group || 'upload_settings' == $group );
	}	
	
	protected function group_special_upload_tips ( &$doa ) {
		$output = __( 'For tips, go to ', 'wp-issues-crm' ) . 
			'<a href="http://wp-issues-crm.com/?page_id=2" target = "_blank">WPissuesCRM.com</a>.';

		return ( $output ); 					
	}	
	
		// legends
	protected function group_special_upload_settings ( &$doa ) { 
		// report configuration settings related to upload capacity;
		$output = 
			'<ul id = "system-settings-list">' .
				'<li>' . 'file_uploads: ' 				. ( 1 == ini_get ( 'file_uploads' ) ? 'on' : 'off' ) 	. '</li>' .	
				'<li>' . 'upload_max_filesize: ' 	. ini_get ( 'upload_max_filesize' ) 			. '</li>' .			
				'<li>' . 'post_max_size: ' 			. ini_get ( 'post_max_size' ) 					. '</li>' .
				'<li>' . 'memory_limit: ' 				. ini_get ( 'memory_limit' ) 				. '</li>' .
				'<li>' . 'max_input_time: ' 			. ini_get ( 'max_input_time' ) . ' seconds' 	. '</li>' .		
				'<li>' . 'max_execution_time: ' 		. ini_get ( 'max_execution_time' ) . ' seconds' 		. '</li>' .
				'<li>' . 'session.gc_maxlifetime: ' . ini_get ( 'session.gc_maxlifetime' ) . 'seconds' . '</li>' .

			'<ul>';
					
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
		return $output;
	}
		
	protected function get_the_legends( $sql = '' ) {
		$legend = '';	
		$legend = '<div class = "wic-upload-legend">' . $legend . '</div>';	
		return ( $legend );
	}

	// message
	protected function format_message ( &$data_array, $message ) {
		$formatted_message =  __('Upload a .csv or .txt file of constituent and/or activity data. ' , 'wp-issues-crm') . $message;
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