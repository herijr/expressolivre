<?php

namespace App\Modules\Admin;

class CommonFunctions
{
	public function convertChar($param)
	{
		$param = mb_convert_encoding( $param ,"UTF-8", "ISO-8859-1" );

		$array1 = array( "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç"
		, "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
		
		$array2 = array( "a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c"
		, "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
		
		return str_replace( $array1, $array2, $param);
	}

	public function mascaraBirthDate($param)
	{
		$bDate = trim(preg_replace("/[^0-9]/", "", $param));

		$bDate = preg_replace('/(\d{2})(\d{2})(\d{4})/','$1/$2/$3',$bDate);

		if( preg_match("#/#", $bDate) === 1 )
			$strDate = implode("-",array_reverse(explode('/',$bDate))); 

		return $strDate;
	}

	public function mascaraCPF($param)
	{
		$cpf = trim(preg_replace("/[^0-9]/", "", $param));

		return preg_replace('/(\d{3})(\d{3})(\d{3})/','$1.$2.$3-',$cpf);
	}

	public function mascaraPhone($param)
	{
		$phone = trim(preg_replace("/[^0-9]/", "", $param));

		return preg_replace('/(\d{2})(\d{4})(\d{4})/','($1)$2-$3',$phone);
	}

	public function validatePassword($param)
	{
		$sizePassword = strlen(trim($param));
		
		$numbers = strlen(preg_replace("/[^0-9]/", "", trim($param)));

		$return['status'] = true;

		if( (int)$sizePassword < 8 )
		{
			$return["status"] = false;
			$return["msg"] = "you need a minimum of 8 characters for the password";
		}
		else
		{
			if( (int)$numbers < 2 )
			{
				$return["status"] = false;
				$return["msg"] = "must have 2 numbers in the password";
			}
		}	

		return $return;
	}

	public function validateCharacters( $params, $field = false)	
	{
		if( $field && $field === "accountLogin" )
		{
			$search = trim(preg_replace("/[^a-z0-9A-Z\_\-\.]/", "", $params));
		}
		else
		{
			if( $field && $field == "accountMailQuota" )
				$search = trim(preg_replace("/[^0-9_.]/", "", $params));
			else	
				$search = trim(preg_replace("/[^a-z0-9A-Z\_\-\.\@\\s]/", "", $params));
		}
		
		$return['status'] = true;

		if( strtolower($search) != strtolower(trim($params)) )
		{
			$return['status'] = false;
			$return['msg'] = "Field contains characters not allowed";
		}

		return $return;
	}

	public function validateCPF( $cpf )
	{
		$seqInvalid = array('11111111111','22222222222','33333333333',
							'44444444444','55555555555','66666666666',
							'77777777777','88888888888','99999999999',
							'00000000000', '12345678909');
		
		$cpf = trim(preg_replace("/[^0-9]/", "", $cpf));

		if( strlen($cpf) != 11 )
			return False;

		if( in_array( $cpf, $seqInvalid ) )
		{
			return False;
		}

		$a = 0;
		
		for( $i = 0 ; $i < 9 ; $i++ )
		{
			$a += ($cpf[$i]*(10 - $i));
		}

		$b = ($a % 11);
		
		$a = ( ($b > 1) ? (11 - $b) : 0);
		
		if( $a != $cpf[9] )
		{
			return False;
		}
		
		$a = 0;

		for ( $i=0; $i < 10; $i++ )
		{
			$a += ($cpf[$i]*(11 - $i));
		}

		$b = ($a % 11);

		$a = (($b > 1) ? (11 - $b) : 0);
		
		if( $a != $cpf[10] )
		{
			return False;
		}

		return True;
	}
}
