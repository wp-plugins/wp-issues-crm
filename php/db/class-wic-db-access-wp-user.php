<?php
/*
*
* class-wic-db-access-wp-user.php
*		 wraparound for access to wp user meta  
*
*
* 
*/

class WIC_DB_Access_WP_User Extends WIC_DB_Access {

	const WIC_METAKEY =  'wic_data_user_preferences';
	
	protected function db_search( $meta_query_array, $search_parameters ) {
		global $current_user;
		get_currentuserinfo ();
		
		$wic_user_meta = get_user_meta ( $current_user->id, self::WIC_METAKEY ) ;
		$preferences = ( count ( $wic_user_meta ) > 0 ) ? unserialize ( $wic_user_meta[0] ) : array();
		$show_viewed_issue 	= isset ( $preferences['show_viewed_issue'] ) ? $preferences['show_viewed_issue'] : ''; 
		$show_latest_issue 	= isset ( $preferences['show_latest_issues'] ) ? $preferences['show_latest_issues'] : ''; 
		$max_issues_to_show 	= isset ( $preferences['max_issues_to_show'] ) ? $preferences['max_issues_to_show'] : '';
		$first_form			 	= isset ( $preferences['first_form'] ) ? $preferences['first_form'] : '';
		
		$user_preferences = new WIC_DB_User_Preferences_Object ( 
			$current_user->id, 
			$current_user->display_name,
			$show_viewed_issue,
			$show_latest_issue,
			$max_issues_to_show,
			$first_form
		);

		$interface_array = array ( $user_preferences );		
		
		$this->sql = 'Retrieving User Meta Information Using Core Wordpress Functions';

		// return array analagous to wpdb return
		$this->result = $interface_array;
		// always behaving as successful, even if not found
		$this->showing_count = 1;
		$this->found_count = 1;
		$this->found_count_real = true;
		$this->retrieve_limit = 1;
		$this->outcome = true; 
		$this->explanation = '';
	}
		
		// default search parameters supplied -- not all apply in the WP context

	protected function db_update ( &$save_update_array ) {
		$this->process_save_update_array ( $save_update_array );
	}

	protected  function db_save ( &$save_update_array ) {
	}

	private function process_save_update_array ( &$save_update_array ) {

		$id = '';
		$interface_array = array();

		foreach ( $save_update_array as $update_clause ) {
			
			if ( 'ID' == $update_clause['key'] ) {
				$id = $update_clause['value'];			
			} elseif ( 'display_name' != $update_clause ['key'] ) {
				$interface_array[$update_clause['key']] = $update_clause['value'];
			} 
		}

		// get current value for change testing 
		$wic_user_meta = get_user_meta ( $id, self::WIC_METAKEY ) ;
		$preferences = ( count ( $wic_user_meta ) > 0 ) ? unserialize ( $wic_user_meta[0] ) : array();

		// a non-update is successful
		$this->outcome = true;
		$this->explanation = '';
		
		if ( 0 < count ( array_diff_assoc ( $interface_array, $preferences ) ) ) { 
		
			$result = update_user_meta ( $id, self::WIC_METAKEY, serialize ( $interface_array ) );

			if ( false === $result ) {
			// test current value to be able to differentiate lack of change from an actual bad outcome
				$this->outcome = false;
				$this->explanation = __( '.', 'wp-issues-crm' );
			};

		} 
	}

	protected function db_delete_by_id ($id){ // not implemented for users
	}

	// return preference value for specified user preference string
	public static function get_wic_user_preference ( $preference ) {
		$user_id = get_current_user_id(); 
		$wic_user_meta = get_user_meta ( $user_id, self::WIC_METAKEY ) ;
		$preferences = ( count ( $wic_user_meta ) > 0 ) ? unserialize ( $wic_user_meta[0] ) : array();
		return ( isset ( $preferences[$preference] ) ?  $preferences[$preference] : false );
	}

	protected function db_updated_last ( $user_id ) {} // not implemented for users
	protected function db_get_option_value_counts( $field_slug ) {} // not implemented for users


}


