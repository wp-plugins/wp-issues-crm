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
		
		// prepare upload parameters as straight key value_array
		foreach ( $doa as $field => $control ) {
			if ( $control->is_transient() ) {
				$update_parameter_array[$field] = $control->get_value();
			}
		}			

		// add a last entry to the save_update_array which will need to be popped off in db_save and db_update
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


		// create a table with the appropriate number of columns -- get column names from first row if available
		$sql = "CREATE TABLE $table_name ( "; 
		$i = 1;
		$column_names = array();
		foreach ( $columns as $column ) {
			$column_name = ( in_array ( $this->sanitize_column_name ( $column ), $column_names ) 
							 || '' == $this->sanitize_column_name ( $column ) 
							 || 0 == $includes_column_headers ) 
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
 					'PRIMARY KEY (STAGING_TABLE_ID) ) 
					ENGINE=MyISAM  DEFAULT CHARSET=utf8;';

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
			if ( isset ( $lookup [0] ) ) { 
				$found = new WIC_DB_Upload_Column_Object ( $lookup[0]->matched_entity, $lookup[0]->matched_field );
			}
			$column_map[$column] =$found;			
		}
		$this->serialized_column_map = json_encode ( $column_map );	
		
		$this->upload_status = WIC_Entity_Upload::are_any_columns_mapped ( $column_map ) ? 'mapped' : 'staged';
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
		// if offset is zero do a rest; then maintain validation indicator
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
	
   public static function create_unique_unmatched_table ( $upload_id, $staging_table ) {		
		// create parallel lists of constituent fields and related fields in the upload -- working directly from column map
		$column_map = json_decode ( WIC_DB_Access_Upload::get_column_map ( $upload_id ) );
		$constituent_field_array = array();
		$staging_table_column_array = array();		
		foreach ( $column_map as $column => $entity_field_object ) {
			if ( '' < $entity_field_object ) { // unmapped columns have an empty entity_field_object
				if ( 'constituent' == $entity_field_object->entity  ) {
					$constituent_field_array[] = $entity_field_object->field;
					$staging_table_column_array[] = $column;	
				}
			}		
		}		
		
		$unmatched_staging_table = $staging_table . '_unmatched'; 

		global $wpdb;
		// create a table with the the available constituent columns
		$sql = "CREATE TABLE $unmatched_staging_table ( "; 
		foreach ( $constituent_field_array as $field ) {
			$sql .= ' ' . $field . ' varchar(65535) NOT NULL, ';
		}

		$sql .=  'FIRST_NOT_FOUND_MATCH_PASS varchar(50) NOT NULL,
					INSERTED_CONSTITUENT_ID bigint(20) unsigned NOT NULL,
					STAGING_TABLE_ID_STRING varchar(65535) NOT NULL,
					KEY MATCH_PASS (FIRST_NOT_FOUND_MATCH_PASS) ) 
					ENGINE=MyISAM  DEFAULT CHARSET=utf8;';

		$result = $wpdb->query ( $sql );
		if ( false === $result ) {
			return ( false );		
		}

		// populate that table with unique unfound values		
		$select_column_list = '';
		foreach ( $staging_table_column_array as $column ) {
			$select_column_list .= ' MAX(' . $column . '), ';		
		}
		$sql = 	"INSERT $unmatched_staging_table
					SELECT $select_column_list FIRST_NOT_FOUND_MATCH_PASS, 0, GROUP_CONCAT( STAGING_TABLE_ID )
					FROM $staging_table 
					WHERE MATCH_PASS = '' AND FIRST_NOT_FOUND_MATCH_PASS > '' AND VALIDATION_STATUS = 'y'
					GROUP BY FIRST_NOT_FOUND_MATCH_PASS, NOT_FOUND_VALUES";
		// MATCH_PASS = '' means never matched.  Might have FIRST_NOT_FOUND_MATCH_PASS = '' if lacked fields for all passes
		$insert_count = $wpdb->query ( $sql );
		if ( false === $insert_count ) {
			return ( false );		
		} 
		
		// calculate unique counts from each match pass
		$sql = "SELECT FIRST_NOT_FOUND_MATCH_PASS as pass_slug, COUNT(FIRST_NOT_FOUND_MATCH_PASS) AS pass_count
				FROM $unmatched_staging_table 
				GROUP BY FIRST_NOT_FOUND_MATCH_PASS";
		$result_array = $wpdb->get_results ( $sql );
		if ( false === $result_array ) {
			return ( false );		
		} 


		// update match result array
		$match_results = json_decode ( self::get_match_results( $upload_id ) );

		// set all unmatched counters to 0 -- initialized as empty up to this point (next step may not hit them all)
		foreach ( $match_results as $rule ) {
			$rule->unmatched_unique_values_of_components = 0;			
		}	

		if ( 0 != count ( $result_array ) ) { 
			foreach ( $result_array as $result ) { 
				$match_results->{$result->pass_slug}->unmatched_unique_values_of_components = $result->pass_count;		
			}
		}
		
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

	
}


