<?php
/*
*
*	wic-entity-comment.php
*
*  Comment is a psuedo entity -- doesn't map directly to any database entity and supports no updates or searching
*  Just a reporting link -- supports cross table queries
*
*/



class WIC_Entity_Comment extends WIC_Entity_Multivalue {

	public $entity;		// top level entity searched for or acted on ( e.g., constituents or issues )
	public $sql; 			// for search, the query executed;
	public $result = array(); 		// entity_object_array -- as saved, update or found( possibly multiple ) (each row as object with field_names properties)
	public $outcome; 		// true or false if error
	public $explanation; // reason for outcome
	public $found_count; // integer save/update/search # records found or acted on
	public $retrieve_limit;
	public $found_count_real;
	public $showing_count;

	protected function set_entity_parms( $args ) {
		$this->entity = 'comment';
	} 

	public static function create_comment_list ( &$doa ) {
		// this function spans multiple entities and levels and could be placed in multiple places
		// since it is a one off list -- there are no related update functions, viewing it as basically a presentation issue
		global $wpdb;
		$output = '';
		
		// grab emails for constituent in array				
		$known_emails_array = array();
		foreach ( $doa['email']->get_value() as $email_entity ) {
			( $email_entity->get_email_address() ); 
			$known_emails_array[] = $email_entity->get_email_address();		
		}
		if (  0 == count ( $known_emails_array ) ) {
			$output = '<br/><p class = "wic-form-field-group-legend">' . __( 'No online activity found for this constituent.', 'wp-issues-crm' ) . '</p><br/>';	
			return ( $output );	
		}

		// first check for twcc guest posts with that email 
		$meta_query_args = array ( 
			'relation' => 'AND',
			array (
				'key' => 'twcc_post_guest_author_email',
				'value' => $known_emails_array,
				'compare' => 'IN',			
			)
		);
		$args = array (
			'posts_per_page' =>25,
			'ignore_sticky_posts' => true,
			'meta_query' => $meta_query_args
			);		

		$guest_post_list = new WP_Query ( $args );

		// hold that thought and check for comments
		$where_string = '';	
		foreach ( $known_emails_array as $email_address ) {
			$where_string .= ( '' == $where_string ) ? '%s' : ', %s';   		
		}

		$sql = $wpdb->prepare ( 
			"SELECT max( comment_ID ) as comment_ID, max( comment_date ) as comment_date, comment_post_ID 
			FROM wp_comments 
			WHERE comment_author_email IN ( $where_string ) 
			GROUP BY comment_post_ID
			ORDER BY max( comment_date ) DESC
			LIMIT 0, 25			
			", $known_emails_array ); 

		$result = $wpdb->get_results ( $sql );
		
		// if neither posts nor comments, quit 
		if ( 0 == count ( $result ) && ! $guest_post_list->have_posts() ) {
			$output = '<br/><p class = "wic-form-field-group-legend">' . __( 'No online activity found for this constiutent.', 'wp-issues-crm' ) . '</p><br/>';
			return ( $output );		
		}

		// otherwise run a consolidate list of whatever has come from both
		$output .= '<ul id = "constituent-comment-list">';

		// show posts first
		if ( $guest_post_list->have_posts() ) {
			while ( $guest_post_list->have_posts() ) {
				$guest_post_list->the_post();
					$output .= '<li class = "constituent-comment-row">' . 
						'<ul class = "constituent-comment-row" >' .
							'<li class = "constituent-comment-date"> ' . 
								date_i18n( 'Y-m-d', strtotime( $guest_post_list->post->post_date ) ) .', ' .
							'</li>' . 
							'<li class = "constituent-comment-issue">' .
								'<a href="' . esc_url( get_permalink( $guest_post_list->post->ID) ) .
									 '" title = "' . __('View Comment in Issue Context', 'wp-issues-crm' ) .'">' . 
										get_the_title( $guest_post_list->post->ID ) . 
								'</a>' .
							'</li>' .
							'<li class =  "comment-issue-link" >' . self::issue_link_button( $guest_post_list->post->ID  ) . '</li>' .					
						'</ul>' .
					'</li>';
			}
		}
		wp_reset_postdata();
		
		foreach ( $result as $comment ) {
			$output .= '<li class = "constituent-comment-row">' . 
				'<ul class = "constituent-comment-row" >' .
					'<li class = "constituent-comment-date"> ' . 
						date_i18n( 'Y-m-d', strtotime( $comment->comment_date ) ) .
					'</li>' . 
					'<li class = "constituent-comment-issue">' . 
						'<a href="' . esc_url( get_comment_link( $comment->comment_ID ) ) .
							 '" title = "' . __('View Comment in Issue Context', 'wp-issues-crm' ) .'">' . 
								get_the_title( $comment->comment_post_ID ) . 
						'</a>' .
					'</li>' .
					'<li class = "comment-issue-link" >' . self::issue_link_button( $comment->comment_post_ID ) . '</li>' .
				'</ul>' .
			'</li>';
		}
		$output .= '</ul>';

		return ( $output );						
	}

	private static function issue_link_button ( $id ) {
		$list_button_args = array(
			'entity_requested'	=> 'issue',
			'action_requested'	=> 'id_search',
			'button_class' 		=> 'wic-form-button wic-comment-issue-link-button',
			'id_requested'			=> $id,
			'button_label' 		=> __( 'View Issue', 'wp-issues-crm' ),
			'title'					=> __( 'View associated WIC issue (i.e., post).', 'wp-issues-crm' ),				
			);			
		return ( WIC_Form_Parent::create_wic_form_button( $list_button_args ) );		
	}
	
	/********
	*
	* $id_array of issue ID's from issue query
	* $search_id is just a pass through for use in download link
	*   in WIC_List_Constituent
	*
	*********/
	public function get_constituents_by_issue_id ( $args ) {
		
		// designed to mimic return from a constituent search so it can be fed to constituent list
		// called by entity-issue to list constituents after and by list-constituent-export to list constituents for array of issues 
		$id_array = array();
		$search_id = 0;
		extract ( $args );

		// test incoming values	
		// search ID should be a positive integer
		if ( 1 > absint ( $search_id ) || $search_id != absint ( $search_id ) ) {
			WIC_Function_Utilities::wic_error ( sprintf ( __( 'Bad |search ID| passed in list request -- |%s|.', 'wp-issues-crm' ) , $search_id ), __FILE__, __LINE__, __METHOD__, false );		
		}
		// should have at least one value in search array		
		if ( 0 == count ( $id_array ) || ( 0 == $id_array[0] ) ) {
			WIC_Function_Utilities::wic_error ( __( 'No non-zero issue ID passed in search request.', 'wp-issues-crm' ) , __FILE__, __LINE__, __METHOD__, false );		
		}		
		// search array should all be integers 
		foreach ( $id_array as $id ) {
			if ( $id != absint ($id) ) {
				WIC_Function_Utilities::wic_error ( sprintf( __( 'Invalid |element| in passed ID array -- |%s|', 'wp-issues-crm' ), $id ), __FILE__, __LINE__, __METHOD__, true );
			}		
		}		

 		// make wp query object available
		global $wpdb;

		// prepare ID array for use in search		
		$id_string = implode ( ',', $id_array );		

		// get unique constituents with activities
		$table =  $wpdb->prefix . 'wic_' . 'activity';
		$sql1 = 	"
			SELECT constituent_id 
			FROM $table
			WHERE	issue IN ( $id_string )
			GROUP BY constituent_id 
			";
		$first_array_of_constituent_ids = $wpdb->get_results ( $sql1 );
		 
	 	
		// now get unique constituents with comments
		$table =  $wpdb->prefix . 'wic_' . 'email';
		$sql2 = 	"		
			SELECT constituent_id  
			FROM wp_comments inner join $table email on comment_author_email = email_address
			WHERE comment_post_ID in ( $id_string )
			GROUP BY constituent_id
			";
		$second_array_of_constituent_ids = $wpdb->get_results ( $sql2 );
		
		// now pick up the authors of the posts in several steps:
		$third_array_of_constituent_ids = array();

		// first create array the emails of the authors		
		$email_array = array();
		foreach ( $id_array as $id ) {
			$email = '';
			$guest_email = '';
			$regular_user_email = '';
			// supporting front end post no spam plugin -- check if have email in metadata for post
			$guest_email = get_post_meta ( $id, 'twcc_post_guest_author_email', true );
			// if not, look for wp author's email
			if ( '' == $guest_email )  {
				$sqltemp = "SELECT post_author from wp_posts where ID = $id ";
				$temp_result = $wpdb->get_results ( $sqltemp );
				$regular_user_email = get_the_author_meta ( 'user_email', $temp_result[0]->post_author ); 
			}
			// use guest email if non blank, otherwise, use the regular
			if ( '' < $guest_email ) { 
				$email = $guest_email;
			} else {
				$email = $regular_user_email;			
			}
			// if got a non-blank email from either source, put it in the array of emails
			if ( $email > '' ) {
				$email_array[] = $email;
			}				
		}
		
		$clean_email_array = array_unique ( $email_array ); 

		// if author emails found, search for their constituent_id 
		$sql3 = '';
		if ( 0 < count ( $clean_email_array ) ) {
			
			$where_string = '';
			foreach ( $clean_email_array as $email_address ) {
				$where_string .= ( '' == $where_string ) ? '%s' : ' or email_address =  %s';   		
			}
			
			$table =  $wpdb->prefix . 'wic_' . 'email';
			$sql3 = $wpdb->prepare( "
				SELECT constituent_id 
				FROM $table
				WHERE email_address = $where_string
				GROUP BY constituent_id
				",
				$clean_email_array );

			$third_array_of_constituent_ids = $wpdb->get_results( $sql3 );
		}  			

		// merge arrays of constituent IDs from the three different sources
		$simple_combined_array_of_ids = array();
		foreach ( $first_array_of_constituent_ids as $id ) {
			$simple_combined_array_of_ids[] = $id->constituent_id;		
		}		
		foreach ( $second_array_of_constituent_ids as $id ) {
			$simple_combined_array_of_ids[] = $id->constituent_id;		
		}
		foreach ( $third_array_of_constituent_ids as $id ) {
			$simple_combined_array_of_ids[] = $id->constituent_id;		
		}				
		
		$unique_ids = array_unique ( $simple_combined_array_of_ids );

		// returning combined results as an array of objects with ID as property (for compatibility with WP_Query results)
		foreach ( $unique_ids as $id ) {
			$this->result[] = new WIC_Constituent_ID_Item ( $id );		
		}

		$this->search_id = $search_id; // just a pass through from primary searches for issues
		$this->entity = 'constituent';
		$this->retrieve_limit = 9999999999; // had not retrieve limits in this process
		$this->found_count = count ( $unique_ids ); // final unique count of constituents
		$this->found_count_real = true;
		$this->showing_count = $this->found_count;
		$this->sql = '(1) ' . $sql1 . '; (2) ' . $sql2 . '; (3) ' . $sql3 . ';'; 
	} 
}

class WIC_Constituent_ID_Item {
	// this class just helps mimic the wpdb result return
	public $constituent_id;
	
	public function __construct ( $id ) {
		$this->ID = $id;	
	}

}
