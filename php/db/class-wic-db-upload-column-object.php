<?php
/*
* class-wic-db-upload-column-object.php
*	supports retrieval of lists in format like $wpdb output
*
*/

class WIC_DB_Upload_Column_Object {
	
	public $entity;
	public $field;
	public $non_empty_count;
	public $valid_count;
	
	public function __construct ( $entity, $field, $non_empty_count = 0, $valid_count = 0) {
		$this->entity = $entity;
		$this->field = $field;
		$this->non_empty_count = $non_empty_count;
		$this->valid_count = $valid_count;	
	}

}