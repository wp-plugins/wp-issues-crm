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
		$title = self::format_name_for_title ( $data_array );
		$formatted_message = sprintf ( __('Update %1$s. ' , 'wp-issues-crm'), $title )  . $message;
		return ( $formatted_message );
	}

	// choose update controls for form
	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	// legends
	protected function get_the_legends( $sql = '' ) {
		// report configuration settings related to upload capacity;
		$legend = '<p>' . __( 'These system settings related to file uploads can be adjusted in your php.ini file:', 'wp-issues-crm' ) . '</p>' .
			'<ul>' .
				'<li>' . 'file_uploads: ' 				. ( 1 == ini_get ( 'file_uploads' ) ? 'on' : 'off' ) 	. '</li>' .	
				'<li>' . 'upload_max_filesize: ' 	. ini_get ( 'upload_max_filesize' ) 			. '</li>' .			
				'<li>' . 'post_max_size: ' 			. ini_get ( 'post_max_size' ) 					. '</li>' .
				'<li>' . 'max_input_tim: ' 			. ini_get ( 'max_input_time' ) . ' seconds' 	. '</li>' .		
				'<li>' . 'max_execution_time: ' 		. ini_get ( 'max_execution_time' ) . ' seconds' 		. '</li>' .
				'<li>' . 'session.gc_maxlifetime: ' . ini_get ( 'session.gc_maxlifetime' ) . 'seconds' . '</li>' .
				'<li>' . 'memory_limit: ' 				. ini_get ( 'memory_limit' ) 				. '</li>' .
			'<ul>';
		$legend = '<div class = "wic-form-legend">' . $legend . '</div>';					
			
		return ( $legend );
	}
	// support function for message
	public static function format_name_for_title ( &$data_array ) {

		// construct title starting with first name
		$title = 	$data_array['upload_time']->get_value() . 'by' . $data_array['upload_time']->get_value();  
		
		return  ( $title );
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