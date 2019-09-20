<?php

namespace App\Modules\Preferences;

use App\Errors;
use App\Adapters\PreferencesAdapter;

class ChangePasswordResource extends PreferencesAdapter
{
	public function post($request)
	{
		$currentPassword	= urldecode(trim($request['currentPassword']));
		$newPassword_1		= urldecode(trim($request['newPassword_1']));
		$newPassword_2		= urldecode(trim($request['newPassword_2']));

		// If empty current password
		if(  empty($currentPassword) )
		{
			return Errors::runException( "EMPTY_CURRENT_PASSWORD" );
		}

		// If empty newPassword1/2
		if( empty($newPassword_1) && empty($newPassword_2) )
		{
			return Errors::runException( "EMPTY_NEW_PASSWORD" );
		}

		// Equal $newPassword_1 and $newPassword_2
		if( !( $newPassword_1 === $newPassword_2 ) )
		{
			return Errors::runException( "NEW_PASSWORDS_DIFFERENT" );
		}	

		if( $this->getAclPassword() === "true" )
		{
			if( $this->isPassword($currentPassword) )
			{	
				if( strlen($newPassword_1) >= 8 )
				{
					$changePassword	= false;
					$onlyAlfa 		= trim(preg_replace("/[^a-zA-Z0-9]/", "", $newPassword_1));
					$onlyNumbers 	= trim(preg_replace("/[^0-9]/", "", $newPassword_1));

					if( ( strlen($newPassword_1) - strlen($onlyAlfa) ) > 0 )
					{
						$changePassword = true;
					}
					else
					{
						if( strlen($onlyNumbers) >= 2 )
						{
							$changePassword = true;
						}	
					}

					if( $changePassword )
					{ 
						return $this->setPassword( $newPassword_1 , $currentPassword);
					}
					else
					{
						return Errors::runException("NEEDS_2_NUMBERS_OR_SPECIAL_CHARACTERS");
					}
				}
				else
				{
					return Errors::runException( "NEEDS_8_OR_MORE_LETTERS" );
				}
			}
			else
			{
				return Errors::runException( "CURRENT_PASSWORD_DOES_NOT_MATCH");
			}
		}
		else
		{
			return Errors::runException("WITHOUT_PERMISSION");
		}
	}
}
