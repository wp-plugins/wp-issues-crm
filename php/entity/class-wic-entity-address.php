<?php
/*
*
*	wic-entity-address.php
*
*/



class WIC_Entity_Address extends WIC_Entity_Multivalue {

	protected function set_entity_parms( $args ) {
		extract ( $args );
		$this->entity = 'address';
		$this->entity_instance = $instance;
	} 


	private function get_zip_from_usps () {
		
		$uspsRequest = new WIC_Entity_Address_USPS(); //class instantiation
		$uspsRequest->address2 = $this->data_object_array['address_line']->get_value();   
		$uspsRequest->address1 = '';
		$uspsRequest->city = $this->data_object_array['city']->get_value();
		$uspsRequest->state = $this->data_object_array['state']->get_value();
		$uspsRequest->zip = '';
 
		if ( $uspsRequest->address2 > '' && $uspsRequest->city > '' && $uspsRequest->state > '' ) {	

			$result = $uspsRequest->submit_request();

			if ( !empty( $result ) ) {
				$xml = new SimpleXMLElement( $result );
				if( ! isset($xml->Address[0]->Error) && ! isset($xml->Number) ) {
				// if not an address lookup error and also not a basic access error, then overlay entered data
					$this->data_object_array['address_line']->set_value( (string) $xml->Address[0]->Address2 );
					$this->data_object_array['city']->set_value( (string) $xml->Address[0]->City );	
					$this->data_object_array['zip']->set_value( (string) $xml->Address[0]->Zip5 ); 		 		 
				}
			} else {
				echo '<h4>' . __( 'Empty return from USPS ZipCode Validator. Unknown error. You can disable validator at: ', 'wp-issues-crm' )  . '<a href="/wp-admin/admin.php?page=wp-issues-crm-settings#usps"> WP Issues CRM settings.</a>' . '</h4>';
			}
			if ( strpos ( $result, '80040B' ) ) {
				echo '<h4>' . __( 'USPS ZipCode Validator error -- check User Name setting in: ', 'wp-issues-crm') . '<a href="/wp-admin/admin.php?page=wp-issues-crm-settings#usps"> WP Issues CRM settings.</a>' . '</h4>';
			}
		}
	}

	public function validate_values () { 
		$options = get_option ('wp_issues_crm_plugin_options_array');
		if ( isset ( $options['use_postal_address_interface'] ) ) {
			$this->get_zip_from_usps();
		}
		return ( parent::validate_values() );
	} 

}