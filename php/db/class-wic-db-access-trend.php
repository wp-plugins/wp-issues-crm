<?php
/*
*
* class-wic-db-access-trend.php
*		handles "trend" object as mechanism for accessing activity queries defined here
*
*/

class WIC_DB_Access_Trend Extends WIC_DB_Access {
	
	// implements trend search variables as activity search variables and then aggregates

	protected function db_search( $meta_query_array, $search_parameters ) { 

		// parse activities where clause
		$where_clause = $this->parse_activities_where_clause ( $meta_query_array ); 
		$where = $where_clause['where'];
		$values = $where_clause['values'];


		// search parameters ignored

		$top_entity = 'activity'; // trend passes through variables to an activity query
		$deleted_clause = 'AND c.mark_deleted != \'deleted\'';
				 
		// set global access object 
		global $wpdb;



		// straight activity query to start
		$join = $wpdb->prefix . 'wic_activity activity inner join ' . $wpdb->prefix . 'wic_constituent c on c.id = activity.constituent_id';

		// prepare SQL
		$activity_sql = "
					SELECT constituent_id, issue, max(pro_con) as pro_con
					FROM 	$join
					WHERE 1=1 $deleted_clause $where 
					GROUP BY $top_entity.constituent_ID, $top_entity.issue
					LIMIT 0, 9999999
					";	
		$activity_sql = ( $where > '' ) ? $wpdb->prepare( $activity_sql, $values ) : $activity_sql;					
		// $sql group by always returns single row, even if multivalues for some records 
		$sql =  	"
					SELECT p.id, count(constituent_id) as total, sum( if (pro_con = '0', 1, 0) ) as pro,  sum( if (pro_con = '1', 1, 0) ) as con  
					FROM ( $activity_sql ) as a 
					INNER JOIN $wpdb->posts p on a.issue = p.ID
					GROUP BY p.ID
					ORDER BY count(constituent_id) DESC
					";
		$sql_found = "SELECT FOUND_ROWS() as found_count";
		$this->sql = $sql; 
		// do search
		$this->result = $wpdb->get_results ( $sql );
	 	$this->showing_count = count ( $this->result );
	 	$this->found_count = count ( $this->result );
		// set value to say whether found_count is known
		$this->outcome = true;  // wpdb get_results does not return errors for searches, so assume zero return is just a none found condition (not an error)
										// codex.wordpress.org/Class_Reference/wpdb#SELECT_Generic_Results 
		$this->explanation = ''; 
	}	


	// no parent version of this function
	public function search_log_last_general ( $user_id ) { 
		
		global $wpdb;		
		$search_log_table = $wpdb->prefix . 'wic_search_log';
		$entity = $this->entity;
		
		$sql = 			
			"
			SELECT ID
			FROM $search_log_table
			WHERE user_id = $user_id
				AND entity = '$entity'
			ORDER	BY time DESC
			LIMIT 0, 1
			";
		
		$latest_search = $wpdb->get_results ( $sql );

		return ( $latest_search[0]->ID );

	} 	

	public function search_activities_with_category_slice ( $meta_query_array, $category_contributors ) { 
		// category_contributors is comma separated string of term id's
		
		// parse activities where clause from array
		$where_clause = $this->parse_activities_where_clause ( $meta_query_array ); 
		$where = $where_clause['where'];
		$values = $where_clause['values'];
		$where; 

		$top_entity = 'activity'; // trend passes through variables to an activity query
		$deleted_clause = 'AND c.mark_deleted != \'deleted\'';
				 
		// set global access object 
		global $wpdb;

		// do it all in one join -- get non-deleted constituents with activities meeting criteria in category
		$join = 	$wpdb->prefix . 'wic_activity activity inner join ' . 
					$wpdb->prefix . 'wic_constituent c on c.id = activity.constituent_id inner join ' .
					$wpdb->term_relationships . ' tr on activity.issue = tr.object_id inner join ' .
					$wpdb->term_taxonomy . ' tt on tt.term_taxonomy_id = tr.term_taxonomy_id';

		// prepare SQL
		$activity_sql = "
					SELECT constituent_id as ID
					FROM 	$join
					WHERE 1=1 $deleted_clause $where AND tt.term_id IN ( $category_contributors )
					GROUP BY activity.constituent_ID 
					LIMIT 0, 9999999
					";	
		$activity_sql = ( $where > '' ) ? $wpdb->prepare( $activity_sql, $values ) : $activity_sql;	

		$this->result = $wpdb->get_results ( $activity_sql );
		
	}


	// utility
	private function parse_activities_where_clause( $meta_query_array ) {
		// prepare $where clause
		$where = '';
		$values = array();
		// explode the meta_query_array into where string and array ready for wpdb->prepare
		foreach ( $meta_query_array as $where_item ) {

			$field_name		= $where_item['key'];
			$table 			= 'activity';
			$compare 		= $where_item['compare'];
			
			// set up $where clause with placeholders and array to fill them
			if ( '=' == $compare || '>' == $compare || '<' == $compare || '!=' == $compare ) {  // straight strict match			
				$where .= " AND $table.$field_name $compare %s ";
				$values[] = $where_item['value'];
			} elseif ( 'BETWEEN' == $compare ) { // date range
				$where .= " AND $table.$field_name >= %s AND $table.$field_name <= %s" ;  			
				$values[] = $where_item['value'][0];
				$values[] = $where_item['value'][1];
			} else {
				WIC_Function_Utilities::wic_error ( sprintf( 'Incorrect compare settings for field %1$s.', $this->field->field_slug ), __FILE__, __LINE__, __METHOD__, true );
			}
		}
	
		return ( array ( 'where' => $where, 'values' => $values ) );
	}

	/* required functions not implemented */
	protected function db_save ( &$meta_query_array ) {}
	protected function db_update( &$meta_query_array ) {  }
	protected function db_delete_by_id ( $args ){}
	protected function db_updated_last ( $arts ) {}
	protected function db_get_option_value_counts( $field_slug ) {} // not implemented for trends

}

