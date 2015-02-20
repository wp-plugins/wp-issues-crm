<?php
/*
*
*  class-wic-form-issue-search.php
*
*/

class WIC_Form_issue_Search extends WIC_Form_Parent  {
	
	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'issue' );	
	}

	// choose form buttons
	protected function get_the_buttons ( &$data_array ) {
		
		$button_args_main = array(
			'entity_requested'			=> 'issue',
			'action_requested'			=> 'form_search',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Search', 'wp-issues-crm')
		);	
		
		$buttons =  $this->create_wic_form_button ( $button_args_main );
		
		return ( $buttons );
	}
	
	// define message
	protected function format_message ( &$data_array, $message ) {
		return ( __('Search issues. ', 'wp-issues-crm') . $message );
	}

	// show search controls
	protected function get_the_formatted_control ( $control ) {
		return ( $control->search_control() ); 
	}

	// define legend
	protected function get_the_legends( $sql = '' ) {
		$legend = '<p class = "wic-form-legend">' .  __( 'Issue content (body and title) is searched using Word Press full text scan.' , 'wp-issues-crm' ) . '</p>';
		return ( $legend );
	}

	protected function group_screen ( $group ) {
		return ( true );	
	}
	
	// hooks not implemented
	protected function group_special( $group ) {}
	protected function pre_button_messaging ( &$data_array ){}
   protected function post_form_hook ( &$data_array ) {} 
   protected function supplemental_attributes() {}

}
