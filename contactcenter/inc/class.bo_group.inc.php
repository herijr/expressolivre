<?php
	
	class bo_group
	{
			
		var $so;
		
		function bo_group()
		{	
			$this->so = CreateObject('contactcenter.so_group');
		}
		
		function get_groups()
		{				
			return $this->so -> select();			
		}
		
		function get_group($id)
		{	$result = $this-> so -> select($id);			
			return $result[0]; 
		}
		
		function commit($status, $data)
		{
			if($status == 'insert')
				$result = $this-> so -> insert($data);
				
			else if($status == 'update')
				$result = $this-> so -> update($data);
				
			else if($status == 'delete')
				$result = $this-> so -> delete($data);				
				
			
			return $result;
		}
		
		function get_all_contacts($field = false,$owner=null){
		
			$result = $this-> so -> selectAllContacts($field,$owner);
			return $result;
		}
		
		function verify_contact($email){
		
			$result = $this-> so -> verifyContact($email);
			return $result;
		}
		
		function get_contacts_by_group($id){
		
			$result = $this-> so -> selectContactsByGroup($id);
			return $result;
		}
		
		function getContactsByGroupAlias($alias){
			$result = $this-> so -> selectContactsByGroupAlias($alias);
			return $result;
		}		
		
		
	}
?>
