<?php
/*
* class-wic-control-range.php
*
*/ 

class WIC_Control_Date extends WIC_Control_Range {

	public function sanitize() {  
		$this->value 		= $this->value 	> '' ? $this->sanitize_date ( $this->value ) 	: '';
		$this->value_lo 	= $this->value_lo > '' ? $this->sanitize_date ( $this->value_lo ) : '';
		$this->value_hi	= $this->value_hi > '' ? $this->sanitize_date ( $this->value_hi ) : '';	
	}

	/*
	* no error message for bad date, but will fail a required test 
	*/   
	protected function sanitize_date ( $possible_date ) {
		try {
			$test = new DateTime( $possible_date );
		}	catch ( Exception $e ) {
			return ( '' );
		}	   			
 		return ( date_format( $test, 'Y-m-d' ) );
	}
	
	protected static function create_control ( $control_args ) { 
		$control_args['input_class'] .= ' datepicker ';
		$control = parent::create_control( $control_args);  
		return ( $control );
	}
	
	
}

