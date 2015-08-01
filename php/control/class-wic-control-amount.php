<?php
/*
* 
* class-wic-control-amount.php
* input for numeric amount -- forces positive
*
*/

class WIC_Control_Amount extends WIC_Control_Range {
	
	protected static function create_control ( $control_args ) { // basic create text control, accessed through control methods above

		extract ( $control_args, EXTR_OVERWRITE );  
		
     	$class_name = 'WIC_Entity_' . $entity_slug; 
		$formatter = $list_formatter; // ( field slug has instance args in it )
		if ( method_exists ( $class_name, $formatter ) ) { 
			$value = $class_name::$formatter ( $value );
		} elseif ( function_exists ( $formatter ) ) {
			$value = $formatter ( $value );		
		}

		$readonly = $readonly ? 'readonly' : '';

		$control = ( $field_label > '' && ! ( 1 == $hidden ) ) ? '<label class="' . esc_attr ( $label_class ) .
				 ' ' . esc_attr( $field_slug_css ) . '" for="' . esc_attr( $field_slug ) . '">' . esc_html( $field_label ) . '</label>' : '' ;
		$control .= '<input class="' . esc_attr( $input_class ) . ' ' .  esc_attr( $field_slug_css ) . '" id="' . esc_attr( $field_slug )  . 
			'" name="' . esc_attr( $field_slug ) . '" type="number" min="0" step="0.01" placeholder = "' .
			 esc_attr( $placeholder ) . '" value="' . esc_attr ( $value ) . '" ' . $readonly  . '/>'; 
			
		return ( $control );

	} 	
	
}

