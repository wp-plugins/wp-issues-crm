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
	}
	
	// group screen
	protected function group_screen( $group ) {
		return ( 'save_options' != $group->group_slug && 'upload_tips' != $group->group_slug   ) ;	
	}
	
	// special group handling for the comment group
	protected function group_special ( $group ) {
			return ( 'upload_parameters' == $group );
	}
	
	// function to be called for special group
	protected function group_special_upload_parameters ( &$doa ) {
		return ( $doa['serialized_upload_parameters']->get_value() ); 					
	}
	
	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {}
	 	
}