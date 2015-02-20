<?php
/*
*
* class-wic-db-access-dictionary.php
*		supports access to the dictionary for editing of the dictionary
*
*/

class WIC_DB_Access_Dictionary Extends WIC_DB_Access_WIC {
	
	public $field_slug;

	// function adds a field to constituent table and then feeds field_name to data dictionary save array
	protected function external_update ( $set ) {

		global $wpdb;	
		
		// check used custom field names on constituent table
		$table1 = $wpdb->prefix . 'wic_constituent';
		$table_rows = $wpdb->get_results (
			"
			SELECT * FROM $table1 WHERE 1
			LIMIT 0, 1		
			", ARRAY_A
		);	
		$column_names = array_keys ( $table_rows[0] );
		$filtered_column_names = array_filter ( $column_names, array ( $this, 'filter_custom_columns' ) );
		
		// determine new field name 
		if ( count ( $filtered_column_names ) > 0 ) {
			rsort ( $filtered_column_names );
			$last_custom = (int) substr( $filtered_column_names[0], 13, 3);
			$new_field_name = 'custom_field_' . sprintf("%03d", $last_custom + 1 );
		 } else {
			$new_field_name = 'custom_field_001';		 
		 }

		// set property to be picked up in wic-entity-data-dictionary->special_entity_value_hook
		$this->field_slug = $new_field_name;
		// update database
		$wpdb->query ( 
			"
			ALTER TABLE $table1 ADD $new_field_name VARCHAR( 255 ) NOT NULL ,
			ADD INDEX ( $new_field_name ) ;
			"		
			);
		// die on error		
		if ( $wpdb->last_error > '' ) {
			WIC_Function_Utilities::wic_error ( sprintf( 'MySQL could not add custom field. Error reported was: %s', $wpdb->last_error ), __FILE__, __LINE__, __METHOD__, true );
		// other wise proceed to do set up save to data dictionary		
		} else {		
			// add to set clause with placeholders
			$set['set_clause_with_placeholders'] .= ', field_slug = %s, entity_slug = %s, customizable = %d';
			// add to set value array		
			array_push ( $set['set_value_array'], $new_field_name );	
			array_push ( $set['set_value_array'], 'constituent' );
			array_push ( $set['set_value_array'], 1 );			
			return ( $set );
		}	
	}	

	private function filter_custom_columns ($value) {
		return ( 'custom_field_' == substr ( $value, 0, 13 ) ); 
	}	
	
	// return customizable fields from form layout
	public static function get_current_customizable_form_field_layout ( $entity ) {
		global $wpdb;
		$table1 = $wpdb->prefix . 'wic_form_field_groups';
		$table2 = $wpdb->prefix . 'wic_option_value';			
		$table3 = $wpdb->prefix . 'wic_data_dictionary';
		
		$sql = "
			select group_label, field_label, field_order
			from $table1 g 
			left join $table2 v on v.option_value = g.group_slug
			left join $table3 d on g.group_slug = d.group_slug 
			where g.entity_slug = '$entity' and v.option_group_id = 20 
			order by sidebar_location, group_order, field_order
			";
		
		$form_fields = $wpdb->get_results( $sql );
		
		return ( $form_fields );
	}	
	
	// return tables and fields that are using a given option group	
	public static function get_current_fields_using_option_group ( $option_group ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wic_data_dictionary';
		
		$sql = $wpdb->prepare ( 
			"
			select entity_slug, field_slug, field_label
			from $table 
			where option_group = %s
			",
			array ( $option_group )
			);
		
		$fields_using_option = $wpdb->get_results( $sql );
		
		return ( $fields_using_option );
	}	
	
	protected function db_get_option_value_counts( $field_slug ) {} // not implemented for dictionary
	
}

