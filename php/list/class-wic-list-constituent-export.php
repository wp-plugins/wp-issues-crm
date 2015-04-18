<?php
/*
* class-wic-list-constituent-export.php
*
* 
*/ 

class WIC_List_Constituent_Export {


	/*
	*	Runs off of temporary table created early in transaction
	*  Write directly to temp file in full permissions tmp subdirectory
	*	The full permissions tmp subdirectory operates to make the temp file unlinkable by php although owned by mysql 
	*/
	public static function assemble_list_for_export () { 
		
		// reference global wp query object	
		global $wpdb;	
		$prefix = $wpdb->prefix . 'wic_';
		
		$constituent 	= $prefix . 'constituent';
		$email 			= $prefix . 'email';
		$phone			= $prefix . 'phone';
		$address			= $prefix . 'address';
		
		// id list passed through user's temporary file wp_wic_temporary_id_list, lasts only through the server transaction (multiple db transactions)
		$temp_table = $wpdb->prefix . 'wic_temporary_id_list';

		// naming the outfile
		$current_user = wp_get_current_user();	
		$file_name = 'wic-export-' . $current_user->user_firstname . '-' .  current_time( 'Y-m-d-H-i-s' )  .  '.csv' ;
		$temp_dir = self::check_download_temp_directory();		
		$temp_file = $temp_dir . DIRECTORY_SEPARATOR . $file_name;

		// column headers for the output -- not available directly in outfile mode, so:
		$column_list = self::get_column_list ( $constituent );		
			
		// now set up select with the column headers fully specified
		$sql = "SELECT 'first_name', 'last_name', 
			'email_address', 
			'city', 
			'phone_number', 
			'address_line_1' ,
			'address_line_2', 
			'zip', " . 
			$column_list . 
			" UNION ALL ";
	
   	// go direct to database and do customized search
		$sql .= 	"SELECT  first_name, last_name,  
						max( email_address ), 
						max( city ), 
						max( phone_number ),
						max( address_line ) as address_line_1,
						max( concat ( city, ', ', state, ' ',  zip ) ) as address_line_2,
						max( zip ), 
						c.* 
					FROM $temp_table i INNER JOIN $constituent c on c.ID = i.ID
					left join $email e on e.constituent_id = c.ID
					left join $phone p on p.constituent_id = c.ID
					left join $address a on a.constituent_id = c.ID	
					WHERE ( address_type = '0' or address_type is null )
					GROUP BY c.ID
					INTO OUTFILE '$temp_file'
					FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY '\\\\'
  					LINES TERMINATED BY '\n'					
					"; 

		$results = $wpdb->get_results ( $sql, ARRAY_A ); 
		
		return ( $file_name );
	}


	public static function do_constituent_download ( $id ) {
		
		if ( true === self::do_download_security_checks() )  { 
					
			// retrieves only the meta array, not the search parameters, since will supply own
			$search = WIC_DB_Access::get_search_from_search_log( $id );	
	
			$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( $search['entity'] );
			
			$search_parameters = array (		
				'select_mode' 		=> 'download', // with this setting, object will create the temp table that export assembler is looking for
				'sort_order' 		=> true,
				'compute_total' 	=> false,
				'retrieve_limit' 	=> 999999999,
				'show_deleted'		=> false,
				'log_search'		=> false,
			);
	
			$wic_query->search ( $search['meta_query_array'], $search_parameters );
	
			// one step if constituent export
			if ( 'constituent' == $search['entity'] ) { 			
				$file_name = self::assemble_list_for_export(); // runs off temp table
			// two steps if getting constituents from issue list
			} elseif ( 'issue' ==  $search['entity'] ) {  
				$issue_array = array();
				foreach ( $wic_query->result as $issue ) {
					$issue_array[] = $issue->ID;
				}
				$args = array (			
					'id_array' => $issue_array,
					'search_id' => $id,
					'retrieve_mode' => 'download',
					);
				$comment_query = new WIC_Entity_Comment ( 'get_constituents_by_issue_id', $args );
				$file_name = self::assemble_list_for_export(); // values passed through $temp_table from comment query
			} 
			
			self::do_the_export( $file_name );
	
			WIC_DB_Access::mark_search_as_downloaded( $id );
			
			die;
		}
	}


	// apply a category screen to the constituents from the last trend search
	public static function do_constituent_category_download( $search_id_and_contributors ) { // search id and category_contributors is comma separated string of term id's
		if ( true === self::do_download_security_checks() )  { 
			$user_id = get_current_user_id();
			// set up a trend data access object 
			$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( 'trend' );
			// pull the search ID out of the input string
			$input_array = explode ( ',', $search_id_and_contributors);
			$search_id = $input_array[0];
			unset ( $input_array[0] );
			// prepare string of category term_id's for sql use
			$category_contributors = implode ( ',',  $input_array );
			// get the parameters of the current trend search
			$search =  WIC_DB_Access::get_search_from_search_log( $search_id );
			// initiate a query with those activity search parameters and issue category as an additional criterion
			$wic_query->search_activities_with_category_slice( $search['meta_query_array'], $category_contributors );
			// pass the retrieved constituent ID's to assembly function for details	
			$file_name = self::assemble_list_for_export ( ); 
			// send the file
			self::do_the_export ( $file_name );
			// mark the whole search as downloaded (no mechanism to mark slice)	
			WIC_DB_Access::mark_search_as_downloaded( $search_id  );
			// done -- no log marking
			exit;
		}
	}

	public static function do_staging_table_download ( $parameters ) {
		if ( true === self::do_download_security_checks() )  { 
			$parameters_array 	= explode ( ',' , $parameters );
			$staging_table_name 	= $parameters_array[0];
			$file_requested 		= $parameters_array[1];
	
			global $wpdb;		
			
			// naming the outfile
			$file_name = 'wic-staging-' . $file_requested . '-' .  $staging_table_name  .  '.csv' ;
			$temp_dir = self::check_download_temp_directory();		
			$temp_file = $temp_dir . DIRECTORY_SEPARATOR . $file_name;
			
			$column_list = self::get_column_list ( $staging_table_name );
			$sql = "SELECT $column_list UNION ALL ";		
			if ( 'validate' == $file_requested ) {
				$sql .= "
					SELECT * FROM $staging_table_name
					WHERE VALIDATION_STATUS = 'N'
					";
			} else if ( 'new_constituents' == $file_requested ) {
				$sql .= "
					SELECT * FROM $staging_table_name
					WHERE MATCH_PASS = '' AND FIRST_NOT_FOUND_MATCH_PASS > '' 
					";
			} else if ( 'match' == $file_requested ) {
				$sql .= "
					SELECT * FROM $staging_table_name
					WHERE MATCH_PASS > '' 
					";
			} else if ( 'bad_match' == $file_requested ) {				
				$sql .= "
					SELECT * FROM $staging_table_name
					WHERE VALIDATION_STATUS = 'N' AND MATCH_PASS = '' AND FIRST_NOT_FOUND_MATCH_PASS = '';					
					";
			} else if ( 'dump' == $file_requested ) {
				$sql .= "
					SELECT * FROM $staging_table_name
					";
			}	
	
			$sql .= 
				" 
				INTO OUTFILE '$temp_file'
				FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY '\\\\'
	  			LINES TERMINATED BY '\n'					
				";
			
			$wpdb->query ( $sql );

			self::do_the_export( $file_name );	
			
			exit;
		}
		

	}



	private static function do_download_security_checks() {

		$wic_plugin_options = get_option( 'wp_issues_crm_plugin_options_array' ); 
		// if not set, limit access to administrators
		$main_security_setting = isset( $wic_plugin_options['access_level_required_downloads'] ) ? $wic_plugin_options['access_level_required_downloads'] : 'activate_plugins';
		if ( ! current_user_can ( $main_security_setting ) ) {
			echo '<h3>' . __( 'Sorry, you do not have permission to download data.  Please consult your administrator', 'wp-issues-crm' ) . '</h3>';
			return ( false );		 
		} 	
		if ( isset($_POST['wp_issues_crm_post_form_nonce_field']) &&
			check_admin_referer( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field') ) {
			return true;
		 } else { 
			WIC_Function_Utilities::wic_error ( 'Apparent cross-site xscripting or configuration error.', __FILE__, __LINE__, __METHOD__, true );
		}
	}

	public static function do_the_export ( $file_name ) {

		$temp_file =  sys_get_temp_dir() . DIRECTORY_SEPARATOR  . 'wp_wic_temp_files'  . DIRECTORY_SEPARATOR . $file_name; 
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename={$file_name}");
		header("Expires: 0");
		header("Pragma: public");

		copy ( $temp_file, 'php://output' );
		// able to unlink even though owner is mysql since stored in full permissions temp subdirectory owned by user
		unlink ( $temp_file );
		
	}

	public static function check_download_temp_directory() {

		$temp_dir =  sys_get_temp_dir() . DIRECTORY_SEPARATOR  . 'wp_wic_temp_files'; 

		// make sure that full permission temp directory exists and if not, create it.
		if ( ! file_exists (  $temp_dir ) ) {
			mkdir ( $temp_dir ); 		
		} 
		// default permission is 0777,but umask is likely 0022, yielding permissions of 0755, not adequate
		// so do chmod which is not limited by umask
		chmod ( $temp_dir, 0777 );	
		
		return ( $temp_dir );
				
	}

	public static function get_column_list ( $table ) {
		global $wpdb;		
		$sql = "SHOW COLUMNS IN $table";
		$column_list_lookup = $wpdb->get_results ( $sql );
		if ( false === $column_list_lookup ) {
			WIC_Function_Utilities::wic_error ( 'Error accessing column list for download table -- probable database or configuration error.', __FILE__, __LINE__, __METHOD__, true );		
		} else {
			$column_list_array = array();
			foreach ( $column_list_lookup as $column ) {
				$column_list_array[] = $column->Field;
			}
			$column_list = "'" . implode( "','", $column_list_array ) . "'";		
		}	
		return ( $column_list );
	}

}	

