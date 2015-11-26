<?php
/*
*
*  class-wic-form-upload-update.php
*
*/

class WIC_Form_Upload_Update extends WIC_Form_Parent  {

	protected function format_tab_titles( &$data_array ) {
		return ( WIC_Entity_Upload::format_tab_titles( $data_array['ID']->get_value() ) );	
	}


	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'upload' );	
	}

	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {
		$button_args_main = array(
			'entity_requested'			=> 'upload',
			'action_requested'			=> 'form_update',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Update', 'wp-issues-crm')
		);	
		
		$buttons = $this->create_wic_form_button ( $button_args_main );

		$buttons .= '<a href="' . site_url() . '/wp-admin/admin.php?page=wp-issues-crm-uploads">' . __( 'Back to Uploads List', 'wp-issues-crm' ) . '</a>';

		return ( $buttons  ) ;
	}
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		$formatted_message =  __( 'Upload details. ' , 'wp-issues-crm' )   . $message;
		return ( $formatted_message );
	}

	// choose update controls for form
	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	// legends
	protected function get_the_legends( $sql = '' ) {
	}
	
	// group screen
	protected function group_screen( $group ) {
		return ( 'initial' == $group->group_slug || 
					'upload_parameters' == $group->group_slug  
		  ) ;	
	}
	
	// special group handling for the upload parameters group
	protected function group_special ( $group ) {
			return ( 'upload_parameters' == $group );
	}
	
	// function to be called for special group
	protected function group_special_upload_parameters ( &$doa ) {

		// note that this data is also shown as a hidden readonly control in the form
		$array = json_decode ( $doa['serialized_upload_parameters']->get_value() );
		$output = '<table id="wp-issues-crm-stats">';
		foreach ($array as $key=>$value ) {
			if ( 'includes_column_headers' == $key ) {
				$value = ( 1 == $value ) ? 'Yes' : 'No';			
			}
			$output .= '<tr><td class = "wic-statistic-table-name">' . esc_html ( $key  ) . '</td><td>' . esc_html ( $value ) . '</td><tr>';
			 		
		}
		$output .= '</table>';
		return $output; 					
	}
	
	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {}
	 	
}