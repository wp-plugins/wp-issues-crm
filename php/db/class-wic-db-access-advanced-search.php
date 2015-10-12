<?php
/*
*
* class-wic-db-access-advanced-search.php
*		handles "trend" object as mechanism for accessing activity queries defined here
*
*/

class WIC_DB_Access_Advanced_Search Extends WIC_DB_Access {
	
	public $amount_total;
	public $financial_activities_in_results;
	public $entity_retrieved;	
	public $advanced_search = 'yes'; // flag used to preserve original identity as advanced search while spoofing constituent or activity search
	public $blank_rows_ignored = 0; 
	
	protected function db_search( $meta_query_array, $search_parameters ) { 
		
		global $wic_db_dictionary;
		global $wpdb;

		/*
		* set up all search parameters
		*/

		// default search parameters
		$select_mode 		= 'id';
		$sort_order 		= false;
		$retrieve_limit 	= '10';
		$show_deleted		= true;

		// extract search parameters from passed array
		extract ( $search_parameters, EXTR_OVERWRITE );

		// default special search parameters 
		$activity_or_constituent 		= 'constituent';
		$activity_and_or_constituent 	= 'and';
		$activity_and_or				= 'and';
		$constituent_and_or				= 'and';
		$constituent_having_and_or		= 'and';

		// extract special search parameters from passed query array
		// extract row arrays ( which are two value arrays -- array ( 'row', row array of query clauses )
		$query_clause_rows = array();

		foreach ( $meta_query_array as $populated_field ) {
			if ( isset ( $populated_field['table'] ) ) { 
				${$populated_field['key']} = $populated_field['value'];			
			} else {
				$query_clause_rows[] = $populated_field[1]; // [1] is the row array of query clauses
			}		
		} 


		// implement basic search parameters
		$sort_clause = $sort_order ? $wic_db_dictionary->get_sort_order_for_entity( $activity_or_constituent ) : '';
		$order_clause = ( '' == $sort_clause ) ? '' : " ORDER BY $sort_clause "; 
		$deleted_clause = $show_deleted ? '' : 'AND constituent.mark_deleted != \'deleted\'';
		$retrieve_limit = is_numeric ( $retrieve_limit ) ? $retrieve_limit : 50; // bullet proofing, since can't prepare
		$this->entity_retrieved = $activity_or_constituent; // saved for setting of entity for listing
		// retrieve limit goes directly into SQL
		 
		// set global access object 
		global $wpdb;
		global $wic_db_dictionary;

		// prepare stubs for where and join clauses, values arrays, and counters before process clauses in loop
		$table_array = array();
		$activity_where = '';
		$constituent_where = '';
		$activity_not = ( strpos ( $activity_and_or, 'NOT' ) > 0 ? 'NOT' : '' );
		$constituent_not = ( strpos ( $constituent_and_or, 'NOT' ) > 0 ? 'NOT' : '' );
		$having = '';
		$activity_where_count = 0;
		$constituent_where_count = 0;
		$having_count = 0;
		$activity_values = array();
		$constituent_values = array();
		$having_values = array();
		$additional_select_terms = '';
		$additional_select_terms_values = array();
		$join = ' ' .	$wpdb->prefix . 'wic_constituent constituent LEFT JOIN ' . 
							$wpdb->prefix . 'wic_activity activity on activity.constituent_id = constituent.id ';

		// prepare select clause and augment join clause
		if ( 'constituent' == $activity_or_constituent ) {
			$select_list = ' constituent.ID ';
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		} else {
			$select_list =  'download' == $select_mode ? ' activity.ID ' :
				' activity.ID as ID, activity.constituent_id as constituent_id, activity_date, activity_type, activity_amount, pro_con, last_name, first_name, activity.constituent_id, post_title ';
			$join .= " inner join $wpdb->posts p on activity.issue = p.ID " ;
			$found_rows = ''; // in activity retrieval, do secondary search for amount totals			
		}

		// parse the query_clause_rows into where string and array ready for wpdb->prepare
		foreach ( $query_clause_rows as $query_clause_row ) { 

			$field_name = ''; // will be set in inner loop
			$table 		= ''; // will be 
			$compare 	= ''; // will be 
			$type		= ''; // may not be 
			$value 		= ''; // may not be 
			$aggregator	= ''; // may not be 
			$having_cat = ''; // may not be 
			$blank_search_valid = false; // will not be set in inner loop -- may be set in compare switch
			$value_variable = '%s'; // will not be set in inner loop -- may be set in compare switch
			$type_filter = ''; // will not be set in inner loop -- may be set in row logic

			$row_type = substr( $query_clause_row[0]['table'], 16 );
			foreach ( $query_clause_row as $query_clause_item ) { 
				if ( $row_type . '_field' == $query_clause_item['key'] ) {
					$field = $wic_db_dictionary->get_field_rules_by_id( $query_clause_item['value'] );
					$field_name		= $field['field_slug'];
					$table 			= $field['entity_slug'];
				} elseif ( $row_type . '_comparison' == $query_clause_item['key']  ) {
					$compare = $query_clause_item['value']; 				
				} elseif ( $row_type . '_value' == $query_clause_item['key']  ) { 
					$value =  $query_clause_item['value']; 
				} elseif ( $row_type . '_entity_type' == $query_clause_item['key'] ||  $row_type . '_type' == $query_clause_item['key'] ) { // different naming for activity and constituent 
					$type =  $query_clause_item['value']; 
				} elseif ( $row_type . '_aggregator' == $query_clause_item['key']  ) { 
					$aggregator =  $query_clause_item['value']; 
				} elseif ( $row_type . '_issue_cat' == $query_clause_item['key']  ) { 
					$having_cat =  $query_clause_item['value'];
				}															
			}
			
			// set flag for top entity messaging
			if ( '' == $value ) {
				$this->blank_rows_ignored++;
			}
			
			// accumulate subsidiary tables		
			if( ! in_array( $table, $table_array ) ) {
				$table_array[] = $table;			
			}
			
			// set $compare, $value and $value_variable based on compare type
			switch ( $compare ) {
				case 'SCAN':
					$value = '%' . $wpdb->esc_like ( $value ) . '%' ;
					$compare = 'LIKE';
					break;
				case 'LIKE':
					$value = $wpdb->esc_like ( $value ) . '%'	;			
					break;
				case 'cat':
				case 'category__in':
				case 'category__and':
				case 'category__not_in':
					if ( ! is_array ( $value ) ) {
						continue(2);// jump out of the switch and query clause where loop -- do nothing with blank category selections					
					}
					$value_variable = $this->get_issue_list_from_category ( $compare, $value ); // can't do prepare on this, b/c adds quotes
					$compare = 'IN'; ;// develop in string logic
					break;
				case 'BLANK':
				case 'NOT_BLANK';
					$value = '';
					$blank_search_valid = true;
					$compare = 'BLANK' == $compare ? '=' : '>' ;
					break;
				case 'IS_NULL';
					$value_variable = ''; // can't do on prepare on this, b/c adds quotes
					$blank_search_valid = true;
					$compare = ' IS NULL ';
					break;
				default:					
					// no action -- $value = $value	and $value_variable = %s;			
			} 

			
			// prepare for special handling for when field is a type field (only possible for activity and constituent rows
			$is_a_type_field = in_array ( $field_name, array ( 'activity_type', 'phone_type', 'email_type', 'address_type' ) ); 			
			if ( $is_a_type_field ) {
				$value = $type;
			}
			
			if ( ( 'activity' == $row_type || 'constituent' == $row_type ) && ( '' < $value || $blank_search_valid ) ) {
				$where_connecter = ${ $row_type . '_where_count' } > 0 ? ${ $row_type . '_and_or' } : ${ $row_type . '_not' };
				$field_name = $table . '.' . $field_name;
				if ( ! $is_a_type_field && '' < $type ) { // not a type field and type not blank, so set filter
					$type_name = $table . '.' . $table . '_type';
					$type_filter = " $type_name = %s and ";
					${ $row_type  . '_values'}[] = $type;				}
				if ( '%s' == $value_variable ) {
					${ $row_type  . '_values'}[] = $value; 
				} 
				${ $row_type . '_where' } .= " $where_connecter ( $type_filter $field_name $compare $value_variable ) ";					
				${ $row_type . '_where_count' }++;
			} elseif ( 'constituent_having' == $row_type && 'constituent' == $activity_or_constituent && '' < $value ) { 
				$issue_string = '';
				$type_string = '';
				$if_condition = '';
				$having_connecter = $having_count > 0 ? $constituent_having_and_or : ( strpos ( $constituent_having_and_or, 'NOT' ) > 0 ? 'NOT' : '' );
				// set up issue cat string
				if ( count( $having_cat ) > 0 ) { 
					$issue_string = ' activity.issue IN ' . $this->get_issue_list_from_category ( 'cat' , $having_cat );				
				} 
				// set up type string
				if ( '' < $type ) {
					$type_string =  ' activity.activity_type = %s ';
					$having_values[] = $type;
					$additional_select_terms_values[] = $type;	
				} 
				// combine strings to make if condition
				if ( $type_string > '' && $issue_string > '' ) {
					$if_condition = $type_string . ' AND ' . $issue_string . ' AND ';
				} elseif ( $type_string > '' || $issue_string > '' ) {
					$if_condition = $type_string . $issue_string . ' AND ';  // at least one of which is blank				
				} 
				// combine strings to make having condition
				$having .= " $having_connecter $aggregator" . "(if($if_condition  1=1, activity.$field_name, NULL )) $compare %s ";
				$having_values[] = $value;
				$having_count++;
				// add a select term so that aggregation result can be included in download
				$additional_select_terms .= ", $aggregator" . "(if($if_condition  1=1, activity.$field_name, NULL )) as " . $aggregator . '_' . $having_count;
			}	 
		}
		
		// see whether will need a connector between the stub where clauses and any addons
		$connector = 'AND (';
		$close_paren = ')';
 		if ( '' == $activity_where && '' == $constituent_where ) {
			 $connector = '';
			 $close_paren = '';	
 		}

		// see whether will need connector between activity and constituent where clauses
 		if ( '' == $activity_where || '' == $constituent_where ) {
			 $activity_and_or_constituent = '';		
 		}
		
		// wrap the where clauses
		$activity_where 		= $activity_where > '' 		? '( ' . $activity_where . ')' 		: '';
		$constituent_where 	= $constituent_where > '' 	? '( ' . $constituent_where . ')'	: '';		
		// $having 					= rtrim ( $having, "ANDOR ");		

		// expand the join clause		
		foreach ( $table_array as $table ) {
			if ( $table != 'constituent' && $table != 'activity' ) {
				$table_name  = $wpdb->prefix . 'wic_' . $table;
				$child_table_link = 'constituent_id';
				$join .= " LEFT JOIN $table_name as $table on $table.constituent_id = constituent.ID ";
			}
		}
		
		// complete the having clause
		$having = ( $having_count > 0 ) ? 'HAVING ' . $having : '';
		

		// merge prepare values (note that start with digit 1 so will always be non-empty use in where 1=1)
		$values = array_merge ( $additional_select_terms_values, array(1),  $constituent_values, $activity_values, $having_values );
		
		$group_by = 'GROUP BY ' . 	$activity_or_constituent . '.ID ';

		// prepare SQL ( or skip prepare if no user input to where clause)
		$sql = $wpdb->prepare( "
				SELECT $found_rows $select_list $additional_select_terms
				FROM 	$join
				WHERE 1=%d $deleted_clause $connector $constituent_where $activity_and_or_constituent $activity_where $close_paren  
				$group_by
				$having
				$order_clause
				",
			$values );
		// $sql group by always returns single row, even if multivalues for some records 
		
		$this->sql = $sql; // final retrieval sql

		if ( 'download' == $select_mode ) {
			$temp_table = $wpdb->prefix . 'wic_temporary_id_list';			
			$sql = "CREATE temporary table $temp_table " . $sql;
			$temp_result = $wpdb->query  ( $sql );
			if ( false === $temp_result ) {
				WIC_Function_Utilities::wic_error ( sprintf( 'Error in download, likely permission error.' ), __FILE__, __LINE__, __METHOD__, true );
			}			
		
		} else {
			$sql .= " LIMIT 0, $retrieve_limit "; // add the retrieve limit to the sql serving screen
			// do search
			$this->result = $wpdb->get_results ( $sql );
		 	$this->showing_count = count ( $this->result );
			
			// prepare summaries in different ways for constituent and activity searches
			if ( 'activity' == $activity_or_constituent ) {
				
				$select_list = " activity.ID, activity_amount, activity_type ";
				$summary_select = WIC_DB_Access_Activities::prepare_select_clause_with_financial_types();
				$summary_select = str_replace ( 'activity.ID', 'ID', $summary_select ); // compatibility issue 
				 			
				$summary_sql = $wpdb->prepare( "
					SELECT $summary_select FROM( 
						SELECT $select_list
						FROM 	$join
						WHERE 1=%d $deleted_clause $connector $constituent_where $activity_and_or_constituent $activity_where $close_paren  
						$group_by
						$having ) base_query
					",
					$values ); 

				$summary = $wpdb->get_results ( $summary_sql );
				$this->found_count = $summary[0]->activity_count;
				$this->amount_total = $summary[0]->total_amount;
				$this->financial_activities_in_results = $summary[0]->includes_financial_types > 0 ? true : false;
			}	else {		
				$sql_found = "SELECT FOUND_ROWS() as found_count"; // summary sql
				// only do sql_calc_found_rows on id searches; in other searches, found count will always equal showing count
				$found_count_object_array = $wpdb->get_results( $sql_found );
				$this->found_count = $found_count_object_array[0]->found_count;
				// set value to say whether found_count is known
				$this->found_count_real = true; // always looking for full found count
			}
			$this->retrieve_limit = $retrieve_limit;
				
			$this->outcome = true;  // wpdb get_results does not return errors for searches, so assume zero return is just a none found condition (not an error)
											// codex.wordpress.org/Class_Reference/wpdb#SELECT_Generic_Results 
			$this->explanation = ''; 
		}
		
	}	

	private function get_issue_list_from_category ( $compare, $value ) {
		$query_clause =  array ( // double layer array to standardize a return that allows multivalue fields
			array (
				'table'	=> 'issue',
				'key' 	=> 'post_category',
				'value'	=> $value,
				'compare'=> $compare,
				'wp_query_parameter' => 'cat',
			)
		);	

		$search_parameters = array (
			'retrieve_limit' 	=> -1,
			'log_search' 		=> false,		
		);

		$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( 'issue' );
		$wic_query->search ( $query_clause, $search_parameters );
		
		$in_string = '(99999999'; // make the string non-empty
		$in_string_count = 0;
		foreach ( $wic_query->result as $post ) {
			$in_string .= ',' . $post->ID;
		}	
		$in_string .= ')';

		return ( $in_string );

	}


	/* 
	*
	* 	retrieve list of constituent ID's from list of ids -- supports constituent list classes
	*  simple pass through 
	*
	*/
	protected function db_list_by_id ( $id_string ) { 

		$wic_constituent_query = 	WIC_DB_Access_Factory::make_a_db_access_object( 'constituent' );
		$wic_constituent_query->list_by_id ( $id_string );

		$this->sql = $wic_constituent_query->sql; 
		$this->result = $wic_constituent_query->result;
	 	$this->showing_count = $wic_constituent_query->showing_count;
	 	$this->found_count = $wic_constituent_query->found_count;
		$this->outcome =  $wic_constituent_query->found_count;
		$this->explanation = $wic_constituent_query->explanation; 
	}


	/* required functions not implemented */
	protected function db_save ( &$meta_query_array ) {}
	protected function db_update( &$meta_query_array ) {  }
	protected function db_delete_by_id ( $args ){}
	protected function db_get_option_value_counts( $field_slug ) {} 
	public function db_get_time_stamp ( $id ) {} 
	protected function db_do_time_stamp ( $table, $id ) {} 

}

