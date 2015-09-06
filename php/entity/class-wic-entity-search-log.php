<?php
/*
*
*	wic-entity-search-log.php
*
*
*/

class WIC_Entity_Search_Log extends WIC_Entity_Parent {

	protected function set_entity_parms( $args ) { // 
		// accepts args to comply with abstract function definition, but as a parent does not process them -- no instance
		$this->entity = 'search_log';
	} 

	/**************************************************************************************************************************************
	*
	*	Search Log Request Handlers
	*
	*
	*	Search log retrieval can take two approaches -- either to the search form or to the found list
	*	Need to carry old search ID back if showing the found list, so that export and redo search buttons can work, so
	*	   maintain old search ID in array returned from ID retrieval 
	*
	***************************************************************************************************************************************/

	// navigation from back button
	public function back ( $args ) { // takes no actual arguments, working with history
		$this->id_search_execute ( array ( 'id_requested' => WIC_DB_Search_History::history_pointer_move( false ) ) ); 	
	}

	// navigation from forward button
	public function forward ( $args ) { // takes no actual arguments, working with history
		$this->id_search_execute ( array ( 'id_requested' => WIC_DB_Search_History::history_pointer_move( true ) ) ); 	
	}

	// request handler for search log list -- adds search_log id to user history and then executes query 
	public function id_search( $args ) {
		WIC_DB_Search_History::update_search_history( $args['id_requested'] );
		$this->id_search_execute( $args );	
	}
	
	// request handler for search log list -- re-executes query 
	public function id_search_execute( $args ) {
		$search = $this->id_retrieval( $args );
		$class_name = 'WIC_Entity_'. $search['entity'];
		$search['show_form'] = true;
		${ $class_name } = new $class_name ( 'redo_search_from_query', $search  ) ;		
	}	
	
	// request handler for back to search button -- brings back to filled search form, but does not reexecute the query
	public function id_search_to_form( $args ) {
		$search = $this->id_retrieval( $args );
		$class_name = 'WIC_Entity_'. $search['entity'];
		${ $class_name } = new $class_name ( 'redo_search_form_from_query', $search ) ;		
	}
	
	// get the search identified by number from the search log and return it unserialized
	protected function id_retrieval ( $args ) {
		$id = $args['id_requested']; // expects standard button format args
		$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		$wic_query->list_by_id ( '(' . $id . ')' );
		return ( array ( 
			'search_id' => $id,
			'entity' => $wic_query->result[0]->entity, 
			'unserialized_search_array' => unserialize( $wic_query->result[0]->serialized_search_array ),
			'unserialized_search_parameters' => unserialize( $wic_query->result[0]->serialized_search_parameters ),
			) 
		);
	}	
	
	/**************************************************************************************************************************************
	*
	*	Formatters for search log list
	*
	***************************************************************************************************************************************/
	public static function favorite_formatter ( $favorite ) {
		$dashicon = $favorite ? '<span class="dashicons dashicons-star-filled"></span>' : '<span class="dashicons dashicons-star-empty"></span>';
		return ( $dashicon );	
	}	
	
	// update the favorite setting in AJAX call
	public static function set_favorite ( $search_id, $data ) { 
		$data = json_decode ( stripslashes ( $data ) );
		$favorite = $data->favorite ? 1 : 0;		
		$result = WIC_DB_Access::set_search_favorite ( $search_id, $favorite ); 
		if ( false === $result ) {
			echo __( 'Bad result on favorite toggle', 'wp-issues-crm' );		
		}
	}
	
	public static function serialized_search_array_formatter ( $serialized ) {
		
		global $wic_db_dictionary;
		$search_array = unserialize ( $serialized );
		$search_phrase = '';

		// first repack search array, exploding any items that are row arrays
		// two components are labeled as in advanced search array 
		$unpacked_search_array_definitions = array();
		$unpacked_search_array_terms = array();
		foreach ( $search_array as $search_clause ) {
			if ( isset ( $search_clause[0] ) ) { 
				$new_clause = array();
				$row_type = substr( $search_clause[1][0]['table'], 16 );
				foreach ( $search_clause[1] as $clause_component ) {
					if ( $row_type . '_field' == $clause_component['key'] ) {
						$field = $wic_db_dictionary->get_field_rules_by_id( $clause_component['value'] );
						$new_clause['key']		= $field['field_slug'];
						$new_clause['table']		= $field['entity_slug'];
					} elseif ( $row_type . '_comparison' == $clause_component['key']  ) {
						$new_clause['compare'] = $clause_component['value']; 				
					} elseif ( $row_type . '_value' == $clause_component['key']  ) { 
						$new_clause['value']  = $clause_component['value']; 
					} elseif ( $row_type . '_type' == $clause_component['key']  ) { 
						$new_clause['type'] =  $clause_component['value']; 
					} elseif ( $row_type . '_aggregator' == $clause_component['key']  ) { 
						$new_clause['aggregator'] =  $clause_component['value']; 
					}										
				}
				$unpacked_search_array_terms[] = $new_clause;			
			} else {
				$unpacked_search_array_definitions[] = $search_clause;			
			}		
		}
		$search_array = array_merge ( $unpacked_search_array_terms, $unpacked_search_array_definitions );

		if ( count ( $search_array ) > 0 ) { 
			foreach ( $search_array as $search_clause ) {
	
					
				$value =  isset ( $search_clause['value'] ) ? $search_clause['value'] : '' ; // default
				$show_item = true; 

				// look up categories if any for post_category ( from either regular or advanced search format array )			
				if ( 'post_category' == $search_clause['key'] || strpos( $search_clause['compare'], 'cat' ) > -1 ) { 
					if ( 0 < count ( $value ) ) {
						$value_string = '';
						foreach ( $value as $key => $selected ) {
							$value_string .= ( $value_string > '' ) ? ', ': '';
							$value_string .= get_the_category_by_ID ( $key );				
						}
						$value = $value_string;
					} else {
						$value = '';
						$show_item = false;				
					}
				} elseif ( is_array( $value ) ) {
					$value = implode ( ',', $value );		
				} else {
					if ( 'advanced_search' != $search_clause['table'] ) { // don't unpack connector terms for advanced search
						$label = $wic_db_dictionary->get_option_label( $search_clause['table'], $search_clause['key'], $value );
						$value = ( $label > '' ) ? $label : $value;
					}
				}
				
				if ( $show_item )	{	
					if ( 'ID' == $search_clause['key']  ) { // only accessible for issues and constituents and overrides all other criteria
						if ( 'issue' == $search_clause['table'] ) {
						 	$search_phrase = __( 'Issue -- ', 'wp-issues-crm' ) . get_the_title ( $search_clause['value'] );
						} else {
							$search_phrase = __( ' Constituent -- ', 'wp-issues-crm' ) . esc_html( WIC_DB_Access_WIC::get_constituent_name ( $search_clause['value'] ) );
						}					
					} else {  		
						$search_phrase .= ( 'advanced_search' == $search_clause['table'] ? '' : $search_clause['table'] . ': ' ). 
							( isset ( $search_clause['type'] )  		? ' of type ' . $search_clause['type'] . ' ' : '' ) .
							( isset ( $search_clause['aggregator'] ) 	? ' ' . $search_clause['aggregator'] . ' of ' : '' ) .
							$search_clause['key'] . ' ' . 
							$search_clause['compare'] . ' ' . 
							esc_html( $value ) . '<br />';
					}
				}		
			}
		}
		return ( $search_phrase );	
	}	
	
  	public static function time_formatter( $value ) {
		$date_part = substr ( $value, 0, 10 );
		$time_part = substr ( $value, 11, 10 ); 		
		// return ( $date_part . '<br/>' . $time_part ); 
		return ( $value );
	} 

	public static function download_time_formatter ( $value ) {
		return ( self::time_formatter ( $value ) );	
	}

	public static function user_id_formatter ( $user_id ) {

		$display_name = '';		
		if ( isset ( $user_id ) ) { 
			if ( $user_id > 0 ) {
				$user =  get_users( array( 'fields' => array( 'display_name' ), 'include' => array ( $user_id ) ) );
				$display_name = esc_html ( $user[0]->display_name ); // best to generate an error here if this is not set on non-zero user_id
			}
		}
		return ( $display_name );
	}

}