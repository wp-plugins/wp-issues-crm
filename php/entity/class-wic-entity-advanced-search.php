<?php
/*
*
*	wic-entity-advanced-search.php
*
*  Essentially, trends are issues, but the search model is different -- driven by WIC Activities
* 		-- so need this wraparound object
*
*
*/

class WIC_Entity_Advanced_Search extends WIC_Entity_Parent {

	protected function set_entity_parms( $args ) { // 
		// accepts args to comply with abstract function definition, but as a top level entity does not process them -- no instance arg
		$this->entity = 'advanced_search';
	} 

	// handle a request for a new search form
	protected function new_form() { 
		$this->new_form_generic( 'WIC_Form_Advanced_Search' );
		return;
	}
	
	// handle a search request coming from a search form
	protected function form_search () { 
		$this->form_search_generic ( 'WIC_Form_Advanced_Search_Again', 'WIC_Form_Advanced_Search_Again');
		return;				
	}

	//handle a search request coming search log (buttons with search ID go to search log first)
	protected function redo_search_from_query ( $search ) {  
		$this->redo_search_from_meta_query ( $search, 'WIC_Form_Advanced_Search_Again','WIC_Form_Advanced_Search_Again' );
		return;
	}		
	
	// handle a request to return to a search form
	protected function redo_search_form_from_query ( $search ) { 
		$this->search_form_from_search_array ( 'WIC_Form_Advanced_Search_Again',  __( 'Redo search.', 'wp-issues-crm'), $search );
		return;
	}








	
	// in metaquery array assembly, group multivalue rows into single top-level array entries
	public function assemble_meta_query_array ( $search_clause_args ) {

		$meta_query_array = array ();

		foreach ( $this->data_object_array as $field => $control ) {
			
			// get query clause
			if ( ! $control->is_multivalue() ) { 
				$query_clause = $control->create_search_clause( $search_clause_args );
			} else { 
				$query_clause = $control->create_advanced_search_clause( $search_clause_args );
			}
			
			// add to array if returned a query clause (not a blank string)
			if ( is_array ( $query_clause ) ) { 
				$meta_query_array = array_merge ( $meta_query_array, $query_clause ); // will do append since the arrays of arrays are not keyed arrays
			}  

		}

		return $meta_query_array;
	}		

	// the reverse process to assembly -- have to convert array back to format expected by multivalue controls
	protected function populate_data_object_array_with_search_parameters ( $search ) { 

		$this->initialize_data_object_array();
		// reformat $search_parameters array
		
		$key_value_array = self::reformat_search_array ( $search['unserialized_search_array'] );	

		// already in key value format
		$combined_form_values = array_merge ( $key_value_array, $search['unserialized_search_parameters']);

		// pass data object array and see if have values
		foreach ( $this->data_object_array as $field_slug => $control ) { 
			if ( isset ( $combined_form_values[$field_slug] ) ) {
					$control->set_value ( $combined_form_values[$field_slug] );
			}
		} 
	}

	public static function reformat_search_array( $unserialized_search_array ) {
		$key_value_array = array();		
		foreach ( $unserialized_search_array as $search_array ) { 
			if ( isset ( $search_array['table'] ) ) { // these are the search connect terms
				$key_value_array[$search_array['key']] = $search_array['value'];
			} else { // is a row array for a multivalue control
				$row_field = $search_array[1][0]['table'];				
				$row_array = array(); 
				foreach ( $search_array[1] as $term ) {
					$row_array[$term['key']] = $term['value'];				
				} 
				if ( ! isset ( $key_value_array[$row_field] ) ) {
					$key_value_array[$row_field] = array ( $row_array );			
				} else {
					$key_value_array[$row_field] = array_merge ( $key_value_array[$row_field], array( $row_array ) );
				}
			}
		}
		return ( $key_value_array );
	}



	// set lister class based on entity_retrieved; spoof entity for lister
	protected function set_lister_class ( &$wic_query ) {
		$wic_query->entity = $wic_query->entity_retrieved;
		return ( 'WIC_List_' . $wic_query->entity_retrieved );	
	}

	protected function pre_list_message ( &$lister, &$wic_query, &$data_object_array ) {
		if ( 'activity' == $wic_query->entity_retrieved ) {
			$message = $lister->format_message( $wic_query ); 		
			return ( '<div id="post-form-message-box" class = "wic-form-routine-guidance" >' . esc_html( $message ) . '</div>' );
		} else {
			return ( '' );		
		}	
	}


	protected function id_search_generic ( $id, $success_form = '', $sql = '', $log_search = '', $primary_search_id = ''  ) {
		$args = array ( 'id_requested' => $id );
		$constituent_entity = new WIC_Entity_Constituent ( 'id_search_no_log', $args );	
	}

	public static function make_blank_control( $new_control_field_id  ) {
		global $wic_db_dictionary;
		if ( 'CATEGORY' == $new_control_field_id ) {
			$field = array ( 
				'field_type' => 'multiselect',
				'field_slug' => 'post_category',
				'entity_slug' => 'issue',			
			);		
		} else {
			$field = $wic_db_dictionary->get_field_rules_by_id( $new_control_field_id  );
		}	
		$control = WIC_Control_Factory::make_a_control ( $field['field_type'] );
		// need to initialize with values from field being searched for, not field in advanced_search form
		$control->initialize_default_values(  $field['entity_slug'], $field['field_slug'], 'wic_blank_control_template' );
		// all controls should be updateable and want in update format (no search ranges, for example)
		$control->override_readonly();
		$control = $control->update_control();
		$control_no_label = substr( $control, strpos( $control, 'label>' ) + 6 );
		echo json_encode( $control_no_label );
		die();
	}
}