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
	
	// note that in the absence of specific sanitization function for the control, parent does: sanitize_text_field ( stripslashes ( ) )
	// this may be excessive for some file names, but no harm as file name is just a memo field, not even used for deduping or restart
	// also is truncated to 255 as is varchar 255 field
	
	// put validation logic here -- format of parent validator is to pass a value to entity level validator while there is no value to pass
	// the validation logic offered is about any file control, not about the upload file entity

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

		// does this at least purport to be a csv file ?
		if ( 'csv' != pathinfo( $this->value, PATHINFO_EXTENSION) ) {
			$validation_error = __( 'This upload function only accepts .csv files.', 'wp-issues-crm' );
			return ( $validation_error );
		}

		// check file size > 0 
		if ( $upload_file_array["size"]  == 0) {
      	$validation_error = __( 'File uploaded shows as having size 0.', 'wp-issues-crm' );
      	return ( $validation_error );
		} 

		// take a closer look
		$handle = fopen( $upload_file_array['tmp_name'], 'rb' );
		
		// abort if can't open the file
		if ( ! $handle ) {
			$validation_error .= __( 'Error opening uploaded file', 'wp-issues-crm' );		
			return ( $validation_error );
		}
	
		// does it really act like a csv file?
  	   $data = fgetcsv( $handle, 10000, ',' ); // taking comma as delimiter can also specify enclosure and escape -- defaults are:  '"', "\" );
  	   		// setting high maximum line length, mainly to catch condition where no recognized line breaks
      if ( false === $data ) {
			$validation_error = __( 'File uploaded and opened, but unable to read file as csv. ', 'wp-issues-crm' );		
			return ( $validation_error );
		} elseif (  count( $data ) < 2 ) {      	
			$validation_error = __( 'File appears to have zero or one columns, possible error in delimiter definition.', 'wp-issues-crm' );		
			return ( $validation_error );
      }
      
      // check for consistent column count
      $count = count ( $data );
      $row_count = 1;
      while ( ($data = fgetcsv($handle, 10000, ",")) !== FALSE) {	
      	$row_count++;	
			if ( count ( $data ) != $count ) {
				$validation_error = sprintf ( __( 'File appears to have inconsistent column count.  
								First row had %1$d columns, but row %2$d had %3$d columns.', 'wp-issues-crm' ), 
								$count, $row_count, count ( $data ) );
				return ( $validation_error );						
			} 
      }

		// reject singleton row count
		if ( 1 == $row_count ) {
			$validation_error = __( 'File appears to have only one row, possible error in file creation.', 'wp-issues-crm' );					
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

