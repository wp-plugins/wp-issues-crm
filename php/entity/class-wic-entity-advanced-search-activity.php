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
	
}