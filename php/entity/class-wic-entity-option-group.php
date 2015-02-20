<?php
/*
*
*	wic-option-group.php
*
*/

class WIC_Entity_Option_Group extends WIC_Entity_Parent {
	
	public function __construct() {

		$this->set_entity_parms( '' );
		if ( ! isset ( $_POST['wic_form_button'] ) ) {
			$this->list_option_groups();
		} else {
			$control_array = explode( ',', $_POST['wic_form_button'] ); 		
			$args = array (
				'id_requested'			=>	$control_array[2],
				'instance'				=> '', // unnecessary in this context, absence will not create an error but here for consistency about arguments;
			);
			// note that control[0] is superfluous in admin context since page only serves a single entity class
			$this->{$control_array[1]}( $args );
		}
	}
	
	/*
	*
	* Request handlers
	*
	*/

	protected function set_entity_parms( $args ) { // 
		// accepts args to comply with abstract function definition, but as a parent does not process them -- no instance
		$this->entity = 'option_group';
	} 


	// handle a request for a blank new constituent form
	protected function new_option_group() {
		$this->new_form_generic ( 'WIC_Form_Option_Group_Save' );	
	}

	// handle a search request for an ID coming from anywhere
	protected function id_search ( $args ) {
		$id = $args['id_requested']; 
		$this->id_search_generic ( $id, 'WIC_Form_Option_Group_Update', '', false, false  ); // no logging and no old search ID
		return;		
	}

	//handle an update request coming from an update form
	protected function form_update () {
		$this->form_save_update_generic ( false, 'WIC_Form_Option_Group_Update', 'WIC_Form_Option_Group_Update' );
		return;
	}
	
	//handle a save request coming from a save form
	protected function form_save () {
		$this->form_save_update_generic ( true, 'WIC_Form_Option_Group_Save', 'WIC_Form_Option_Group_Update' );
		return;
	}
	
	// set values from update process to be visible on form after save or update
	protected function special_entity_value_hook ( &$wic_access_object ) {}
	
	public static function option_group_slug_sanitizor ( $raw_slug ) { 
		return ( preg_replace("/[^a-zA-Z0-9_]/", '', $raw_slug) ) ;
	}
	
	protected function list_option_groups () {
		// table entry in the access factory will make this a standard WIC DB object
		$wic_query = 	WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		// do simple search array to select those that are not system reserved option groups
		$meta_query_array  = array ( 
			array (
				"table"	=> "option_group",
				"key"		=> "is_system_reserved",
				"value"	=> 0,
				"compare"=> "=",
				"wp_query_parameter" => ""
			),
		);
		$wic_query->search ( $meta_query_array, array( 'retrieve_limit' => 9999, 'show_deleted' => true, 'log_search' => false ) );
		$lister_class = 'WIC_List_' . $this->entity ;
		$lister = new $lister_class;
		$list = $lister->format_entity_list( $wic_query, '' ); 
		echo $list;
	}
	
	public static function option_label_list_formatter ( $list ) {
		// sorts labels and replaces emptystring with the word BLANK
		$label_array = explode ( ',', $list );
		
		sort( $label_array );

		if ( count( $label_array ) > 0 ) {	
			for ( $i = 0; $i < count ( $label_array); $i++ )  {
				$label_array[$i] =  '' == trim( $label_array[$i] )  ? 'BLANK' : trim($label_array[$i]);		
			}
		}

		return ( implode ( '|', $label_array ) );
	}
	
}