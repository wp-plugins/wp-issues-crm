<?php
/*
*
*	wic-db-autocomplete.php 
*  psuedo entity to pass through Ajax calls to specialized db access for fast autocompletes
*  
*  used to maintain consistency in autocomplete calls with two wp-issues-crm standards
*		Ajax calls are to an entity class
*     DB calls are in a db class 
*  
*/

class WIC_DB_Autocomplete  {

	public static function do_autocomplete( $look_up_mode, $term ) {
		global $wpdb;

		$post_table 			= $wpdb->posts;
		$postmeta_table 		= $wpdb->postmeta;
		$activity_table 		= $wpdb->prefix . 'wic_activity';
		
		// strip look_up_mode out of $look_up_mode if encumbered by indexing
		$look_up_mode = strrchr ( $look_up_mode , '[') === false ? $look_up_mode : ltrim( rtrim( strrchr ( $look_up_mode , '['), ']' ), '[' );

		if ( strlen ( $term ) > 2 ) { // protect against short strings that are sanitized blanks  		
			
			switch ( $look_up_mode ) {
				case 'activity_issue': // retrieve id/title array of in descending order of status
					$response = array();
					$sql_template = "
						SELECT wic_matching_posts.ID, concat( post_title, ' (' , if( open_status > '', 'Open -- ', '' ) , count(a.ID), ' activities, ' , comment_count, ' comments)' )  as post_title from  
							( SELECT ID, post_title, comment_count, max( if( meta_key = 'wic_data_wic_live_issue', meta_value, '') ) as open_status   
							FROM $post_table p left join $postmeta_table pm on p.ID = pm.post_id 
							WHERE post_type = 'post' AND
								( post_status = 'publish' or post_status = 'private' ) AND
								post_title like %s
							GROUP BY p.ID
							HAVING MAX( IF (meta_key = 'wic_data_wic_live_issue' and meta_value = 'closed', 1, 0 ) ) = 0 ) wic_matching_posts 
						LEFT JOIN $activity_table a on a.issue = wic_matching_posts.ID 
						GROUP BY wic_matching_posts.ID
						ORDER BY open_status  desc, post_title asc
	
						"; 
					$values = array ( '%' . $term . '%' ); // table scan for term in titles
					$sql = $wpdb->prepare ( $sql_template, $values );
					$results = $wpdb->get_results ( $sql ); 
					foreach ( $results as $result ) {
						$response[] = new WIC_DB_Activity_Issue_Autocomplete_Object (  $result->post_title, $result->ID );
					}
					echo json_encode ( $response ) ;
					wp_die();				
				case 'first_name':
				case 'last_name':
					$table = $wpdb->prefix . 'wic_constituent';
					break;
				case 'email_address':
					$table = $wpdb->prefix . 'wic_email';
					break;
				case 'address_line':	
					$table = $wpdb->prefix . 'wic_address';
					break;
			}
				 
			// look up process applicable to all except activity ( responded and died already )
			$response = '[ ';
			$sql_template = "
				SELECT $look_up_mode from $table
				WHERE $look_up_mode LIKE %s
				GROUP BY $look_up_mode
				";
			$values = array ( $term . '%' ); // right wild card only
			$sql = $wpdb->prepare ( $sql_template, $values );
			$results = $wpdb->get_results ( $sql ); 
			foreach ( $results as $result ) {
				$response .= '"' . $result->$look_up_mode . '",';				
			}
			echo rtrim ( $response, ',' ) . ']';
		// if underlength sanitized string, just send back empty	 
		} else {
			echo json_encode ( array() );		
		}				

		wp_die();
	}
}