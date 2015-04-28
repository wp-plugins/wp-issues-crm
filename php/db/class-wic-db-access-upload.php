<?php
/*
*
* class-wic-db-access-upload.php
*
*
* 
*/

class WIC_DB_Access_Upload Extends WIC_DB_Access_WIC {

	public $upload_time;
	public $upload_by;
	public $serialized_upload_parameters;
	public $serialized_column_map;
	public $upload_status;

	/*
	*
	*	Overrides parent save/update array function to capture the upload parameters
	*		-- they are transient controls and so don't emit update clauses, but they are still present
	*
	*/
	protected function assemble_save_update_array ( &$doa ) {
		
		$save_update_array = parent::assemble_save_update_array ( $doa );
				
		
		// prepare to create upload parameters as straight key value_array
		$update_parameter_array = array();		
		
		// access list of save_options fields
		global $wic_db_dictionary;
		$group_fields = $wic_db_dictionary->get_fields_for_group ( 'upload', 'save_options' );
		
		// extract values of the save options from the data object array
		foreach ( $group_fields as $order => $field_slug ) {
			$update_parameter_array[$field_slug] = $doa[$field_slug]->get_value();
		}			

		// add the update_parameter_array as a last entry to the save_update_array which will need to be popped off in db_save and db_update
		$save_update_array[] = $update_parameter_array;			
			
		return ( $save_update_array );
	}



	/*
	* this instance of db_save does the copy of the uploaded file to the database staging table 
	* and also maintains description/name fields using the parent db_save
	*/

	protected function db_save ( &$save_update_array ) {

		// start with a presumption of false outcome -- set to true if success
		$this->outcome = false;
		$save_start = time();

		// get upload parameters		
		$upload_parameters = array_pop ( $save_update_array ); 
		extract ( $upload_parameters ); 
	
		set_time_limit ( $max_execution_time );  // attempt this -- host may not allow it 	
		
		// handles MAC uploads 
		ini_set('auto_detect_line_endings', true);
		
		// open the file
		$handle = fopen( $_FILES['upload_file']['tmp_name'], 'rb' );
		// abort if can't open the file
		if ( ! $handle ) {
			$this->explanation =  __( 'Error opening uploaded file.', 'wp-issues-crm' );		
			return;
		}
	
		// have already validated as a csv in WIC_Control_File with consistent column count
  	   $columns = fgetcsv( $handle, $max_line_length, $delimiter, $enclosure, $escape  ); 
  	   // need to reget the column count
      $count_columns = count ( $columns );
		
		// access wordpress database object
		global $wpdb;

		// set up new table name
		$this->upload_time = $this->get_mysql_time();
		$this->upload_by = get_current_user_id();
		$table_name = $wpdb->prefix . 'wic_staging_table_' .
				str_replace ( '-', '_', str_replace ( ' ', '_', str_replace ( ':', '_', 
					$this->upload_time ) ) ) . 
					'_' . get_current_user_id();

		// create an array of reserved column names to test against
		// avoid duplicate column errors when reloading downloaded staging tables
		$reserved_column_names = array (
			'STAGING_TABLE_ID',
			'VALIDATION_STATUS',
			'VALIDATION_ERRORS',
			'MATCHED_CONSTITUENT_ID',
			'MATCH_PASS',
			'FIRST_NOT_FOUND_MATCH_PASS',
			'NOT_FOUND_VALUES',
			'INSERTED_NEW', 
			'INSERTED_CONSTITUENT_ID',
			'STAGING_TABLE_ID_STRING',
			'new_issue_ID',
			'new_issue_title',
			'new_issue_content',
			'record_count', 
			'inserted_post_id', 
		);	
				
		// create a table with the appropriate number of columns -- get column names from first row if available
		$sql = "CREATE TABLE $table_name ( "; 
		$i = 1;
		$column_names = array();
		foreach ( $columns as $column ) {
			// use user supplied column name (sanitized) if none of the following obtain
			$column_name = ( 
							in_array ( $this->sanitize_column_name ( $column ), $reserved_column_names ) ||	// not a reserved name after sanitization
							in_array ( $this->sanitize_column_name ( $column ), $column_names ) || 				// not a dup after sanitization 
							'' == $this->sanitize_column_name ( $column ) || 											// not empty after sanitization
							0 == $includes_column_headers ) 																	// not a data row
					?  'COLUMN_' . $i : $this->sanitize_column_name ( $column ); 
			$column_names[] = $column_name; 			
			$sql .= ' `' . $column_name . '` varchar(65535) NOT NULL, ';
			$i++;		
		}

		$sql .=  ' STAGING_TABLE_ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					VALIDATION_STATUS varchar(1) NOT NULL, '	.					// y or n -- n if there are errors, y if validated clean
					'VALIDATION_ERRORS varchar(65535) NOT NULL, ' .				// concatenation of all field validation errors
					'MATCHED_CONSTITUENT_ID bigint(20) unsigned NOT NULL, ' . // constituent id found in marked match pass
					'MATCH_PASS varchar(50) NOT NULL, ' . 							// populated only if constituent id found; stops later pass attempts to find
					'FIRST_NOT_FOUND_MATCH_PASS varchar(50) NOT NULL, ' .		// first match pass where values present if not found; may be found in later pass
					'NOT_FOUND_VALUES varchar(65535) NOT NULL, ' .				// concatenated values from not found match pass
					'INSERTED_NEW varchar(1) NOT NULL, ' .							// 'y' if inserted new (updated on insert)   
 					'PRIMARY KEY (STAGING_TABLE_ID), ' . 
 					'KEY MATCHED_CONSTITUENT_ID (MATCHED_CONSTITUENT_ID) ' . 
					') ENGINE=MyISAM  DEFAULT CHARSET=utf8;';

		$result = $wpdb->query ( $sql );

		if ( false === $result ) {
			$this->explanation =  __( 'Error creating staging table.', 'wp-issues-crm' );
			return;
		}
	
		/***********************************************************************************
		*
		*	Considered use of load data infile direct upload
		*   -- when it works, it is clearly faster, but  . . . 
		*  Whether it will work can depend on many factors, so can't rely on it for all users
		*   -- http://stackoverflow.com/questions/10762239/mysql-enable-load-data-local-infile
		*   -- http://dev.mysql.com/doc/refman/5.1/en/load-data.html
		*   -- http://dev.mysql.com/doc/refman/5.0/en/load-data-local.html
		*	 -- http://ubuntuforums.org/showthread.php?t=822084
		*   -- http://stackoverflow.com/questions/3971541/what-file-and-directory-permissions-are-required-for-mysql-load-data-infile
		*   -- http://stackoverflow.com/questions/4215231/load-data-infile-error-code-13 (apparmor issues)
		*	 -- https://help.ubuntu.com/lts/serverguide/mysql.html (configuration)
		*
		*  No real payoff in preserving it as an option for users.
		*
		*	Strategy: execution time aside, the risk with an insert approach is that 
		*  users will can into memory and packet size issues with larger packets in
		* 	long insert statements -- rather than force naive users to change these parameters, keep likely 
		* 	packet size low enough 
		*	// http://dev.mysql.com/doc/refman/5.5/en/packet-too-large.html
		*  
		************************************************************************************/
		
		// now prepare INSERT SQL stub to which will be added the values repetitively in loop below
		$first_column = $column_names[0];
		$sql_stub = "INSERT INTO $table_name ( `$first_column` " ;
		// add columns after column 0 with commas preceding
		$i = 0;
		foreach ( $column_names as $column_name ) {
			if ( $i > 0 ) {
				$sql_stub .= ", `$column_name` ";
			}	
			$i++;	
		}
		$sql_stub .= ') VALUES '; 	
		
		// if don't have column headers need to start over since went to get count record
		if ( 0 == $includes_column_headers ) {		
			rewind ( $handle );
		}	
		/****************
		*
		*	note: dominant consideration in setting rows_per_packet below is to avoid user blowing up and having to look for system parameters
		*	-- mysql max_allowed_packet (in multi-row inserts)
		*		http://dev.mysql.com/doc/refman/5.5/en/packet-too-large.html
		* 	-- php memory_limit (from array)
		*		packet size = row_length * number of rows (row length somewhat (20%?) longer than input file b/c stub + commas, quotes, etc.)
		*  	memory_limit = logically, 2 * packet size (+ whatever other memory needs) because have $sql and $values array roughly same size
		*			BUT peak memory usage >> what you would expect ( have observed x30 ish ) -- 
		*				see https://nikic.github.io/2011/12/12/How-big-are-PHP-arrays-really-Hint-BIG.html
		*	incremental per transaction time savings of larger row count (multi-row insert statements) declines and
		*     benefit is most significant for short rows; http://dev.mysql.com/doc/refman/5.0/en/insert-speed.html
		*     for longer rows typical of a conversion, the expense is in the long row length add
		*  max_allowed_packet size can be set in mysql by set global, but not for just session -- likely not permitted on shared servers
		*  memory_limit can also be set dynamically and limits may run higher on many servers, but no payoff if can't also raise packet size
		*  also limits to accommodate a file of 25,000 reasonably long records (600b) go close to 256M, 100,000 records could need 1G, poss not avail.
		*  only reason NOT to break into smaller packets is to avoid partial saves -- user can see this result in staging and redo if necessary
		*  SO: APPROACH THIS CONSERVATIVELY DO BREAK INTO SMALLISH INSERT PACKETS
		*   	-- set rows per packet at 7500000 / max line length -- this will keep packet size under 1M and memory requirements modest
		*			 -- short rows with high overhead could blow this formula up . . . . go with row_count of 100 if this is lower  
		*  	-- set max execution time at 1500 with user in control -- should cover a million records at under 1000/second
		*		-- don't set memory limit (already at 256M in wordpress and keeping packet size small) 
		*
		**************/ 
		$computed_rows_per_packet 	= $max_line_length > 0 					? abs( intval( 750000/$max_line_length ) ) : 1; 
		$rows_per_packet 				= $computed_rows_per_packet < 100 	? $computed_rows_per_packet : 100;
		$rows_per_packet 				= 0 == $rows_per_packet 				? 1 : $rows_per_packet;

		$insert_count = 0;			 
				
		// loop until end of file; would like to do transaction processing, but not supported by myisam
		// http://stackoverflow.com/questions/19153986/how-to-use-mysql-transaction-in-wordpress
		while ( ! feof ( $handle ) ) {

			$sql = $sql_stub;
			$j = 0;
			$values = array();
	      while ( ( $data = fgetcsv( $handle, $max_line_length, $delimiter, $enclosure, $escape  ) ) !== false )  {	
				$row = '( %s';
				$values[] = $this->null_to_empty ( $data[0] );
				$i = 0; 
				foreach ( $data as $column ) {	
					if ( $i > 0 ) {
						$row .= ',%s' ;
						$values[] = $this->null_to_empty ( $column ); 
					} 
					$i++;  // only counter purpose is to skip first column, since added at start of row (handling punctuation);
				}
				$row .= "),";
				$sql .= $row; 
				$j++;
				$insert_count++;
				if ( $j == $rows_per_packet ) {
					break;					
				}
			}

			// drop the final comma
			$sql = substr ( $sql, 0, -1 );

			// prepare the sql			
			$sql = $wpdb->prepare ( $sql, $values );

			// execute the insert
			$result = $wpdb->query ( $sql );

			// exit on failure
			if ( false === $result ) {
				$this->explanation =  __( 'Error loading staging table.', 'wp-issues-crm' );
				return;
			}

		} // end not eof loop

		$method = "INSERTS in packets of $rows_per_packet rows via wpdb";
		$database_insert_count = WIC_DB_Access::table_count ( $table_name ) > 0;
		if ( $database_insert_count != $insert_count ) {
			$discrepancy = __( 'Database count does not equal insert count for unknown reasons -- retry upload.', 'wp-issues-crm' );			
		}
		$this->outcome == true;
		
		// at this stage, $this->outcome = true because otherwise quit the loop and returned
		$elapsed_time = time() - $save_start;
		// expand the upload parameters array with results/accounting 
		$upload_parameters['method'] 					= $method;
		$upload_parameters['execution_time_allowed'] = ini_get ( 'max_execution_time' ); // should be same as max setting if was successful
		$upload_parameters['actual_execution_time'] 			= $elapsed_time;
		$upload_parameters['peak_memory_usage'] 	= memory_get_peak_usage( true );
		$upload_parameters['columns_count']			= $count_columns;
		$upload_parameters['insert_count']			= $insert_count;
		$upload_parameters['staging_table_name']	= $table_name;
		if ( isset ( $discrepancy ) ) {
			$upload_parameters['discrepancy']		= $discrepancy;
		} 
	
		// nota bene:  may be unsafe to unserialize and reserialize -- the escape character may generate problems
		// this field should be used in a readonly way from here on out -- it is a record of the upload and is frozen.
		$this->serialized_upload_parameters = json_encode( $upload_parameters );
		
		$interface_table = $wpdb->prefix . 'wic_interface';
		// prepare to lookup fields in learned column map
		$sql = "SELECT * FROM $interface_table WHERE upload_field_name = %s";
		// initialize column map for later use with unmapped columns
		$column_map = array();
		foreach ( $column_names as $column ) {
			// do lookups on field name 
			$lookup_sql = $wpdb->prepare ( $sql, array ( $column ) );
			$lookup = $wpdb->get_results ( $lookup_sql );
			$found = '';
			// if input column name found in interface table, put into column map as mapped (if target not already mapped to) 			
			if ( isset ( $lookup [0] ) ) { 
				$found = new WIC_DB_Upload_Column_Object ( $lookup[0]->matched_entity, $lookup[0]->matched_field );
				// test whether db field has already been mapped to
				foreach ( $column_map as $column_object ) {
					if ( $column_object > '' ) { // only testing columns that have actually been mapped
						if ( $column_object->entity == $found->entity && $column_object->field == $found->field ) {
							// if already mapped to, blank out found value
							$found = '';
							break;
						}				
					}			
				}	
			}
			// place a map value in array for every column -- empty if not found or not unique
			$column_map[$column] = $found;			
		}
		$this->serialized_column_map = json_encode ( $column_map );	
		
		$this->upload_status = ( '' == WIC_Entity_Upload::is_column_mapping_valid ( $column_map ) ) ? 'mapped' : 'staged';
		// proceed to update the upload table with the identity of the successful upload
		$save_update_array[] = array( 
			'key' 					=> 'upload_time', 
			'value'					=> $this->upload_time,
			'wp_query_parameter' => '', 
			'soundex_enabled'		=> false,
		);
		
		$save_update_array[] = array( 
			'key' 					=> 'upload_by', 
			'value'					=> $this->upload_by,
			'wp_query_parameter' => '', 
			'soundex_enabled'		=> false,
		); 	
		
		$save_update_array[] = array( 
			'key' 					=> 'serialized_upload_parameters', 
			'value'					=> $this->serialized_upload_parameters,
			'wp_query_parameter' => '', 
			'soundex_enabled'		=> false,
		); 	

		$save_update_array[] = array( 
			'key' 					=> 'serialized_column_map', 
			'value'					=> $this->serialized_column_map,
			'wp_query_parameter' => '', 
			'soundex_enabled'		=> false,
		); 	
		
		$save_update_array[] = array( 
			'key' 					=> 'upload_status', 
			'value'					=> $this->upload_status,
			'wp_query_parameter' => '', 
			'soundex_enabled'		=> false,
		); 	
					
		parent::db_save( $save_update_array );
		
	}
	
	private function null_to_empty ( $value ) {
		$value = ( '\N' == $value  ||  'NULL' == $value || NULL === $value ) ? '' : $value;
		return $value; 
	}	
	
	// limit column name to letters, digits and underscore
	private function sanitize_column_name ( $column_name ) { 
		$stripped = preg_replace( '/[^A-Za-z0-9_]/', '', $column_name );	
		$non_numeric_column_name = is_numeric( $stripped ) ? '' : $stripped;
		$clean_column_name = $this->null_to_empty ( $non_numeric_column_name );
		return ( $clean_column_name ); // may be empty if reduces to empty or a number 
	}	
	
	protected function db_update ( &$save_update_array ) {
		// get rid of the upload parameters
		array_pop ( $save_update_array );
		parent::db_update ( $save_update_array );	
	}	
	
	/**
	*
	* support for column mapping ajax in wic_entity_upload
	*
	*/	
	
	// get sample data for columns
	public static function get_sample_data ( $staging_table_name ) {
		global $wpdb;
		$sql = "SELECT * from $staging_table_name limit 0, 5";
		$result = $wpdb->get_results( $sql, ARRAY_A );
		$inverted_array = array();
		foreach ( $result as $key => $row ) {
			foreach ( $row as $column_head => $value ) {
				$inverted_array[$column_head][$key] = $value;			
			}
		}
		$column_head_array = array();
		foreach ( $inverted_array as $column_head => $column ) {
			$column_head_array[$column_head] = esc_attr ( substr( implode( ', ', $column ), 0, 100 ) );
		}
		return ( $column_head_array );
	}	
	
	// quick look up
	public static function get_column_map ( $upload_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wic_upload';
		$sql = "SELECT serialized_column_map FROM $table where ID = $upload_id";
		$result = $wpdb->get_results( $sql );
		return ( $result[0]->serialized_column_map );
	}	
	
	// quick update
	public static function update_column_map ( $upload_id, $serialized_map ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wic_upload';
		$sql = "UPDATE $table set serialized_column_map = '$serialized_map' WHERE ID = $upload_id";
		$result = $wpdb->query( $sql );
		return ( $result );
	}	
	
	// quick update
	public static function update_upload_status ( $upload_id, $upload_status ) {
		global $wpdb;  
		$table = $wpdb->prefix . 'wic_upload';
		$sql = "UPDATE $table set upload_status = '$upload_status' WHERE ID = $upload_id";
		$result = $wpdb->query( $sql );
		return ( $result );
	}	

	// quick update
	public static function update_interface_table ( $upload_field, $entity_field_object ) {

		global $wpdb;

		// if unmatching the field, entity_field_object will come in as empty string		
		if ( is_object ( $entity_field_object ) ) {
			$entity = $entity_field_object->entity;
			$field = $entity_field_object->field;
		} else { 
			$entity = '';
			$field  = '';		
		}	

		$table = $wpdb->prefix . 'wic_interface';
		$sql = "SELECT matched_entity, matched_field from  $table WHERE  upload_field_name = '$upload_field'";
		$result = $wpdb->get_results( $sql );
		if ( isset ( $result[0] ) ) {
			if ( 	$result[0]->matched_entity != $entity  ||
					$result[0]->matched_field != $field 
				) {
				if ( '' != $field && '' != $entity ) {	
					$sql = "UPDATE $table SET matched_entity = '$entity', matched_field = '$field' WHERE upload_field_name = '$upload_field'";
				} else { // no empty entries
					$sql = "DELETE from $table WHERE upload_field_name = '$upload_field'";				
				}
				$result = $wpdb->query ( $sql );
			} 
		} else {
			$sql = "INSERT INTO $table ( upload_field_name, matched_entity, matched_field ) VALUES ( '$upload_field', '$entity', '$field' )";
			$result = $wpdb->query ( $sql );
		}
		
		return ( $result );
	}

	/**
	*
	* support for column validation ajax in wic_entity_upload
	*
	*/	

	public static function get_staging_table_records( $staging_table, $offset, $limit, $field_list ) {
	
		global $wpdb;
		$field_list = ( '*' == $field_list ) ? $staging_table . '.' . $field_list : $field_list;
		$sql = "SELECT STAGING_TABLE_ID, VALIDATION_STATUS, VALIDATION_ERRORS, 
				MATCHED_CONSTITUENT_ID, MATCH_PASS, 
 				FIRST_NOT_FOUND_MATCH_PASS, NOT_FOUND_VALUES,				
				$field_list FROM $staging_table LIMIT $offset, $limit";
		$result = $wpdb->get_results( $sql );
		return ( $result ); 
	}

	public static function record_validation_results ( &$update_clause_array, $staging_table, $id, $error ) {

		global $wpdb;
		
		// code validation_status -- empty error is valid
		$validation_status = ( '' == $error ) ? 'y' : 'n';
		// set up update sql from array
		$record_update_string = ' VALIDATION_STATUS = %s, VALIDATION_ERRORS = %s ';
		$record_update_array = array( $validation_status, $error );
		if ( count ( $update_clause_array ) > 0 ) {
			foreach ( $update_clause_array as $update_clause ) {
				$record_update_string .= ', '. $update_clause['column'] . ' = %s '; 		
				$record_update_array[] = $update_clause['value'];
			}
		}
		$pre_sql = "UPDATE $staging_table SET $record_update_string WHERE STAGING_TABLE_ID = $id";

		// prepare sql -- theoretically unnecessary, since data was already escaped when saving to database . . .
		$sql = $wpdb->prepare( $pre_sql, $record_update_array );
		// run the update
		$result = $wpdb->query( $sql );

		// $result = record count.  anything other than 1 is an error in this context. false is a database error.
		return ( $result == 1 ); 	

	}
	
	public static function reset_staging_table_validation_indicators( $table ) { 
		global $wpdb;
		$sql = "UPDATE $table SET VALIDATION_STATUS = '', VALIDATION_ERRORS = ''";	
		$result = $wpdb->query( $sql );
		// 0 is an OK result if reset did nothing
		return ( $result !== false );
	}
	
	/*
	*
	* support match functions
	*/
	
		// quick look up
	public static function get_match_results ( $upload_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wic_upload';
		$sql = "SELECT serialized_match_results FROM $table where ID = $upload_id";
		$result = $wpdb->get_results( $sql );
		return ( $result[0]->serialized_match_results );
	}
		
	// quick update
	public static function update_match_results ( $upload_id, $serialized_match_results ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wic_upload';
		$sql = "UPDATE $table set serialized_match_results = '$serialized_match_results' WHERE ID = $upload_id";
		$result = $wpdb->query( $sql );
		return ( $result );
	}	
	
	public static function reset_staging_table_match_indicators( $table ) { 
		global $wpdb;
		$sql = "UPDATE $table SET MATCHED_CONSTITUENT_ID = 0, MATCH_PASS = '', FIRST_NOT_FOUND_MATCH_PASS = '', NOT_FOUND_VALUES = '' ";	
		$result = $wpdb->query( $sql );
		// 0 is an OK result if reset did nothing
		
		$unmatched_table = $table . '_unmatched';


		$sql = "DROP TABLE IF EXISTS $unmatched_table";

		$result2 = $wpdb->query( $sql );		
		
		return ( $result !== false && $result2 != false );
	}
	
	public static function record_match_results ( 
					$staging_table, 
					$staging_id,
 					$match_pass,
					$matched_constituent_id,
					$first_not_found_match_pass,
					$not_found_values ) {
		
		// should only be called if exactly one value is non-blank 
		if ( ( '' == $match_pass && '' == $first_not_found_match_pass ) || ( '' < $match_pass && '' < $first_not_found_match_pass ) ) {
			return ( false );		
		}

		global $wpdb;

		// set up to update either the found variables or the not found variables  
		$set_clause = ( '' < $match_pass ) ? 
			" SET MATCH_PASS = '$match_pass', MATCHED_CONSTITUENT_ID = $matched_constituent_id " :
			$wpdb->prepare( " SET FIRST_NOT_FOUND_MATCH_PASS = '$first_not_found_match_pass', NOT_FOUND_VALUES = %s ", array( $not_found_values ) );  
		$sql = "UPDATE $staging_table 
			$set_clause  
			WHERE STAGING_TABLE_ID = $staging_id";
		$result = $wpdb->query( $sql );
		// $result = record count.  anything other than 1 is an error in this context. false is a database error.
		return ( $result == 1 ); 	

	}
	
	/*
	*	If staging table has at least one "group required" identifier -- last_name, first_name or email -- can create new constituents.
	*	In this step, create an intermediate table grouping staging table by the identifiers in the match process --
	*			group by the first match stage for which record had all available identifiers; include only those not matched in any pass
	*	This "_unmatched" table includes constituent stubs with all fields on the constituent entity and pointers back to staging table records.
	*	Filter out any records which lack all of the group identifiers -- all constituent stubs on this table can be added
	*	Complete report of unmatched counts, omitting from the count (as from the _unmatched table) those records that are not viable (lacking fn/ln/email)
	*  
	*/	
   public static function create_unique_unmatched_table ( $upload_id, $staging_table ) {		
		// create parallel arrays of WIC constituent fields and associated fields in the upload -- working directly from column map
		$column_map = json_decode ( WIC_DB_Access_Upload::get_column_map ( $upload_id ) );
		$constituent_field_array = array();
		$staging_table_column_array = array();	
		$group_required_identifiers_array = array();	
		foreach ( $column_map as $column => $entity_field_object ) {
			if ( '' < $entity_field_object ) { // unmapped columns have an empty entity_field_object
				if ( 'constituent' == $entity_field_object->entity  ) {
					$constituent_field_array[] = $entity_field_object->field;
					$staging_table_column_array[] = $column;	
				}
				// create secondary array to construct having clause to assure at least one identifier present
				if ( 	( 'constituent' == $entity_field_object->entity && 'first_name' 		== $entity_field_object->field ) || 
						( 'constituent' == $entity_field_object->entity && 'last_name' 		== $entity_field_object->field ) ||
						( 'email' == $entity_field_object->entity 		&& 'email_address' 	== $entity_field_object->field ) ) {
					$group_required_identifiers_array[] = $column;
					// group required in sense that none is individually required, but must have at least one				
					// does not exactly match to dictionary which supports email as multivalued -- durable enough idea to hard code
				}
			}		
		}		

		// get match result array ( has everything but the unmatched filled in at this stage )
		$match_results = json_decode ( self::get_match_results( $upload_id ) );

		// set all unmatched counters to 0 -- initialized as '? up to this point (next step may not hit them all)
		foreach ( $match_results as $rule ) {
			$rule->unmatched_unique_values_of_components = 0;			
		}	

		// proceed to prepare constituent stubs for addition and count additions, but first check that have at least one of required identifiers
		$identifiers_count = count ( $group_required_identifiers_array ); 

		// only proceed if have mapped at least one of required identifiers -- if none, cannot be adding constituents
		//  . . . in that case, bypass whole process 
		if ( $identifiers_count > 0 ) {
			
			// first create the table which will hold the unmatched records
			$unmatched_staging_table = $staging_table . '_unmatched'; 
			global $wpdb;
			
			// belt and suspenders -- should have happened on reset_match 
			$sql = "DROP TABLE IF EXISTS $unmatched_staging_table";
			$drop_result = $wpdb->query( $sql );
			
			// use all the available constituent columns (constituent level data items that have been mapped to)
			// these are items like first name, last name, date of birth that exist on the constituent database record
			$sql = "CREATE TABLE $unmatched_staging_table ( "; 
			foreach ( $constituent_field_array as $field ) {
				$sql .= ' ' . $field . ' varchar(65535) NOT NULL, ';
			}
			// . . . include standardized tracking columns in the table too
			$sql .=  'STAGING_TABLE_ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						FIRST_NOT_FOUND_MATCH_PASS varchar(50) NOT NULL,
						INSERTED_CONSTITUENT_ID bigint(20) unsigned NOT NULL,
						STAGING_TABLE_ID_STRING varchar(65535) NOT NULL,
						PRIMARY KEY (STAGING_TABLE_ID), 
						KEY MATCH_PASS (FIRST_NOT_FOUND_MATCH_PASS) ) 
						ENGINE=MyISAM  DEFAULT CHARSET=utf8;';
			$result = $wpdb->query ( $sql );
			if ( false === $result ) {
				return ( false );		
			}
	
			// now populate that table with unique unfound values

			// from the staging table, collect all input columns mapped to constituent level data items
			$select_column_list = '';  
			foreach ( $staging_table_column_array as $column ) {
				$select_column_list .= ' MAX(' . $column . '), ';		
			}
			
			// all available identifiers -- require have at least one non-blank to form constituent stub
			// note that email will not actually be in the constituent stub, but will pick it up later 
			// since have pointer back to staging record
			$having_clause = ' HAVING ';  
			$i = 0;
			foreach ( $group_required_identifiers_array as $column ) {
				$i++;
				$having_clause .= " MAX( $column ) > ''  ";
				$having_clause .= ( $i < $identifiers_count ) ? 'OR' : ''; 		
			}

			
			$sql = 	"INSERT $unmatched_staging_table
						SELECT $select_column_list NULL, FIRST_NOT_FOUND_MATCH_PASS, 0, GROUP_CONCAT( STAGING_TABLE_ID )
						FROM $staging_table 
						WHERE MATCH_PASS = '' AND FIRST_NOT_FOUND_MATCH_PASS > '' AND VALIDATION_STATUS = 'y'
						GROUP BY FIRST_NOT_FOUND_MATCH_PASS, NOT_FOUND_VALUES
						$having_clause						
						";

			// MATCH_PASS = '' means never matched.  Might have FIRST_NOT_FOUND_MATCH_PASS = '' if lacked values for all passes
			$insert_count = $wpdb->query ( $sql );
			if ( false === $insert_count ) {
				return ( false );		
			} 

			// calculate updateable records remaining unmatched from each match pass (after all matches done )
			$sql = "
					SELECT FIRST_NOT_FOUND_MATCH_PASS as pass_slug, COUNT(FIRST_NOT_FOUND_MATCH_PASS) AS pass_count
					FROM $staging_table 
					WHERE MATCH_PASS = '' AND FIRST_NOT_FOUND_MATCH_PASS > '' 
					GROUP BY FIRST_NOT_FOUND_MATCH_PASS
					";
			$result_array = $wpdb->get_results ( $sql );
			if ( false !== $result_array ) {
				if ( 0 != count ( $result_array ) ) { 
					foreach ( $result_array as $result ) { 
						$match_results->{$result->pass_slug}->unmatched_records_with_valid_components = $result->pass_count;		
					}
				}	
			}
			
			// calculate unique counts from each match pass
			$sql = "SELECT FIRST_NOT_FOUND_MATCH_PASS as pass_slug, COUNT(FIRST_NOT_FOUND_MATCH_PASS) AS pass_count
					FROM $unmatched_staging_table 
					GROUP BY FIRST_NOT_FOUND_MATCH_PASS";
			$result_array = $wpdb->get_results ( $sql );
			if ( false === $result_array ) {
				return ( false );		
			} 
	
			if ( 0 != count ( $result_array ) ) { 
				foreach ( $result_array as $result ) { 
					$match_results->{$result->pass_slug}->unmatched_unique_values_of_components = $result->pass_count;		
				}
			}
		} // close branch of having at least one group required identifier		
		
		// this may reflect all 0's in unmatched unique if did not find group identifiers 	
		self::update_match_results ( $upload_id, json_encode ( $match_results ) );
		
		return ( $match_results );
	}

	// support default decisions logging
		// quick update
	public static function update_default_decisions ( $upload_id, $serialized_default_decisions ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wic_upload';
		$sql = "UPDATE $table set serialized_default_decisions = '$serialized_default_decisions' WHERE ID = $upload_id";
		$result = $wpdb->query( $sql );
		return ( $result );
	}	

	public static function get_default_decisions ( $upload_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wic_upload';
		$sql = "SELECT serialized_default_decisions FROM $table where ID = $upload_id";
		$result = $wpdb->get_results( $sql );
		return ( $result[0]->serialized_default_decisions );
	}

	/*
	* for issue titles in upload that don't exist on wp_post, create a table for later possible post creation
	* table will also be used to show user what will be created
	*
	* note: makes no sense to default tags or cats -- in normal usage, these should vary across records 
	* also, they are unlikely to be included in input sources -- if they are, user knows how to do
	* backend processing and can solve own problems
	*/ 
	public static function get_unmatched_issues ( $staging_table, $issue_title_column, $issue_content_column ) {
		
		global $wpdb;
		// new staging table name				
		$new_issue_table = $staging_table . '_new_issues';
		// wordpress post table
		$post_table = $wpdb->posts;
		// drop table if it already exists (may have been remapped)
		$sql = "DROP TABLE IF EXISTS $new_issue_table";
		$result = $wpdb->query( $sql );		
			
		$sql = "CREATE TABLE $new_issue_table (
			new_issue_ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			new_issue_title varchar(255) NOT NULL,
			new_issue_content varchar(65535) NOT NULL,
			record_count bigint(20) unsigned NOT NULL,
			inserted_post_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY (new_issue_ID),			
			KEY new_issue_title (new_issue_title)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ";
		$result = $wpdb->query( $sql );
		
		$new_issue_content_source = ( $issue_content_column > '' ) ? $issue_content_column : "''";				
		
		$sql = 	"INSERT INTO $new_issue_table ( new_issue_title, new_issue_content, record_count )
					SELECT new_issue_title, new_issue_content, record_count 
					FROM ( 
						SELECT 
							$issue_title_column as new_issue_title,
							$new_issue_content_source as new_issue_content, 
							count(STAGING_TABLE_ID) as record_count 
						FROM $staging_table 
						WHERE VALIDATION_STATUS = 'y' 
						GROUP BY $issue_title_column 
						) as issues
					LEFT JOIN $post_table ON new_issue_title = post_title
						AND ( post_status = 'publish' or post_status = 'private' ) and post_type = 'post'
					WHERE post_title is null
						";
		$result = $wpdb->query( $sql );	
		
		$sql = "SELECT new_issue_title, record_count FROM $new_issue_table ORDER BY new_issue_title";
	
		$results = $wpdb->get_results( $sql );
		return ($results);
	
	}
	
	/*	
	*
	* functions to support final completion of upload
	* completion is a three phase process:
	*	- add new issues if any
	*	- add new constituents if any
	*	- apply updates to old and new constituents
	*		in the case of the new constituents, the updates are additions of address, email, phone or activity records
	*		in case of old, same and/or updates to the base constituent record
	*	- results are retained serialized_final_results field on the upload table record
	*
	*/
	
	// quick look up -- includes initial construction of value if not already there
	public static function get_final_results ( $upload_id ) {

		global $wpdb;
		$table = $wpdb->prefix . 'wic_upload';
		$sql = "SELECT serialized_final_results FROM $table where ID = $upload_id";
		$result = $wpdb->get_results( $sql );

		if ( $result[0]->serialized_final_results > '' )
			return ( $result[0]->serialized_final_results );
		// if still blank, then this is first pass -- return starting json string with 0 values
		else { 
			$stub = '{
				"new_issues_saved":0,
				"new_constituents_saved":0, 
				"input_records_associated_with_new_constituents_saved":0, 
				"constituent_updates_applied":0, 
				"total_valid_records_processed":0
				}';
			return ( $stub );				
		}

	}
		
	// quick update
	public static function update_final_results ( $upload_id, $serialized_final_results ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wic_upload';
		$sql = "UPDATE $table set serialized_final_results = '$serialized_final_results' WHERE ID = $upload_id";
		$result = $wpdb->query( $sql );
		return ( $result );
	}	
	
	// first phase of upload completion
	public static function complete_upload_save_new_issues ( $upload_id, $staging_table, $offset, $chunk_size ) {
		// note that this function does not actually use $offset and $chunk-size -- does single save process, unphased.

		// set globals
		global $wpdb;
		$table = $staging_table . '_new_issues';
		
		// get result count object -- get will create blank object, since this is first phase of uplod
		$final_results = json_decode ( self::get_final_results ( $upload_id ) );

		/*
		* is there a new issues table at all -- not created if no titles mapped
		* belt and suspenders -- check that new issues table exists -- shouldn't be calling this if it doesn't
		* see wic-upload-complete.js:  this workphase is not invoked unless option to create new issues is selected and
		*  . . . see wic-upload-set-defaults.js . . . that option is not offered unless title table is created
		* -- title table is created in wic-upload-set-defaults.js to show user what titles will be created
		* -- see get_unmatched_issues above, the method invoked from this class
		*/ 
		$sql = "SHOW TABLES LIKE '$table'";
		$result = $wpdb->get_results ( $sql );

		$post_table = $wpdb->posts;

		$new_issues_saved = 0;
		// if table was not found, $new_issues_saved stays 0		
		if ( 0 < count ( $result ) ) {
			
			// select new issues that are still not inserted to assure rerunnability
			// if have already added them inserted_post_id will have been set > 0
			$sql = "SELECT * FROM $table 
						WHERE inserted_post_id = 0
						";
			$new_issues = $wpdb->get_results( $sql );

			// create a WIC WP query object for repeated use
			$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( 'issue' );
			
			// create a template to spoof a form submission
			$save_array_template = array (
				array( 'key' 	=> 'ID', 
				 'value'	=> '', 
				 'wp_query_parameter' => 'p', 
				), 
				array( 'key' 	=> 'post_title', 
				 'value'	=> '', 
				 'wp_query_parameter' => 'post_title', 
				),
				array( 'key' 	=> 'post_content', 
				 'value'	=> '', 
				 'wp_query_parameter' => 'post_content', 
				), 
			); 
			
			// submit each record in the issue table for saving as new post 
			foreach ( $new_issues as $new_issue ) {
				// populate the template with title and content
				$save_array_template[1]['value'] = $new_issue->new_issue_title;
				$save_array_template[2]['value'] = $new_issue->new_issue_content;

				// invoke query object save function directly 
				$wic_query->db_save ( $save_array_template );
				$id_to_save = $wic_query->insert_id;
				$new_issue_id = $new_issue->new_issue_ID;				
				
				// update issue staging table with new post ID 
				$sql = "UPDATE $table SET inserted_post_id = $id_to_save WHERE new_issue_ID = $new_issue_id";
				$wpdb->query ( $sql );
				$new_issues_saved++;
			}				
		} 
		
		// save issue count and return the final results object as updated	
		$final_results->new_issues_saved = $new_issues_saved;		
		$final_results = json_encode ( $final_results );
		self::update_final_results( $upload_id, $final_results );
		return ( $final_results ) ;		
	}
	
	/*
	*
	* Save the new constituents that have been identified through the matching process
	*
	*/
	public static function complete_upload_save_new_constituents	( $upload_id, $staging_table, $offset, $chunk_size ) {
		
		// set globals
		global $wpdb;
		global $wic_db_dictionary;
		
		// construct data object array with only the controls we need to spoof form data entry
		$data_object_array = array(); 
		
		$table = $staging_table . '_unmatched';
		
		// get the current counts -- from prior phase or pass
		$final_results = json_decode ( self::get_final_results ( $upload_id ) );
		
		/*
		* is there a new constituent table at all -- not created if no constituents unmatched
		* belt and suspenders -- again, shouldn't be calling this if it doesn't exist
		* see wic-upload-complete.js:  this phase is not invoked unless option to create new constituent is selected and
		*  . . . see wic-upload-set-defaults.js . . . that option is not offered to set unless there are new constituents to save
		*/ 
		$sql = "SHOW TABLES LIKE '$table'";
		$result = $wpdb->get_results ( $sql );

		// initialize count variables
		$new_constituents_saved = 0;
		$input_records_associated_with_new_constituents_saved = 0; // multiple input records can group to a single new constituent
		
		// skip processing if no unmatched table found
		if ( 0 < count ( $result ) ) {
			$sql = "SELECT * FROM $table LIMIT $offset, $chunk_size";
			$new_constituents = $wpdb->get_results( $sql );

			// get array of columns in table that are mapped to constituent fields -- 
			// same selection of columns used in previous construction of $table 
			// see method above -- create_unique_unmatched_table
			$column_map = json_decode ( WIC_DB_Access_Upload::get_column_map ( $upload_id ) );
			foreach ( $column_map as $column => $entity_field_object ) {
				if ( '' < $entity_field_object ) { // unmapped columns have an empty entity_field_object
					if ( 'constituent' == $entity_field_object->entity ) {
						$field_rule = $wic_db_dictionary->get_field_rules ( 'constituent', $entity_field_object->field );					
						$data_object_array[$field_rule->field_slug] = WIC_Control_Factory::make_a_control( $field_rule->field_type );	
						$data_object_array[$field_rule->field_slug]->initialize_default_values(  'constituent', $field_rule->field_slug, '' );				
					}
				}		
			}	
			
			/*
			*	need ID in the array for updates through the query object
			*	note that the possible situations here are as follows:
			*		a) ID is not in the object array, so not also not on the staging table -- clean; just create new id's
			*			staging table records will be sent to method db_save with ID = 0 and get new ID in save process
			*		b) ID is in the object array so also on the staging table and, by logic enforced in upload_match_strategies, the only match field
			*			-- if 0, is not valid, not in unmatched table
			*			-- if empty, not matchable, so not capable of being not found, so not in the unmatched table
			*			-- if non-empty valid, then matched (b/c must be validation for ID is match) , not in the unmatched table
			*			-- if non-empty not valid, not in unmatched table
			*	In other words, if ID is mapped, unmatched table will be created, but is always empty.
			*/
			if ( ! isset ( $data_object_array['ID'] ) ) {
				$field_rule = $wic_db_dictionary->get_field_rules ( 'constituent', 'ID' );					
				$data_object_array[$field_rule->field_slug] = WIC_Control_Factory::make_a_control( $field_rule->field_type );	
				$data_object_array[$field_rule->field_slug]->initialize_default_values(  'constituent', $field_rule->field_slug, '' );
			}
			$data_object_array['ID']->set_value ( 0 );
			
			// create a db access object to which will spoof partial forms			
			$wic_access_object = WIC_DB_Access_Factory::make_a_db_access_object( 'constituent' );
			
			// process the unmatched file
			foreach ( $new_constituents as $new_constituent ) { 
	
				// first test to see if this job might have already been done -- assuring rerunnability
				// just skip the record if already processed
				if ( 0 == $new_constituent->INSERTED_CONSTITUENT_ID ) { 	
					
					// populate data_object_array with values from staging table (none allowed are multivalue)
					foreach ( $data_object_array as $field => $control ) {
						if ( isset ( $new_constituent->$field ) ) {				// test isset b/c may not have ID among fields
							$control->set_value( $new_constituent->$field ); 	// should loop through all $new_constituent fields
						}
					}
					
	
					// use database object -- no search_logging or extra time stamps in this:  efficient
					// also does prepare on all values, so this is as robust as form -- validated, sanitized, now escaped
					$wic_access_object->save_update( $data_object_array );
					// if save successful, log save in several ways 
					if ( $wic_access_object->outcome ) {
						
						// do count for the final results 
						$new_constituents_saved++;
						
						// update the unmatched table -- this is to assure rerunnability
						$id_to_save = $wic_access_object->insert_id;
						$id_to_update = $new_constituent->STAGING_TABLE_ID;
						$sql = "UPDATE $table SET INSERTED_CONSTITUENT_ID = $id_to_save 
							WHERE STAGING_TABLE_ID =  $id_to_update";
						$result = $wpdb->query ( $sql ); 
					
						// now prepare to update the possibly multiple staging table records with the insert ID		
						$staging_table_id_string =  $new_constituent->STAGING_TABLE_ID_STRING ;
						$staging_table_id_array  =  explode ( ',', $staging_table_id_string );
						// posting insert ID's back to the original staging table
						$sql = "UPDATE $staging_table SET MATCHED_CONSTITUENT_ID = $id_to_save, INSERTED_NEW = 'y' 
								WHERE STAGING_TABLE_ID IN  ( $staging_table_id_string )";
						$result = $wpdb->query ( $sql );
						if ( $result !== false ) {
							$input_records_associated_with_new_constituents_saved += count ( $staging_table_id_array );
						} 
					}  // close did successful save?
				}	// close test for already processed 
			}	// close loop through unmatched table
		}	// close unmatched table found

		$final_results->new_constituents_saved += $new_constituents_saved;
		$final_results->input_records_associated_with_new_constituents_saved += $input_records_associated_with_new_constituents_saved;
		$final_results = json_encode ( $final_results );
		self::update_final_results( $upload_id, $final_results );
		return ( $final_results ) ;		
	}
	
	/*
	*
	* it all builds to this! -- final update of constituents
	*
	*/
	public static function complete_upload_update_constituents	( $upload_id, $staging_table, $offset, $chunk_size ) {
	
		global $wpdb;
		global $wic_db_dictionary;
		
		// have multiple entities to update -- create array of data object arrays for each
		$data_object_array_array = array(
			'constituent' 	=> array(),
			'address'		=> array(),
			'email'			=> array(),
			'phone'			=> array(),		
			'issue'			=> array(),	// order matters -- need to process issue before activity to look up title if using it	
			'activity'		=> array(),
		); 
		
		$final_results = json_decode ( self::get_final_results ( $upload_id ) );
		
		// set counters of activity for this pass
		$constituent_updates_applied 		= 0; // counts non-new valid records (i.e., matched records)
		$total_valid_records_processed 	= 0; // counts all valid records (matched and unmatched, possible multiple records for each new unmatched)

		// get array of array of columns of table mapped to entity fields 
		$column_map = json_decode ( WIC_DB_Access_Upload::get_column_map ( $upload_id ) );
		
		// inject controls into data_object_arrays for each mapped column; also create a separate array of mapped/used columns
		$used_columns = array();
		foreach ( $column_map as $column => $entity_field_object ) {
			if ( '' < $entity_field_object ) { // unmapped columns have an empty entity_field_object
				$used_columns[] = $column;
				$field_rule = $wic_db_dictionary->get_field_rules (  $entity_field_object->entity, $entity_field_object->field );					
				$data_object_array_array[ $entity_field_object->entity ][$field_rule->field_slug] = WIC_Control_Factory::make_a_control( $field_rule->field_type );	
				$data_object_array_array[ $entity_field_object->entity ][$field_rule->field_slug]->initialize_default_values(  $entity_field_object->entity, $field_rule->field_slug, '' );				
			} 	
		}	
		
		
		// get the serialized default decisions 		
		$default_decisions = json_decode ( WIC_DB_Access_Upload::get_default_decisions ( $upload_id ) );		
		
		// need ID in the array if not there
		// in same loop, populate an array of data access objects
		// in same loop, add default decisions into array
		// in same loop, add constituent_id into the multivalue entities
		$wic_access_object_array = array();
		foreach ( $data_object_array_array as $entity=>&$data_object_array ) {	// looping on pointer, so altering underlying object
		
			// add ID to each array
			if ( ! isset ( $data_object_array['ID'] ) ) {
				$field_rule = $wic_db_dictionary->get_field_rules ( $entity, 'ID' );					
				$data_object_array[$field_rule->field_slug] = WIC_Control_Factory::make_a_control( $field_rule->field_type );	
				$data_object_array[$field_rule->field_slug]->initialize_default_values( $entity, $field_rule->field_slug, '' );
			}
			$data_object_array['ID']->set_value ( 0 );
			
			// set up corresponding access object
			$wic_access_object_array[$entity] = WIC_DB_Access_Factory::make_a_db_access_object( $entity );
			// supplement array with default fields set 
			// (will not be overlayed by values from staging record in loop below since by def are not mapped )
			// for each entity, loop through default fields and if set, add them into array with value set
			// upload default field groups are named to match entities 
			$group_fields =  $wic_db_dictionary->get_fields_for_group ( 'upload', $entity );
			// note that $group_fields will be empty for issue and constituent,  
			// but the return is an array, so foreach does not generate error but does nothing 
			foreach ( $group_fields as $field_order => $field_slug ) {
				if ( $default_decisions->$field_slug > '' ) {
					$field_rule = $wic_db_dictionary->get_field_rules (  $entity, $field_slug );
					$data_object_array[$field_slug] = WIC_Control_Factory::make_a_control( $field_rule->field_type );	
					$data_object_array[$field_slug]->initialize_default_values(  $entity, $field_slug, '' );
					$data_object_array[$field_slug]->set_value( $default_decisions->$field_slug );
				}			
			}
			// add constituent id control
			if ( $entity != 'constituent' && $entity != 'issue' ) {
				$field_rule = $wic_db_dictionary->get_field_rules ( $entity, 'constituent_id' );					
				$data_object_array[$field_rule->field_slug] = WIC_Control_Factory::make_a_control( $field_rule->field_type );	
				$data_object_array[$field_rule->field_slug]->initialize_default_values( $entity, $field_rule->field_slug, '' );
			}
		}
		// necessary to unset when using foreach with pointer -- http://php.net/manual/en/control-structures.foreach.php
		unset ( $entity );
		unset ( $data_object_array );		

		// get staging records -- processing all, without validity checking, 
		// but invalid do not have matched_constitutent_id, so will be skipped
		$used_columns_string = implode ( ',', $used_columns );
		$sql = "SELECT $used_columns_string, MATCHED_CONSTITUENT_ID, INSERTED_NEW
				  FROM $staging_table LIMIT $offset, $chunk_size
				  ";
		$staging_records = $wpdb->get_results( $sql );
		

		// set up search parms for use within loop
		$search_parameters = array(
			'log_search' => false,
		);
		// set dup_check to true forces = comparison for all; match_level = 0 does the same, . . . 
		// but dup_check makes range control do a single value = comparison ( which is what we want for date fields )
		// note that in the form context, dedup property of field controls whether gets polled in assemble_meta_query_array when dup_check = true,
		// but since not using assemble_meta_query_array to assemble array, setting dup_check true does not lose any fields
		$search_clause_args = array(
			'match_level' => '0',
			'dup_check' => true, 
			'category_search_mode' => '',
			);
		
		// determine whether to use issue titles in the loop; add the issue control to activity if using titles and not already mapped
		if ( 	isset ($data_object_array_array['issue']['post_title'] ) && //  have title and 
				!isset( $data_object_array_array['activity']['issue'] ) ) {			//  need title (issue was not mapped or defaulted)
			$use_title = true;
			$field_rule = $wic_db_dictionary->get_field_rules ( 'activity', 'issue' );					
			$data_object_array_array['activity']['issue'] = WIC_Control_Factory::make_a_control( $field_rule->field_type );	
			$data_object_array_array['activity']['issue']->initialize_default_values( 'activity', 'issue', '' );
		} else {
			$use_title = false;		
		}

		foreach ( $staging_records as $staging_record ) {
			
			// populate the data object arrays with values from mapped columns (omitting the control columns on the staging record)
			// note that controls for set defaults are already populated 
			foreach ( $used_columns as $column ) {
				$data_object_array_array[$column_map->$column->entity][$column_map->$column->field]->set_value( $staging_record->$column );
			}
			
			// apply switches to determine whether to skip updates
			if ( 0 == $staging_record->MATCHED_CONSTITUENT_ID || // never matched -- invalid OR matched to dups on db OR unmatched, but unmatched save off   
				  ( ! $default_decisions->update_matched && '' ==  $staging_record->INSERTED_NEW ) )// update_matched is false && this record not new
				  { 
				  continue; // go to next staging file record without doing an update ( and without incrementing valid record counter at bottom of loop )
			}
			// now go through array of arrays, and do updates
			foreach ( $data_object_array_array as $entity => $data_object_array ) {
				if ( 'constituent' == $entity ) {
					// don't touch the basic constituent record if protecting identity data (setting supports soft identity matching) 
					if ( $default_decisions->protect_identity ) {
						continue;					
					}
					// if constituent and just added it, don't reupdate the top entity record
					// also, if only have ID control, nothing to update on the top entity record
					// assuming not either of those go ahead and update (even without, will count as valid record processed) 
					if ( 'y' != $staging_record->INSERTED_NEW &&  1 < count ( $data_object_array ) ) { 
						$data_object_array['ID']->set_value ( $staging_record->MATCHED_CONSTITUENT_ID );
						// do the update, passing the blank_overwrite choice
						$wic_access_object_array[$entity]->upload_save_update( $data_object_array, $default_decisions->protect_blank_overwrite );
					} 
				} elseif ( 'issue' == $entity ) {
					// if have post_title through mapping or default and have assured that all post_titles exist on database				
					if ( ! $use_title ) {
						continue; // continue to next entity					
					} else { 	
						$query_array =	$data_object_array['post_title']->create_search_clause ( $search_clause_args );
						// execute a search
						$wic_access_object_array[$entity]->search ( $query_array, $search_parameters );
						// if matches found, take the first for update purposes 
						if ( $wic_access_object_array[$entity]->found_count > 0 ) {
							$data_object_array_array['activity']['issue']->set_value( $wic_access_object_array[$entity]->result[0]->ID );					
						} else {
							// this could happen if user was quick enough to get through good-to-go status on the set default form and OK leave form
							// while new issue table creation was in progress and table were only partially populated -- not likely
							WIC_Function_Utilities::wic_error ( 
								sprintf ( 'Data base corrupted for post title: %1$s in update constituent phase.' , $data_object_array['post_title']->get_value() ), 
								__FILE__, __LINE__, __METHOD__, true );
						} 					
					}
				} else { // so not constituent and not issue, in other words is any of the multivalue entities
					if ( 3 > count ( $data_object_array ) ) { // multivals always have ID and constituent ID, if that's all, nothing to update
						continue; // continue to next entity				
					} 	
					// set current constituent id for the entity
					$data_object_array['constituent_id']->set_value ( $staging_record->MATCHED_CONSTITUENT_ID );
					// prepare a query array for those fields used in upload match/dedup checking for multi-value fields 
					$query_array = array();
					foreach ( $data_object_array as $field_slug => $control ) { 
						if ( $control->is_upload_dedup() ) {
							$query_array = array_merge ( $query_array, $control->create_search_clause ( $search_clause_args ) );
						}
					} 
					// execute a search for the multivalue entity -- treating it as a top level entity, but query object is OK with that
					$wic_access_object_array[$entity]->search ( $query_array, $search_parameters );
					// if matches found, take the first for update purposes 
					if ( $wic_access_object_array[$entity]->found_count > 0 ) {
						$id_to_update = $wic_access_object_array[$entity]->result[0]->ID;
						// don't touch the found address record if protecting identity data (setting supports soft identity matching)
						if ( $default_decisions->protect_identity && 'address' == $entity ) {
							continue;					
						}
					// but if don't have the address for this type, proceed regardless of protect_identity setting 					
					} else {
						$id_to_update = 0;
					} 					
					$data_object_array['ID']->set_value ( $id_to_update );

					// now, either update found record ( email,phone, address or activity ) or save new one
					// pass user decision as to whether blanks should be overwritten (only matters on update)
					$result = $wic_access_object_array[$entity]->upload_save_update ( $data_object_array, $default_decisions->protect_blank_overwrite ) ;


				} 
			} // close loop for entities

			// increment counters -- note that, at top of loop, continuing past invalid records ( by testing for match field )		
			if ( 'y' != $staging_record->INSERTED_NEW ) { // non-new updates 
				$constituent_updates_applied++;
			}
			$total_valid_records_processed++;
		} // close for loop for staging table

		// save tallies		
		$final_results->constituent_updates_applied += $constituent_updates_applied;		
		$final_results->total_valid_records_processed += $total_valid_records_processed;
		$final_results = json_encode ( $final_results );
		self::update_final_results( $upload_id, $final_results );
		return ( $final_results ) ;		

	} // close  update constituents	

	public static function backout_new_constituents( $upload_id, $staging_table) {
		
		global $wpdb;
		$wic_prefix = $wpdb->prefix . 'wic_';
		$entity_array = array ( 'constituent', 'activity', 'phone', 'email', 'address' );

		$return_result = true;
		foreach ( $entity_array as $entity ) {
			$id = ( 'constituent' == $entity ) ? 'ID' : 'constituent_id';	
			$table = $wic_prefix . $entity;		
			$sql = "DELETE d FROM $table d INNER JOIN $staging_table s ON s.MATCHED_CONSTITUENT_ID = d.$id WHERE 'y' = s.INSERTED_NEW ";
			$result = $wpdb->query ( $sql );
			if ( false === $result ) {
				$return_result = false;			
			}
		}
		
		$return = $return_result ? __( 'Backout of added constituents successful.', 'WP_Issues_CRM' ) : false;

		// on successful completion, set final results for new constituents saved to zero, update upload status		
		if ( false !== $return_result ) {
			$final_results = json_decode ( self::get_final_results ( $upload_id ) );
			$final_results->new_constituents_saved = 0;
			$final_results = json_encode ( $final_results );	
			self::update_final_results( $upload_id, $final_results );					
			self::update_upload_status ( $upload_id, 'reversed' );
		}

		return ( $return_result );
	}

}


