<?php
/*
* 
* class-wic-control-file.php
*
*
*/

class WIC_Control_File extends WIC_Control_Parent {
	
	
	public static function create_control ( $args ) {
		$args['type'] = 'file';
		return ( ' <input type="hidden" name="MAX_FILE_SIZE" value="30000000" />' . parent::create_control ($args) );
	}
}

