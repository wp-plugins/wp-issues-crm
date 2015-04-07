<?php
/*
* class-wic-form-upload-complete.php
*
*
*/

class WIC_Form_Upload_Complete extends WIC_Form_Upload_Validate  {  			
	
	public function layout_form ( &$data_array, $message, $message_level, $sql = '' ) {

		global $wic_db_dictionary;		
		
		echo $this->format_tab_titles( $data_array );		
		
		?><div id='wic-forms'> 
		
		<form id = "<?php echo $this->get_the_form_id(); ?>" <?php $this->supplemental_attributes(); ?> class="wic-post-form" method="POST" autocomplete = "on">

			<?php // form layout driven by upload status
			$upload_status = $data_array['upload_status']->get_value();
			
			// file has not been mapped or has been remapped but not validated -- show error message		
			if ( 'defaulted' != $upload_status ) {			
				$message = __( 'Complete all prior steps before completing upload.', 'wp-issues-crm' );
				$message_level = 'error';
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
			// file has been mapped and validated and ready for test matching -- needs to be matched/rematched
			} elseif ( 'completed' != $upload_status ) { 
				// show message inviting match/rematch
				$message =  sprintf ( __( 'Ready to complete upload from %s.' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				// show validation start button (not a submit -- will drive AJAX)
				$disabled = ( 'completed' == $upload_status ); // belt and suspenders
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __('Complete Upload', 'wp-issues-crm'),
					'type'							=> 'button',
					'id'								=> 'upload-button',
					'disabled'						=> $disabled,
				);	
				$button = $this->create_wic_form_button ( $button_args_main );
				echo $button;
				$group_array = $this->generate_group_content_for_entity( $data_array );
				extract ( $group_array );
			
				// explain game plan
				echo '<div id = "upload-game-plan">';
				echo 	'<h3>' . __( 'What will happen:', 'wp-issues-crm' ) . '</h3>';
				echo  $this->summary_results( $data_array );
				echo '</div>';
				// place for progress bar -- ajax controlled; initial display none; results wrapper also filled by ajax
				echo '<div id = "wic-upload-progress-bar"></div>';
				echo '<div id = "upload-results-table-wrapper"></div>'; 	
			}
  		
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


	protected function summary_results ( &$data_array ) {
			// retrieve/compute file totals
		$upload_parameters 	= json_decode ( $data_array['serialized_upload_parameters']->get_value() ); 			
		$match_results 		= json_decode ( $data_array['serialized_match_results']->get_value() );
		$default_decisions 	= json_decode ( $data_array['serialized_default_decisions']->get_value() );
		$total_input = $upload_parameters->insert_count;
		$valid_matched = 0;
		$valid_unique  = 0;
		$valid_dups		= 0;
		foreach ( $match_results as $slug => $match_object  ) {
			$valid_matched += $match_object->matched_with_these_components;
			$valid_unique  += $match_object->unmatched_unique_values_of_components;	
			$valid_dups	   +=	$match_object->not_unique;			
		}								

		if ( $default_decisions->update_matched ) {
			$update_message = sprintf( __( 'WP Issues CRM will make %d updates to constituent records.	These updates may include multiple 
				updates to some constituents.', 'wp-issues-crm' ), $valid_matched ); 		
		} else {
			$update_message = sprintf( __( 'WP Issues CRM will bypass %d possible updates to constituent records.	
				%d records in your input file will be ignored.', 'wp-issues-crm' ), $valid_matched, $valid_matched ) ;		
		}

		if ( 0 == $valid_unique ) {
			$add_message = __( 'No new constituent records will be added, because there were no valid, viable, unmatched records in your input file.' );		
		} else {
			if ( $default_decisions->add_unmatched ) {
				$add_message = sprintf( __( 'WP Issues CRM will store %d new constituent records.	These new constituents may include some with multiple 
					activities or other updates in your input file.', 'wp-issues-crm' ), $valid_unique ); 		
			} else {
				$add_message = sprintf( __( 'WP Issues CRM will bypass %d possible new constituent additions.	
					%d possible additions in your input file will be ignored.', 'wp-issues-crm' ), $valid_unique, $valid_unique ) ;		
			}		
		}

		if ( 0 < $valid_dups ) {
			$dup_message = sprintf( __( '%d records with valid data will not be uploaded because they match to more than one constituent.', 
				 'wp-issues-crm' ), $valid_dups );
		} else {
			$dup_message = __( 'There are no instances where WP Issues CRM will bypass a record because it matches to more than one constituent.' );		
		}

		if ( $default_decisions->create_issues ) {
			$issues_message = __( 'WP Issues CRM will create new issues ( Wordpress private posts ) with the titles you confirmed in setting defaults.', 'wp-issues-crm' );
		} else {
			$issues_message = __( 'WP Issues CRM will create no new issues with this upload.', 'wp-issues-crm' );		
		}


		$output = '<ul class = "upload-status-summary" >' .
					'<li>' .
						__( 'You will make <em>irreversible database changes</em> -- so far you have only tested upload plans and settings.', 'wp-issues-crm' ) .
					'</li>' .
					'<li>' .
						__( 'You will not be able to go back and alter mapping, matching or defaults.', 'wp-issues-crm' ) .
					'</li>' .
					'<li>' .
						sprintf( __( 'Your original %d input records -- now mapped, validated and matched -- will be finally processed.', 'wp-issues-crm' ), $total_input ) .
					'</li>' .
					'<li>' .
						$update_message .
					'</li>' .
					'<li>' .
						$add_message .
					'</li>' .					
					'<li>' .
 						$dup_message .
					'</li>' .
					'<li>' .
 						$issues_message .
					'</li>' .	
				'</ul>';	
				
		return $output;	
	} 
	
	 	
}