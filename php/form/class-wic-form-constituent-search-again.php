<?php
/*
*
*  class-wic-form-constituent-search-again.php
*
*/

class WIC_Form_Constituent_Search_Again extends WIC_Form_Constituent_Search  {
	
	// differ from search form only in button definition
	protected function get_the_buttons ( &$data_array ) {

		$buttons = '';
		
		$button_args_main = array(
			'entity_requested'			=> 'constituent',
			'action_requested'			=> 'form_search',
			'button_class'					=> 'button button-primary wic-form-button',			
			'button_label'					=> __('Search Again', 'wp-issues-crm')
		);	
		
		$buttons .= $this->create_wic_form_button ( $button_args_main );

		$button_args_main = array(
			'entity_requested'			=> 'constituent',
			'action_requested'			=> 'save_from_search_request',
			'button_class'					=> 'button button-primary wic-form-button second-position',
			'button_label'					=> __('Save New', 'wp-issues-crm')
		);	
		
		$buttons .=  $this->create_wic_form_button ( $button_args_main );

		$button_args_main = array(
			'entity_requested'			=> 'constituent',
			'action_requested'			=> 'new_form',
			'button_class'					=> 'button button-primary wic-form-button second-position',
			'button_label'					=> __('Start Over', 'wp-issues-crm')
		);	
		
		$buttons .=  $this->create_wic_form_button ( $button_args_main );		
		
		$buttons .=	'<a target = "_blank" href="http://wp-issues-crm.com/?page_id=155">' . __( 'Constituent FAQ', 'wp-issues-crm' ) . '</a>';
			
		return $buttons;
	}
	
}