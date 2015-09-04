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

	public static function constituent_type_options (){
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

	// slot used by wic_entity_advanced_search_constituent
	protected function do_field_interaction_rules(){
		global $wic_db_dictionary;	
		$field =  $this->data_object_array['constituent_field']->get_value();
		if ( $field > '' ) {
			// set type options based on constituent field entity	
			$field_data = $wic_db_dictionary->get_field_rules_by_id( $field );
			$entity = $field_data['entity_slug']; 
			$this->data_object_array['constituent_entity_type']->set_options( $entity . '_type_options' );
			// set value control to look like as if in own entity
			$final_field_slug = $this->data_object_array['constituent_value']->get_args_field_slug( );						
			$this->data_object_array['constituent_value'] = WIC_Control_Factory::make_a_control( $field_data['field_type'] );
			$this->data_object_array['constituent_value']->initialize_default_values ( $field_data['entity_slug'], $field_data['field_slug'], '' );
			$this->data_object_array['constituent_value']->set_args_field_slug( $final_field_slug ); 
		}
	}

}