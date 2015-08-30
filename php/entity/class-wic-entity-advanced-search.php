<?php
/*
*
*	wic-entity-trend.php
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
	
	// set lister class based on list type
	protected function set_lister_class ( &$data_object_array ) {
		$list_type = $data_object_array['activity_or_constituent']->get_value();
		return ( 'WIC_List_' . $list_type );	
	}

	
/*
	// handle a search request for an ID coming from anywhere (really for own list of issues, pass through to issue entity)
	protected function id_search ( $args ) {
		$issue_pass_through_entity = new WIC_Entity_Issue ( 'id_search', $args ) ;	
		return;		
	}




	//handle a search request coming search log -- own function here, since need to combine form redisplay with list again
	protected function redo_search_from_query ( $search ) { 
		// prepare all  the data for a form  
		$this->populate_data_object_array_with_search_parameters( $search );
		// then also do the list
		$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		// don't want to log previously logged search again, but do want to know ID for down load and redo search purposes
		// talking to search function as if a new search, but with two previous parameters set
		$search['unserialized_search_parameters']['log_search'] = false;
		$search['unserialized_search_parameters']['old_search_id'] = $search['search_id'];
		$wic_query->search ( $search['unserialized_search_array'], $search['unserialized_search_parameters'] ); //
		$this->handle_search_results ( $wic_query, 'WIC_Form_Trend_Search_Again', 'WIC_Form_Trend_Search_Again' ); // show form or list
	}
	// handle search results
	protected function handle_search_results ( $wic_query, $not_found_form, $found_form ) {
		$sql = $wic_query->sql;
		$trend_search_mode = $this->data_object_array['trend_search_mode']->get_value();
		if ( 0 == $wic_query->found_count ) {
			$message = __( 'No activity found matching search criteria.', 'wp-issues-crm' );
			$message_level =  'error';
			$form = new $not_found_form;
			$form->layout_form ( $this->data_object_array, $message, $message_level, $sql );			
		} else {
			// get lister class in place before running list, so that can use message function 
			// use trend lister for cats and issues; activity lister for activities			
			switch ( $trend_search_mode ) {
				case 'cats':
					$lister_class = 'WIC_List_Trend';
					$lister_function = 'category_stats';
					break;
				case 'issues':
					$lister_class = 'WIC_List_Trend';		
					$lister_function = 'format_entity_list';
					break;		
				case 'activities':
					$lister_class = 'WIC_List_Activity';	
					$lister_function = 'format_entity_list';
					break;								
			}
			$lister = new $lister_class;
			$message = $lister->format_message( $wic_query ); // hoisting message (normally w/i lister form) out of the lister to top of search form
			$message_level = 'guidance';	
			
			// do trend search form
			$form = new $found_form;
			$form->layout_form ( $this->data_object_array, $message, $message_level, $sql );
			
			// do activity list below form
			$list = $lister->$lister_function( $wic_query, ''); 
			echo $list;	
		}
	}
*/
}