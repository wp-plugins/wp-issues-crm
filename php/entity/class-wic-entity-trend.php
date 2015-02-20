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

class WIC_Entity_Trend extends WIC_Entity_Parent {

	protected function set_entity_parms( $args ) { // 
		// accepts args to comply with abstract function definition, but as a top level entity does not process them -- no instance arg
		$this->entity = 'trend';
	} 

	// handle a request for a new search form
	protected function new_form() { 
		$this->new_form_generic( 'WIC_Form_Trend_Search' );
		return;
	}
	
	// handle a search request coming from a search form
	protected function form_search () { 
		$this->form_search_generic ( 'WIC_Form_Trend_Search_Again', 'WIC_Form_Trend_Search_Again');
		return;				
	}

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
		if ( 0 == $wic_query->found_count ) {
			$message = __( 'No activity found matching search criteria.', 'wp-issues-crm' );
			$message_level =  'error';
			$form = new $not_found_form;
			$form->layout_form ( $this->data_object_array, $message, $message_level, $sql );			
		} else { 
			$message = sprintf( __( 'Issues with activity matching selection criteria -- found %s.' , 'wp-issues-crm' ), $wic_query->found_count );
			$message_level = 'guidance';	
			$form = new $found_form;
			$form->layout_form ( $this->data_object_array, $message, $message_level, $sql );
			$lister_class = 'WIC_List_Trend' ;
			$lister = new $lister_class;
			if ( 'issues' == $this->data_object_array['trend_search_mode']->get_value() ) {
				$list = $lister->format_entity_list( $wic_query, '' );
			} else {
				$list = $lister->category_stats( $wic_query, '' );			
			}
			echo $list;	
		}
	}

}