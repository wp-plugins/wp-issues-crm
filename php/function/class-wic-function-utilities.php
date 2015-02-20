<?php
/**
*
* class-wic-function-utilities.php
*
**/
 

class WIC_Function_Utilities { // functions that serve multiple entities	

	// display error message
	public static function wic_error ( $text, $file, $line, $method, $fatal ) {
		$fatal_phrase = $fatal ? __( 'WP Issues CRM Fatal Error: ', 'wp-issues-crm' ) : __( 'WP Issues CRM Non Fatal Error: ', 'wp_issues_crm' );
		$error_class  = $fatal ? 'wp-issues-crm-fatal-error' : 'wp-issues-crm-non-fatal-error'; 
		echo '<div class="' . $error_class . '">' . ' 
			<h3>' . $fatal_phrase . __( $text, 'wp-issues-crm' ) . '</h3>' .  
			'<p>' . sprintf ( __( '  File Reporting: %s on line number %s -- method %s.', 'wp-issues-crm' ), $file, $line, $method ) . '</p>' . 
		'</div>';
		if ( $fatal ) {
			die;		
		}	
	}	
	
	// get administrator array
	public static function get_administrator_array() {
	
		$user_select_array = array();	
	
		$roles = array( 'Administrator', 'Editor', 'Author', 'wic_constituent_manager' ) ;
		
		foreach ( $roles as $role ) {
			$user_query_args = 	array (
				'role' => $role,
				'fields' => array ( 'ID', 'display_name'),
			);						
			$user_list = new WP_User_query ( $user_query_args );
	
	
			foreach ( $user_list->results as $user ) {
				$temp_array = array (
					'value' => $user->ID,
					'label'	=> $user->display_name,									
				);
				array_push ( $user_select_array, $temp_array );								
			} 


		}

	array_push ( $user_select_array,  array ( 'value' => '' , 'label' => '' ) );
	
	return ( $user_select_array );
		
	}
	
	// get array of people who updated table  
	private static function get_last_updated_by_array( $table ) {
		global $wpdb;
		$table = $wpdb->prefix . $table;
		$updaters = $wpdb->get_results(
			"
			SELECT last_updated_by
			FROM $table
			GROUP BY last_updated_by
			"
		);

		$user_select_array = array( array ( 'value' => '' , 'label' => '' ) );
		foreach ( $updaters as $updater ) {
			if ( $updater->last_updated_by > 0 ) { 
				$user = get_user_by ( 'id', $updater->last_updated_by );
				$display_name = isset ( $user->display_name ) ? $user->display_name : '';
				$temp_array = array (
					'value' => $updater->last_updated_by,
					'label' => $display_name				
				);
				array_push ($user_select_array, $temp_array);
			}		
		}

		return ( $user_select_array );
		
	}

	public static function constituent_last_updated_by () {
		return ( self::get_last_updated_by_array( 'wic_constituent' ) );		
	}

	public static function activity_last_updated_by () {
		return ( self::get_last_updated_by_array( 'wic_activity' ) );		
	}

	/*
	* extract label from value/label array
	*/
	public static function value_label_lookup ( $value, $options_array ) {
		if ( '' ==  $value ) {
			return ( '' );	
		}	

		foreach ( $options_array as $option ) {
				if ( $value == $option['value'] ) {
					return ( $option['label'] );			
				} 
			}
		return ( sprintf ( __('Option value (%s) missing in look up table.', 'wp-issues-crm' ), $value ) );
	}
	
	/*
	* convert dirty string with various possible white spaces and commas into clean compressed comma separated	
	*/
	public static function sanitize_textcsv ( $textcsv ) {
		
		$temp_tags = explode ( ',', $textcsv );
		
		$temp_tags2 = array();
		foreach ( $temp_tags as $tag ) {
			if ( sanitize_text_field ( stripslashes ( $tag ) ) > '' ) {
				$temp_tags2[] = sanitize_text_field ( stripslashes ( $tag ) );
			}			
		}
		$output_textcsv = implode ( ',', $temp_tags2 );
		return ( $output_textcsv );
	}	

	public static function get_today ( )  {
		return ( date ( 'Y-m-d' ) );
	}	

	/* 
	*  The following two array functions just create option arrays used in the admin area. 
	*/

	
	public static function order_array () {
		$order_array = array(
			array(
				'value' => '',
				'label' => 'Order?',
			),
		);
		
		/* offer multiples of 10 first */
		for ( $i = 1; $i <= 20; $i++ ) {
			$temp = array (
				'value' => $i * 10,
				'label' => $i * 10,
			);
			array_push ( $order_array, $temp );
		}
		/* offer multiples of 5 next */
		for ( $i = 0; $i <= 20; $i++ ) {
			$temp = array (
				'value' => $i * 10 + 5,
				'label' => $i * 10 + 5,
			);
			array_push ( $order_array, $temp );
		}
		/* offer individual lines */
		for ( $i = 1; $i <= 200; $i++ ) {
			$temp = array (
				'value' => $i,
				'label' => $i,
			);
			if ( $i % 5 > 0 ) {
				array_push ( $order_array, $temp );
			}
		}

		return ( $order_array );		
		
	}	
	
	public static function list_option_groups() {

		global $wpdb;
		$table = $wpdb->prefix . 'wic_option_group' ;
		$option_groups = $wpdb->get_results(
			"
			SELECT option_group_slug, option_group_desc
			FROM $table
			"
		);
		
		$option_group_array = array( array ( 'value' => '' , 'label' => 'None' ) );
		foreach ( $option_groups as $option_group ) {
			$temp = array(
				'value' => $option_group->option_group_slug,
				'label' => $option_group->option_group_desc,
			);
			array_push ( $option_group_array, $temp );
		}
		
		return ( $option_group_array );
	}
	
}
