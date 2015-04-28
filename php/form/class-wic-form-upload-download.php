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
			// message only speaks to completion
			$message =  sprintf ( __( 'Download ' . $status_phrase  . ' input records from %s.' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
			?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php			

			// get totals
			$match_results 		= json_decode ( $data_array['serialized_match_results']->get_value() );
			$upload_parameters	= json_decode ( $data_array['serialized_upload_parameters']->get_value() );
			
			$valid_matched = 0;
			$valid_unique  = 0;
			$valid_dups		= 0;
			$unmatched_records_with_valid_components = 0;
			$validation_errors = ''; // set up as string initially -- if unknown, show as empty in string
			if ( $match_results > '' ) {
				foreach ( $match_results as $slug => $match_object  ) {
					$valid_matched += $match_object->matched_with_these_components;
					$valid_unique  += $match_object->unmatched_unique_values_of_components;	
					$valid_dups	   +=	$match_object->not_unique;
					$unmatched_records_with_valid_components += $match_object->unmatched_records_with_valid_components;			
				}	
			}

					
			// create array of downloads -- for each, button title, explanatory text and disabled -- true = disabled 
			$download_layout = 	array (
				'validate' 		=>		array ( 
					'Validation Errors', 
					__( 'Records that cannot be uploaded because of bad data or missing required data in one or more mapped fields. ', 'wp-issues-crm' ), 
					'staged' == $upload_status || 'mapped' == $upload_status,
				 ), 	// note: cannot show count of validation errors, because don't track and can't compute by backing out valid 
				 		// b/c don't have count of those valid but lacking match components ( which don't show in any of the $match_results numbers ) 
 				 'new_constituents'	=>		array (
					'New Constituents',
					sprintf ( __( 'Records with valid data that match no existing constituents.  
						WP Issues CRM dedups new records against each other and against existing records before upload.
						New records that match each other but not to any existing records are combined and uploaded.
						The count here ( %1$s ) may be reduced on upload as dups in this file are combined. ',
						'wp-issues-crm' ) , $unmatched_records_with_valid_components),				 
				 	'staged' == $upload_status || 'mapped' == $upload_status  || 'validated' == $upload_status || 0 == $unmatched_records_with_valid_components,
				 ), 
				 'match'			=>		array (
					'Matches',
					sprintf ( __( '%s record(s) matching to unique constituents on database based on one or more of matching criteria selected. ',
						'wp-issues-crm' )  , $valid_matched ),				 
				 	'staged' == $upload_status || 'mapped' == $upload_status  || 'validated' == $upload_status || 0 == $valid_matched,
				 ), 
 				 'bad_match'	=>		array (
					'Bad Match Errors',
					__( 'Records that lack match fields or match to multiple existing database records -- 
						will not be uploaded or used for update.  ', 'wp-issues-crm' ) , 				 
				 	'staged' == $upload_status || 'mapped' == $upload_status  || 'validated' == $upload_status ,
				 	// can't show count b/c  . . . same as invalid
				 ), 			
 				 'unmatched'	=>		array (
					'Need Attention',
					__( 'All records that have not been marked as matched.  This includes the records that could not be finally processed for any reason.  Input records with no validation errors 
						are marked as matched at the matching stage if they match to existing constituents. Apparent new constituents with no validation errors 
						are marked as matched to their new constituent records during the upload completion process.',
						 'wp-issues-crm' ) , 				 
				 	'completed' != $upload_status ,
				 ), 		
 				 'dump'	=>		array (
					'All Input',
					sprintf ( __( 'Dump all input records (%s) with all error and matching statuses . ', 'wp-issues-crm' ) , $upload_parameters->insert_count ),				 
				 	false
				 ), 			
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