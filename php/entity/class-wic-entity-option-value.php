<?php
/*
*
*	wic-entity-option-value.php
*
*/



class WIC_Entity_Option_value extends WIC_Entity_Multivalue {

	protected function set_entity_parms( $args ) {
		extract ( $args );
		$this->entity = 'option_value';
		$this->entity_instance = $instance;
	} 

	public static function option_value_sanitizor ( $raw_slug ) { 
		return ( preg_replace("/[^a-zA-Z0-9_]/", '', $raw_slug) ) ;
	}
	
}