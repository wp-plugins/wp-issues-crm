<?php
/*
*
*	wic-entity-option-value.php
*
*/



class WIC_Entity_Upload extends WIC_Entity_Parent {

	protected function set_entity_parms( $args ) {
		extract ( $args );
		$this->entity = 'upload';
		$this->entity_instance = $instance;
	} 

	public static function option_value_sanitizor ( $raw_slug ) { 
		return ( preg_replace("/[^a-zA-Z0-9_]/", '', $raw_slug) ) ;
	}
	
}