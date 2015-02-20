<?php
/*
*
* class-wic-db-access-wic.php
*		main db access for constituents, etc.
*
*/


class WIC_DB_Access_WIC Extends WIC_DB_Access {


	/*
	*
	* save an individual database record for the object entity based on the save_update_array containing fields and values
	*
	*/
	protected function db_save ( &$save_update_array ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'wic_' . $this->entity;  
		
		$set = $this->parse_save_update_array( $save_update_array );
		// hook for updating parallel table in class extension
		$set = $this->external_update( $set );
  		$set_clause_with_placeholders = $set['set_clause_with_placeholders'];
		$sql = $wpdb->prepare( "
				INSERT INTO $table 	
				$set_clause_with_placeholders
				",
			$set['set_value_array'] );
		$save_result = $wpdb->query( $sql );
				
		if ( 1 == $save_result ) {
			$this->outcome = true;		
			$this->insert_id = $wpdb->insert_id;
			$this->last_updated_time = $this->get_mysql_time();
			$this->last_updated_by = get_current_user_id();
	
		} else {		
			$this->outcome = false;
			$this->explanation = __( 'Unknown database error. Save may not have been successful', 'wp-issues-crm' );
		}
		$this->sql = $sql;
		$this->made_changes = true; // true even if attempted change failed, an insert is always a change
		return;
	}
	
	protected function external_update ( $set ) {
		return ( $set );	
	}	
	
	
	/*
	*
	* update an individual database record for the object entity based on the save_update_array containing fields and values
	*
	*/
	protected function db_update ( &$save_update_array ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'wic_' . $this->entity;

		$have_changed_values = $this->have_changed_values ( $save_update_array );		
		
		if ( $have_changed_values // if values have changed, apply the updates
			|| ( 1 == count ( $save_update_array ) && 'ID' == $save_update_array[0]['key'] ) ) { // or if just passing an ID to do a time stamp
			// parse the array to set up clause and value array for $wpdp->prepare
			$set = $this->parse_save_update_array( $save_update_array );
	  		$set_clause_with_placeholders = $set['set_clause_with_placeholders'];
			$sql = $wpdb->prepare( "
					UPDATE $table
					$set_clause_with_placeholders
					WHERE ID = %s
					",
				$set['set_value_array'] );
	
			$update_result = $wpdb->query( $sql );
			// if here because actually doing updates, as opposed to just a time stamp, update the main fields.  
			if ( $have_changed_values ) {
				$this->outcome = ! ( false === $update_result );
				$this->explanation = ( $this->outcome ) ? '' : __( 'Unknown database error. Update may not have been successful', 'wp-issues-crm' );
				$this->last_updated_time = $this->get_mysql_time();
				$this->last_updated_by = get_current_user_id();
				$this->sql = $sql;
				$this->made_changes = true; // true even if changes may have failed
			}
		} else {	// no updates, just set result parameters . . .	
			$this->outcome = true;
			$this->made_changes = false;
		}
		
		return;
	}

	/*
	*
	* retrieve database records joined across parent and child tables based on array of search parameters
	*
	*/
	protected function db_search( $meta_query_array, $search_parameters ) { // $select_mode = '*' ) {

		global $wic_db_dictionary;

		// default search parameters
		$select_mode 		= 'id';
		$sort_order 		= false;
		$sort_direction	= 'ASC';
		$compute_total 	= false;
		$retrieve_limit 	= '10';
		$show_deleted		= true;
		
		extract ( $search_parameters, EXTR_OVERWRITE );

		// implement search parameters
		$top_entity = $this->entity;
		if ( 'id' == $select_mode) {
			$select_list = $top_entity . '.' . 'ID ';	
		} else {
			$select_list = $top_entity . '.' . '* '; 
		}
		$sort_clause = $sort_order ? $wic_db_dictionary->get_sort_order_for_entity( $this->entity ) : '';
		$order_clause = ( '' == $sort_clause ) ? '' : " ORDER BY $sort_clause $sort_direction ";
		$deleted_clause = $show_deleted ? '' : 'AND ' . $top_entity . '.mark_deleted != \'deleted\'';
		$found_rows = $compute_total ? 'SQL_CALC_FOUND_ROWS' : '';
		// retrieve limit goes directly into SQL
		 
		// set global access object 
		global $wpdb;

		// prepare $where and join clauses
		$table_array = array( $this->entity );
		$where = '';
		$join = '';
		$values = array();
		// explode the meta_query_array into where string and array ready for wpdb->prepare
		foreach ( $meta_query_array as $where_item ) {

			// pull out tables for join clause		
			if( ! in_array( $where_item['table'], $table_array ) ) {
				$table_array[] = $where_item['table'];			
			}

			$field_name		= $where_item['key'];
			$table 			= $where_item['table'];
			$compare 		= $where_item['compare'];
			
			// set up $where clause with placeholders and array to fill them
			if ( '=' == $compare || '>' == $compare || '<' == $compare || '!=' == $compare ) {  // straight strict match			
				$where .= " AND $table.$field_name $compare %s ";
				$values[] = $where_item['value'];
			} elseif ( 'like' == $compare ) { // right wild card like match
				$where .= " AND $table.$field_name like %s ";
				$values[] = $wpdb->esc_like ( $where_item['value'] ) . '%' ;	
			} elseif( 'scan' == $compare ) { // right and left wild card match
				$where .= " AND $table.$field_name like %s ";
				$values[] = '%' . $wpdb->esc_like ( $where_item['value'] )  . '%';
			} elseif ( 'sound' == $compare ) {
				$where .= " AND $table.$field_name = soundex( %s ) ";
				$values[] = $wpdb->esc_like ( $where_item['value'] );
			} elseif ( 'BETWEEN' == $compare ) { // date range
				$where .= " AND $table.$field_name >= %s AND $table.$field_name <= %s" ;  			
				$values[] = $where_item['value'][0];
				$values[] = $where_item['value'][1];
			} else {
				WIC_Function_Utilities::wic_error ( sprintf( 'Incorrect compare settings for field %1$s.', $this->field->field_slug  ), __FILE__, __LINE__, __METHOD__, true );
			}	 
		}
		// prepare a join clause		
		$array_counter = 0;
		foreach ( $table_array as $table ) {
			$table_name  = $wpdb->prefix . 'wic_' . $table;
			$child_table_link = $top_entity . '_id';
			$join .= ( 0 < $array_counter ) ? " INNER JOIN $table_name as $table on $table.$child_table_link = $top_entity.ID " : " $table_name as $table " ;
			$array_counter++; 		
		}
		$join = ( '' == $join ) ? $wpdb->prefix . 'wic_' . $this->entity : $join; 

		// prepare SQL
		$sql = $wpdb->prepare( "
					SELECT $found_rows	$select_list
					FROM 	$join
					WHERE 1=1 $deleted_clause $where 
					GROUP BY $top_entity.ID
					$order_clause
					LIMIT 0, $retrieve_limit
					",
				$values );	
		// $sql group by always returns single row, even if multivalues for some records 

		$sql_found = "SELECT FOUND_ROWS() as found_count";
		$this->sql = $sql; 
		
		// do search
		$this->result = $wpdb->get_results ( $sql );
	 	$this->showing_count = count ( $this->result );
		// only do sql_calc_found_rows on id searches; in other searches, found count will always equal showing count
		$found_count_object_array = $wpdb->get_results( $sql_found );
		$this->found_count = $found_count_object_array[0]->found_count;
		// set value to say whether found_count is known
		$this->found_count_real = $compute_total;
		$this->retrieve_limit = $retrieve_limit;
		$this->outcome = true;  // wpdb get_results does not return errors for searches, so assume zero return is just a none found condition (not an error)
										// codex.wordpress.org/Class_Reference/wpdb#SELECT_Generic_Results 
		$this->explanation = ''; 


	}	
	
	/*
	*
	* parse a save update array into clauses and value array for pre-processing by $wpdb->prepare before a save or update  
	*
	*/
	protected function parse_save_update_array( $save_update_array ) {

		$set_clause_with_placeholders = 'SET last_updated_time = now()';
		$set_array = array();
		$current_user = wp_get_current_user();
		$entity_id = '';
		
		foreach ( $save_update_array as $save_update_clause ) {
			if ( $save_update_clause['key'] != 'ID' ) {
				$set_clause_with_placeholders .= ', ' . $save_update_clause['key'] . ' = %s'; 		
				$set_value_array[] = $save_update_clause['value'];
				if ( $save_update_clause['soundex_enabled'] ) {
					$set_clause_with_placeholders .= ', ' . $save_update_clause['key'] . '_soundex = soundex( %s ) '; 		
					$set_value_array[] = $save_update_clause['value'];
				}
				if ( isset( $save_update_clause['secondary_alpha_search'] ) ) {
					// this supports address line being indexed by street name -- assumes address line begins with number/suffix and then a space
					// may entrain apartment number, but not a problem if searching this field on a right wild card basis.
					// implemented in data dictionary as field type alpha
					$set_clause_with_placeholders .= ', ' . $save_update_clause['secondary_alpha_search'] . ' = %s ';
					$first_space = strpos( $save_update_clause['value'], ' ' );	
					$probable_alpha_value = trim ( substr ( $save_update_clause['value'], $first_space ) );
					$set_value_array[] = $probable_alpha_value;
				}
			} else { 
				$entity_id = $save_update_clause['value'];
			}
		}

		$set_clause_with_placeholders .= ', last_updated_by = %d ';
		$set_value_array[]  = $current_user->ID;		

		if ( $entity_id > '' ) {
			$set_value_array[] = $entity_id; // tag entity ID on to end of array (will not be present in save cases, since is a readonly field)
		}	// see setup in WIC_CONTROL_Parent::create_update_clause
	
		return ( array (
			'set_clause_with_placeholders' => $set_clause_with_placeholders,
			'set_value_array'	=> $set_value_array,
				)		
			);
	}

	/*
	*
	* test whether values have changed on an individual database record before updating that record
	*
	*/
	protected function have_changed_values ( &$save_update_array ) {
		
		global $wpdb;		
		$table  = $wpdb->prefix . 'wic_' . $this->entity;
		
		$search_clause = 'ID';

		// extract the field names from the save_update_clause sent with the update request		
		foreach ( $save_update_array as $save_update_clause ) {
			if ( $save_update_clause['key'] != 'ID' ) {
				$search_clause .=  ', ' . $save_update_clause['key']; 		
			} else { 
				$entity_id = $save_update_clause['value'];
			}
		} 	
	
		// construct SQL to retrieve those values for the update target record
		// no db prepare since not using user supplied values	
		$sql = "
			SELECT $search_clause
			FROM $table
			WHERE ID = $entity_id
			";
		
		// retrieve the target record
		$results = $wpdb->get_results ( $sql );
		
		// compare the proposed update, field by field
		$have_changed_values = false;
		foreach ( $save_update_array as $save_update_clause ) {
			if ( $save_update_clause['value'] != $results[0]->$save_update_clause['key'] ) {
				$have_changed_values = true;
			} 
		} 
		
		return ( $have_changed_values );
	} 


	protected function db_delete_by_id ( $id ) {
		global $wpdb;		
		$table  = $wpdb->prefix . 'wic_' . $this->entity;
		$outcome = $wpdb->delete ( $table, array( 'ID' => $id ) );
		if ( ! ( 1 == $outcome ) ) {
			WIC_Function_Utilities::wic_error ( sprintf( 'Database error on execution of requested delete of %s.', $this->entity  ), __FILE__, __LINE__, __METHOD__, true );
		} 
	}

	protected function db_list_by_id ( $id_string, $sort_direction ) {

		global $wpdb;
		global $wic_db_dictionary;	

		$top_entity = $this->entity;
		$table_array = array( $this->entity );
		$where = $top_entity . '.ID IN ' . $id_string . ' ';
		$join = $wpdb->prefix . 'wic_' . $this->entity . ' AS ' . $this->entity;
		$sort_clause = $wic_db_dictionary->get_sort_order_for_entity( $this->entity );
		$order_clause = ( '' == $sort_clause ) ? '' : " ORDER BY $sort_clause $sort_direction ";
		$select_list = '';	

		// assemble list query based on dictionary list specification
		$fields =  $wic_db_dictionary->get_list_fields_for_entity( $this->entity );
		// retrieving those with non-zero listing order -- SQL error will occur if none have non-zero listing order
		// either for main list or for fields of multivalue entities which are included in main list
		foreach ( $fields as $field ) {
			// standard single field addition to list
			if ( 'multivalue' != $field->field_type ) {
				$select_list .= ( '' == $select_list ) ? $top_entity . '.' : ', ' . $top_entity . '.' ;
				$select_list .= $field->field_slug . ' AS ' . $field->field_slug ;
			// else multivalue field calls for multiple instances, compressed into single value
			} else {
				// create comma separated list of list fields for entity 
				$select_list .= '' == $select_list ? '' : ', ';
				$sub_fields = $wic_db_dictionary->get_list_fields_for_entity( $field->field_slug );
				$sub_field_list = ''; 
				foreach ( $sub_fields as $sub_field ) {
					if ( 'ID' != $sub_field->field_slug ) { 
						$sub_field_list .= ( '' == $sub_field_list ) ? $field->field_slug . '.' : ', ' . $field->field_slug . '.' ;
						$sub_field_list .= $sub_field->field_slug;
					}
				}
				// concat multivalues for single row display
				$select_list .= ' GROUP_CONCAT( DISTINCT ' . $sub_field_list . ' SEPARATOR \', \' ) AS ' . $field->field_slug;
				$join .= ' LEFT JOIN ' .  $wpdb->prefix . 'wic_' . $field->field_slug . ' ' . $field->field_slug . ' ON ' . 
					$this->entity . '.ID = ' . $field->field_slug . '.' . $this->entity . '_id ';
			}		
		}	

		$sql = 	"SELECT $select_list
					FROM 	$join
					WHERE $where
					GROUP BY $top_entity.ID
					$order_clause
					";

		$this->sql = $sql; 
		if ( '()' != $id_string ) {
			$this->result = $wpdb->get_results ( $sql );
	 		$this->showing_count = count ( $this->result );
	 		$this->found_count = $this->showing_count;
	 	} else {
	 		$this->sql = 'Empty ID string -- not valid'; 
			$this->result = array();
			$this->showing_count = 0;
			$this->found_count = 0;	 	
	 	} 
		$this->outcome = true;  // wpdb get_results does not return errors for searches, so assume zero return is just a none found condition (not an error)
										// codex.wordpress.org/Class_Reference/wpdb#SELECT_Generic_Results 
		$this->explanation = ''; 
	}

	public function db_updated_last ( $user_id ) {
		
		global $wpdb;
	
		$table = $wpdb->prefix . 'wic_' . $this->entity;
		
		$sql = 
			"
			SELECT ID, last_updated_time
			FROM $table
			WHERE last_updated_by = $user_id
			ORDER BY last_updated_time DESC
			LIMIT 0, 1		
			";
			
		$latest_updated_array = $wpdb->get_results( $sql );

		return ( array (
			'latest_updated' => $latest_updated_array[0]->ID,
			'latest_updated_time' =>$latest_updated_array[0]->last_updated_time,
			)
		);

	}

	// necessary to have a quick look up that by passes search logging, etc. for use in the search log!
	public static function get_constituent_name ( $id ) {

		global $wpdb;
		$table = $wpdb->prefix . 'wic_constituent';
		
		$sql = $wpdb->prepare(
			"
			SELECT trim( concat ( first_name, ' ', last_name ) ) as name
			FROM $table
			WHERE ID = %s
			",
			array ( $id )
			);

		$result = $wpdb->get_results( $sql ); 		

		return ( $result[0]->name );
	
	}	

	protected function db_get_option_value_counts( $field_slug ) {
			
		global $wpdb;
	
		$table = $wpdb->prefix . 'wic_' . $this->entity;
	
		$sql = "SELECT $field_slug as field_value, count(ID) as value_count from $table group by $field_slug ORDER BY $field_slug";
	
		$field_value_counts = $wpdb->get_results( $sql );
		
		return ( $field_value_counts );	
	
	} 


}

