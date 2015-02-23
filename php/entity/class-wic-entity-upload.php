<?php
/*
*
*	wic-entity-upload.php
*
*/



class WIC_Entity_Upload extends WIC_Entity_Parent {

	protected function set_entity_parms( $args ) {
		$this->entity = 'upload';
		$this->entity_instance = '';
	} 
	
	public function __construct() {

		$this->set_entity_parms( '' );
		if ( ! isset ( $_POST['wic_form_button'] ) ) {
			$this->list_uploads();
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
	
	protected function list_uploads () {
		// table entry in the access factory will make this a standard WIC DB object
		$wic_query = 	WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		// pass a blank array to retrieve all uploads
		$wic_query->search ( array(), array( 'retrieve_limit' => 9999, 'show_deleted' => true, 'log_search' => false ) );
		$lister_class = 'WIC_List_' . $this->entity ;
		$lister = new $lister_class;
		$list = $lister->format_entity_list( $wic_query, '' ); 
		echo $list;
	}	
	
	// handle a request for a blank new upload form
	protected function new_blank_form() {
		$this->new_form_generic ( 'WIC_Form_Upload_Save' );	
	}
	
	//handle a save (upload) request coming from a save form
	protected function form_save () {
		$this->form_save_update_generic ( true, 'WIC_Form_Upload_Save', 'WIC_Form_Upload_Update' );
	return;
	}	
	
	//handle an update request coming from an update form
	protected function form_update () {
		$this->form_save_update_generic ( false, 'WIC_Form_Upload_Update', 'WIC_Form_Upload_Update' );
		return;
	}	
	
	// handle a search request for an ID coming from anywhere
	protected function id_search ( $args ) {
		$id = $args['id_requested']; 
		$this->id_search_generic ( $id, 'WIC_Form_Upload_Update', '' , false, false ); // no sql, but do log search as individual, no old search
		return;		
	}	
	
	// function from parent needs to be overridden to set name value from $_FILES array
	protected function populate_data_object_array_from_submitted_form() {

		$this->initialize_data_object_array();

		foreach ( $this->fields as $field ) {  	
			if ( isset ( $_POST[$field->field_slug] ) ) {		
				$this->data_object_array[$field->field_slug]->set_value( $_POST[$field->field_slug] );
			} elseif ( isset ( $_FILES[$field->field_slug]['name'] ) ) {
				$this->data_object_array[$field->field_slug]->set_value( $_FILES[$field->field_slug]['name'] );			
			}	
		} 
	}	
	
	// no special sanitize function for file name -- go with default -- sanitize strip slashes -- see notes in WIC_control_file
	
	// primary validation function for an incoming file is in control file

	// set values from initial save process to be visible on update form after save; on update, do nothing
	protected function special_entity_value_hook ( &$wic_access_object ) {
		if ( isset ( $wic_access_object->upload_time ) ) { // if one set, both set -- only set in access object on initial save		
			$this->data_object_array['upload_time']->set_value( $wic_access_object->upload_time );
			$this->data_object_array['upload_by']->set_value( $wic_access_object->upload_by );
		}		
	}	
	
	// function to sanitize value of upload_file (which will be saved file name)
	protected function sanitize_upload_file () {
		echo 'xxxxxxxxxxxxxxxxxxxx';	
	}
	
}

// validation errors

/* http://php.net/manual/en/features.file-upload.errors.php
<?php

class UploadException extends Exception
{
    public function __construct($code) {
        $message = $this->codeToMessage($code);
        parent::__construct($message, $code);
    }

    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;

            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }
}

// Use
if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
//uploading successfully done
} else {
throw new UploadException($_FILES['file']['error']);
} */