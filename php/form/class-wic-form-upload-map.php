<?php
/*
* class-wic-form-upload-map.php
*
*
*/

class WIC_Form_Upload_Map extends WIC_Form_Parent  {

	protected function format_tab_titles( &$data_array ) {
		return ( WIC_Entity_Upload::format_tab_titles( $data_array['ID']->get_value() ) );	
	}


	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'upload' );	
	}

	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {}
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		$formatted_message =  sprintf ( __( 'Map fields for %s. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() )  . $message;
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
		return ( 'upload_parameters' == $group->group_slug ) ;	
	}
	
	// special group handling for the comment group
	protected function group_special ( $group ) { 
			return ( 'upload_parameters' == $group );
	}
	
	// function to be called for special group
	protected function group_special_upload_parameters ( &$doa ) {
		// note that this data is also shown as a hidden readonly control in the form
		$output = '';
		$output = '<div id = "wic-draggable-column">';		
		$column_map = unserialize ( $doa['serialized_column_map']->get_value() );
		foreach ( $column_map as $key=>$value ) {
			$output .= '<div class="wic-draggable">' . $key . '</div>';
		}
		$output .= '</div><div id = "wic-droppable-column">';
		global $wic_db_dictionary;
		$fields_array = $wic_db_dictionary->get_uploadable_fields (); 
		foreach ( $fields_array as $field ) {
			$output .= '<div class="wic-droppable">' . $field['entity'] . '--' . $field['field'] . '</div>';
		}
		$output .= '</div><div class = "horbar-clear-fix"></div>';


		return $output; 					
	}
	
	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {}
	 	
}