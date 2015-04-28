<?php
/*
* 
* class-wic-control-file.php
*
*
*/

class WIC_Control_File extends WIC_Control_Parent {
	
	public function save_control () {
		$final_control_args = $this->default_control_args;
		$final_control_args['type'] = 'file';
		$final_control_args['value'] = '';
		$control = $this->create_control( $final_control_args );
		return ( $control );
	}
	
	public function update_control () { 
		$final_control_args = $this->default_control_args;
		$final_control_args['value'] = $this->value;
		$final_control_args['readonly'] = 1; // kludgey to put readonly value here, but would have to kludge the save control if put it in data dictionary
		$control = $this->create_control( $final_control_args );	
		return ( $control );
	}
	

	// the validation logic offered is about any file control, not about the upload file entity
	// see entity_upload for additional validation

	public function validate() { 
	
		// no validation of field on update, since nothing updateable -- don't want to return an error
		// will fail php error if no file uploaded on save
		if ( ! isset ( $_FILES[$this->field->field_slug] ) ) {
			return;
		} else {
			$upload_file_array = $_FILES[$this->field->field_slug];
		}	

		// any non-blank validation_error value (message) will trigger fail form	
		$validation_error = '';

		// first test for basic php upload errors
		$php_error = $upload_file_array['error'];
		if ( $php_error === UPLOAD_ERR_OK ) { 
			// no php errors
		} else {
			$validation_error =  $this->codeToMessage( $php_error );
			return ( $validation_error );
 		}

		// check that the file apparently uploaded in fact an uploaded file
		if ( ! is_uploaded_file ( $upload_file_array['tmp_name'] ) ) {
      	$validation_error = __( 'File identity violation -- working file not an uploaded file.', 'wp-issues-crm' );
      	return ( $validation_error );		
		}

		// check file size > 0 
		if ( $upload_file_array["size"]  == 0) {
      	$validation_error = __( 'File uploaded shows as having size 0.', 'wp-issues-crm' );
      	return ( $validation_error );
		} 

		return ( $validation_error );
	}
	
	// derived from http://php.net/manual/en/features.file-upload.errors.php
   private function codeToMessage( $code ) {
    	switch ( $code ) {
      	case UPLOAD_ERR_INI_SIZE:
         	$message = __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'wp-issues-crm' );
            break;
			case UPLOAD_ERR_FORM_SIZE: // we don't use this (lit says it does nothing on the client side)-- should be irrelevant
				$message = __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'wp-issues-crm' );
				break;
			case UPLOAD_ERR_PARTIAL:  // UPLOAD_ERR_PARTIAL is given when the mime boundary is not found after the file data.
				$message = __( 'The uploaded file was only partially uploaded.', 'wp-issues-crm' );
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = __( 'No file was uploaded.', 'wp-issues-crm' );
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = __( 'Missing a temporary folder.', 'wp-issues-crm' );
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = __( 'Failed to write file to disk.', 'wp-issues-crm' );
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = __( 'File upload stopped by extension.', 'wp-issues-crm' );
				break;
			default:
				$message = _( 'Unknown upload error.', 'wp-issues-crm' );
				break;
			}
      return $message;
    } 
	 
}

