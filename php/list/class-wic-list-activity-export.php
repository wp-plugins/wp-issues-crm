<?php
/*
* class-wic-list-constituent-export.php
*
* 
*/ 

class WIC_List_Activity_Export extends WIC_List_Constituent_Export {

	public static function do_activity_download ( $button_value ) { 
		
		$button_value_array = explode ( ',', $button_value );	
		$download_type = $button_value_array[1];	
		$search_id = $button_value_array[2];		
		
		if ( '' == $download_type ) {
			return;		
		}		
		
		if ( true === self::do_download_security_checks() )  { 

			// naming the outfile
			$current_user = wp_get_current_user();	
			$file_name = 'wic-activity-export-' . $current_user->user_firstname . '-' .  current_time( 'Y-m-d-H-i-s' )  .  '.csv' ;
					
			// retrieves only the meta array, not the search parameters, since will supply own
			$search = WIC_DB_Access::get_search_from_search_log( $search_id );	
			// will be trend
			$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( $search['entity'] );
			
			$search_parameters = array (
				'trend_search_mode' => $download_type,		
				'select_mode' 		=> 'download', // with this setting, object will create the temp table that export sql assembler is looking for
				'sort_order' 		=> true,
				'compute_total' 	=> false,
				'retrieve_limit' 	=> 999999999,
				'show_deleted'		=> false,
				'log_search'		=> false,
			);
			
			// do the search, saving retrieved id list in temp table if constituent, setting up next step if issue	
			$wic_query->search ( $search['meta_query_array'], $search_parameters );
			
			$sql = self::assemble_activity_export_sql( $download_type ); // runs off temp table

			self::do_the_export( $file_name, $sql );
	
			WIC_DB_Access::mark_search_as_downloaded( $search_id );
			
			exit;
		}
	} 
	
	/*
	*	based on temporary table created early in transaction, creates second temporary table
	*  returns sql to read second table
	*  
	*/
	public static function assemble_activity_export_sql ( $download_type ) { 
		
		// reference global wp query object	
		global $wpdb;	
		$prefix = $wpdb->prefix . 'wic_';
		
		$activity		= $prefix . 'activity';
		$constituent 	= $prefix . 'constituent';
		$email 			= $prefix . 'email';
		$phone			= $prefix . 'phone';
		$address			= $prefix . 'address';
		$post				= $wpdb->posts;
		
		// id list passed through user's temporary file wp_wic_temporary_id_list, lasts only through the server transaction (multiple db transactions)
		$temp_table = $wpdb->prefix . 'wic_temporary_id_list';
		
		// pass activity list through repeated chunks to db as temp table
		$temp_activity_table = $wpdb->prefix . 'wic_temporary_activity_list_' . time();		

		global $wic_db_dictionary;
		$custom_fields = $wic_db_dictionary->custom_fields_match_array ();
		$custom_fields_string = '';
		if ( count ( $custom_fields ) > 0 ) {
			foreach ( $custom_fields as $field => $match_array ) {
				$custom_fields_string .= ', ' . $field . ' as `' . $match_array['label'] . '` ';			
			}
	 		
		}

		// initialize download sql -- if remains blank, will bypass download
		
		$download_sql = '';
		switch ( $download_type ) { // using switch to add possible formats later
			case 'activities':		
				$download_sql = " 		
					SELECT  if( count(ac.ID) > 1,'yes','not necessary' ) as 'constituent_data_consolidated',
						activity_date, activity_type, activity_amount, pro_con, post_title, first_name as fn, last_name as ln,  
						city, 
						email_address, 
						phone_number,
						address_line as address_line_1,
						concat ( city, ', ', state, ' ',  zip ) as address_line_2,
						state, zip $custom_fields_string
					FROM $temp_table t inner join $activity ac on ac.ID = t.ID 
					INNER JOIN $constituent c on c.ID = ac.constituent_id
					INNER JOIN $post wp on wp.ID = ac.issue
					left join $email e on e.constituent_id = c.ID
					left join $phone p on p.constituent_id = c.ID
					left join $address a on a.constituent_id = c.ID	
					GROUP BY ac.ID";	
				break;
		} 	
	
   	// go direct to database and do customized search and write temp table
		$sql = 	"CREATE TEMPORARY TABLE $temp_activity_table
					$download_sql
					"; 
		
		$result = $wpdb->query ( $sql );

		// pass back sql to retrieve the temp table
		$sql = "SELECT * FROM $temp_activity_table ";

		return ( $sql);
	}






}	

