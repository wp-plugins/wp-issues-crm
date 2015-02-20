<?php
/*
* class-wic-list-trend.php
*
*
*/ 

class WIC_List_Trend extends WIC_List_Parent {
	/*
	* Need to two step list and also to format header for this hybrid entity, so can't use much of parent functions
	*
	*/
	
	public function format_entity_list( &$wic_query, $header ) {

  		// set up form
		$output = '<div id="wic-post-list"><form method="POST">' . 
			'<div class = "wic-post-field-group wic-group-odd">';


		// prepare the custom/mixed list fields for header set up and list formatting
  		$fields = array (
			array ( __( 'Constituents with Activities by Issue', 'wp-issues-crm' ), 'id' ),
			array ( __( 'Total', 'wp-issues-crm' ), 'total' ),   		
			array ( __( 'Pro', 'wp-issues-crm' ), 'pro' ),
			array ( __( 'Con', 'wp-issues-crm' ), 'con' ),
			array ( __( 'Categories', 'wp-issues-crm' ), 'post_category' ),
  		);
	
		$output .= '<ul class = "wic-post-list">' .  // open ul for the whole list
			'<li class = "pl-odd">' .							// header is a list item with a ul within it
				'<ul class = "wic-post-list-headers">';				
					foreach ( $fields as $field ) {
							$output .= '<li class = "wic-post-list-header pl-' . $wic_query->entity . '-' . $field[1] . '">' . $field[0] . '</li>';
						}			
		$output .= '</ul></li>'; // header complete
		$output .= $this->format_rows( $wic_query, $fields ); // format list item rows from child class	
		$output .= '</ul>'; // close ul for the whole list
		
		$output .= 	wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ) .
		'</form></div>'; 
		
		$output .= 	'<p class = "wic-list-legend">' . __('Search SQL was:', 'wp-issues-crm' )	 .  $wic_query->sql . '</p>';	

		return $output;
   } // close function 	
	
	
protected function format_rows( &$wic_query, &$fields ) {

		$output = '';
		$line_count = 1;

		// check current user so can highlight assigned cases
		$current_user_id = get_current_user_id();

		foreach ( $wic_query->result as $row_array ) {

			$row= '';
			$line_count++;
			$row_class = ( 0 == $line_count % 2 ) ? "pl-even" : "pl-odd";
			
			// add special row class to reflect case assigned status
			$issue_staff = get_post_meta ( $row_array->id, 'wic_data_issue_staff' );
			$issue_staff = isset ( $issue_staff[0] ) ? $issue_staff[0] : '';		
			$issue_status = get_post_meta ( $row_array->id, 'wic_data_follow_up_status' );
			$issue_status = isset ( $issue_status[0] ) ? $issue_status[0] : '';
			$issue_review_date = get_post_meta ( $row_array->id, 'wic_data_review_date' );
			$issue_review_date = isset ( $issue_review_date[0] ) ? $issue_review_date[0] : '';			
			
			if ( $current_user_id == $issue_staff ) { 
				$row_class .= " case-assigned ";
				if ( 'open' == $issue_status ) {
					$row_class .= " case-open ";
					if ( '' == $issue_review_date ) {	
						$review_date = new DateTime ( '1900-01-01' );
					} else {
						$review_date = new DateTime ( $issue_review_date );					
					}
					$today = new DateTime( current_time ( 'Y-m-d') );
					$interval = date_diff ( $review_date, $today );
					if ( 0 == $interval->invert ) {
						$row_class .= " overdue ";				
						if ( 7 < $interval->days ) {
							$row_class .= " overdue long-overdue ";				
						}
					}
				} elseif ( 0 == $issue_status ) {			
					$row_class .= " case-closed ";
				}	
			}	

			$row .= '<ul class = "wic-post-list-line">';			
				foreach ( $fields as $field ) { 
					if ( 'id' != $field[1] && 'post_category' != $field[1] ) {
							$display_value = $row_array->$field[1];
						} elseif ( 'post_category' == $field[1] ) {
							$display_value =  esc_html( WIC_Entity_Issue::get_post_categories( $row_array->id ) );		
						} else {
							$display_value =  get_the_title ( $row_array->id );		
						}
						$row .= '<li class = "wic-post-list-field pl-' . $wic_query->entity . '-' . $field[1] . ' "> ';
							$row .=  $display_value ;
						$row .= '</li>';			
					}	

			$row .='</ul>';				
			
			$list_button_args = array(
					'entity_requested'	=> 'trend',
					'action_requested'	=> 'id_search',
					'button_class' 		=> 'wic-post-list-button ' . $row_class,
					'id_requested'			=> $row_array->id,
					'button_label' 		=> $row,				
			);			
			$output .= '<li>' . WIC_Form_Parent::create_wic_form_button( $list_button_args ) . '</li>';	
		} // close for each row
		return ( $output );		
	} // close function 


	// create tree-form list of categories as buttons for download of constituents 
	public function category_stats ( &$wic_query ) { 
	
  		// set up form
		$output = '<div id="wic-post-list"><form method="POST">' . 
			'<div class = "wic-post-field-group wic-group-odd">';

		// get an array of issue counts by category ( term_id => issue count )
		$category_count_array = array();
		foreach ( $wic_query->result as $row_array ) { 
			$post_categories = get_the_category ( $row_array->id ); 
			if ( is_array ( $post_categories ) and count ( $post_categories ) > 0 ) {
				foreach ( $post_categories as $category ) {		
					if ( isset ( $category_count_array[$category->term_id] ) ) {
						$category_count_array[$category->term_id] = $category_count_array[$category->term_id]  + 1;					
					} else {
						$category_count_array[$category->term_id]  = 1;					
					}							
				}
			}	
		}


		// recreate the site's category tree slicing out only those branches with positive counts from the search
		$trimmed_category_tree = WIC_Entity_Issue::get_post_category_count_tree( $category_count_array );
		
		$line_count = 1;

		$output .= '<ul class = "wic-post-list">'; // open a ul of category buttons
			$output .= '<li class = "pl-odd">' . // create a header	
					'<ul class = "wic-post-list-category-headers">' .				
						'<li class = "wic-post-list-header pl-trend-category">' . __( 'Category', 'wp-issues-crm' ) . '</li>' .
						'<li class = "wic-post-list-header pl-trend-category-count">' . __( 'Count: Issues in Category with Found Activities in Period', 'wp-issues-crm' ) . '</li>' .
					'</ul>' .
				'</li>';
		foreach ( $trimmed_category_tree as $category ) {
			$row= '';
			$line_count++;
			$row_class = ( 0 == $line_count % 2 ) ? "pl-even" : "pl-odd";
			$row = '<ul class = "wic-post-list-category-line" >' . 
						'<li class = "wic-post-list-field pl-trend-category '. $category['class'] . '">' .  esc_html( $category['label'] ) . '</li>' .
						'<li class = "wic-post-list-field pl-trend-category-count">' . $category['count']  . '</li>' .
					'<ul>';
			
			$category_contributors = implode ( ',' , $category['twigs'] );
			$search_id_and_contributors = $wic_query->search_id . ',' . $category_contributors;
					
			$list_button_args = array(
					'button_class' 		=> 'wic-post-list-button ' . $row_class,
					'name'					=> 'wic-category-export-button',
					'id'						=> 'wic-category-export-button',
					'value'					=> $search_id_and_contributors,
					'button_label' 		=> $row,	
					'title'					=>	__( 'Download constituents', 'wp-issues-crm' ),			
			);			
			$output .= '<li>' . WIC_Form_Parent::create_wic_form_button( $list_button_args ) . '</li>'; // out put a category list in the array	 
		}
		$output .= '</ul>'; // close ul of category buttons
			 		
		$output .= 	wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ) .
		'</form></div>'; 
		
		$output .= 	'<p class = "wic-list-legend">' . __('Counts for categories may exceed actual count of issues 
			if multiple categories assigned to issues.  Each assignment of a category counts to the category total.', 'wp-issues-crm' )	. '</p>';
		$output .= 	'<p class = "wic-list-legend">' . __('Search SQL was:', 'wp-issues-crm' )	 .  $wic_query->sql . '</p>';	

		return $output;

	}

	protected function format_message( &$wic_query, $header='' ) {}

 }	

