<?php
/**
*
* class-wic-admin-dashboard.php
*/


class WIC_Admin_Dashboard {

	/* 
	*  This is the central request handler for the working screens of the plugin -- all requests are button submits named wic_form_button.
	*	WIC_Admin_Navigation just handles the page selection from the wordpress menu and checks nonces and user capabilities.
	*
	*  The constructor of this function, when instantiated by WIC_Admin_Navigation, distributes button submissions 
	*    (all of which have the same name, with a string of values) to an entity class with an action request and arguments.
	*
	*	See WIC_Form_Parent::create_wic_form_button for button interface (exclusive main form button creator for system)
	*
	*  Only other entry points ( other than fields/options/settings ) is at WIC_List_Constituent_Export 
	*	 same security tests as in Navigation are done there -- is logged in and, other than for dashboard first screen (my cases) have nonce
	*
	*/
	
	public function __construct() { 

		ob_start();
		// is submitting a previous form; 
		if ( isset ( $_POST['wic_form_button'] ) ) {
			//parse button arguments
			$control_array = explode( ',', $_POST['wic_form_button'] ); 
			// before handling request check if a top menu button and if so, do a history branch
			if ( '' 			== $control_array[0] || 
				'dashboard' == $control_array[0] || 
				'new_form' 	== $control_array[1] || 
				'new_blank_form'  == $control_array[1] ) {
				WIC_DB_Search_History::new_history_branch (); 
			}
			//proceed to handle request
			if ( '' == $control_array[0] || 'dashboard' == $control_array[0] ) { 
				// $control_array[0] should never be empty if button is set, but bullet proof to dashboard, which is also bullet proof
				$this->show_dashboard( $control_array [1] );		
			} else {
				$class_name = 'WIC_Entity_' . $control_array[0]; // entity_requested
				$action_requested 		= $control_array[1]; 
				$args = array (
					'id_requested'			=>	$control_array[2],
					'instance'				=> '', // unnecessary in this context, absence will not create an error but here for consistency about arguments;
				);
				${ 'wic_entity_'. $control_array[0]} = new $class_name ( $action_requested, $args ) ;		
			}
			
		// logged in user, but not coming from form -- show first form
		} else {
			// do a history branch
			WIC_DB_Search_History::new_history_branch (); 
			$this->show_dashboard( WIC_DB_Access_WP_User::get_wic_user_preference( 'first_form' ) );
		}
		
		 // main form output grabbed from buffer
		$form_output = ob_get_clean();
		// show top menu buttons before echoing form but after form preparation (need processing to determine whether to show back/forward buttons)
		if ( isset ( $_POST['wic_form_button'] ) ) {
			$this->show_top_menu_buttons ( $control_array[0], $control_array[1], $control_array[2] );
		} else {
			$this->show_top_menu_buttons ( 'dashboard',  WIC_DB_Access_WP_User::get_wic_user_preference( 'first_form' ), NULL );
		}
		echo $form_output;
	}

	private function show_top_menu_buttons ( $class_requested, $action_requested, $id_requested ) {  


		echo '<form id = "wic-top-level-form" method="POST" autocomplete = "on">';
		wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ); 

		$back_forward_enabled =  WIC_DB_Search_History::history_buttons();
		extract ( $back_forward_enabled ); 

		$user_id = get_current_user_id();

		//  all top nav buttons start new directions of user navigation -- if are the selected action (and not back/forward), then set history in $this->is_selected
		$top_menu_buttons = array (
			array ( 'search_log', 'back', '<span class="dashicons dashicons-arrow-left-alt"></span>' , __( 'Previous search or item', 'wp-issues-crm' ), $disable_backward ),
			array ( 'search_log', 'forward', '<span class="dashicons dashicons-arrow-right-alt"></span>', __( 'Next search or item', 'wp-issues-crm' ), $disable_forward ),
			// go to constituent options
			array ( 'constituent', 	'new_blank_form',	'<span class="dashicons dashicons-plus-alt"></span><span class="dashicons dashicons-smiley">' , __( 'New constituent.', 'wp-issues-crm' ), false ), // new
			array ( 'constituent', 	'new_form',		'<span class="dashicons dashicons-search"></span><span class="dashicons dashicons-smiley"></span>', __( 'Search constituents.', 'wp-issues-crm' ), false ), // search
			array ( 'dashboard', 	'my_cases',	 '<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-smiley"></span>', __( 'Constituents assigned to me.', 'wp-issues-crm' ), false  ),
			// array ( 'constituent', 	'get_latest',	'<span class="dashicons dashicons-smiley"></span><span class="dashicons dashicons-arrow-left-alt"></span>', __( 'Last constituent.', 'wp-issues-crm' ), false ), // new
			// go to issue options
			array ( 'issue', 			'new_blank_form',	'<span class="dashicons dashicons-plus-alt"></span><span class="dashicons dashicons-format-aside"></span>', __( 'New issue.', 'wp-issues-crm' ), false ),
			array ( 'issue', 			'new_form',		'<span class="dashicons dashicons-search"></span><span class="dashicons dashicons-format-aside"></span>', __( 'Search for issues.', 'wp-issues-crm' ), false ),
			array ( 'dashboard', 	'my_issues',	'<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-format-aside"></span>', __( 'Issues assigned to me.', 'wp-issues-crm' ), false ),
			// array ( 'issue', 			'get_latest',	'<span class="dashicons dashicons-format-aside"></span><span class="dashicons dashicons-arrow-left-alt"></span>', __( 'Last issue.', 'wp-issues-crm' ), false ),
			// analyze/download			
			array ( 'trend', 			'new_form',		'<span class="dashicons dashicons-chart-line"></span>', __( 'Get activity/issue counts.', 'wp-issues-crm' ), false ), 
			// go to search history
			array ( 'dashboard', 	'search_history',	'<span class="dashicons dashicons-arrow-left-alt"></span><span class="dashicons dashicons-arrow-left-alt"></span>', __( 'Recent searches.', 'wp-issues-crm' ), false ),		
			);		

		foreach ( $top_menu_buttons as $top_menu_button ) {
			$selected_class = $this->is_selected ( $class_requested, $action_requested, $top_menu_button[0], $top_menu_button[1] ) ? 'wic-form-button-selected' : '';
			$button_class = 'button button-primary wic-top-menu-button ' . $selected_class;	
			$button_class .= ( 'back' == $top_menu_button[1] || 'forward' == $top_menu_button[1] ) ? ' wic-nav-button ' : '' ; 		
			$button_args = array (
				'entity_requested'	=> $top_menu_button[0],
				'action_requested'	=> $top_menu_button[1],
				// 'id_requested'			=> 'get_latest' == $top_menu_button[1] ? $user_id : 0, // not actually used in the methods responsive to these buttons, except in the get_latest methods
				'button_class'			=> $button_class, 	
				'button_label'			=>	$top_menu_button[2],
				'title'					=>	$top_menu_button[3],
				'disabled'				=>	$top_menu_button[4],
			);
			echo WIC_Form_Parent::create_wic_form_button( $button_args );
		}				
		echo '</form>';		
	}

	// for semantic highlight of top buttons (note, in this function referring to class as in entity, not as in css class )
	private function is_selected ( $class_requested, $action_requested, $button_class, $button_action ) {
		// if last pressed the button, show it as selected 
		if ( $class_requested == $button_class && $action_requested == $button_action ) {
			return true; 
		} else { 
			return false;
		}
	}
	/**************************************************************************************************
	*
	* Dashboard display and dashboard action functions	
	*
	***************************************************************************************************/	
	
	// show the top menu buttons and call the action requested for the dashboard	
	private function show_dashboard( $action_requested ) {
		
		$user_ID = get_current_user_id();	
		
		// bullet proofed to always yield an action, but $action_requested should always be specified
		// exception: user has not specified a first screen preference, defaults to my cases
		if ( 'my_issues' == $action_requested || 'search_history' == $action_requested || 'my_cases' == $action_requested ) {
			$this->$action_requested( $user_ID );
		} else {
			$this->my_cases ( $user_ID );		
		}
			
	}	
	
	// display a list of cases assigned to user
	private function my_cases( $user_ID ) {
		
		$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( 'constituent' );

		$search_parameters= array(
			'sort_order' => true,
			'compute_total' 	=> false,
			'retrieve_limit' 	=> 9999999999,// kludge here:  this retrieve limit is a sentinel to the lister not to show the back button
			'show_deleted' 	=> false,
			'select_mode'		=> 'id',
			'log_search'		=> false,
		);

		$search_array = array (
			array (
				 'table'	=> 'constituent',
				 'key'	=> 'case_assigned',
				 'value'	=>  $user_ID, 
				 'compare'	=> '=', 
				 'wp_query_parameter' => '',
			),
			array (
				 'table'	=> 'constituent',
				 'key'	=> 'case_status',
				 'value'	=> '0', 
				 'compare'	=> '!=', 
				 'wp_query_parameter' => '',
			), 
		);

		$wic_query->search ( $search_array, $search_parameters ); // get a list of id's meeting search criteria
		$sql = $wic_query->sql;
		if ( 0 == $wic_query->found_count ) {
			echo '<h3>' . __( 'No cases assigned.', 'wp-issues-crm' ) . '</h3>';		
		} else {
			$lister_class = 'WIC_List_Constituent' ;
			$lister = new $lister_class;
			$list = $lister->format_entity_list( $wic_query, __( 'My Cases: ', 'wp-issues-crm' ) );
			echo $list;			
		}
	}
		
	// display a list of issues assigned to user	
	private function my_issues( $user_ID ) {
		
		$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( 'issue' );

		$search_parameters= array(
			'sort_order' => true,
			'compute_total' 	=> false,
			'retrieve_limit' 	=> 9999999999, // kludge here:  this retrieve limit is a sentinel to the lister not to show the back button
			'show_deleted' 	=> false,
			'select_mode'		=> 'id',
			'log_search'		=> false,
		);

		$search_array = array (
			array (
				 'table'	=> 'issue',
				 'key'	=> 'issue_staff',
				 'value'	=> $user_ID,
				 'compare'	=> '=', 
				 'wp_query_parameter' => '',
			),
			array (
				 'table'	=> 'issue',
				 'key'	=> 'follow_up_status',
				 'value'	=> 'open', 
				 'compare'	=> '=', 
				 'wp_query_parameter' => '',
			), 
		);

		$wic_query->search ( $search_array, $search_parameters ); // get a list of id's meeting search criteria
		$sql = $wic_query->sql;
		if ( 0 == $wic_query->found_count ) {
			echo '<h3>' . __( 'No issues assigned.', 'wp-issues-crm' ) . '</h3>';		
		} else {
			$lister_class = 'WIC_List_Issue' ;
			$lister = new $lister_class;
			$list = $lister->format_entity_list( $wic_query,  __( 'My Issues: ', 'wp-issues-crm' ) );
			echo $list;			
		}
	}

	// display user's search log ( which includes form searches, items selected from lists and also items saved )
	private function search_history( $user_ID ) {
	
		$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( 'search_log' );

		$search_parameters= array(
			'sort_order' => true,
			'compute_total' 	=> false,
			'retrieve_limit' 	=> 50,
			'show_deleted' 	=> true,
			'select_mode'		=> 'id',
			'sort_direction'	=> 'DESC',
			'log_search'		=> false,
		);

		$search_array = array (
			array (
				 'table'	=> 'search_log',
				 'key'	=> 'user_id',
				 'value'	=> $user_ID,
				 'compare'	=> '=', 
				 'wp_query_parameter' => '',
			),				
		);

		$wic_query->search ( $search_array, $search_parameters ); // get a list of id's meeting search criteria
		$sql = $wic_query->sql;
		if ( 0 == $wic_query->found_count ) {
			echo '<h3>' . __( 'Search logs purged since last search.', 'wp-issues-crm' ) . '</h3>';		
		} else {
			$lister_class = 'WIC_List_Search_Log' ;
			$lister = new $lister_class;
			$list = $lister->format_entity_list( $wic_query, '' );
			echo $list;			
		}
	}

}

