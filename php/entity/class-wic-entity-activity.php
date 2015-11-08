<?php
/*
*
*	wic-entity-activity.php
*
*/



class WIC_Entity_Activity extends WIC_Entity_Multivalue {

	public function update_row() {
		$this->show_hide_activity_elements( true );
		$new_update_row_object = new WIC_Form_Activity_Update ( $this->entity, $this->entity_instance );
		$new_update_row = $new_update_row_object->layout_form( $this->data_object_array, null, null );
		return $new_update_row;
	}

	public function save_row() {
		$this->show_hide_activity_elements( true );
		return ( parent::save_row() );	
	}

	public function search_row() {
		$this->show_hide_activity_elements( false );
		return ( parent::search_row() );	
	}

	protected function set_entity_parms( $args ) {
		extract ( $args );
		$this->entity = 'activity';
		$this->entity_instance = $instance;
	} 

/**************************************************
*
*  Support for particular object properties
*
***************************************************/
	
	// get and parse freeze date setting
	public static function get_freeze_date() {
		$wic_option_array = get_option('wp_issues_crm_plugin_options_array'); 
		if ( isset ( $wic_option_array['freeze_older_activities'] ) ) {
			$date_value = $wic_option_array['freeze_older_activities'] > '' ? 
				WIC_Control_Date::sanitize_date( $wic_option_array['freeze_older_activities'] ) :
				'';
		} else {
			$date_value = '';		
		}
		return ( $date_value );
	}
	
	public static function get_issue_options( $value ) {

		// top element in options array is always a placeholder -- should decide
		$issues_array = array( 
			array ( 'value' => '' , 'label' => __( 'Activity Issue?', 'wp-issues-crm' ) ),
		);	

		$user_id = get_current_user_id();	
		
		// variable tested to see if need to add an already save activity's issue to the array
		$value_in_option_list = false;	
		
		// is activity issue search allowed for users?  if so, apply preferences in defining drop down selection
		// these will apply whether or not user has chosen the search dropdown or the simple drop down
		// if search drop down, these preferences will only define priorities; 
		$wic_option_array = get_option('wp_issues_crm_plugin_options_array');
		if ( ! isset ( $wic_option_array['disallow_activity_issue_search'] ) ) {
			// if user prefers, add next the last issue viewed by user or modified by user (if not author may not show as mod)
			if ( WIC_DB_Access_WP_User::get_wic_user_preference( 'show_viewed_issue' ) )	{	
				$args = array ( 'id_requested' => $user_id ); // spoof a button handoff by the request handler
				$entity = new WIC_Entity_Issue( 'get_latest_no_form', $args ); // initialize the data_object_array with the latest
				$latest_viewed_issue = $entity->get_current_ID_and_title(); // pull info from the doa
				if ( ! WIC_Entity_Issue_Open_Metabox::is_issue_closed ( $latest_viewed_issue['current'] ) ) { // allow open or not-defined status
					if ( $latest_viewed_issue['current'] > '0' ) {
						array_push ( $issues_array, array ( 'value' => $latest_viewed_issue['current'] , 'label' => $latest_viewed_issue['title'] ) );
						$value_in_option_list = ( $value == $latest_viewed_issue['current'] );
					}
				}
			}

			// if user prefers add latest used issues to the array
			if ( WIC_DB_Access_WP_User::get_wic_user_preference( 'show_latest_issues' ) != 'x' ) {
				$recent_issues = WIC_DB_Access_WP::get_wic_latest_issues(); 
				if ( $recent_issues && count ( $recent_issues ) > 0 ) {
					foreach ( $recent_issues as $recent_issue ) {		
						$issues_array[] = array(
							'value'	=> $recent_issue->ID,
							'label'	=>	esc_html ( $recent_issue->post_title ),
						);
						if ( $value == $recent_issue->ID ) {
							$value_in_option_list = true;
						}	
					}
				}
			}
		}
		// add WIC open issues to the array
		$open_posts = WIC_DB_Access_WP::get_wic_live_issues();			
		foreach ( $open_posts as $open_post ) {		
			$issues_array[] = array(
				'value'	=> $open_post->ID,
				'label'	=>	esc_html ( $open_post->post_title ),
			);
			if ( $value == $open_post->ID ) {
				$value_in_option_list = true;
			}
		}
		
		// add current value if missing
		if ( ! $value_in_option_list && $value > '' ) {
			$issues_array[] = array (
				'value'	=> $value,			
				'label'	=> get_the_title( $value ),
			);
		}		
		
		return ( $issues_array );
	}


	// generate upload id options
	public static function upload_id( $value ) {
		
		// base value is a non-uploaded activity
		$uploads_array = array( 
			array ( 'value' => 0 , 'label' => __( 'Not Uploaded', 'wp-issues-crm' ) ),
		);			
		// get upload list
		$uploads = WIC_DB_Access_Upload::list_uploads();
		// assume value not found
		$value_found = false;
		// if got a list of uploads, populate the option list
		if ( is_array ( $uploads ) ) {
			foreach ( $uploads as $upload ) {
				$uploads_array[] = array ( 'value' => $upload->ID , 'label' => $upload->upload_file . ' (' . $upload->upload_time . ') ' );		
				if ( $upload->ID == $value ) {
					$value_found = true;
				}
			}
		}
		if ( false === $value_found && $value > 0 ) {
			$uploads_array[] = array ( 'value' => $value , 'label' => __( 'Upload history purged.', 'wp-issues-crm' ) );	
		
		}
	    return ( $uploads_array );
	}

	// handle special variable display properties in activity rows -- 
	// better here than in js (avoid visual bounce)
	protected function show_hide_activity_elements ( $not_a_search = true ) {

		/*
		* if no financial activity types or type of current record is not financial, hide amount control
		*/
		$current_activity_type = $this->data_object_array['activity_type']->get_value();
		$wic_option_array = get_option('wp_issues_crm_plugin_options_array'); 
		$financial_types_array = explode (',' , isset ( $wic_option_array['financial_activity_types'] ) ? $wic_option_array['financial_activity_types'] : '') ;
		if ( 1 == count( $financial_types_array) && '' == $financial_types_array[0] ) { // no financial types set
			$this->data_object_array['activity_amount']->set_input_class_to_hide_element();	
		} elseif ( $not_a_search ) {
			$current_activity_type = $this->data_object_array['activity_type']->get_value();
			if ( ! in_array ( $current_activity_type, $financial_types_array ) ) {
				$this->data_object_array['activity_amount']->set_input_class_to_hide_element();
			}			
		}

		/*
		* if activity_date is before frozen date, make all controls read only
		*  -- require activity_date non-blank -- don't freeze the template row
		*/
		if ( '' < $this->data_object_array['activity_date']->get_value() &&
				$this->data_object_array['activity_date']->get_value() < self::get_freeze_date()  ) {
			foreach ( $this->data_object_array as $control ) {
				$control->override_readonly( true );
			}
		}
		 
		/*
		* if not using autocomplete mode for activity (i.e., disallowed or user opted out) hide the autocomplete control (it will not be referenced)
		*/
		if ( isset( $wic_option_array['disallow_activity_issue_search'] ) || 
				( 1 == WIC_DB_Access_WP_User::get_wic_user_preference( 'activity_issue_simple_dropdown' ) ) ) {
			$this->data_object_array['issue_autocomplete']->set_input_class_to_hide_element();
		// conversely, if using autocomplete, hide the select element (remains the database referenced element while hidden)		
		} else {  
			$this->data_object_array['issue']->set_input_class_to_hide_element();
			// also, populate the displayed value in issue_autocomplete if exist			
			$issue = $this->data_object_array['issue']->get_value();
			if ( $issue > '' ) {
				$this->data_object_array['issue_autocomplete']->set_value( get_the_title( $issue ) );
			} // $issue > ''
		}  // using autocomplete		
	}  // function show hide activity elements
} // class