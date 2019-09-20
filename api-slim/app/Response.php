<?php

namespace App;

use App\Encoding\UTF8;

class Response
{
	private $utf8;

	public function __construct()
	{
		$this->utf8 = new UTF8();
	}

	public function write($app, $response)
	{
		$json_opts = 0;
		$json_opts |= version_compare(PHP_VERSION, '5.5.0', '>=') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;

		$response = (isset($response['error'])) ?
			json_encode($this->utf8->encoding($response), $json_opts) : json_encode(array("result" => $this->utf8->encoding($response)), $json_opts);

		$app->response->setStatus(200);
		$app->response->headers->set('Content-Type', 'application/json');
		$app->response->write($response);
	}
}
