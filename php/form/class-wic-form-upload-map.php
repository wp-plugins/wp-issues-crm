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

	// no buttons in this form, ajax loader comes up as display: none -- available to display in an appropriate place
	protected function get_the_buttons ( &$data_array ) { 
		$buttons =  '<span id = "ajax-loader">' .
			'<img src="' . plugins_url( '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'ajax-loader.gif' , __FILE__ ) .
			'"></span>'; 
		return ( $buttons ); 
	}
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		$formatted_message =  sprintf ( __( 'Map fields from %s to WP Issues CRM fields. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() )  . $message;
		return ( $formatted_message );
	}

	// choose update controls for form
	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	// legends
	protected function get_the_legends( $sql = '' ) {
		// report configuration settings related to upload capacity;
		$legend = '<p>' .  __( '* For tips on field mapping and validation, go to ', 'wp-issues-crm' ) . 
			'<a href="http://wp-issues-crm.com/?page_id=51" target = "_blank">WPissuesCRM.com</a>.' . '</p>';
			
		$legend = '<div class = "wic-upload-legend">' . $legend . '</div>';		
		return $legend;
		
	}
	// group screen
	protected function group_screen( $group ) { 
		return ( 'upload_parameters' == $group->group_slug  || 'mappable' == $group->group_slug ) ;	
	}
	
	// special use existing groups as special within this form
	protected function group_special ( $group ) { 
			return ( 'upload_parameters' == $group || 'mappable' == $group );
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
			$unique_identifier = '___' . $field['entity'] . '___' . $field['field']; // three underscore characters before each slug
			if ( $field['order'] < 1000 ) {			
				$output_constituent .= '<div class="wic-droppable" id = "wic-droppable' . $unique_identifier  . '">' 
							. $show_field . '</div>';
			} else {
				$output_activity .= '<div class="wic-droppable" id = "wic-droppable' . $unique_identifier  . '">'  
							. $show_field . '</div>';			
			}
		}
		
		// assemble output
		$output .= '<div id = "wic-droppable-column">';
		$output .= '<h3>' . __( 'Drag/drop upload fields to map them to these WP Issues CRM fields*', 'wp-issue-crm' ) . '</h3>';
		$output .= '<div id = "constituent-targets"><h4>' . __( 'Constituent fields' , 'wp-issues-crm' ) . '</h4>' . $output_constituent . '</div>';		
		$output .= '<div class = "horbar-clear-fix"></div>';
		$output .= '<div id = "activity-targets"><h4>' . __( 'Activity fields' , 'wp-issues-crm' ) . '</h4>'. $output_activity . '</div>';
		$output .= '</div>';



		$output .= '<div class = "horbar-clear-fix"></div>';
		$output .= $doa['ID']->update_control();

		return $output; 					
	}
	
	protected function group_special_mappable ( &$doa ) {
		
		$output = ''; 
		
				// list fields from upload file to be matched
		$output .= '<div id = "wic-draggable-column-wrapper">';
		$output .= '<h3>' . __( 'Upload fields to be mapped.', 'wp-issue-crm' ) . '</h3>';
		$output .= '<div id = "wic-draggable-column">';
		
		// get the column map array				
		$column_map = json_decode ( $doa['serialized_column_map']->get_value() );
 
		// get an array of sample data to use as titles for the column divs
		$upload_parameters = json_decode ( $doa['serialized_upload_parameters']->get_value() );

		$staging_table_name = $upload_parameters->staging_table_name;
		$column_titles_as_samples = WIC_DB_Access_Upload::get_sample_data ( $staging_table_name ); 

		foreach ( $column_map as $key=>$value ) {
			$output .= '<div id = "wic-draggable___' . $key . '" class="wic-draggable" title = "' . $column_titles_as_samples[$key] . '">' . $key . '</div>'; // column names are already unique
		}
		$output .= '</div></div>';
		return $output;
		
	}
	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {}
	 	
}