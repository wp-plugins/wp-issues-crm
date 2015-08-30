<?php
/*
*
*	wic-entity-advanced-search-constituent.php
*
*/



class WIC_Entity_Advanced_Search_Constituent extends WIC_Entity_Multivalue {

	protected function set_entity_parms( $args ) {
		extract ( $args );
		$this->entity = 'advanced_search_constituent';
		$this->entity_instance = $instance;
	} 

	public static function constituent_fields () {
		global $wic_db_dictionary;
		return( $wic_db_dictionary->get_search_field_options( 'constituent' ) );
	}

	public static function constituent_field_types (){
		return ( array ( array ( 'value' => '', 'label' => 'Type is N/A' ) ) );	
	}

	// in lieu of sanitize text field default sanitizor, test that in option values
	// sanitize text field replaces <= operators
	public static function constituent_comparison_sanitizor ( $incoming ) {
		global $wic_db_dictionary;
		$valid_array = $wic_db_dictionary->lookup_option_values( 'advanced_search_comparisons' );
		foreach ( $valid_array as $option_value_pair ) {
			if ( $incoming == $option_value_pair['value'] ) {
				return ( $incoming );			
			}		
		} 	
		return ('=');
	}

}