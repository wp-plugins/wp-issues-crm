<?php
/*
*
*  class-wic-form-constituent-search.php
*
*/

class WIC_Form_Constituent_Search extends WIC_Form_Parent  {
	
	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'constituent' );	
	}

	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {

		$buttons = '';
		
		$button_args_main = array(
			'entity_requested'			=> 'constituent',
			'action_requested'			=> 'form_search',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Search', 'wp-issues-crm')
		);	
		$buttons .= $this->create_wic_form_button ( $button_args_main );
	
		return $buttons;
		
	}
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		return ( __('Search constituents. ', 'wp-issues-crm') . $message );
	}

	// chose search controls
	protected function get_the_formatted_control ( $control ) {
		return ( $control->search_control() ); 
	}

	// legends for like and soundex searching, driven by control fields in data dictionary
	protected function get_the_legends( $sql = '' ) {

		global $wic_db_dictionary;


		$legend = '';
	
		$like_string = $wic_db_dictionary->get_match_type_string( "constituent", "1" );
		$soundex_string = $wic_db_dictionary->get_match_type_string( "constituent", "2" );
		$joined_string = $like_string . ( $like_string > '' && $soundex_string > ''  ? ', ' : '' ) . $soundex_string;
		
		if ( '' < $like_string || '' < $soundex_string ) {
			$legend .=  sprintf ( __( '%s can be searched using right wild card matching. ', 'wp-issues-crm' ), $joined_string  );
		}

		if ( '' < $soundex_string ) {
			$legend .=  sprintf ( __( '%s can also be searched using Soundex matching. ', 'wp-issues-crm' ), $soundex_string );
		}

		if ( '' < $legend ) {
			$legend = '<p class = "wic-form-legend">' . $legend . 
					__( 'The default setting is right wild card.  See Name Match search options on this screen. 
						Text area fields ( like activity notes ) are	always searched using a full text scan.', 
						'wp-issues-crm') 
			. '</p>';		
		}

		return  $legend;
	}
	
	// group screen -- groups not to include
	protected function group_screen ( $group ) {
		return ( 'save_options' != $group->group_slug && 'comment' != $group->group_slug );	
	}	

	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function group_special( $group ) {}
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {} 

}