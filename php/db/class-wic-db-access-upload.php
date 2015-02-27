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

	protected function db_save ( &$save_update_array ) {
		
		// first attempt to process the validated file into a staging table in the database -- if not successful, bail with database error
		$handle = fopen( $_FILES['upload_file']['tmp_name'], 'rb' );
		// abort if can't open the file
		if ( ! $handle ) {
			$this->outcome = false;
			$this->explanation =  __( 'Error opening uploaded file.', 'wp-issues-crm' );		
			return;
		}
	
		// have already validated as a csv in WIC_Control_File
  	   $data = fgetcsv( $handle, 10000, ',' ); 
  	   // need to reget the column count
      $count_columns = count ( $data );
		
		// access wordpress database object
		global $wpdb;

		// set up new table name
		$this->upload_time = $this->get_mysql_time();
		$this->upload_by = get_current_user_id();
		$table_name = $wpdb->prefix . 'wic_staging_table_' . 
				str_replace ( '-', '_', str_replace ( ' ', '_', str_replace ( ':', '_', 
					$this->upload_time ) ) ) . 
					'_' . get_current_user_id();


		// create a table with the appropriate number of columns
		$sql = "CREATE TABLE $table_name ( "; 
		for ( $i = 0; $i < $count_columns; $i++ ) {
			$sql .= ' COLUMN_' . $i . ' varchar(255) NOT NULL, ';		
		}
		$sql .= ' ID bigint(20) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (ID) ) 
					ENGINE=MyISAM  DEFAULT CHARSET=utf8;';

		$result = $wpdb->query ( $sql );

		if ( false === $result ) {
			$this->outcome = false;
			$this->explanation =  __( 'Error creating staging table.', 'wp-issues-crm' );
			return;
		}
	
		/***********************************************************************************
		*
		*	Now attempt to use load data infile direct upload
		*   -- first attempt as LOCAL upload -- this will work if settings allow it
		*  Whether this will work can depend on many factors, so can't rely on it for all users
		*   -- http://stackoverflow.com/questions/10762239/mysql-enable-load-data-local-infile
		*   -- http://dev.mysql.com/doc/refman/5.1/en/load-data.html
		*   -- http://dev.mysql.com/doc/refman/5.0/en/load-data-local.html
		*	 -- http://ubuntuforums.org/showthread.php?t=822084
		*   -- http://stackoverflow.com/questions/3971541/what-file-and-directory-permissions-are-required-for-mysql-load-data-infile
		*   -- http://stackoverflow.com/questions/4215231/load-data-infile-error-code-13 (apparmor issues)
		*	 -- https://help.ubuntu.com/lts/serverguide/mysql.html (configuration)
		*
		***********************************************************************************/
		$file_name =  $_FILES['upload_file']['tmp_name'];
		$sql = "LOAD DATA LOCAL INFILE \"$file_name\"
				 INTO TABLE $table_name" ;		
		$result = $wpdb->query ( $sql );
		var_dump ( $wpdb->last_error );
		/***********************************************************************************
		*
		*  Now load data infile attempt without local parameter 
		*		-- in some installations, this may work since file to be loaded is in universal tmp directory
		*
		************************************************************************************/
		if ( ! $result ) {
			$sql = "LOAD DATA INFILE \"$file_name\"
				 INTO TABLE $table_name" ;		
			$result = $wpdb->query ( $sql );
			var_dump ( $wpdb->last_error );
		}				
		/***********************************************************************************
		*
		*  If neither version of load data infile worked, proceed to the much slower, 
		*	   but less installation-sensitive, straight Wordpress approch 
		*
		*	note: many users will run into memory and packet size issues with larger packets in
		* 	long insert statements -- rather than force naive users to change these parameters, keep likely 
		* 	packet size well below 1 m // http://dev.mysql.com/doc/refman/5.5/en/packet-too-large.html
		*  
		************************************************************************************/
		if ( ! $result ) {				
			// now prepare INSERT SQL stub to which will be added the values repetitively in loop below
			$sql_stub = "INSERT INTO $table_name ( COLUMN_0 " ;
			// add columns after column 0 with commas preceding
			for ( $i = 1; $i < $count_columns; $i++ ) {
				$sql_stub .= ', COLUMN_' . $i ;		
			}
			$sql_stub .= ') VALUES '; 	
			
			// need to start over since went to get count record		
			rewind ( $handle );	

			/****************
			*
			*	note: dominant consideration in setting rows_per_packet below is to avoid user blowing up
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
			*  memory_limit can also be set dynamically and limits may run higher on many servers, but not payoff if can't also raise packet size
			*  and limits to accomodate a file of 25,000 reasonably long records (600b) go close to 256K, 100,000 records could need 1G, poss not avail.
			*  only reason not to break into smaller packets is to avoid partial saves -- user can see this result
			*  SO: APPROACH THIS CONSERVATIVELY 
			*   	-- set rows per packet at 800000 / max row length -- this will keep packet size under 1M and memory requirements modest
			*  	-- set max execution time at 1500 if not already higher than that -- should cover a million records at under 1000/second
			*
			**************/ 
			$rows_per_packet = 25000;
			$k = 0;
			//set_time_limit ( 15000 );
			//ini_set ( 'memory_limit', '5M' ); 
			echo 'memory limit, nothing set: ';var_dump ( ini_get ('memory_limit' )) ; 
			$result= $wpdb->query ( "SET GLOBAL MAX_ALLOWED_PACKET = 5000000" );
			echo "<br/><br/>$rows_per_packet = rows per packet";
			echo 'before outer while' . time() . '</br>';	
					
			// test for not eof and restart loop; would like to do transaction processing, but not supported by myisam
			// http://stackoverflow.com/questions/19153986/how-to-use-mysql-transaction-in-wordpress
			while ( ! feof ( $handle ) ) {

				$sql = $sql_stub;
				$j = 0;
				$values = array();
		      while ( ( $data = fgetcsv( $handle, 10000, ",") ) !== false )  {	
					$row = '( %s';
					$values[] = $data[0];
					$i = 0; 
					foreach ( $data as $column ) {	
						if ( $i > 0 ) {
							$row .= ',%s' ;
							$values[] = $column; 
						} 
						$i++;  // only counter purpose was to skip first column, since added at start of row (handling punctuation);
					}
					$row .= "),";
					$sql .= $row; 
					$j++;
					$k++;
					if ( $j == $rows_per_packet ) {
						break;					
					}
				}

				// drop the final comma
				$sql = substr ( $sql, 0, -1 );
			
				$sql = $wpdb->prepare ( $sql, $values );
				echo "length of prepared sql strign = "; var_dump (strlen($sql)); 
				// avoid database error if happen to have on row count that is multiple of $rows_per_packet
				if ( count ( $values ) > 0 ) {
					$result = $wpdb->query ( $sql );
				}
				// exit on failure
				if ( false === $result ) {
					$this->outcome = false;
					$this->explanation =  __( 'Error loading staging table.', 'wp-issues-crm' );
					return;
				}
	
			} // end not eof loop

			echo 'xxxxafter outer while' . time() . '</br>'; 
			echo 'false peak ussage: ';	
			var_dump (memory_get_peak_usage ( false )); 
			echo '<br/> true peak usage: '; var_dump(memory_get_peak_usage( true )); 
						echo '<br/> crurrent  usage: '; var_dump(memory_get_usage( true )); 
			echo "k is set as $k";
		echo 'memory limit, after all: ';var_dump ( ini_get ('memory_limit' )) ; 
			$this->outcome == true;
		}
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
		parent::db_save( $save_update_array );
		
	}
	
}


