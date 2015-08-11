<?php
/*
*
* class-wic-db-access-trend.php
*		handles "trend" object as mechanism for accessing activity queries defined here
*
*/

class WIC_DB_Access_Activities Extends WIC_DB_Access {
	
	public $amount_total;
	public $financial_activities_in_results;	
	
	// implements trend search variables as activity search variables and then aggregates

	protected function db_search( $meta_query_array, $search_parameters ) {
		
		// fix retrieve limit (not passed in search parameters from form)
		$this->retrieve_limit = 200; 		
		
		// parse activities where clause
		$where_clause = $this->parse_activities_where_clause ( $meta_query_array ); 
		$where = $where_clause['where'];
		$values = $where_clause['values'];

		$top_entity = 'activity'; // trend passes through variables to an activity query
		$deleted_clause = 'AND c.mark_deleted != \'deleted\'';
				 
		// set global access object 
		global $wpdb;


		// just get id's if will be doing download (in download, doing temp table of id's, see also below)
		// referencing constituent table only to exclude deleted constituents
		$join = 	$wpdb->prefix . "wic_activity activity inner join " . $wpdb->prefix . "wic_constituent c on c.id = activity.constituent_id"; 
		if ( 'download' == $search_parameters['select_mode'] ) {
			$select =  ' activity.ID ';
		// load necessary fields if displaying list
		} else {
			$select =  ' activity_date, activity_type, activity_amount, pro_con, last_name, first_name, constituent_id, post_title '; 
			$join .= " inner join $wpdb->posts p on activity.issue = p.ID" ;			
		}
		
		// structure sql with the where clause
		$sql = "
				SELECT $select from $join 
				WHERE 1=1 $deleted_clause $where 
				ORDER BY activity_date desc, activity.last_updated_time 
				";
		$sql = ( $where > '' ) ? $wpdb->prepare( $sql, $values ) : $sql;	

		// download mode -- creates temp table picked up by export routine ( see also different id only sql above )
		if ( 'download' == $search_parameters['select_mode'] ) { 
			$temp_table = $wpdb->prefix . 'wic_temporary_id_list';			
			$sql = "CREATE temporary table $temp_table " . $sql . " LIMIT 0, 999999999";
			$temp_result = $wpdb->query  ( $sql );
			if ( false === $temp_result ) {
				WIC_Function_Utilities::wic_error ( sprintf( 'Error in download, likely permission error.' ), __FILE__, __LINE__, __METHOD__, true );
			}			
		} else {
			$this->sql = $sql . " LIMIT 0, " . $this->retrieve_limit; 
			// do search
			$this->result = $wpdb->get_results ( $this->sql ); // use sql with limit
		 	$this->showing_count = count ( $this->result );

			// set value to say whether found_count is known
			$this->outcome = true;  // wpdb get_results does not return errors for searches, so assume zero return is just a none found condition (not an error)
											// codex.wordpress.org/Class_Reference/wpdb#SELECT_Generic_Results 
			$this->explanation = '';
			
			// do a second query to check sum and presence of financial_activity_types (and get count for free)
			$summary_select = " count(activity.ID) as activity_count, sum(activity_amount) as total_amount";
			
			// prepare 'IN' phrase
			$in_phrase = '';			
			$wic_option_array = get_option('wp_issues_crm_plugin_options_array');
			$financial_activity_type_array = explode (',' , isset ( $wic_option_array['financial_activity_types'] ) ? $wic_option_array['financial_activity_types'] : '') ;
			$formatted_financial_activity_type_string = '';			
			if ( '' != $financial_activity_type_array ) {
				foreach ( $financial_activity_type_array as $type ) {
					$formatted_financial_activity_type_string .= '\'' . $type . '\',';  								
				}
				$formatted_financial_activity_type_string = rtrim( $formatted_financial_activity_type_string, ',' );	
				// the CAST and COLLATE syntax makes the IN operator case sensitive -- this is the standard		
				$in_phrase = ", sum(if( CAST(activity_type AS CHAR CHARACTER SET latin1) COLLATE latin1_general_cs IN (" . $formatted_financial_activity_type_string . "),1,0)) as includes_financial_types";	
				$summary_select .= $in_phrase;
			}			

			$summary_sql = " 
				SELECT $summary_select from $join 
				WHERE 1=1 $deleted_clause $where
				";  
			$summary_sql = ( $where > '' ) ? $wpdb->prepare( $summary_sql, $values ) : $summary_sql;	
			$summary = $wpdb->get_results ( $summary_sql );
			$this->found_count = $summary[0]->activity_count;
			$this->amount_total = $summary[0]->total_amount;
			if ( '' != $financial_activity_type_array ) { 
				$this->financial_activities_in_results = $summary[0]->includes_financial_types > 0 ? true : false;
			} else {
				$this->financial_activities_in_results = false;
			}
		}
	}	
	// utility
	protected function parse_activities_where_clause( $meta_query_array ) {
		// prepare $where clause
		$where = '';
		$values = array();
		// explode the meta_query_array into where string and array ready for wpdb->prepare
		foreach ( $meta_query_array as $where_item ) {

			$field_name		= $where_item['key'];
			$table 			= 'activity';
			$compare 		= $where_item['compare'];
			
			// set up $where clause with placeholders and array to fill them
			if ( '=' == $compare || '>' == $compare || '<' == $compare || '!=' == $compare || '>=' == $compare || '<=' == $compare ) {  // straight strict match			
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

