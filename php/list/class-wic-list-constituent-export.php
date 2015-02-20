<?php
/*
* class-wic-list-constituent-export.php
*
* 
*/ 

class WIC_List_Constituent_Export {
	/*
	*
	*
	*/
	public static function assemble_list_for_export ( &$wic_query ) {
				// convert the array objects from $wic_query into a string

  		$id_list = '(';
		foreach ( $wic_query->result as $result ) {
			$id_list .= $result->ID . ',';		
		} 	
  		$id_list = trim($id_list, ',') . ')';

   	// go direct to database and do customized search
   	// create a new WIC access object and search for the id's

		global $wpdb;	

		$sql = 	"SELECT first_name, last_name,  
						max( email_address ) as emails, 
						max( city ) as city, 
						max( phone_number ) as phones,
						max( address_line ) as address_line_1,
						max( concat ( city, ', ', state, ' ',  zip ) ) as address_line_2,
						max( zip ), 
						c.* 
					FROM wp_wic_constituent c
					left join wp_wic_email e on e.constituent_id = c.ID
					left join wp_wic_phone p on p.constituent_id = c.ID
					left join wp_wic_address a on a.constituent_id = c.ID	
					WHERE c.ID IN $id_list
					AND ( address_type = '0' or address_type is null )
					GROUP BY c.ID
					"; 

		$results = $wpdb->get_results ( $sql, ARRAY_A ); 

		return ( $results );
	}


	public static function do_constituent_download ( $id ) {
		
		if ( true === self::do_download_security_checks() )  { 
					
			// retrieves only the meta array, not the search parameters, since will supply own
			$search = WIC_DB_Access::get_search_from_search_log( $id );	
	
			$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( $search['entity'] );
			
			$search_parameters = array (		
				'select_mode' 		=> 'id',
				'sort_order' 		=> true,
				'compute_total' 	=> false,
				'retrieve_limit' 	=> 999999999,
				'show_deleted'		=> false,
				'log_search'		=> false,
			);
	
			$wic_query->search ( $search['meta_query_array'], $search_parameters );
	
			if ( 'constituent' == $search['entity'] ) { // done if constituents only
				$results = self::assemble_list_for_export( $wic_query ); 
			} elseif ( 'issue' ==  $search['entity'] ) { // need to run issues through 'Comment' logic to get constituents 
				$issue_array = array();
				foreach ( $wic_query->result as $issue ) {
					$issue_array[] = $issue->ID;
				}
				$args = array (			
					'id_array' => $issue_array,
					'search_id' => $id,
					);
				$comment_query = new WIC_Entity_Comment ( 'get_constituents_by_issue_id', $args );
				$results = self::assemble_list_for_export( $comment_query ); 
			} 
			
			self::do_the_export( $results );
	
			WIC_DB_Access::mark_search_as_downloaded( $id );
			
			exit;
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
			$results = self::assemble_list_for_export ( $wic_query ); 
			// send the file
			self::do_the_export ( $results );
			// mark the whole search as downloaded (no mechanism to mark slice)	
			WIC_DB_Access::mark_search_as_downloaded( $search_id  );
			// done -- no log marking
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

	private static function do_the_export ( $results ) {
			
		$current_user = wp_get_current_user();	
		$fileName = 'wic-export-' . $current_user->user_firstname . '-' .  current_time( 'Y-m-d-H-i-s' )  .  '.csv' ; 
		
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename={$fileName}");
		header("Expires: 0");
		header("Pragma: public");
		
		$fh = @fopen( 'php://output', 'w' );
		
		 global $wpdb;
		$headerDisplayed = false;
		
		foreach ( $results as $data ) {
		    if ( !$headerDisplayed ) {
		        fputcsv($fh, array_keys($data));
		        $headerDisplayed = true;
		    }
		    fputcsv($fh, $data);
		}
		fclose($fh);
	
	}

}	

