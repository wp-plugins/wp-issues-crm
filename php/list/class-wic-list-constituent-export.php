<?php
/*
* class-wic-list-constituent-export.php
*
* 
*/ 

class WIC_List_Constituent_Export {

	public static function do_constituent_download ( $download_type, $search_id ) { 
		
		// if user changed download type back to empty (initial button value), do nothing.
		// barred by js, but bullet proofing
		if ( '' == $download_type ) {
			return;		
		}		
		
		if ( true === self::do_download_security_checks() )  { 

			// naming the outfile
			$current_user = wp_get_current_user();	
			$file_name = 'wic-constituent-export-' . $current_user->user_firstname . '-' .  current_time( 'Y-m-d-H-i-s' )  .  '.csv' ;
					
			// retrieves only the meta array, not the search parameters, since will supply own
			$search = WIC_DB_Access::get_search_from_search_log( $search_id );	
			// can be issue or constituent	
			$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( $search['entity'] );
			
			$search_parameters = array (		
				'select_mode' 		=> 'download', // with this setting, object will create the temp table that export sql assembler is looking for
				'sort_order' 		=> true,
				'compute_total' 	=> false,
				'retrieve_limit' 	=> 999999999,
				'show_deleted'		=> false,
				'log_search'		=> false,
			);
			
			// do the search, saving retrieved id list in temp table if constituent, setting up next step if issue	
			$wic_query->search ( $search['meta_query_array'], $search_parameters );
	
			// additional step if getting constituents from issue list
			// select_mode download has no effect on issues query, need to get constituents by issue id
			if ( 'issue' ==  $search['entity'] ) {  
				$issue_array = array();
				foreach ( $wic_query->result as $issue ) {
					$issue_array[] = $issue->ID;
				}
				$args = array (			
					'id_array' => $issue_array,
					'search_id' => $search_id,
					'retrieve_mode' => 'download',
					);
				$comment_query = new WIC_Entity_Comment ( 'get_constituents_by_issue_id', $args );
			} 
			
			$sql = self::assemble_constituent_export_sql( $download_type ); // runs off temp table

			self::do_the_export( $file_name, $sql );
	
			WIC_DB_Access::mark_search_as_downloaded( $search_id );
			
			exit;
		}
	}


	// apply a category screen to the constituents from the last trend search
	public static function do_constituent_category_download( $search_id_and_contributors ) { // search id and category_contributors is comma separated string of term id's

		if ( true === self::do_download_security_checks() )  { 

			// naming the outfile
			$current_user = wp_get_current_user();	
			$file_name = 'wic-category-export-' . $current_user->user_firstname . '-' .  current_time( 'Y-m-d-H-i-s' )  .  '.csv' ;

			// set up a trend data access object 
			$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( 'trend' );
			// pull the search ID out of the input string
			$input_array = explode ( ',', $search_id_and_contributors);
			$search_id = $input_array[0];
			// prepare string of category term_id's for sql use
			unset ( $input_array[0] ); // first remove the search id from the input array
			$category_contributors = implode ( ',',  $input_array );
			// get the parameters of the current trend search
			$search =  WIC_DB_Access::get_search_from_search_log( $search_id );
			// initiate a query with those activity search parameters and issue category as an additional criterion
			$wic_query->search_activities_with_category_slice( $search['meta_query_array'], $category_contributors );
			// query leaves results in a temp table picked up $sql 
			$sql = self::assemble_constituent_export_sql( 'type0' ); 
			// send the file
			self::do_the_export ( $file_name, $sql  );
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
			
			// naming the download file
			$file_name = $staging_table_name  . '_' . $file_requested . '.csv' ;
			$sql = "SELECT * FROM $staging_table_name ";
			// set up the sql			
			if ( 'validate' == $file_requested ) {
				// validation errors; can export after validation
				$sql .= " WHERE VALIDATION_STATUS = 'n' "; 										
			} else if ( 'new_constituents' == $file_requested ) {
				// not matched, but have matching fields; can export after define matching 
				$sql .= " WHERE MATCH_PASS = '' AND FIRST_NOT_FOUND_MATCH_PASS > '' "; 	
			} else if ( 'match' == $file_requested ) {
				// actually matched; can export after define matching
				$sql .= " WHERE MATCH_PASS > '' AND MATCHED_CONSTITUENT_ID > 0 "; 		
			} else if ( 'bad_match' == $file_requested ) {				
				// EITHER found in a match pass, but not matched because 2 or more found OR valid, but no match attempted  -- can export after define matching
				$sql .= " WHERE ( MATCH_PASS > '' AND MATCHED_CONSTITUENT_ID = 0 ) "; 	// 
				$sql .= " OR ( VALIDATION_STATUS = 'y' AND MATCH_PASS = '' AND FIRST_NOT_FOUND_MATCH_PASS = '' )"; 
			} else if ( 'unmatched' == $file_requested ) {
				// not linked -- meaningful only after completion										
				$sql .= " WHERE MATCHED_CONSTITUENT_ID = 0 ";
			}	
			// 'dump' has no where clause
			self::do_the_export( $file_name, $sql );	
			
			exit;
		}

	}


	/*
	*	sql points to temporary table created early in transaction
	*/
	public static function assemble_constituent_export_sql ( $download_type ) { 
		
		// reference global wp query object	
		global $wpdb;	
		$prefix = $wpdb->prefix . 'wic_';
		
		$constituent 	= $prefix . 'constituent';
		$email 			= $prefix . 'email';
		$phone			= $prefix . 'phone';
		$address			= $prefix . 'address';
		
		// id list passed through user's temporary file wp_wic_temporary_id_list, lasts only through the server transaction (multiple db transactions)
		$temp_table = $wpdb->prefix . 'wic_temporary_id_list';
		
		// pass constituent list through repeated chunks to db as temp table
		$temp_constituent_table = $wpdb->prefix . 'wic_temporary_constituent_list' . time();		

		global $wic_db_dictionary;
		$custom_fields = $wic_db_dictionary->custom_fields_match_array ();
		$custom_fields_string = '';
		if ( count ( $custom_fields ) > 0 ) {
			foreach ( $custom_fields as $field => $match_array ) {
				$custom_fields_string .= ', ' . $field . ' as `' . $match_array['label'] . ' -- ' . $field . '` ';			
			}
	 		
		}


		// initialize download sql -- if remains blank, will bypass download
		$download_sql = '';
		switch ( $download_type ) {
			case 'emails':		
				$download_sql =" 
					SELECT  first_name as fn, last_name as ln,  
						email_type, email_address, 
						max( city ) as city $custom_fields_string , i.*
					FROM $temp_table i INNER JOIN $constituent c on c.ID = i.ID
					inner join $email e on e.constituent_id = c.ID
					left join $address a on a.constituent_id = c.ID	
					GROUP BY c.ID, e.ID
					"; 
				break;
			case 'phones':		
				$download_sql =" 
					SELECT  first_name as fn, last_name as ln,  
						phone_type, phone_number, extension, 
						max( city ) as city $custom_fields_string , i.*
					FROM $temp_table i INNER JOIN $constituent c on c.ID = i.ID
					inner join $phone p on p.constituent_id = c.ID
					left join $address a on a.constituent_id = c.ID	
					GROUP BY c.ID, p.ID
					"; 		
				break;
			case 'addresses':		
				$download_sql =" 
					SELECT  first_name as fn, last_name as ln,  
						address_type, 
						address_line as address_line_1,
						concat ( city, ', ', state, ' ',  zip ) as address_line_2,
						city, state, zip $custom_fields_string , i.*
					FROM $temp_table i INNER JOIN $constituent c on c.ID = i.ID
					inner join $address a on a.constituent_id = c.ID	
					GROUP BY c.ID, a.ID
					"; 		
				break;		
			case 'type0':
				$download_sql = " 		
					SELECT  first_name as fn, last_name as ln,  
						city, 
						email_address, 
						phone_number,
						address_line as address_line_1,
						concat ( city, ', ', state, ' ',  zip ) as address_line_2,
						state, zip $custom_fields_string , i.*
					FROM $temp_table i INNER JOIN $constituent c on c.ID = i.ID
					left join $email e on e.constituent_id = c.ID
					left join $phone p on p.constituent_id = c.ID
					left join $address a on a.constituent_id = c.ID	
					WHERE ( address_type = '0' or address_type is null ) &&
					( email_type = '0' or email_type is null ) && 
					( phone_type = '0' or phone_type is null )  
					";	
				break;
			case 'dump':
				$download_sql = " 		
					SELECT  first_name as fn, last_name as ln,  
						email_address, 
						phone_number,
						address_line,
						city, state, zip,
						c.* , i.*
					FROM $temp_table i INNER JOIN $constituent c on c.ID = i.ID
					left join $email e on e.constituent_id = c.ID
					left join $phone p on p.constituent_id = c.ID
					left join $address a on a.constituent_id = c.ID	
					";	
				break;				
		
		} 	
	
   	// go direct to database and do customized search and write temp table
		$sql = 	"CREATE TEMPORARY TABLE $temp_constituent_table
					$download_sql
					"; 
		
		$result = $wpdb->query ( $sql );

		$sql = "SELECT * FROM $temp_constituent_table ";

		return ( $sql);
	}



	protected static function do_download_security_checks() {

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

	// do_the_export runs the $sql in chunks and exports to filename
	protected static function do_the_export ( $file_name, $sql ) {

		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename={$file_name}");
		header("Expires: 0");
		header("Pragma: public");

		$fh = fopen( 'php://output', 'wt' ); // writing plain text files; good to support txt 
		
		global $wpdb;
		
		// chunking the retrievals to keep $results array size down on large downloads
		// 10000 peaks memory usage at just under 100M for constituent download without any especially long custom fields 
		// believe packet size not an issue -- outgoing sql is short and the packet is just a row coming back (?).
		// 1000 chunk size cuts memory usage to 13M ( WP is roughly 4M) and but is only about 30% slower on large files
		$i = 0;
		$header_displayed = false;
		while ( $results = $wpdb->get_results ( $sql . " LIMIT " . $i * 1000 . ", 1000 ", ARRAY_A ) ) {
			$i++;	
			foreach ( $results as $result ) {
				if ( !$header_displayed ) {
		      	fputcsv($fh, array_keys($result));  
		        	$header_displayed = true;
		   	}
		    fputcsv($fh, $result); // defaults are delimiter ',', enclosure '"', escape '/'
			}
		} 

		if ( 0 == $i ) { // write a not found header if no records were found
			fputcsv( $fh, array ( "No records found for $file_name" ) ); 		
		}

		fclose ( $fh );		

	}



}	

