<?php

namespace App\Modules\Services;

use App\Errors;
use App\Adapters\ServicesAdapter;

class ChatResource extends ServicesAdapter
{
	public function post()
	{
		return $this->authChat();
 	}	
}
