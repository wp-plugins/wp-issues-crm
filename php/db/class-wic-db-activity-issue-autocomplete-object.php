<?php
/*
* class-wic-db-user-preferences-object.php
*	interface object for user preferences
*
*/
class WIC_DB_Activity_Issue_Autocomplete_Object {
	
	public $label;
	public $value;
	
	public function __construct ( $label, $value ) {
		$this->label = $label;
		$this->value = $value;
	}

}