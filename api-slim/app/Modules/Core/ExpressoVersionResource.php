<?php

namespace App\Modules\Core;

use App\Adapters\ExpressoAdapter;

class ExpressoVersionResource extends ExpressoAdapter {

	private $apiVersion = '1.1';

	public function any(){

		return array(
			'expressoVersion' =>  $this->getExpressoVersion(),
			'apiVersion' => $this->apiVersion 
		);
	}
}
