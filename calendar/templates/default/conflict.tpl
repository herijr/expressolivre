<!-- BEGIN list -->
<html>
<head>
<title> .: Expresso :. </title>
<link href='/phpgwapi/templates/classic/login.css' rel='stylesheet' type='text/css'>
</head>
<body class='body'>
<br>
<center>
<table border='0' cellpading='0' cellspacing='0'>
	<tr>
		<td class='td' align="center"><b><font face='Verdana, Arial, Helvetica, sans-serif'>{notify_message}</font></b></td>
	</tr>
	<tr>
</table>
</center>
<br>
<center>
<table border='0' cellpading='0' cellspacing='0'>
<tr>
	<td>
		<form>
		<tr>
		  <td>
			<input type='button' value="{ignore_conflict}" onclick='javascript:window.location="{action_ignore}"'>
		  </td>
		  <td>
		   &nbsp;<input type='button' value="{reject_event}" onclick='javascript:window.location="{action_reject}"'>
		  </td>
		</tr>
		</form>
	</td>
</tr>
</table>
</center>
</body>
</html>
<!-- END list -->