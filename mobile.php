<?php

$EXPRESSO_MOBILE = 'https://m.expresso.pr.gov.br/';

require_once 'prototype/library/mobileDetect/Mobile_Detect.php';

$detect = new Mobile_Detect;

if( $detect->isMobile() || $detect->isTablet() )	
{
	header('Location: ' . $EXPRESSO_MOBILE );
	exit;
}

?>
