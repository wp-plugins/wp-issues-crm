<?php
/*
*
*	wic-entity-multivalue.php
*
*/



abstract class WIC_Entity_Multivalue extends WIC_Entity_Parent {

	protected function initialize() {
		
		global $wic_db_dictionary;


		$this->fields = $wic_db_dictionary->get_form_fields( $this->entity );
		$this->initialize_data_object_array();
	}
	
	protected function populate_from_form( $args ) {
		extract( $args );
		// expects form_row_array among args; 
		// instance also present, but has already been processed in __construct
		// here, just getting values from form array 
		// note that row numbering may not synch between $_POST and the multivalue array 
		$this->initialize();
		foreach ($this->fields as $field ) {
			if ( isset ( $form_row_array[$field->field_slug] ) ) {
				$this->data_object_array[$field->field_slug]->set_value( $form_row_array[$field->field_slug] );
			}
		}
	}

	protected function populate_from_object( $args ) {
		extract( $args );
		$this->initialize();
		foreach ( $this->fields as $field ) {
			if ( ! $this->data_object_array[$field->field_slug]->is_transient() ) {
				$this->data_object_array[$field->field_slug]->set_value( $form_row_object->{$field->field_slug} );
			}
		}
	}

	public function search_row() {
		$new_search_row_object = new WIC_Form_Multivalue_Search ( $this->entity );
		$new_search_row = $new_search_row_object->layout_form ( $this->data_object_array, null, null);
		return $new_search_row;
	}

	public function update_row() {
		$new_update_row_object = new WIC_Form_Multivalue_Update ( $this->entity, $this->entity_instance );
		$new_update_row = $new_update_row_object->layout_form( $this->data_object_array, null, null );
		return $new_update_row;
	}
	
	public function save_row() {
		$new_save_row_object = new WIC_Form_Multivalue_Save ( $this->entity, $this->entity_instance );
		$new_save_row = $new_save_row_object->layout_form( $this->data_object_array, null, null );
		return $new_save_row;
	}

	public function do_save_update( $parent_slug, $id ) {
		$parent_link_field = $parent_slug . '_' . 'id';
		$this->data_object_array[$parent_link_field]->set_value( $id );
		$wic_access_object = WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		$wic_access_object->save_update( $this->data_object_array ); 
		if ( false === $wic_access_object->outcome ) {
			$error =  $wic_access_object->explanation . ' Error reported by class WIC_Entity_Multivalue. ';
		} else {
			$error = '';
			if ( '' == $this->data_object_array['ID']->get_value() ) { // then just did a save, so . . .
				$this->data_object_array['ID']->set_value( $wic_access_object->insert_id );
			}
			// if update was successful (possibly no update, but not an error), record changes as made to this entity
			// this will be aggregated to changes made for any row in the control
			$this->made_changes = $wic_access_object->were_changes_made();
		}		
		return ( $error );
	}
}