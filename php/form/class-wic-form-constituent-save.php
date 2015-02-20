<?php
/*
*
*  class-wic-form-constituent-save.php
*
*	-- changes little in extending Update form
*/

class WIC_Form_Constituent_Save extends WIC_Form_Constituent_Update  {

	// buttons	
	protected function get_the_buttons ( &$data_array ) {
		$button_args_main = array(
			'entity_requested'			=> 'constituent',
			'action_requested'			=> 'form_save',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Save', 'wp-issues-crm')
		);	
		return ( $this->create_wic_form_button ( $button_args_main ) ) ;
	}
	
	// group screen
	protected function group_screen ( $group ) {
		return ( 'comment' != $group->group_slug &&  'search_parms' != $group->group_slug  );	
	}	
	
	// message
	protected function format_message ( &$data_array, $message ) {
		$formatted_message =  __('Save new constituent. ' , 'wp-issues-crm') . $message;
		return $formatted_message; 
	}

	// use save controls
	protected function get_the_formatted_control ( $control ) {
		return ( $control->save_control() ); 
	}

	
}