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
	
		
		// now prepare INSERT SQL
		$sql = "INSERT INTO $table_name ( COLUMN_0 " ;
		// add columns after column 0 with commas preceding
		for ( $i = 1; $i < $count_columns; $i++ ) {
			$sql .= ', COLUMN_' . $i ;		
		}
		$sql .= ') VALUES '; 
		
		// reset file pointer to the beginning
		rewind ( $handle );

      while ( ($data = fgetcsv($handle, 10000, ",")) !== FALSE) {	
			$row = '( ' . '\'' . $data[0] . '\'';
			$i = 0; 
			foreach ( $data as $column ) {	
				if ( $i > 0 ) {
					$row .= ', ' . '\'' . $column . '\'';	
				} 
			$i++;  // only counter purpose was to skip first column, since added at start of row (handling punctuation;
			}
			$row .= ' ),';
			$sql .= $row;

		}
		
		// drop the final comma
		$sql = substr ( $sql, 0, -1 );
	
		$result = $wpdb->query ( $sql );
		
		if ( false === $result ) {
			$this->outcome = false;
			$this->explanation =  __( 'Error loading staging table.', 'wp-issues-crm' );
			return;
		} else {
			// note success		
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
	
}


