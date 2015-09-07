<?php
/*
*
*  class-wic-form-advanced-search-again.php
*
*/

class WIC_Form_Advanced_Search_Again extends WIC_Form_Advanced_Search  {
	

	// no header tabs
	protected function format_tab_titles( &$data_array ){}

	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {
		
		$buttons = '';
		
		$button_args_main = array(
			'entity_requested'			=> 'advanced_search',
			'action_requested'			=> 'form_search',
			'button_class'					=> 'button button-primary wic-form-button',			
			'button_label'					=> __('Search Again', 'wp-issues-crm')
		);	
		
		$buttons .= $this->create_wic_form_button ( $button_args_main );

		$button_args_main = array(
			'entity_requested'			=> 'advanced_search',
			'action_requested'			=> 'new_form',
			'button_class'					=> 'button button-primary wic-form-button second-position',
			'button_label'					=> __('Start Over', 'wp-issues-crm')
		);	
		
		$buttons .=  $this->create_wic_form_button ( $button_args_main );		


		
		$buttons .=	'<a target = "_blank" href="http://wp-issues-crm.com/?page_id=167">' . __( 'Advanced Search Tips', 'wp-issues-crm' ) . '</a>';
			
		return ( $buttons  ) ;
	}	
	

}