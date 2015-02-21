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
			'button_label'					=> __('Save', 'wp-issues-crm')
		);	
		return ( $this->create_wic_form_button ( $button_args_main ) ) ;
	}
	
	// group screen
	protected function group_screen ( $group ) {
	//	return ( 'comment' != $group->group_slug &&  'search_parms' != $group->group_slug  )
		return true;	
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