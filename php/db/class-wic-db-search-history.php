<?php
/**
*
* class-wic-db-search-history.php
*
* uses wp_options table to store a set of pointers back to the search log so that user can navigate forward and backward using familiar buttons
* allowed to persist across sessions indefinitely, but limited to 50 entries; 
*
*
**/
class WIC_DB_Search_History {

// no constructor -- all functions are static

	private static function search_history_init() {
		// used by other class methods -- sets up the option if not already in place
		// returns it either way
		$user_id = get_current_user_id();
		
		$history_array_serialized = get_option ( '_wp_issues_crm_individual_search_history_' . $user_id );
		
		if ( ! $history_array_serialized ) {
			$history_array = array (
				'history' => array(),
				'pointer' => 1, // beyond end of array
			);
			add_option (  '_wp_issues_crm_individual_search_history_' . $user_id, serialize ( $history_array ) );
		} else {
			$history_array = unserialize ( $history_array_serialized )	;	
		}
		return ( $history_array );
	}
	
	private static function search_history_save( $history_array ) {
		$user_id = get_current_user_id();
		// limit saved length to 50 entries;
		if ( count ( $history_array['history'] ) > 50 ) {
			$discard = array_shift ( $history_array['history'] ); 	// drop the earliest entry -- now, length = hard coded history length limit
			$history_array['pointer'] = count ( $history_array['history'] ) - 1;
		}
		update_option ( '_wp_issues_crm_individual_search_history_' . $user_id, serialize ( $history_array ) );
	}	
	
	public static function update_search_history( $search_log_id ) {
		// adds a search log id to the end of the search history and sets pointer to end of history
		// called whenever a new search log entry is made or is accessed from the search_log page ( <-<- )
		$history_array = self::search_history_init();
		// only add the new entry if it is not already at the end of the list -- 
		// no need for adjacent dups in the history, but non-adjacent dups make sense
		$count = count ( $history_array['history'] );
		if ( 0 < $count ) {
			if ( $search_log_id == $history_array['history'][ $count - 1 ] ) {
				return;			
			} 		
		} 
		$history_array['history'][] = $search_log_id;
		$history_array['pointer'] = $count; // $count is the new true count - 1
		self::search_history_save ( $history_array );		
	} 
	
	public static function new_history_branch () {
		// truncates history at current pointer value (if not front) and sets pointer = to count (in other words beyond end of array)
		// may be called repeatedly with same result until user does something loggable
		// called on button presses to dashboard, or new issue/constituent/search
		$history_array = self::search_history_init();
		$history_array['history'] = array_slice ( $history_array['history'], 0, $history_array['pointer'] + 1 );
		$history_array['pointer'] = count ( $history_array['history'] );
		self::search_history_save ( $history_array );
	}
	
	public static function history_pointer_move ( $forward ) { // takes binary true is forward, back is false
		// moves pointer forward or backwards and returns history entry at pointer
		$increment = $forward ? 1 : -1; 
		$history_array = self::search_history_init();
		$history_array['pointer'] = $history_array['pointer'] + $increment;
		self::search_history_save ( $history_array );
		$search_id = $history_array['history'][$history_array['pointer']];
		return ( $search_id );
	}
	
	public static function history_buttons( ) {
		// determine disability status of buttons
		$history_array = self::search_history_init();
		$disable_backward = ( 0 == count( $history_array ['history'] ) || 0 == $history_array['pointer'] );
		$disable_forward  = count ( $history_array ['history'] ) - 2 < $history_array['pointer'];  // - 1 is last position so pointer is there or beyond
		return ( array (
			'disable_backward' => $disable_backward,		
			'disable_forward' => $disable_forward,		
			)
		);
	}
}
