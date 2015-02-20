<?php
/*
*
*  class-wic-form-data-dictionary-update.php
*
*/

class WIC_Form_Data_Dictionary_Update extends WIC_Form_Parent  {
	
	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'data_dictionary' );	
	}

	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {

		$buttons = '';
		
		$button_args_main = array(
			'entity_requested'			=> 'data_dictionary',
			'action_requested'			=> 'form_update',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Update', 'wp-issues-crm'),
		);	
		$buttons .= $this->create_wic_form_button ( $button_args_main );
		
		$buttons .= '<a href="/wp-admin/admin.php?page=wp-issues-crm-fields">' . __( 'Back to Fields List', 'wp-issues-crm' ) . '</a>';

		return $buttons;
		
	}
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		return ( __('Update Custom Field. ', 'wp-issues-crm') . $message );
	}

	// chose search controls
	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	// screen in all groups (only one)
	protected function group_screen ( $group ) {
		return true;	
	}

	protected function group_special( $group_slug ) {
		return ( 'current_field_config' == $group_slug );
	}

	protected function group_special_current_field_config () {

		// get form fields
		$form_fields = WIC_DB_Access_Dictionary::get_current_customizable_form_field_layout( 'constituent' );

				
		//layout table 		 
		$output = '<table id="wp-issues-crm-stats"><tr>' .
			'<th class = "wic-statistic-text">' . __( 'Screen Group', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic-text">' . __( 'Visible Name of Field', 'wp-issues-crm' ) . '</th>'	.	
			'<th class = "wic-statistic">' . __( 'Order', 'wp-issues-crm' ) . '</th>'	.								
		'</tr>';
		
		// create rows for table
		foreach ( $form_fields as $row ) { 
			$output .= '<tr>' .
			'<td class = "wic-statistic-table-name">' . $row->group_label . '</td>' .
			'<td class = "wic-statistic-text" >' . $row->field_label . '</td>' .
			'<td class = "wic-statistic" >' . $row->field_order . '</td>' .
			'</tr>';
		} 
		$output .= '</table>';
		
		return ( $output );
			
	} 

	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function get_the_legends( $sql = '' ) {}	
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {} 

}