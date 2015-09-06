<?php
/*
*
*	wic-entity-advanced-search-constituent-having.php
*
*/



class WIC_Entity_Advanced_Search_Constituent_Having extends WIC_Entity_Multivalue {

	protected function set_entity_parms( $args ) {
		extract ( $args );
		$this->entity = 'advanced_search_constituent_having';
		$this->entity_instance = $instance;
	} 

	public static function constituent_having_fields () {

		global $wic_db_dictionary;
		
		// get activity fields
		$all_activity_fields =  $wic_db_dictionary->get_search_field_options( 'activity' );
		
		// strip out inappropriate for aggregation
		$having_fields = array();	
		foreach ( $all_activity_fields as $field ) { 
			if ( 	false === strpos ( $field['label'], 'activity:issue' ) && 
					false === strpos ( $field['label'], 'activity:activity_note' ) &&
					false === strpos ( $field['label'], 'activity:activity_type' ) &&
					false === strpos ( $field['label'], 'activity:last_updated_by' )					
				 )  {
				$having_fields[] = $field;			
			}
		}
		
		return ( $having_fields );
	}

	public static function advanced_search_having_comparisons() {
		
		global $wic_db_dictionary;		
		
		// get comparison options
		$all_comparison_fields =  $wic_db_dictionary->lookup_option_values( 'advanced_search_comparisons' );
		
		// strip out comparison inappropriate for aggregate amounts (keep =, >= and <=) 
		$having_comparison_options = array();	
		foreach ( $all_comparison_fields as $field ) { 
			if ( 	false !== strpos( '>=<=', $field['value'] ) ) {
				$having_comparison_options[] = $field;			
			}
		}
		
		return ( $having_comparison_options );
	}

	public static function constituent_having_comparison_sanitizor ( $incoming ) { 
		return ( WIC_Entity_Advanced_Search_Constituent::constituent_comparison_sanitizor ( $incoming ) );	
	}	



}