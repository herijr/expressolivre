<?php

class EventCategoriesResource extends CalendarAdapter {

	public function setDocumentation() {

		$this->setResource("Calendar","Calendar/EventCategories","Retorna as categorias dos eventos.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autenticação do Usuário.",false);

		$this->addResourceParam("eventCategoryID","integer",false,"ID da categoria do evento que será retornado.");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{

			$eventCategoryID = $this->getParam('eventCategoryID');

			$this->validateInteger($eventCategoryID,false,"CALENDAR_INVALID_CATEGORY");

			$result = array("eventCategories" => $this->getEventCategories($eventCategoryID));

			$this->setResult($result);


		}

		return $this->getResponse();
	}
}
