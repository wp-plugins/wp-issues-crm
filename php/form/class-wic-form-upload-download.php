<?php
/*
* class-wic-form-upload-download.php
*
*
*/

class WIC_Form_Upload_Download extends WIC_Form_Upload_Validate  {  			
	
	public function layout_form ( &$data_array, $message, $message_level, $sql = '' ) {

		global $wic_db_dictionary;		
		
		echo $this->format_tab_titles( $data_array );		
		
		?><div id='wic-forms'> 
		
		<form id = "<?php echo $this->get_the_form_id(); ?>" <?php $this->supplemental_attributes(); ?> class="wic-post-form" method="POST" autocomplete = "on">

			<?php 
			$upload_status = $data_array['upload_status']->get_value();
			$status_phrase = ( 'completed' == $upload_status ) ? 'fully processed' : 'partially processed' ;					

			// form layout not dependent on upload status, but individual buttons will be enabled according to status
			$message =  sprintf ( __( 'Download ' . $status_phrase  . ' input records from %s.' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
			?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php			

			// get totals
			$match_results 		= json_decode ( $data_array['serialized_match_results']->get_value() );
			$upload_parameters	= json_decode ( $data_array['serialized_upload_parameters']->get_value() );
			
			$valid_matched = 0;
			$valid_unique  = 0;
			$valid_dups		= 0;
			$unmatched_records_with_valid_components = 0;
			foreach ( $match_results as $slug => $match_object  ) {
				$valid_matched += $match_object->matched_with_these_components;
				$valid_unique  += $match_object->unmatched_unique_values_of_components;	
				$valid_dups	   +=	$match_object->not_unique;
				$unmatched_records_with_valid_components += $match_object->unmatched_records_with_valid_components;			
			}	
			$validation_errors = $upload_parameters->insert_count - $unmatched_records_with_valid_components - $valid_matched - $valid_dups;			
			
			// create array of downloads -- for each, button title, explanatory text and disabled -- true = disabled 
			$download_layout = 	array (
				'validate' 		=>		array ( 
					'Validation Errors', 
					sprintf ( '%s records that cannot be uploaded because of bad data in one or more fields.  ' , $validation_errors ), 
					'staged' == $upload_status || 'mapped' == $upload_status || 0 == $validation_errors,
				 ),
 				 'new_constituents'	=>		array (
					'New Constituents',
					sprintf ( '%s records with valid data that match no existing constituents.  Will be grouped by unique identifier combinations and uploaded as new.', $unmatched_records_with_valid_components),				 
				 	'staged' == $upload_status || 'mapped' == $upload_status  || 'validated' == $upload_status || 0 == $unmatched_records_with_valid_components,
				 ), 
				 'match'			=>		array (
					'Matches',
					sprintf ( '%s records that match to single constituents on database -- will update those constituents if uploaded.  ' , $valid_matched ),				 
				 	'staged' == $upload_status || 'mapped' == $upload_status  || 'validated' == $upload_status || 0 == $valid_matched,
				 ), 
 				 'bad_match'	=>		array (
					'Bad Match Errors',
					sprintf ( '%s records that lack match fields or match to multiple existing database records -- will not be uploaded or used for update.  ', $valid_dups ),				 
				 	'staged' == $upload_status || 'mapped' == $upload_status  || 'validated' == $upload_status || 0 == $valid_dups  ,
				 ), 			
		
 				 'dump'	=>		array (
					'All Input Records',
					sprintf ( 'Dump all %s input records with all error and matching statuses.  ', $upload_parameters->insert_count ),				 
				 	false
				 ) 			
			); 

			echo '<div id = "upload-download-buttons">';

			foreach ( $download_layout as $button => $download ) {

				if ( ! $download[2] ) {
					$button_args_main = array(
						'entity_requested'			=> $upload_parameters->staging_table_name,
						'action_requested'			=> $button,
						'button_class'					=> 'button button-primary wic-form-button',
						'button_label'					=> __( $download[0], 'wp-issues-crm' ) ,
						'type'							=> 'submit',
						'id'								=> 'wic-staging-table-download-button',
						'name'							=> 'wic-staging-table-download-button',
					);	
					$button = $this->create_wic_form_button ( $button_args_main );
					echo $button;
					echo '<div class = "download-button-legend">' . $download[1] . '</div>';
				}
				
			}
			
			echo '</div>'; 	
			
  		
		   // in all cases, echo ID, serialized working fields, nonce
			echo $data_array['ID']->update_control();	
			echo $data_array['serialized_upload_parameters']->update_control();
			echo $data_array['serialized_column_map']->update_control();		
			echo $data_array['serialized_match_results']->update_control();	
			echo $data_array['serialized_default_decisions']->update_control();		
			echo $data_array['serialized_final_results']->update_control();	
		 	wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ); 
			echo $this->get_the_legends( $sql ); ?>							
		</form>
		<?php // child class may insert messaging here 
		$this->post_form_hook( $data_array ); ?>
		</div>
		
		<?php 
		
	}

	protected function get_the_legends( $sql = '' ) {
		// report configuration settings related to upload capacity;
		$legend = '<p>' .
					'</p>';
			
		$legend = '<div class = "wic-upload-legend">' . $legend . '</div>';		
		return $legend;
		
	}

	// group screen
	protected function group_screen( $group ) { }
	protected function get_the_formatted_control ( $control ) { return ( false ); }
	protected function group_special ( $group ) {}

	
	 	
}