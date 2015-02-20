<?php
/*
*
*  class-form-issue-update.php
*
*/

class WIC_Form_Issue_Update extends WIC_Form_Parent  {
	
	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'issue' );	
	}

	// define form buttons
	protected function get_the_buttons ( &$data_array ) {
		$button_args_main = array(
			'entity_requested'			=> 'issue',
			'action_requested'			=> 'form_update',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Update issue', 'wp-issues-crm')
		);	
		
		$buttons = $this->create_wic_form_button ( $button_args_main );
		
		$buttons .= '<a href="/wp-admin/post.php?post=' . $data_array['ID']->get_value() .'&action=edit">' .
								__( 'Edit post in Wordpress editor.', 'wp-issues-crm' ) .
							'</a>';		

		return ( $buttons ) ;
	}
	
	// define form message
	protected function format_message ( &$data_array, $message ) {
		$title = $this->format_name_for_title ( $data_array );
		return ( sprintf ( __('Update %1$s. ' , 'wp-issues-crm'), $title ) . $message );
	}

	// overriding the parent function here to make special handling for public posts
	protected function the_controls ( $fields, &$data_array ) {
		// determine whether current issues is a public post
		$public_post = false;
		if ( isset ( $data_array['post_status'] ) ) {
			if ( 'publish' == $data_array['post_status']->get_value() ) {
				$public_post = true;
			}
		}
		// prepare controls normally but with an exception for the post content in a public post
		$controls_output = '';
		foreach ( $fields as $field ) { 
			if ( $field == 'post_content' && $public_post) { 
				// show as public posts as text output, but carry a hidden text area to preserve content on save
				$controls_output  .= '<div id="wic-post-content-visible">' . 
						apply_filters( 'the_content', balancetags ( wp_kses_post ( $data_array['post_content']->get_value() ) ) ). 
					'</div>';
				$textarea_control_args = array (
					'readonly' 	=> true,
					'hidden'		=> true, // will not work in older browsers, so make it tolerably well formatted with css
					'field_label' => '',
					'label_class' => '',
					'field_slug' => 'post_content',
					'input_class' => 'wic-post-content-hidden',
					'field_slug_css' => '',
					'placeholder' =>'',
					'value' => $data_array['post_content']->get_value(), // will be escaped with esc_textarea
				);
				// note that text area control only does input sanitization of strip slashes
				$control = WIC_Control_Textarea::create_control ( $textarea_control_args ); 
			} else {
				$control = $this->get_the_formatted_control ( $data_array[$field] );			
			}
			$controls_output .= '<div class = "wic-control" id = "wic-control-' . str_replace( '_', '-' , $field ) . '">' . $control . '</div>';
		} 	
		return ( $controls_output );
		
	}
	/**
	* Note regarding options considered for output sanitization of post_content in routine above.
	* (1) esc_html not an option since shows html characters instead of using them format
	* (2) sanitize_text_field strips tags entirely
	* (3) apply_filters('the_content', -- ) necessary to do autop.  Will run shortcodes possibly a mixed blessing. 
	* (4) wp_kses_post leaves tags unbalanced but handles stray quotes
	* (5) balancetags (with force set to true) still gets hurt by stray quotes
	* CONCLUSION COMBINE 3, 4 AND 5 -- EXPENSIVE, BUT APPROPRIATE, GIVEN RAW CONTENT BEING SERVED --
	* NOTE: Wordpress does not bother to clean post_content up in this way (even through the admin interface) -- so conclude not necessary on save
	* -- only do it here for display; assume properly escaped for storage although not clean display
	**/



	// choose update controls
	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	// define form legends
	protected function get_the_legends( $sql = '' ) {

		global $wic_db_dictionary;

		$legend = '';
	
		$individual_required_string = $wic_db_dictionary->get_required_string( "issue", "individual" );
		if ( '' < $individual_required_string ) {
			$legend =   __('Required for save/update: ', 'wp-issues-crm' ) . $individual_required_string . '. ';
		}
		
		$group_required_string = $wic_db_dictionary->get_required_string( "issue", "group" );
		if ( '' < $group_required_string ) {
			$legend .=   __('At least one among these fields must be supplied: ', 'wp-issues-crm' ) . $group_required_string . '. ';
		}

		if ( '' < $legend ) {
			$legend = '<p class = "wic-form-legend">' . $legend . '</p>';		
		}
		
		if ( '' < $sql ) {
			$legend .= 	'<p class = "wic-form-legend">' . __('Search SQL was:', 'wp-issues-crm' )	 .  $sql . '</p>';	
		}
		return  $legend;
	}
	
	protected function format_name_for_title ( &$data_array ) {
		
		$title = $data_array['post_title']->get_value();
		
		return  ( $title );
	}

	protected function pre_button_messaging( &$data_array ) {
		edit_post_link( __( 'Edit this Post in Wordpress editor.', 'wp-issues-crm' ), '<div id = "wic-issue-post-edit-link">', '</div>', $data_array['ID']->get_value() ) ;
	}	
	
	
	protected function group_screen( $group ) {
		return ( 'search_parms' != $group->group_slug );	
	}
	
	// hooks not implemented
	protected function supplemental_attributes() {}
	protected function group_special( $group ) {}
   protected function post_form_hook ( &$data_array ) {} 
}