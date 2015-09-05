<?php
/*
*
*	wic-entity-advanced-search-activity.php
*
*/



class WIC_Entity_Advanced_Search_Activity extends WIC_Entity_Multivalue {

	protected function set_entity_parms( $args ) {
		extract ( $args );
		$this->entity = 'advanced_search_activity';
		$this->entity_instance = $instance;
	} 
	
	public static function activity_fields () {
		global $wic_db_dictionary;
		return( $wic_db_dictionary->get_search_field_options( 'activity' ) );
	}
	
	public static function activity_comparison_sanitizor ( $incoming ) {
		return ( WIC_Entity_Advanced_Search_Constituent::constituent_comparison_sanitizor ( $incoming ) );	
	}	
	
	public static function activity_value_sanitizor ( $incoming ) {
		if ( is_array ($incoming) ) {
			foreach ( $incoming as $key => $value ) {
				if ( $value != absint( $value ) ) {
					WIC_Function_Utilities::wic_error ( sprintf ( 'Invalid value for multiselect field %s', $this->field->field_slug ) , __FILE__, __LINE__, __METHOD__,true );
				}	
			}
			return ( $incoming );			
		} else {
			return ( sanitize_text_field ( stripslashes ( $incoming ) ) );
		}	
	}	

	// swap in multivalue control if have issue selection in category array form 	
	protected function do_control_replace_rules( $field_slug, $value){
		if ( is_array( $value ) ) {
			echo "<br/> $field_slug points to: "; var_dump ( $value );
			$control = WIC_Control_Factory::make_a_control ( 'multiselect' );
			$control->initialize_overriden_default_values (  'issue', 'post_category', $this->entity_instance , $this->entity, $field_slug  ) ;
			$this->data_object_array['field_slug'] = $control;
		} 
	}	
	
	
}