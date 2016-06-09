<?php
if(!isset($GLOBALS['phpgw_info'])){
        $GLOBALS['phpgw_info']['flags'] = array(
                'currentapp' => 'expressoMail1_2',
                'nonavbar'   => true,
                'noheader'   => true
        );
}
require_once '../header.inc.php';


	$title = $appname;
	$file = array(
		'Preferences'     		=> $GLOBALS['phpgw']->link('/preferences/preferences.php','appname='.$appname),
		'Expresso Offline'			=> $GLOBALS['phpgw']->link('/expressoMail1_2/offline_preferences.php'),
		'Programed Archiving' => $GLOBALS['phpgw']->link('/expressoMail1_2/programed_archiving.php')
	);
	//Do not modify below this line
	display_section($appname,$title,$file);
?>
