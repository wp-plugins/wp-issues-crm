<?php
/*
*
*  class-wic-form-user-update.php
*
*/

class WIC_Form_User_Update extends WIC_Form_Parent  {

	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'user' );	
	}

	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {
		$button_args_main = array(
			'entity_requested'			=> 'user',
			'action_requested'			=> 'form_update',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Update', 'wp-issues-crm')
		);	
		return ( $this->create_wic_form_button ( $button_args_main ) ) ;
	}
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		$display_name = self::format_name_for_title ( $data_array );
		$formatted_message = sprintf ( __('Set Preferences for %1$s. ' , 'wp-issues-crm'), $display_name )  . $message;
		return ( $formatted_message );
	}

	// choose update controls for form
	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	// legends
	protected function get_the_legends( $sql = '' ) {}
	
	// support function for message
	public static function format_name_for_title ( &$data_array ) {
	
		return  ( $data_array['display_name']->get_value() );
	}
	
	// group screen -- don't show dropdown preferences unless global settings allow it
	protected function group_screen( $group ) { 
		$wic_plugin_options = get_option( 'wp_issues_crm_plugin_options_array' ); 
		if ( 'user' == $group->group_slug && isset ( $wic_plugin_options['allow_issue_dropdown_preferences'] ) ) {
			return ( true );	
		} elseif ( 'user' != $group->group_slug ) {
			return (true);		
		}
	}
	
	// special group handling for the comment group
	protected function group_special ( $group ) {
		return ( false );	
	}

	protected function pre_button_messaging ( &$data_array ){}

	
	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function post_form_hook ( &$data_array ) {}
	 	
}