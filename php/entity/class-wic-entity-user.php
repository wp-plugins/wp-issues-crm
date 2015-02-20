<?php
/*
*
*	wic-entity-user.php
*
*/

class WIC_Entity_User extends WIC_Entity_Parent {
	
	public function __construct() {

		$this->set_entity_parms( '' );
		$args = array ();
		if ( ! isset ( $_POST['wic_form_button'] ) ) {
			$this->show_user_preferences();
		} else {
			$control_array = explode( ',', $_POST['wic_form_button'] ); 		
			$this->{$control_array[1]}( );
		}
	}
	
	/*
	*
	* Request handlers
	*
	*/

	protected function set_entity_parms( $args ) { // 
		// accepts args to comply with abstract function definition, but as a parent does not process them -- no instance
		$this->entity = 'user';
	} 

	// handle a search request for an ID coming from anywhere
	protected function show_user_preferences () {
		$id = get_current_user_id();
		$this->id_search_generic ( $id, 'WIC_Form_User_Update', '', false, false ); // no logging and no original query
		return;		
	}

	//handle an update request coming from an update form
	protected function form_update () {
		$this->form_save_update_generic ( false, 'WIC_Form_User_Update', 'WIC_Form_User_Update' );
		return;
	}
	
}