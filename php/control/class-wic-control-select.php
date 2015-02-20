<?php
/*
* wic-control-select.php
*
*/
class WIC_Control_Select extends WIC_Control_Parent {
	
	public function search_control () {
		$final_control_args = $this->default_control_args;
		$final_control_args['readonly'] = false;
		$final_control_args['value'] = $this->value;
		$final_control_args['option_array'] =  $this->create_options_array ( $final_control_args );
		$final_control_args['required'] = ''; // fields never required on search; set explicitly here for correct result in create_options_array
		$control = $this->create_control( $final_control_args ) ;
		return ( $control ) ;
	}	
	
	public function update_control () {
		$final_control_args = $this->default_control_args;
		$final_control_args['value'] = $this->value;
		if ( $this->field->readonly ) {	
			$final_control_args['readonly_update'] = 1 ; // lets control know to only show the already set value if readonly
																		// (readonly control will not show at all on save, so need not cover that case)
		} 
		$final_control_args['option_array'] =  $this->create_options_array ( $final_control_args );
		$control =  $this->create_control( $final_control_args ) ;
		return ( $control );
	}	
	
	public function save_control () {
		$final_control_args = $this->default_control_args;
		if( ! $final_control_args['readonly'] ) {
			$final_control_args['value'] = $this->get_default_value();
			$final_control_args['option_array'] =  $this->create_options_array ( $final_control_args );
			return  ( static::create_control( $final_control_args ) );	
		}
	}	
	
	protected function create_options_array ( $control_args ) {

		global $wic_db_dictionary;
		extract ( $control_args, EXTR_SKIP );
				
		
		$entity_class = 'WIC_Entity_' . $this->field->entity_slug;
		$function_class = 'WIC_Function_Utilities';
		$getter = $this->field->option_group; 
	
		// look for option array in a sequence of possible sources
		$option_array = $wic_db_dictionary->lookup_option_values( $getter );
		// look first for getter as an option_group value in option values cache
		if ( $option_array > '' ) {
			// if found, then already done -- look no further
		} elseif ( method_exists ( $entity_class, $getter ) ) { 
			// look second for getter as a static function built in to the current entity
			$option_array = $entity_class::$getter ( $value );
			// note: including the value parameter to allow the getter to inject the value into the array if needed			
		} elseif ( method_exists ( $function_class, $getter ) ) {
			// look third for getter as a static function in the utility class
			$option_array = $function_class::$getter( $value );			
		} elseif ( function_exists ( $getter ) ) {
			// look finally for getter as a function in the global name space
			$option_array = $getter( $value );			
		} else {
			WIC_Function_Utilities::wic_error ( sprintf ( 'Dropdown field "%s" pointed to undefined or disabled option_group -- "%s".</br> 
				Fix before doing updates on this form; data may be overlayed by updates to the misconfigured field.' , 
				$this->field->field_slug, $getter ) , __FILE__, __LINE__, __METHOD__, false );
		}
		
		if ( isset ( $readonly_update ) ) { 
			// if readonly on update, extract just the already set option if a readonly field, but in update mode 
			// (if were to show as a readonly text, would lose the variable for later use)
			$option_array = array( array ( 
				'value' => $value,				
				'label' => WIC_Function_Utilities::value_label_lookup ( $value,  $option_array ),
				)
			);
		} 	
		return ( $option_array );	
	}	
	
	public static function create_control ( $control_args ) { 

		extract ( $control_args, EXTR_SKIP ); 

		$control = '';
		
		$control = ( $field_label > '' ) ? '<label class="' . $label_class . ' ' .  esc_attr( $field_slug_css ) . '" for="' . esc_attr( $field_slug ) . '">' . 
				esc_html( $field_label ) . '</label>' : '';
		$control .= '<select class="' . esc_attr( $input_class ) . ' ' .  esc_attr( $field_slug_css ) .'" onchange ="' . $onchange . '"id="' . esc_attr( $field_slug ) . '" name="' . esc_attr( $field_slug ) 
				. '" >' ;
		$p = '';
		$r = '';
		foreach ( $option_array as $option ) {
			$label = $option['label'];

			if ( $value == $option['value'] ) { // Make selected first in list
				$p = '<option selected="selected" value="' . esc_attr( $option['value'] ) . '">' . esc_html ( $label ) . '</option>';
			} else {
				$r .= '<option value="' . esc_attr( $option['value'] ) . '">' . esc_html( $label ) . '</option>';
			}
		}
		$control .=	$p . $r .	'</select>';
		return ( $control );
	
	}
		
}


