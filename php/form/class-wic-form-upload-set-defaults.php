<?php
/*
* class-wic-form-upload-set-defaults.php
*
*
*/

class WIC_Form_Upload_Set_Defaults extends WIC_Form_Upload_Validate  {

	public function layout_form ( &$data_array, $message, $message_level, $sql = '' ) {
		
		global $wic_db_dictionary;		
		
		echo $this->format_tab_titles( $data_array );		
		
		?><div id='wic-forms'> 
		
		<form id = "<?php echo $this->get_the_form_id(); ?>" <?php $this->supplemental_attributes(); ?> class="wic-post-form" method="POST" autocomplete = "on">
			
			<?php // form layout driven by upload status 
			$upload_status = $data_array['upload_status']->get_value();
			
			// file has not been defaulted or has been remapped  but not revalidated or not not rematched -- show error message		
			if ( 'staged' == $upload_status || 'mapped' == $upload_status  || 'validated' == $upload_status ) {			
				$message = __( 'You must map fields, validate data and match records before you can set defaults.', 'wp-issues-crm' );
				$message_level = 'error';
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
			// file has been mapped and validated and ready for test matching -- needs to be matched/rematched
			} elseif ( 'matched' == $upload_status || 'defaulted' == $upload_status ) { 
				$message = 	sprintf ( __( 'Set defaults for upload of %s', 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
				$message_level = '';
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php

				// show field test button (not a submit -- will drive AJAX)
				$disabled = ( 'defaulted' == $upload_status );
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __('Test Settings', 'wp-issues-crm'),
					'type'							=> 'button',
					'id'								=> 'match-button',
					'disabled'						=> $disabled,
				);	
				$button = $this->create_wic_form_button ( $button_args_main );
				echo $button;
				
				// invoke parent form generation logic to generate controls	
				$group_array = $this->generate_group_content_for_entity( $data_array );
				extract ( $group_array );
			
				// output form
				echo 	'<div id="wic-upload-default-form-body">' . 
							'<div id="wic-form-main-groups">' . 
								$main_groups . 
							'</div>' .
							'<div id="wic-form-sidebar-groups">' . 
								$sidebar_groups . 
								// place for progress bar and results div for lookup of issue titles if used
								'<div id = "wic-issue-lookup-progress-bar"></div>';
								'<div id = "wic-issue-lookup-results-wrapper"></div>';
							'</div>' . 
						'</div>';					// serialized_default_decisions
			echo '</div><div class = "horbar-clear-fix"></div>';
	  		// file has already been completed
			} elseif ( 'completed' == $upload_status) {
				$message =  sprintf ( __( 'Records previouly matched for %s. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() )  . $message;
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				
				// show button as disabled				
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __('Matched', 'wp-issues-crm'),
					'type'							=> 'button',
					'id'								=> 'match-button',
					'disabled'						=> true,
				);	
				$button = $this->create_wic_form_button ( $button_args_main );
				echo $button;
	  			echo '<div id = "upload-results-table-wrapper">' . 
					// 	  			
	  			'</div>';			
			} 
			echo $data_array['serialized_upload_parameters']->update_control();		   
		   // in all cases, echo ID and progress field
			echo $data_array['ID']->update_control();	
					
			echo $data_array['serialized_upload_parameters']->update_control();
			echo $data_array['serialized_column_map']->update_control();		
			echo $data_array['serialized_match_results']->update_control();	
			echo $data_array['serialized_default_decisions']->update_control();		
		 	wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ); 
			echo $this->get_the_legends( $sql ); ?>
			</div>								
		</form>
		<?php // child class may insert messaging here 
		$this->post_form_hook( $data_array ); ?>
		</div>
		
		<?php 
		
	}

	// group screen
	protected function group_screen( $group ) {
		return (	
			'summary_results' == $group->group_slug  ||
			'constituent_match' == $group->group_slug  ||
			'constituent_default' == $group->group_slug  ||
			'activity_default' == $group->group_slug ||
			'new_issue_creation' == $group->group_slug									
		  ) ;	
	}

	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	// special group handling for the comment group
	protected function group_special ( $group ) {
			return ( 'summary_results' == $group );
	}


	protected function group_special_summary_results ( &$data_array ) {
			// retrieve/compute file totals
		$upload_parameters = json_decode ( $data_array['serialized_upload_parameters']->get_value() ); 			
		$match_results = json_decode ( $data_array['serialized_match_results']->get_value() );
		$total_input = $upload_parameters->insert_count;
		$valid_matched = 0;
		$valid_unique  = 0;
		$valid_dups		= 0;
		foreach ( $match_results as $slug => $match_object  ) {
			$valid_matched += $match_object->matched_with_these_components;
			$valid_unique  += $match_object->unmatched_unique_values_of_components;	
			$valid_dups	   +=	$match_object->not_unique;			
		}								

		$output = '<ul class = "upload-status-summary" >' .
					'<li>' .
						sprintf( __( '%d input records.', 'wp-issues-crm' ), $total_input ) .
					'</li>' .
					'<li>' .
						sprintf( __( '%d records with valid data and matching to a unique constituent.  
							These may include multiple activity or other updates for the same constituent. ', 'wp-issues-crm' ), $valid_matched ) .
					'</li>' .
					'<li>' .
						sprintf( __( '%d records with valid data that cannot be uploaded because they match to more than one constituent.', 
							'wp-issues-crm' ), $valid_dups ) .
					'</li>' .
					'<li>' .
						sprintf( __( '%d unique identifiers that could be used to create new constituents 
							from records having valid data that do not match to any constituent.', 
							'wp-issues-crm' ), $valid_unique ) .
					'</li>' .														
				'</ul>';	
				
		return $output;	
	} 
	 	
}