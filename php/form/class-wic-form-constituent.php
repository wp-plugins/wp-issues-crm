<?php
/*
*
*  class-wic-form-constituent.php
*
*/

class WIC_Form_Constituent extends WIC_Form_Parent  {
	
	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'constituent' );	
	}

	// hooks not implemented
	protected function format_tab_titles( &$data_array ){}
	protected function get_the_buttons ( &$data_array ) {}
	protected function format_message ( &$data_array, $message ) {}
	protected function get_the_formatted_control ( $control ) {}
	protected function get_the_legends( $sql = '' ) {}
	protected function group_screen ( $group ) {}	
	protected function supplemental_attributes() {}
	protected function group_special( $group ) {}
	protected function pre_button_messaging ( &$data_array ){}


	/* use the post_form_hook to put a json string in the form identifying financial transaction activity type codes */
	/* also set flags for use of activity and name/address autocomplete */
	protected function post_form_hook ( &$data_array )  {
		
		/* get option array */
		$wic_option_array = get_option('wp_issues_crm_plugin_options_array');
		
		/* create json string of array of financial transaction activity_codes */ 
		$transaction_type_code_string = json_encode ( explode (',' , $wic_option_array['financial_activity_types'] ) );
		
		echo '<div id="financial_activity_types" class="hidden-template">' . $transaction_type_code_string . '</div>';
		
		/* also check whether activity searching disallowed or supressed*/
		echo '<div id="use_activity_issue_autocomplete" class="hidden-template">' . 
				( ( ! isset( $wic_option_array['disallow_activity_issue_search'] ) && 
				( 0 == WIC_DB_Access_WP_User::get_wic_user_preference( 'activity_issue_simple_dropdown' ) ) ) ? 'yes' : 'no' ) . 
			'</div>';
		
		
		echo '<div id="use_name_and_address_autocomplete" class="hidden-template">' . 
				( 0 == WIC_DB_Access_WP_User::get_wic_user_preference( 'disable_autocomplete' ) ? 'yes' : 'no' ) . 
			'</div>';

	}
	

}