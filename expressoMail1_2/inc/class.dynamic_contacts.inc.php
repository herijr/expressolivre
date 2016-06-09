<?php
/**********************************************************************************\
	* ExpressoMail1_2                 										   *
	* by Gustavo Sandini Linden (gustavo.linden@serpro.gov.br)						   *
	* ---------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it         *
	*  under the terms of the GNU General Public License as published by the           *
	*  Free Software Foundation; either version 2 of the License, or (at your          *
	*  option) any later version.											           *
	\**********************************************************************************/
	
include_once('class.db_functions.inc.php');
include_once('class.functions.inc.php');
	
	/**
	 * dynamic_contacts - User's dynamic contact class
	 * @package dynamic_contacts
	 * @author Gustavo S. Linden
	 * @copyright 2008 - Gustavo S. Linden
	 * 
	 * TODO: Check if email already exists in contactcenter!!
	 * 
	 */	
	class dynamic_contacts
	{
		public $contacts;
		public $db;
		public $number_of_contacts;
		public $functions; 
		
		/**
     	* Constructor
     	* 
       	*/
		function __construct()
		{
			$this->db = new db_functions();
			$this->contacts = $this->db->get_dynamic_contacts();
			$this->number_of_contacts = $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['number_of_contacts'];
			$this->functions = new functions();
		}
		
		/**
		* get dynamic contacts
		*
		* @return array $contacts The list of fields to be returned. The format is:
				$contacts = array(
					'timestamp' => '1220558565',
					'email => 'some@email'
				);
		*/
		function get_dynamic_contacts()
		{
			return $this->contacts;  
		}
		
		/**
		* get number of contacts
		*
		* @return int $number_of_contacts 
		* maximum number of contact allowed by administration
		* if not set return undefined  
		*/
		function get_number_of_contacts()
		{
			return $this->number_of_contacts;  
		}
	
		/**
		* get dynamic contacts in string format
		*
		* @return string $contact of contacts in format ';some@email,;other@email,'
		* this is used in js/DropDownContacts.js  
		*/
		function dynamic_contact_toString()
		{
			$contact='';
			if($this->contacts)
			{
				foreach($this->contacts as $item => $valor)
					//
					if (strstr($this->contacts[$item]['email'], '#')){
						$contact .= str_replace("#",";",$this->contacts[$item]['email']) . ',';
					}else{
						$contact .= ';'.$this->contacts[$item]['email'] . ',';
					}	
					
				//Retira ultima virgula.
				$contact = substr($contact,0,(strlen($contact) - 1));
				return $contact;
			}
			else
				return false;
		}
		
		/**
		* add dynamic contacts in database
		*
		* this function runs thru the array and check if the email sent has new contacts
		* or not. If the contact is new, insert new array $contact in array $contacts else
		* search existing contact to update timestamp. 
		*
		* @param string $full_email_address to add eg. 'some@address,other@email,email@address' 
		* @return array $contacts The list of fields to be returned. The format is:
				$contacts = array(
					'timestamp' => '1220558565',
					'email => 'some@email'
				);
		* this is used in inc/class.db_function.inc.php to insert/update in database  
		*/
		function add_dynamic_contacts($full_email_address)
		{
			// Trim all whitespaces and duplicated commas from full_email_address
			//$full_email_address = preg_replace('/{(,)\1+}/',',',preg_replace( '/ +/', '', $full_email_address));
			$parse_address = imap_rfc822_parse_adrlist($full_email_address, "");
			$new_contacts = array();
			foreach ($parse_address as $val)
			{
				if ($val->mailbox == "INVALID_ADDRESS")
					continue;
				if ($this->contact_exist_in_ContactCenter($val->mailbox."@".$val->host))
					continue;

				if(!$this->contacts) // Used one time to insert the first contact in database
				{
					$this->db->insert_contact(ltrim(rtrim($val->personal))."#".$val->mailbox."@".$val->host);
					// Just new contact added.
					$new_contacts[] = ltrim(rtrim($val->personal)).";".$val->mailbox."@".$val->host;
					$this->contacts = $this->db->get_dynamic_contacts();
				}
				else
				{
					$older_contact_time=time();
					$new_contact_flag = true; // Assume that all email are new in dynamic contact
					foreach($this->contacts as $item => $valor)
					{
						if($this->contacts[$item]['email'] == ltrim(rtrim($val->personal))."#".$val->mailbox."@".$val->host) // check if email already exists
						{	
							$this->contacts[$item]['timestamp'] = time(); //update timestamp of email
							$new_contact_flag = false; //email exist!
						}
						if($this->contacts[$item]['timestamp'] < $older_contact_time) //search for oldest email
						{
							$older_contact = $item; 
							$older_contact_time = $this->contacts[$item]['timestamp'];
						}
					}
					if ($new_contact_flag == true) //new contact!
					{
						// Just new contact added.
						$new_contacts[] = ltrim(rtrim($val->personal)).";".$val->mailbox."@".$val->host;
						if($this->number_of_contacts > count($this->contacts))
						{
							$this->contacts[] = array( 'timestamp'	=> time(),
														'email'		=> ltrim(rtrim($val->personal))."#".$val->mailbox."@".$val->host);
						}
						if($this->number_of_contacts <= count($this->contacts))
						{
							$this->contacts[$older_contact] = array( 'timestamp'	=> time(),
																		'email'		=> ltrim(rtrim($val->personal))."#".$val->mailbox."@".$val->host);
						}
					}
				}
			}
			$this->db->update_contacts($this->contacts);
			return implode(",;",$new_contacts);
		}
		
		/**
		* Verify if contact exist in ContactCenter
		*
		* this function gets an email and check if the email sent is already on users's personal contacts
		* or not.  
		*
		* @param string $full_email_address to add eg. 'some@address,other@email,email@address' 
		* @return boolean  
		*/
		function contact_exist_in_ContactCenter($email)
		{
			$contactcenter_string = $this->db->get_cc_contacts();
			$cc_email = explode(",",$contactcenter_string);
			foreach ($cc_email as $item => $valor) 
			{
				$aux = explode(";",$cc_email[$item]);
				if($email == $aux[1])
				{
					return true;	
				}
				  
			}
			return false;
		}
		
		/**
		* delete dynamic contacts from database
		*
		* This function removes the dynamic contacts of the current user from the database.
		* It uses inc/class.db_function.inc.php to remove.
		*
		*/
		function delete_dynamic_contacts()
		{
			$this->db->remove_dynamic_contact($this->db->user_id,__LINE__,__FILE__);
		}
	}
?>
