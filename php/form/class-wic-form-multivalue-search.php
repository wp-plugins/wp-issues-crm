<?php
/*
*
*  class-wic-form-multivalue-search.php
*
*	this form is for an instance of a multivalue field -- a row (or rows, if multiple groups) of controls, not a full form
*	  
*	entity in this context is the entity that the multivalue field may contain several instances of 
*   -- this form generator doesn't need to know the instance value; 
* 	 -- the control objects within each row know which row they are implementing
*
*/

class WIC_Form_Multivalue_Search extends WIC_Form_Parent  {
	
	
	protected $entity = '';
	
	public function __construct ( $entity ) {
		$this->entity = $entity; 
	}
	
	protected function get_the_entity() {
		return ( $this->entity );	
	}
	
	protected function get_the_formatted_control ( $control ) {
		$args = array();
		return ( $control->search_control( $args ) ); 
	}
	protected function get_the_legends( $sql = '' ) {}	
	
	public function layout_form ( &$data_array, $message, $message_level, $sql = '' ) { 
		global $wic_db_dictionary;
		$groups = $this->get_the_groups();
		$search_row = '<div id="wic-multivalue-block" class = "wic-multivalue-control-set">';
			foreach ( $groups as $group ) {
				 $search_row .= '<div class = "wic-multivalue-field-subgroup" id = "wic-field-subgroup-' . esc_attr( $group->group_slug ) . '">';
						$group_fields = $wic_db_dictionary->get_fields_for_group ( $this->get_the_entity(), $group->group_slug );
						$search_row .= $this->the_controls ( $group_fields, $data_array )
					. '</div>';
			} 
		$search_row .= '</div>';
		return $search_row;
	}
	
	protected function the_controls ( $fields, &$data_array ) {
		$controls = '';
		foreach ( $fields as $field ) {
			$controls .= $this->get_the_formatted_control ( $data_array[$field] );
		}
		return $controls;
	}
	
	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function get_the_buttons( &$data_array ){}
	protected function format_message ( &$data_array, $message ) {}	
	protected function group_special( $group ) {}
	protected function group_screen ( $group ) {}
	protected function pre_button_messaging ( &$data_array ){}
   protected function post_form_hook ( &$data_array ) {} 
}