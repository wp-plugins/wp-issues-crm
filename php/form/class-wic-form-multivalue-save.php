<?php
/*
*
*  class-wic-form-multivalue-save.php
*
*	this form is for an instance of a multivalue field -- a row (or rows, if multiple groups) of controls, not a full form
*	  
*	entity in this context is the entity that the multivalue field may contain several instances of 
*   -- this form generator doesn't need to know the instance value; 
* 	 -- the control objects within each row know which row they are implementing
*
*/

class WIC_Form_Multivalue_Save extends WIC_Form_Multivalue_Update  {

	protected function get_the_formatted_control ( $control ) {
		return ( $control->save_control() ); 
	}
}