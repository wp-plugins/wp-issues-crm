<?php
/*
* wic-control-checked.php
*
*/
class WIC_Control_Checked extends WIC_Control_Parent {
	
	public static function create_control ( $control_args ) {
		
		$input_class = 'wic_input_checked';

		extract ( $control_args, EXTR_SKIP ); 
	
		$readonly = $readonly ? 'readonly' : '';
				 
		$control = ( $field_label > '' ) ?  '<label class="' . $label_class .  ' ' . esc_attr( $field_slug_css ) . '" for="' . 
				esc_attr( $field_slug ) . '">' . esc_html( $field_label ) . ' ' . '</label>' : '';
		$control .= '<input class="' . $input_class . '"  id="' . esc_attr( $field_slug ) . '" name="' . esc_attr( $field_slug ) . 
			'" type="checkbox"  value="1"' . checked( $value, 1, false) . $readonly  .'/>';	

		return ( $control );

	}	

	// note that setting this to zero cause the checked value to be included in search clauses with value 0
	// currently this behavior supports use of the flag for "is deceased"
	public function reset_value() {
		$this->value = 0;	
	}

}	
