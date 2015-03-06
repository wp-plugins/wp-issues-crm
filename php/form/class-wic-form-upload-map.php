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
		// report configuration settings related to upload capacity;
		$legend = '<p>' . __( 'See below for guidance on what WP Issues CRM expects in each target database field.', 
				'wp-issues-crm' ) . '</p>' .
				 '<table id="wp-issues-crm-stats">' .
				 '<tr><td class = "wic-statistic-table-name">' . 'ID: ' . '</td><td>' .  __( 'WP Issues CRM Internal ID -- you will only have this if you started with export from WP Issues CRM.', 'wp-issues-crm' ) 	 . '</td><tr>' . 
				 '<tr><td class = "wic-statistic-table-name">' . 'address_line: ' . '</td><td>' .  __( 'Single field like so: 123 Main St, Apt 1. Do not worry about exactly how you refer to street (ST, st, str) or apartment.', 'wp-issues-crm' ) . '</td><tr>' . 		
				 '<tr><td class = "wic-statistic-table-name">' . 'state: ' . '</td><td>' .  __( 'WP Issues CRM does not care how you abbreviate or do not abbreviate state.', 'wp-issues-crm' ) 	 . '</td><tr>' . 
			'</table>';
			
		$legend = '<div class = "wic-upload-legend">' . $legend . '</div>';		
		return $legend;
		
	}
	// group screen
	protected function group_screen( $group ) { 
		return ( 'upload_parameters' == $group->group_slug  || 'save_options' == $group->group_slug ) ;	
	}
	
	// special use existing groups as special within this form
	protected function group_special ( $group ) { 
			return ( 'upload_parameters' == $group || 'save_options' == $group );
	}
	
	// function to be called for special group
	protected function group_special_upload_parameters ( &$doa ) { 

		$output = '';
		$output_constituent = '';
		$output_activity = '';
			
		// get uploadable fields		
		global $wic_db_dictionary;
		$fields_array = $wic_db_dictionary->get_uploadable_fields (); 

		// assumes fields sorted by entity -- this is how they come from dictionary, but future edits could change
				
		foreach ( $fields_array as $field ) {
			$show_field = $field['label'] > '' ? $field['label'] : $field['field'];
			if ( $field['order'] < 1000 ) {			
				$output_constituent .= '<div class="wic-droppable wic-droppable-'. $field['entity'] . '" id = "wic-droppable-' . $field['field']  . '">' 
							. $show_field . '</div>';
			} else {
				$output_activity .= '<div class="wic-droppable wic-droppable-'. $field['entity'] . '" id = "wic-droppable-' . $field['field']  . '">' 
							. $show_field . '</div>';			
			}
		}
		
		// assemble output
		$output .= '<div id = "wic-droppable-column">';
		$output .= '<h3>' . __( 'Target database fields -- drag and drop upload fields into these targets', 'wp-issue-crm' ) . '</h3>';
		$output .= '<div id = "constituent-targets"><h4>' . __( 'Constituent fields' , 'wp-issues-crm' ) . '</h4>' . $output_constituent . '</div>';		
		$output .= '<div class = "horbar-clear-fix"></div>';
		$output .= '<div id = "activity-targets"><h4>' . __( 'Activity fields' , 'wp-issues-crm' ) . '</h4>'. $output_activity . '</div>';
		$output .= '</div>';



		$output .= '<div class = "horbar-clear-fix"></div>';
		$output .= $doa['ID']->update_control();

		return $output; 					
	}
	
	protected function group_special_save_options ( &$doa ) {
		
		$output = ''; 
				// list fields from upload file to be matched
		$output .= '<div id = "wic-draggable-column">';
		$output .= '<h3>' . __( 'Upload fields to be dropped', 'wp-issue-crm' ) . '</h3>';				
		$column_map = unserialize ( $doa['serialized_column_map']->get_value() );
		foreach ( $column_map as $key=>$value ) {
			$output .= '<div id = "wic-draggable-' . $key . '" class="wic-draggable">' . $key . '</div>';
		}
		$output .= '</div>';
		return $output;
		
	}
	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {}
	 	
}