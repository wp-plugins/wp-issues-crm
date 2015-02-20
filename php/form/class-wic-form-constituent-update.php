<?php
/*
*
*  class-wic-form-constituent-update.php
*
*/

class WIC_Form_Constituent_Update extends WIC_Form_Parent  {

	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'constituent' );	
	}

	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {
		$button_args_main = array(
			'entity_requested'			=> 'constituent',
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

		global $wic_db_dictionary;

		$legend = '';
	
		$individual_required_string = $wic_db_dictionary->get_required_string( "constituent", "individual" );
		if ( '' < $individual_required_string ) {
			$legend =   __('Required for save/update: ', 'wp-issues-crm' ) . $individual_required_string . '. ';
		}
		
		$group_required_string = $wic_db_dictionary->get_required_string( "constituent", "group" );
		if ( '' < $group_required_string ) {
			$legend .=   __('At least one among these fields must be supplied: ', 'wp-issues-crm' ) . $group_required_string . '. ';
		}

		if ( '' < $legend ) {
			$legend = '<p class = "wic-form-legend">' . $legend . '</p>';		
		}
		
		if ( '' < $sql ) {
			$legend .= 	'<p class = "wic-form-legend">' . __('Search SQL was:', 'wp-issues-crm' )	 .  $sql . '</p>';	
		}
		return  $legend;
	}
	
	// support function for message
	public static function format_name_for_title ( &$data_array ) {

		// construct title starting with first name
		$title = 	$data_array['first_name']->get_value(); 
		// if present, add last name, with a space if also have first name		
		$title .= 	( '' == $data_array['last_name']->get_value() ) ? '' : ( ( $title > '' ? ' ' : '' ) . $data_array['last_name']->get_value() );
		// if still empty and email may be available, add email
			// note, the following phrase is broken down for older version of php:
			// if ( '' == $title && isset( $data_array['email']->get_value()[0] ) ) {
			$control = $data_array['email'];
			$result = $control->get_value();
			$email_available = isset( $result[0] );
		if ( '' == $title && $email_available ) {
			// $title = $data_array['email']->get_value()[0]->get_email_address();
			$title = $result[0]->get_email_address();
		} 
		// if still empty, insert word constitent
		$title =		( '' == $title ) ? __( 'Constituent', 'wp-issues-crm' ) : $title;
		
		return  ( $title );
	}
	
	// group screen
	protected function group_screen( $group ) {
		return ( 'search_parms' != $group->group_slug ) ;	
	}
	
	// special group handling for the comment group
	protected function group_special ( $group ) {
		return ( 'comment' == $group );	
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