<?php
/*
* class-wic-form-upload-regrets.php
*
*
*/

class WIC_Form_Upload_Regrets extends WIC_Form_Upload_Validate  {  			
	
	public function layout_form ( &$data_array, $message, $message_level, $sql = '' ) { 
		global $wic_db_dictionary;		
		
		echo $this->format_tab_titles( $data_array );		
		
		?><div id='wic-forms'> 
		
		<form id = "<?php echo $this->get_the_form_id(); ?>" <?php $this->supplemental_attributes(); ?> class="wic-post-form" method="POST" autocomplete = "on">

			<?php 
			// set up decision/message variables
			$upload_status 				= $data_array['upload_status']->get_value();
			$upload_parameters			= json_decode ( $data_array['serialized_upload_parameters']->get_value() );
			$final_results 				= json_decode ( $data_array['serialized_final_results']->get_value() );
			$default_decisions			= json_decode ( $data_array['serialized_default_decisions']->get_value() );
			$new_constituents_saved 	= $final_results->new_constituents_saved;
			$upload_file 					= $data_array['upload_file']->get_value();

			if ( 'completed' == $upload_status ) {  
				if ( $new_constituents_saved > 0 ) {
					$message = sprintf ( __( 'Backout %1$s NEW input records from %2$s.' , 'wp-issues-crm' ), $new_constituents_saved, $upload_file );
				} else {
					$message = sprintf ( __( 'No NEW constituent records created from %s. Matched updates cannot be backed out' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );				
				}					
			} else {
				$message = 	sprintf ( __( 'Upload of %s not completed -- cannot be backed out.' , 'wp-issues-crm' ), $upload_file );  		
			}
			// form layout not dependent on upload status, but button will be enabled according to status
			?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php			
			
			$matched_legend = ( $default_decisions->update_matched ) ? 
				__( 'Note that uploaded records that matched to existing constituents and therefore updated them 
						rather than being added as new cannot be backed out.', 'wp-issues-crm' ) 
				:	'' ;

								
			// create array of backout options  -- for each, button title, explanatory text and disabled -- true = disabled (single) 
			$backout_layout = 	array (
				'backout_new' 		=>		array ( 
					'Backout New', 
					sprintf ( '%s constituents that were added as new by the upload will be backed out along with any activities for them. ' 
							. $matched_legend , $new_constituents_saved ), 
					'completed' != $upload_status || 0 == $new_constituents_saved,
				 ),
			); 

			// keeping css from upload down form
			echo '<div id = "upload-download-buttons">';

			foreach ( $backout_layout as $button_slug => $backout ) { 

				$button_args_main = array(
					'entity_requested'			=> $upload_parameters->staging_table_name,
					'action_requested'			=> $button_slug,
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __( $backout[0], 'wp-issues-crm' ) ,
					'type'							=> 'button',
					'id'								=> 'wic-backout-button',
					'name'							=> 'wic-backout-button',
					'disabled'						=> $backout[2]
				);	
				$button = $this->create_wic_form_button ( $button_args_main );
				echo $button;
				echo '<div class = "download-button-legend" id="'. $button_slug . '_legend" >' . $backout[1] . '</div>';
			}
			echo '<div id = "wic-upload-progress-bar"></div>';
			echo '</div>'; 	
			
			echo $data_array['ID']->update_control();	
			echo $data_array['serialized_upload_parameters']->update_control();
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