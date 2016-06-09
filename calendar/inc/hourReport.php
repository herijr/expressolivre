<?php
$phpgw_flags = Array(
	'currentapp'    =>      'calendar',
	'noappheader'   =>      True,
	'noappfooter'   =>      True,
	'nofooter'      =>      True
);
$GLOBALS['phpgw_info']['flags'] = $phpgw_flags;

include('../../header.inc.php');
$_SESSION['calendar']['user'] = $GLOBALS['phpgw_info']['user'];
$_SESSION['calendar']['server'] = $GLOBALS['phpgw_info']['server'];
define('PHPGW_API_INC','../../phpgwapi/inc');
include_once(PHPGW_API_INC.'/class.db.inc.php');
$page_content = <<<PAGE
<HTML><HEAD></HEAD><BODY>
<SCRIPT type="text/javascript">
var Total = 0;
	function show_table(){
		var MainDiv = document.getElementById('MainDiv');
		var showTable = document.createElement('TABLE');
		showTable.style.color = "#000066"
		showTable.style.background = "#F7F7F7";
		showTable.style.border = "1px solid #CCCCCC";
		showTable.width = "75%";
		showTable.align = "center";
		var trLine = document.createElement('TR');
		var tdVal = document.createElement('TD');
		tdVal.innerHTML = "Titulo";
		trLine.appendChild(tdVal);
		tdVal = document.createElement('TD');
		tdVal.innerHTML = "Descricao";
		trLine.appendChild(tdVal);
                tdVal = document.createElement('TD');
                tdVal.innerHTML = "Data";
                trLine.appendChild(tdVal);
		tdVal = document.createElement('TD');
		tdVal.innerHTML = "Horas";
		trLine.appendChild(tdVal);
		trLine.style.background = "#DDD";
		showTable.appendChild(trLine);

		var Eelement = MainDiv.firstChild;
		while (Eelement){
			trLine = document.createElement('TR');
			tdVal = document.createElement('TD');
			var cal_id = Eelement.childNodes[4].innerHTML;
			tdVal.innerHTML = "<a href='../../index.php?menuaction=calendar.uicalendar.view&cal_id="+cal_id+"'>"+Eelement.childNodes[0].innerHTML+"</a>";
			trLine.appendChild(tdVal);
                        tdVal = document.createElement('TD');
                        tdVal.innerHTML = Eelement.childNodes[1].innerHTML;
                        trLine.appendChild(tdVal);
			tdVal = document.createElement('TD');
			var today = new Date();
			today.setTime(parseInt(Eelement.childNodes[2].innerHTML)*1000);
                        tdVal.innerHTML = (today.getDate()+"/"+(today.getMonth()+1)+"/"+(today.getYear()+1900));
                        trLine.appendChild(tdVal);
			tdVal = document.createElement('TD');
			Total += (Eelement.childNodes[3].innerHTML-Eelement.childNodes[2].innerHTML)/3600;
                        tdVal.innerHTML = ((Eelement.childNodes[3].innerHTML-Eelement.childNodes[2].innerHTML)/3600).toFixed(1);
			trLine.appendChild(tdVal);
			var tbody = document.createElement('tbody');
			tbody.appendChild(trLine);
			showTable.appendChild(tbody);
	                Eelement = Eelement.nextSibling;

		}
		trLine = document.createElement('TR');
		tdVal = document.createElement('TD');
		tdDes =  document.createElement('TD');
		tdDes.innerHTML = "Total";
		tdDes.align = "left";
		tdVal.innerHTML = Total.toFixed(1);
		trLine.style.background = "#FFF";
		trLine.appendChild(tdDes);
		trLine.appendChild(document.createElement('TD'));
                trLine.appendChild(document.createElement('TD'));
		trLine.appendChild(tdVal);
		var tbody = document.createElement('tbody');
                tbody.appendChild(trLine);
		showTable.appendChild(tbody);
		MainDiv.parentNode.appendChild(showTable);
	}
</SCRIPT>
PAGE;
echo $page_content;
$db = new db();
$db->Halt_On_Error = 'no';
$db->connect(
	$_SESSION['calendar']['server']['db_name'],
	$_SESSION['calendar']['server']['db_host'],
	$_SESSION['calendar']['server']['db_port'],
	$_SESSION['calendar']['server']['db_user'],
	$_SESSION['calendar']['server']['db_pass'],
	$_SESSION['calendar']['server']['db_type']
);
if (IsSet($_POST['CAT'])){
	if (!preg_match("/[a-zA-Z0-9]+/i",$_POST['CAT'][0])){
		echo "Invalid entry:".$_POST['CAT'][0];
		return false;
	}
	if (strlen($_POST['DAT']) > 0 && !preg_match("/[0123][0-9].[01][0-9].[12][90][0-9][0-9]$/i",$_POST['DAT'])){
		echo "Invalid entry:".$_POST['DAT'];
		return false;
	}

	if (IsSet($_POST['DAT']))
		$initDate = mktime(null,null,null,substr($_POST['DAT'],3,2),substr($_POST['DAT'],0,2),substr($_POST['DAT'],6,4));
	if ($_POST['CAL'][0] != 'run')
		$user_id = $_POST['CAL'][0];

	foreach ($_POST['CAL'] as $cal_uid){
		$found = false;	
		foreach($_SESSION['calendar']['cals'] as $grant)
			if ($cal_uid == $grant['value'])
				$found = true;
			if (!$found){
				echo "Permission denied, cal. id:".$cal_uid."<br>";
				return;
			}
		}


	foreach($_POST['CAT'] as $catid){
		$query = "select cal_id,title,description,datetime,edatetime from phpgw_cal where ".(!$_POST['NORM']?"cal_type = 'H' and":" is_public = 1 and ")." owner = ".$user_id." and (category like '%,".$catid.",%' or category like '%,".$catid."' or category like '".$catid.",%' or category = '".$catid."')".(IsSet($initDate)?" and datetime > ".$initDate:"");
		if(!$db->query($query)){
			print("<br>Query failed at host:<br>".$_SESSION['calendar']['server']['db_user']."@".$_SESSION['calendar']['server']['db_host'].":".$_SESSION['calendar']['server']['db_port']."<br>");
			return;
		}
		else{
			while($db->next_record()){
				$entry = $db->row();
				$repeated = false;
				if (!empty($result_))
				foreach($result_ as $value){
					if ($value['cal_id'] == $entry['cal_id'])
						$repeated = true;
				}
				if(!$repeated)
					$result_[] = $entry;
			}
		}
}
	echo "<div id='MainDiv' style='display: none;'>";
	if (!empty($result_))
	foreach($result_ as $ind => $entry){
		echo "<div id=\"entry_".$ind."\"><div id=\"title_".$ind."\">".htmlspecialchars($entry['title'])."</div><div id=\"description_".$ind."\">".htmlspecialchars($entry['description'])."</div><div id=\"datetime_".$ind."\">".$entry['datetime']."</div><div id=\"edatetime_".$ind."\">".$entry['edatetime']."</div><div>".$entry['cal_id']."</div></div>";
	}
	echo "</div><script type=\"text/javascript\"> show_table();</script>";
	return;
}

$self = $_SERVER['PHP_SELF'];
print("<table><tbody><tr><td>");
print("<form id=\"form1\" method=\"post\" action=\"$self\">");
print("Categoria(s):<br></td><td>");
print("<select name=\"CAT[]\" multiple>");
print($_SESSION['calendar']['categories']);
print("</select></td></tr><tr><td>");
print("Incluir ocorr&ecirc;ncias do tipo normal:</td><td><input name=\"NORM\" type=\"checkbox\"></input><br></td></tr>");
print("<tr><td>Usuario:</td><td><select name=\"CAL[]\" multiple>");
foreach($_SESSION['calendar']['cals'] as $grant)
	print(!strstr($grant['value'],'g_')?'<option value="'.$grant['value'].'">'.$grant['name'].'</option>':"");
print("</td></tr><tr><td>Apartir da data: dd/mm/aaaa<br>(caso todas deixar em branco):</td><td><input name=\"DAT\" type=\"text\"></input></td></tr>");
print("</td></tr></tbody></table><br><input value=\"Fazer consulta\" type=\"submit\"><br></form>");
print("</BODY></HTML>");
?>
