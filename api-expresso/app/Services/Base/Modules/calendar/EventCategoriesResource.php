<?php

namespace App\Services\Base\Modules\calendar;

use App\Services\Base\Adapters\CalendarAdapter;
use App\Services\Base\Commons\Errors;

class EventCategoriesResource extends CalendarAdapter {

	public function setDocumentation() {
		$this->setResource("Calendar","Calendar/EventCategories","Retorna as categorias dos eventos.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);
		$this->addResourceParam("eventCategoryID","integer",false,"ID da categoria do evento que será retornado.");
	}

	public function post($request){
 		
		$this->setParams( $request );

		$eventCategoryID = $this->getParam('eventCategoryID');

		$this->validateInteger($eventCategoryID,false,"CALENDAR_INVALID_CATEGORY");

		$result = array("eventCategories" => $this->getEventCategories($eventCategoryID));

		$this->setResult($result);

		return $this->getResponse();
	}
}
