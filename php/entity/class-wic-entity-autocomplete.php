<?php
/*
*
*	wic-entity-autocomplete.php 
*  psuedo entity to pass through Ajax calls to specialized db access for fast autocompletes
*  
*  used to maintain consistency in autocomplete calls with two wp-issues-crm standards
*		Ajax calls are to an entity class
*     DB calls are in a db class 
*
*  could meet these standards loading more complex multipurpose objects, but for maximum speed in ajax response, keep it simple
*/

class WIC_Entity_Autocomplete  {

	// note that look_up_mode is being passed as "id requested" in class-wic-admin-ajax.php -- term preserved
	public static function db_pass_through( $look_up_mode, $term ) { 
		WIC_DB_Autocomplete::do_autocomplete ( $look_up_mode, sanitize_text_field( json_decode ( stripslashes( $term ) ) ) ); 
	}


}