<?php
/*
*
*  class-wic-form-option-group-save.php
*
*/

class WIC_Form_Option_Group_Save extends WIC_Form_Option_Group_Update  {
	
	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {

		$buttons = '';
		
		$button_args_main = array(
			'entity_requested'			=> 'option_group',
			'action_requested'			=> 'form_save',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Save', 'wp-issues-crm')
		);	
		$buttons .= $this->create_wic_form_button ( $button_args_main );

		$buttons .= '<a href="/wp-admin/admin.php?page=wp-issues-crm-options">' . __( 'Back to Options List', 'wp-issues-crm' ) . '</a>';

		return $buttons;
		
	}
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		return ( __('Save New Option Group. ', 'wp-issues-crm') . $message );
	}

	// chose search controls
	protected function get_the_formatted_control ( $control ) {
		return ( $control->save_control() ); 
	}

	// screen in all groups (only one)
	protected function group_screen ( $group ) {
		return true;	
	}

	// hooks not implemented
	protected function get_the_legends( $sql = '' ) {}	
	protected function group_special( $group ) {} // don't want to show the value usage on a save (can't);
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {} 

}