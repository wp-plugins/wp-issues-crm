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
			
			// prior steps not completed -- must have reached status 'defaulted'		
			if ( 'staged' == $upload_status || 'mapped' == $upload_status || 'validated' == $upload_status || 'matched' == $upload_status  ) {			
				$message = __( 'Complete all prior steps before completing upload.', 'wp-issues-crm' );
				$message_level = 'error';
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
			// file is ready for final upload ( or upload has been started and/or completed )
			} else {
				// set message and button label based on status
				if ( 'defaulted' == $upload_status ) { 
				// show message inviting match/rematch
					$message =  sprintf ( __( 'Ready to complete upload from %s.' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
					$button_label = __( 'Complete Upload', 'wp-issues-crm' );
				} elseif ( 'reversed' == $upload_status )  {
					$message = sprintf( __( 'Upload of %s already attempted and reversed.', 'wp-issues-crm' ), $data_array['upload_file']->get_value() ) ;
					$message_level = 'error';
					$button_label = __( 'Upload Backed Out' , 'wp-issues-crm' );
				} elseif ( 'started' == $upload_status ) {
					$message =  sprintf ( __( 'Upload interrupted for %s. You can safely attempt restart.' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
					$button_label = __( 'Restart Upload', 'wp-issues-crm' );
				} elseif ( 'completed' == $upload_status ) {
					$message =  sprintf ( __( 'Upload already completed for %s.' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
					$button_label = __( 'Upload completed', 'wp-issues-crm' );
				}
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php

				$disabled = ( 'completed' == $upload_status ); 
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> $button_label,
					'type'							=> 'button',
					'id'								=> 'upload-button',
					'disabled'						=> $disabled,
				);	
				$button = $this->create_wic_form_button ( $button_args_main );
				echo $button;

				// if first run through explain game plan
				if ( 'defaulted' == $upload_status ) { 				
					$upload_parameters 	= json_decode ( $data_array['serialized_upload_parameters']->get_value() );
					$total_input = $upload_parameters->insert_count;	
					echo '<div id = "upload-game-plan">' .
						'<h3>' . __( 'What will happen:', 'wp-issues-crm' ) . '</h3>' .
						'<ul class = "upload-status-summary" >' .
						'<li>' .
							__( 'You will make <em>irreversible database changes</em> -- so far you have only tested upload plans and settings.', 'wp-issues-crm' ) .
						'</li>' .
						'<li>' .
							__( 'You will not be able to go back and alter mapping, matching or defaults.', 'wp-issues-crm' ) .
						'</li>' .
						'<li>' .
							sprintf( __( 'Your original %d input records -- now mapped, validated and matched -- will be finally processed.', 'wp-issues-crm' ), $total_input ) .
						'</li><ul>' .
					'</div>';
				}
				
				// place for progress bar -- ajax controlled; initial display none; 
				echo '<div id = "wic-upload-progress-bar"></div>';
				// results report
				echo '<div id = "upload-results-table-wrapper"><span id="upload-progress-legend"></span>' .
					  $this->summary_results( $data_array ) .
				'</div>';
 	
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
		
		$match_results 		= json_decode ( $data_array['serialized_match_results']->get_value() );
		$default_decisions 	= json_decode ( $data_array['serialized_default_decisions']->get_value() );
		$final_results 		= json_decode ( $data_array['serialized_final_results']->get_value() );
		$column_map				= json_decode ( $data_array['serialized_column_map']->get_value() );

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

		if ( 0 < $valid_dups ) {
			$dup_message = sprintf( __( 'Note: %d records with valid data will not be uploaded because they match to more than one constituent.  
					These records are excluded from counts above.', 
				 'wp-issues-crm' ), $valid_dups );
		} else {
			$dup_message = __( 'Note: There are no instances where WP Issues CRM will bypass a record because it matches to more than one constituent.' );		
		}


		// table headers				
		$table =  '<table id="wp-issues-crm-stats"><tr>' .
			'<th class = "wic-statistic-text">' . __( 'Upload Results', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( 'Planned', 'wp-issues-crm' ) . '</th>' .					
			'<th class = "wic-statistic">' . __( 'Completed', 'wp-issues-crm' ) . '</th>' .
		'</tr>';
		
		// new issues row
		$new_issue_count = $default_decisions->create_issues ? $default_decisions->new_issue_count : 0;
		$new_issue_result = isset ( $final_results->new_issues_saved ) ? $final_results->new_issues_saved : 0; 
		$table .= '<tr>' .
			'<td class = "wic-text">' . __( 'New issues from unique unmatched titles', 'wp-issues-crm' ) . '</td>' .
			'<td class = "wic-statistic" >' . $new_issue_count . '</td>' .
			'<td class = "wic-statistic" id = "new_issues_saved" >' . $new_issue_result . '</td>' .
		'</tr>';
		
		// new constituents row
		$new_constituents_count = $default_decisions->add_unmatched ? $valid_unique : 0;
		$new_constituents_result = isset ( $final_results->new_constituents_saved ) ? $final_results->new_constituents_saved : 0; 
		$table .= '<tr>' .
			'<td class = "wic-text">' . __( 'New constituents (possibly multiple input records per unique unmatched value)', 'wp-issues-crm' ) . '</td>' .
			'<td class = "wic-statistic" >' . $new_constituents_count . '</td>' .
			'<td class = "wic-statistic" id = "new_constituents_saved" >' . $new_constituents_result . '</td>' .
		'</tr>';

		// updates row
		$updated_constituents_count = $default_decisions->update_matched  ? $valid_matched : 0;
		$updated_constituents_result = isset ( $final_results->constituent_updates_applied ) ? $final_results->constituent_updates_applied : 0; 
		$table .= '<tr>' .
			'<td class = "wic-text">' . __( 'Updates for matched constituents', 'wp-issues-crm' ) . '</td>' .
			'<td class = "wic-statistic" >' . $updated_constituents_count . '</td>' .
			'<td class = "wic-statistic" id = "constituent_updates_applied" >' . $updated_constituents_result . '</td>' .
		'</tr>';

		// total valid row
		$total_valid_count = $valid_matched + $unmatched_records_with_valid_components;
		$total_valid_records_processed = isset ( $final_results->total_valid_records_processed ) ? $final_results->total_valid_records_processed : 0; 
		$table .= '<tr>' .
			'<td class = "text">' . __( 'All constituent updates from valid input records (including details for unmatched)', 'wp-issues-crm' ) . '</td>' .
			'<td class = "wic-statistic" >' . '--' . '</td>' .
			'<td class = "wic-statistic" id = "total_valid_records_processed" >' . $total_valid_records_processed . '</td>' .
		'</tr>';		


		$table .= '</table>';
		
		$table .= '<p id="dup-note-to-stats">' . $dup_message . '</p>';	
	
		return ( $table );	
	} 
	
	 	
}