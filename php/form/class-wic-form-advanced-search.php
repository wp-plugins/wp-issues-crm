<?php
/*
*
*  class-wic-form-advanced-search.php
*
*/

class WIC_Form_Advanced_Search extends WIC_Form_Parent  {
	
	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'advanced_search' );	
	}


	// no header tabs
	protected function format_tab_titles( &$data_array ){}

	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {
		$button_args_main = array(
			'entity_requested'			=> 'advanced_search',
			'action_requested'			=> 'form_search',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Search', 'wp-issues-crm')
		);	
		
		$button = $this->create_wic_form_button ( $button_args_main );
		
		$button .=	'<a target = "_blank" href="http://wp-issues-crm.com/?page_id=167">' . __( 'Advanced Search Tips', 'wp-issues-crm' ) . '</a>';
			
		return ( $button  ) ;
	}	
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		$formatted_message = sprintf ( __('Advanced search. ' , 'wp-issues-crm') )  . $message;
		return ( $formatted_message );
	}

	// choose update controls for form
	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	// legends
	protected function get_the_legends( $sql = '' ) {

		$legend = '';

		if ( '' < $sql ) {
			$legend .= 	'<p class = "wic-form-legend">' . __('Search SQL was:', 'wp-issues-crm' )	 .  $sql . '</p>';	
		}
		return  $legend;
	}
	


	protected function post_form_hook( &$data_array ) {
		
		$hidden_control_field_templates = array( 
			array (
				'entity_slug' 	=> 'address',
				'field_slug' 	=> 'address_type'
			),	
			array (
				'entity_slug' 	=> 'email',
				'field_slug' 	=> 'email_type'
			),
			array (
				'entity_slug' 	=> 'phone',
				'field_slug' 	=> 'phone_type'
			),		
		);
		
		echo '<div class = "hidden-template">';
		foreach ( $hidden_control_field_templates as $hidden_control_field_template ) { 
			$template = WIC_Control_Factory::make_a_control( 'select' );
			$template->initialize_default_values(  $hidden_control_field_template['entity_slug'], $hidden_control_field_template['field_slug'], 'control-template' );
			echo ( $template->update_control() );
		}		
		echo '</div>';
	
	
	}

	
	// group screen
	protected function group_screen( $group ) {
		return ( true ) ;	
	}
	
	// special group handling for the comment group
	protected function group_special ( $group ) {
		return ( false );	
	}
	

	// hooks not implemented
	public static function format_name_for_title ( &$data_array ) {}
	protected function supplemental_attributes() {}
	protected function pre_button_messaging ( &$data_array ){}

}