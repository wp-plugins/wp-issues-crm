<?php
/*
*
*	wic-constituent.php
*
*
*/

class WIC_Entity_Constituent extends WIC_Entity_Parent {
	
	/*
	*
	* Request handlers
	*
	*/

	protected function set_entity_parms( $args ) { // 
		// accepts args to comply with abstract function definition, but as a parent does not process them -- no instance
		$this->entity = 'constituent';
	} 

	// handle a request for a new search form
	protected function new_form() { 
		$this->new_form_generic( 'WIC_Form_Constituent_Search',  __( 'If constituent not found, you will be able to save.', 'wp-issues-crm') );
		return;
	}

	// show a constituent save form using values from a completed search form (search again)
	protected function save_from_search_request() { 
		parent::save_from_search ( 'WIC_Form_Constituent_Save',  $message = '', $message_level = 'good_news', $sql = '' );	
	}

	// handle a request for a blank new constituent form
	protected function new_blank_form() {
		$this->new_form_generic ( 'WIC_Form_Constituent_Save' );	
	}

	// handle a search request coming from a completed search ( or search again ) form
	protected function form_search () { // show new search if not found, otherwise show update (or list)
		$this->form_search_generic ( 'WIC_Form_Constituent_Search_Again', 'WIC_Form_Constituent_Update');
		return;				
	}
	
	// handle a search request for an ID coming from anywhere
	protected function id_search ( $args ) {
		$id = $args['id_requested']; 
		$this->id_search_generic ( $id, 'WIC_Form_Constituent_Update', '' , true, false ); // no sql, but do log search as individual, no old search
		return;		
	}
	
	// same as above, no search log
	protected function id_search_no_log ( $args ) {
		$id = $args['id_requested']; 
		$old_search_id = isset ( $args['old_search_ID'] ) ? $args['old_search_ID'] : false;
		$this->id_search_generic ( $id, 'WIC_Form_Constituent_Update', '' , false, $old_search_id ); // no search log but carry old search if known   
		return;		
	}


	//handle an update request coming from an update form
	protected function form_update () {
		$this->form_save_update_generic ( false, 'WIC_Form_Constituent_Update', 'WIC_Form_Constituent_Update' );
		return;
	}
	
	//handle a save request coming from a save form
	protected function form_save () {
		$this->form_save_update_generic ( true, 'WIC_Form_Constituent_Save', 'WIC_Form_Constituent_Update' );
		return;
	}

	//handle a search request coming search log (buttons with search ID go to search log first)
	protected function redo_search_from_query ( $search ) {  
		$this->redo_search_from_meta_query ( $search, 'WIC_Form_Constituent_Save', 'WIC_Form_Constituent_Update' );
		return;
	}		
	
	// handle a request to return to a search form
	protected function redo_search_form_from_query ( $search ) { 
		$this->search_form_from_search_array ( 'WIC_Form_Constituent_Search',  __( 'Redo search.', 'wp-issues-crm'), $search );
		return;
	}

	// set values from update process to be visible on form after save or update
	protected function special_entity_value_hook ( &$wic_access_object ) {
		$this->data_object_array['last_updated_time']->set_value( $wic_access_object->last_updated_time );
		$this->data_object_array['last_updated_by']->set_value( $wic_access_object->last_updated_by );		
	}
	
	public function get_the_title () {
		return ( WIC_Form_Constituent_Update::format_name_for_title ( $this->data_object_array ) ) ;	
	}	
	/***************************************************************************
	*
	* Constituent -- special formatters and validators
	*
	****************************************************************************/ 	


	// note: since phone is multivalue, and formatter is not invoked in the 
	// WIC_Control_Multivalue class (rather at the child entity level), 
	// this function is only invoked in the list context
	public static function phone_formatter ( $phone_list ) {
		$phone_array = explode ( ',', $phone_list );
		$formatted_phone_array = array();
		foreach ( $phone_array as $phone ) {
			$formatted_phone_array[] = WIC_Entity_Phone::phone_number_formatter ( $phone );		
		}
		return ( implode ( '<br />', $formatted_phone_array ) );
	}
	
	public static function email_formatter ( $email_list ) {
		$email_array = explode ( ',', $email_list );
		$clean_email_array = array();
		foreach ( $email_array as $email ) {
			$clean_email_array[] = esc_html ( $email );		
		}
		return ( implode ( '<br />', $clean_email_array ) );
	}		

	public static function mark_deleted_validator ( $value ) {
		if ( $value > '' && trim( strtolower( $value ) ) != 'deleted' ) {
			return __( 'To hide this record from future searches, type the full word "DELETED" into Mark Deleted and then Update.', 'wp-issues-crm');		
		}
	}	
	
}