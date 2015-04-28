<?php
/*
*
* class-wic-db-setup.php
*		accesses sql to create or update database and dictionary  
*/



class WIC_DB_Setup {

	/*********************************************************************************************
	*	
	*	database_setup()	
	*
	*	runs db delta to install/upgrade database
	*
	*  then runs routines to install/upgrade dictionary and options tables
	*
	*	note: all wic_issues_crm tables use the utf8 character set and utf8_general_ci collation  
	*
	*	note: version globals set in wp-issues-crm.php
	*
	*********************************************************************************************/
	private static function database_setup() { 

		global $wp_issues_crm_db_version; // see wp-issues-crm.php 
		$installed_version = get_option ( 'wp_issues_crm_db_version' ); 		
		
		global $wpdb;		
		
		// load the table set up sql 
		$sql = file_get_contents( plugin_dir_path ( __FILE__ ) . '../../sql/wic_structures.sql' );
		
		$sql = self::site_table_names_in_sql ( $sql );
	
		/* conform to db delta spacing convention:
		* 	"You must have two spaces between the words PRIMARY KEY and the definition of your primary key. "
		*  Note: matters on update compares, not on table creation */
		$sql = str_replace ( 'PRIMARY KEY (', 'PRIMARY KEY  (', $sql );	

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$result = dbDelta( $sql, true ); // execute = true
		
		/*
		*
		* if not present add a full text index to post_title on wp_post table
		*
		*/
		$table = $wpdb->posts;		
		$sql = "SHOW INDEXES IN $table";
		$result = $wpdb->get_results ( $sql );
		$ok_index = false;
		// look for a full text index with post_title in first position
		foreach ( $result as $index_component ) {
			if ( 	'post_title' 	== $index_component->Column_name &&
					'FULLTEXT' 		== $index_component->Index_type &&
					1 					== $index_component->Seq_in_index	
				) {
					$ok_index = true;
					break;				
				}		
		}
		// if not found, create desired index
		if ( ! $ok_index )  {
			$sql = "CREATE FULLTEXT INDEX wp_issues_crm_post_title ON $table ( post_title )";		
			$wpdb->query ( $sql );
		}
		
		// install or upgrade data dictionary and field groups
		
		// first purge all except custom fields
		$dictionary = $wpdb->prefix . 'wic_data_dictionary';
		$sql = "DELETE FROM $dictionary WHERE  left( field_slug, 13 ) != 'custom_field_'";
		$outcome1 = $wpdb->query ( $sql );
		
		// lose field groups -- user never touches this
		$field_groups = $wpdb->prefix . 'wic_form_field_groups';
		$sql = "TRUNCATE TABLE $field_groups";
		$outcome2 = $wpdb->query ( $sql );
		
		// insert new (non-custom) dictionary records and all field group records
		$outcome3 = self::execute_file_sql ( 'wic_data_dictionary_and_field_groups' );

		// populate interface table if not populated, otherwise don't touch it
		$interface = $wpdb->prefix . 'wic_interface'; 
		$sql = "SELECT upload_field_name from $interface LIMIT 0, 1";
		$results = $wpdb->get_results ( $sql );
		if ( 0 == count ( $results ) ) {
			$outcome4 = self::execute_file_sql ( 'wic_interface_table' );
		}

		// install base version of option groups if first install
		if ( false === $installed_version ) {
			$outcome5 = self::execute_file_sql ( 'wic_option_groups_and_options' );	
		}

		// define table names for  use in next few steps
		$option_group = $wpdb->prefix . 'wic_option_group'; 
		$option_value = $wpdb->prefix . 'wic_option_value';
	
		// add references to parent_option_group_slug based on option_group_id in option_table  
		// for early versions, did not include parent_option_group_slug in database
		// for 2.2 and higher, it is included in the initial set up
		if ( false === $installed_version || $installed_version < '2.2' ) {		
			$sql = "UPDATE $option_value v INNER JOIN $option_group g ON v.option_group_id = g.ID SET v.parent_option_group_slug = g.option_group_slug";
			$outcome6 = $wpdb->query ( $sql );
			// also, make a couple of option groups system reserved that were originally left to user
			$sql = "UPDATE $option_group SET is_system_reserved = 1 WHERE option_group_slug = 'count_to_ten' OR option_group_slug = 'capability_levels'";
			$outcome6A = $wpdb->query ( $sql );
		}
		
		// add ons in version 2.2
		if ( false === $installed_version || $installed_version < '2.2' ) {
			$outcome7 = self::execute_file_sql ( 'wic_option_groups_and_options_upgrade_001' );					
		}
	
		/*
		* back fill option_group_id based on parent_option_group_slug for upgrades to option table 
		*  -- note that this query will include any groups user added between upgrades, since parent_option_group_slug not maintained elsewhere 
		*		
		* parent_option_group_slug is used only in the upgrade process (and one hard_coded reference in class-wic-db-access-dictionary.php)
		*
		* this step must run after all upgrades/additions to option_value and option_group
		*
		* it maintains integrity of multivalue logic (which relies on ID) while allowing upgrade 
		* to make additions to both option_group table and option_value table with indeterminate option_group_id's
		*
		* insert statements to option_value in upgrades should include parent_option_group_slug, but not option_group_id
		*/
		$sql = "UPDATE $option_value v INNER JOIN $option_group g ON v.parent_option_group_slug = g.option_group_slug 
			SET v.option_group_id = g.ID where option_group_id = '' ";
		$outcomex = $wpdb->query ( $sql );

		// always finish by marking version change
		if ( false !== $installed_version ) {
			update_option( 'wp_issues_crm_db_version', $wp_issues_crm_db_version );		
		} else {
			add_option( 'wp_issues_crm_db_version', $wp_issues_crm_db_version );
		}
		
	}
	
	public static function update_db_check () { 

		global $wp_issues_crm_db_version; // see wp-issues-crm.php

		// check if database up to date; if not, run setup 
		// single version check covers database and dictionary -- unlikely that increase churn by combining
		$installed_version = get_option( 'wp_issues_crm_db_version');
		if ( $wp_issues_crm_db_version != $installed_version ) { // returns false if absent, so also triggered on first run
			self::database_setup();
		}

	}	
	
	public static function execute_file_sql ( $file_name ) {		

		global $wpdb;		
		
		// load the table set up sql 
		$sql = file_get_contents( plugin_dir_path ( __FILE__ ) . '../../sql/' . $file_name . '.sql' );
		$sql = self::site_table_names_in_sql ( $sql );
		
		// execute statements one by one
		$sql_array = explode ( ';', $sql );
		$outcome = true;
		foreach ( $sql_array as $sql_statement ) {
			$result = $wpdb->query ( $sql_statement );
			if ( false === $result ) {
				$outcome = false;			
			}
		}

		return ( $outcome );
		
	}
	
	// replace standard prefix with possible site table prefix
	private static function site_table_names_in_sql ( $sql ) {
		
		global $wpdb;

		// set up name conversion table		
		$table_name_array = array (
			array ( 'wp_wic_activity'		, 	$wpdb->prefix . 'wic_activity' 	),
			array ( 'wp_wic_address'		, 	$wpdb->prefix . 'wic_address'		),
			array ( 'wp_wic_constituent'	, 	$wpdb->prefix . 'wic_constituent'		),
			array ( 'wp_wic_data_dictionary'	,	$wpdb->prefix . 'wic_data_dictionary'		),
			array ( 'wp_wic_email'			, 	$wpdb->prefix . 'wic_email'		),
			array ( 'wp_wic_form_field_groups',$wpdb->prefix . 'wic_form_field_groups'		),
			array ( 'wp_wic_interface',	$wpdb->prefix . 'wic_interface'		),
			array ( 'wp_wic_option_group'	,	$wpdb->prefix . 'wic_option_group'		),
			array ( 'wp_wic_option_value'	,	$wpdb->prefix . 'wic_option_value'		),
			array ( 'wp_wic_phone'			,	$wpdb->prefix . 'wic_phone'		),
			array ( 'wp_wic_search_log'	,	$wpdb->prefix . 'wic_search_log'		),
			array ( 'wp_wic_upload'	,	$wpdb->prefix . 'wic_upload'		),
		);
		
		// convert table names to specific site's production environment
		foreach ( $table_name_array as $table_name ) {
			$sql = str_replace ( $table_name[0], $table_name[1], $sql );		
		}	
		
		return ( $sql );
	
	}	
	
}

