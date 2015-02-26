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
		* many users will run into memory and packet size issues with larger packets in
		* long insert statements -- rather than force naive users to change these parameters, keep likely 
		* packet size well below 1 m // http://dev.mysql.com/doc/refman/5.5/en/packet-too-large.html
		* test performance 
		* lost atomicity *
		// now prepare INSERT SQL stub to which will be added the values repetitively in loop belo
		$sql_stub = "INSERT INTO $table_name ( COLUMN_0 " ;
		// add columns after column 0 with commas preceding
		for ( $i = 1; $i < $count_columns; $i++ ) {
			$sql_stub .= ', COLUMN_' . $i ;		
		}
		$sql_stub .= ') VALUES '; 	
		
		// need to start over since went to get count record		
		rewind ( $handle );	
		// surprisingly, singleton packets perform better -- on repetitive trial with 13.6 mb file, 
		// singleton packets were at 43 seconds, while 10000 packets was at 59 seconds
		// multiple factors here, possibly the prepare processing slower on long records  . . . ?
		// http://dev.mysql.com/doc/refman/5.0/en/load-data.html
		// http://dev.mysql.com/doc/refman/5.0/en/insert-speed.html 
		$rows_per_packet = 10000;
		echo "$rows_per_packet = rows per packet";
		echo 'before outer while' . time() . '</br>';			
		// test for not eof and restart loop
		while ( ! feof ( $handle ) ) {
			
			$sql = $sql_stub;
			$j = 0;
			$values = array();
	      while ( ( $data = fgetcsv($handle, 10000, ",") ) !== FALSE && $j < $rows_per_packet ) {	
				$row = "( %s ";
				$values[] = sanitize_text_field ( $data[0] );
				$i = 0; 
				foreach ( $data as $column ) {	
					if ( $i > 0 ) {
						$row .= ", %s";
						$values[] = $column; //  sanitize_text_field ( $column );		
					} 
				$i++;  // only counter purpose was to skip first column, since added at start of row (handling punctuation;
				}
				$row .= " ),";
				$sql .= $row; 
				$j++;
			}
		 
			// drop the final comma
			$sql = substr ( $sql, 0, -1 );
		
			$sql = $wpdb->prepare ( $sql, $values );
	
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

		} // end not eof loop*/
		echo 'xxxxbefore  db' . time() . '</br>';
		$file_name =  $_FILES['upload_file']['tmp_name'];
	
		$sql = "LOAD DATA INFILE \"$file_name\"
				 INTO TABLE $table_name" ;		

		$result = $wpdb->query ( $sql );
		echo 'xxxxxxxxafter  db' . time() . '</br>';				

		/* $handle = fopen($file_name, 'rb' );
		// abort if can't open the file
		if ( ! $handle ) {
			$this->outcome = false;
			$this->explanation =  __( 'retest Error opening uploaded file.', 'wp-issues-crm' );		
			return;
		}
	  	   $data = fgetcsv( $handle, 10000, ',' ); 
		var_dump ( $data ); */
		// note success -- if any failed, quit		
		$this->outcome == true;

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


