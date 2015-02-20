<?php
/*
*
*  class-wic-form-trend-search-again.php
*
*/

class WIC_Form_Trend_Search_Again extends WIC_Form_Trend_Search  {
	
	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'trend' );	
	}

	// choose form buttons
	protected function get_the_buttons ( &$data_array ) {
		
		$button_args_main = array(
			'entity_requested'			=> 'trend',
			'action_requested'			=> 'form_search',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Search Again', 'wp-issues-crm')
		);	
		
		$buttons =  $this->create_wic_form_button ( $button_args_main );
		
		return ( $buttons );
	}
	
}
