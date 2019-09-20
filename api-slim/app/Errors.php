<?php

namespace App;

class Errors
{
	private static $sInstance;

	private $errors;

	public static function getInstance()
	{
		if (!self::$sInstance) self::$sInstance = new Errors();
		return self::$sInstance;
	}

	function __construct()
	{
		$this->errors = array();
		if ($handle = fopen(dirname(__FILE__) . "/Config/Errors.tsv", "r")) {
			while (!feof($handle)) {
				$line = trim(fgets($handle, 1024));
				if ($line == null || $line[0] == "#")
					continue;
				$error = preg_split("/[\t]+/", $line);
				if (is_array($error) && count($error) == 3) {
					$this->errors[] = array("code" => $error[0], "key" => $error[1], "message" => $error[2]);
				}
			}
		}
	}

	public function getErrors()
	{
		return $this->errors;
	}

	static public function runException($needle)
	{
		$self = self::getInstance();

		$error = current(array_filter($self->errors, create_function('$a', 'return $a["' . (is_int($needle) ? "code" : "key") . '"] == "' . $needle . '";')));

		if (!$error) $self->runException("E_UNKNOWN_ERROR");

		$args = func_get_args();

		array_shift($args);

		$message = mb_convert_encoding(lang($error['message'], $args), "UTF-8", "ISO-8859-1");

		return array("error" => array("message" => $message, "code" => $error['code']));
	}
}
