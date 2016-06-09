<?php

require_once '../header.session.inc.php';

$request_method = '_' . $_SERVER['REQUEST_METHOD'];
switch ( $request_method )
{
	case '_GET' :
		$params = $_GET;
	break;
	case '_POST' :
		$params = $_POST;
	break;
	case '_HEAD' :
	case '_PUT' :
	default :
		echo "controller - request method not avaible";
		return false;
}

require_once dirname(__FILE__) . '/inc/Controller.class.php';

$controller = new Controller;
printf("%s", $controller->exec($$request_method));

exit(0);
?>
