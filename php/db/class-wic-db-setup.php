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
	* 	this function takes sql exported from development environment  
	*	prepares it for production use with appropriate site variables
	*	runs db delta to install it
	*
	*	manual steps in package preparation of export from development
	*		set without autoincrement settings, without 'if not exists' and without back ticks (uncheck all object creation options)
	*		remove all statements other than the create table statements
	*
	*	note: all wic_issues_crm tables use the utf8 character set and utf8_general_ci collation  
	*
	*
	*	note: version globals set in wp-issues-crm.php
	*
	*********************************************************************************************/
	private static function database_setup() { 

		global $wp_issues_crm_db_version; // see wp-issues-crm.php 
		
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
		
		if ( false !== get_option ( 'wp_issues_crm_db_version' ) ) {
			update_option( 'wp_issues_crm_db_version', $wp_issues_crm_db_version );		
		} else {
			add_option( 'wp_issues_crm_db_version', $wp_issues_crm_db_version );
		}
		
	}
	
	public static function update_db_check () { 

		global $wp_issues_crm_db_version; // see wp-issues-crm.php
		global $wp_issues_crm_dictionary_version; // see wp-issues-crm.php

		// check if database up to date; if not, run setup 
		$installed_version = get_option( 'wp_issues_crm_db_version');
		if ( $wp_issues_crm_db_version != $installed_version ) { // returns false if absent, so also triggered on first run
			self::database_setup();
		}

		// check if dictionary is populated -- if not, install -- assuming either a fresh installation . . .
		// or a reset of all options fields (i.e., truncate dictionary, field_groups, option_group, option_value )
		// note that if tables dropped, not truncated, must reset version options to trigger database reinstall
		global $wpdb;
		$table = $wpdb->prefix . 'wic_data_dictionary';
		$test = $wpdb->get_results ( "SELECT ID FROM $table LIMIT 0, 1 " );
		if ( ! isset( $test[0]->ID ) ) {
			self::dictionary_install();
		} else {  // if already populated, run upgrade script if necessary
			$dictionary_version = get_option( 'wp_issues_crm_dictionary_version' );
			if ( $wp_issues_crm_dictionary_version != $dictionary_version ) {
				self::dictionary_upgrade();
			} 		
		}
	}	
	
	// run script to load dictionary data
	private static function dictionary_install() {
	
		global $wp_issues_crm_dictionary_version;  // see wp-issues-crm.php
		global $wpdb;		
		
		// load the table set up sql 
		$sql = file_get_contents( plugin_dir_path ( __FILE__ ) . '../../sql/wic_data_dictionary_and_options.sql' );
		$sql = self::site_table_names_in_sql ( $sql );
		
		// execute statements one by one
		$sql_array = explode ( ';', $sql );
		foreach ( $sql_array as $sql_statement ) {
			$wpdb->query ( $sql_statement );
		}
		
		if ( false !== get_option ( 'wp_issues_crm_dictionary_version' ) ) {
			update_option( 'wp_issues_crm_dictionary_version', $wp_issues_crm_dictionary_version );		
		} else {
			add_option( 'wp_issues_crm_dictionary_version', $wp_issues_crm_dictionary_version );
		}
	
	}

	// run cumulative script to add/modify dictionary
	private static function dictionary_upgrade() {
		// dictionary upgrades may become necessary -- 
		// sql here must be written to be cumulative, executable independent of intermediary versions
		// must write to handle specific changes -- check if option exists, if not, add . . .
		global $wp_issues_crm_dictionary_version;  // see wp-issues-crm.php

		// . . . insert dictionary upgrade script here when and if necessary
		
		update_option( 'wp_issues_crm_dictionary_version', $wp_issues_crm_dictionary_version );		
	}
	
	// replace standard prefix with possible site table prefix
	private static function site_table_names_in_sql ( $sql ) {
		
		global $wpdb;

		// set up name conversion table		
		$table_name_array = array (
			array ( 'wp_wic_activity'		, 	$wpdb->prefix . 'wic_activity' 	),
			array ( 'wp_wic_address'		, 	$wpdb->prefix . 'wic_address'		),
			array ( 'wp_wic_constituent'	, 	$wpdb->prefix . 'wic_constituent'		),
			array ( 'wp_wic_constituent'	,	$wpdb->prefix . 'wic_constituent'		),
			array ( 'wp_wic_email'			, 	$wpdb->prefix . 'wic_email'		),
			array ( 'wp_wic_form_field_groups',$wpdb->prefix . 'wic_form_field_groups'		),
			array ( 'wp_wic_option_group'	,	$wpdb->prefix . 'wic_option_group'		),
			array ( 'wp_wic_option_value'	,	$wpdb->prefix . 'wic_option_value'		),
			array ( 'wp_wic_phone'			,	$wpdb->prefix . 'wic_phone'		),
			array ( 'wp_wic_search_log'	,	$wpdb->prefix . 'wic_search_log'		),
		);
		
		// convert table names to specific site's production environment
		foreach ( $table_name_array as $table_name ) {
			$sql = str_replace ( $table_name[0], $table_name[1], $sql );		
		}	
		
		return ( $sql );
	
	}	
	
}

