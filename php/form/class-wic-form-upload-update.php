<?php
/*
*
*  class-wic-form-upload-update.php
*
*/

class WIC_Form_Upload_Update extends WIC_Form_Parent  {

	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'upload' );	
	}

	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {
		$button_args_main = array(
			'entity_requested'			=> 'upload',
			'action_requested'			=> 'form_update',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Update', 'wp-issues-crm')
		);	
		
		$button = $this->create_wic_form_button ( $button_args_main );

		return ( $button  ) ;
	}
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		$formatted_message =  __( 'Upload details update. ' , 'wp-issues-crm' )   . $message;
		return ( $formatted_message );
	}

	// choose update controls for form
	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	// legends
	protected function get_the_legends( $sql = '' ) {
		// report configuration settings related to upload capacity;
		$legend = '<p>' . __( 'The system settings below can be adjusted in your php.ini file.  To upload 
				successfully, file_uploads must be "on" and size and memory parameters must exceed your file size.
				Input time relates to your connection speed.  Execution time relates to the work done in storing
				the uploaded file to the database ( in preliminary form on upload or in final form after mapping ).
				Your system settings are shown below:', 'wp-issues-crm' ) . '</p>' .
			'<ul>' .
				'<li>' . 'file_uploads: ' 				. ( 1 == ini_get ( 'file_uploads' ) ? 'on' : 'off' ) 	. '</li>' .	
				'<li>' . 'upload_max_filesize: ' 	. ini_get ( 'upload_max_filesize' ) 			. '</li>' .			
				'<li>' . 'post_max_size: ' 			. ini_get ( 'post_max_size' ) 					. '</li>' .
				'<li>' . 'memory_limit: ' 				. ini_get ( 'memory_limit' ) 				. '</li>' .
				'<li>' . 'max_input_time: ' 			. ini_get ( 'max_input_time' ) . ' seconds' 	. '</li>' .		
				'<li>' . 'max_execution_time: ' 		. ini_get ( 'max_execution_time' ) . ' seconds' 		. '</li>' .
				'<li>' . 'session.gc_maxlifetime: ' . ini_get ( 'session.gc_maxlifetime' ) . 'seconds' . '</li>' .

			'<ul>';
		$legend = '<div class = "wic-form-legend">' . $legend . '</div>';					
			
		return ( $legend );
	}
	
	// group screen
	protected function group_screen( $group ) {
		return ( 'search_parms' != $group->group_slug ) ;	
	}
	
	// special group handling for the comment group
	protected function group_special ( $group ) {
	//		return ( 'comment' == $group );
			return false;	
	}
	
	// function to be called for special group
	protected function group_special_comment ( &$doa ) {
		return ( WIC_Entity_Comment::create_comment_list ( $doa ) ); 					
	}
	
	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {}
	 	
}