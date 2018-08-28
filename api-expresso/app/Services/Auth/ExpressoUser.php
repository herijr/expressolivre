<?php

namespace App\Services\Auth;

use Illuminate\Auth\GenericUser;

class ExpressoUser extends GenericUser
{
	public function getAllAttributes()
	{
		return $this->attributes;
	}
}