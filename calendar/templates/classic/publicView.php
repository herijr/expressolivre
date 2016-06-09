<?php 	
	$user = $_POST['user'] ? $_POST['user'] : 
		($_GET['user'] ? $_GET['user']: '');
	
	if($user) {
		$organization = $_POST['organization'] ? $_POST['organization'] : 
		($_GET['organization'] ? $_GET['organization']: '');
		
		if($organization)
			$arg1 = $organization."-".$user;
		else
			$arg1 = $user;
		
		$params = "&account_name=".$arg1;
	}
	else
		$params = '';
?>
<html>
	<head><title>Visualizar Agenda</title>
	<head>
	<style type="text/css">
		.grid_right{
		border-style: groove;
		border-width: 0px;
		border-right-width: 1px;		
		border-color: BLACK;		
		}
		.grid_top{
		border-style: groove;
		border-width: 0px;
		border-top-width: 1px;		
		border-color: BLACK;		
		}
	</style>
	<LINK href="../../../phpgwapi/templates/classic/css/celepar.css" type=text/css rel=StyleSheet>
	<script language="Javascript">
		function validate(){
			if(document.login.user.value =="")	{
				alert('Você deve digitar um nome no campo Usuário.');
				document.login.user.focus();
				return false;
			}
			
			return true;
		}
	</script>
	</head>
	<body>
	  <div align="center" valign="center">
		<table cellspacing="0" cellpadding="5" width="100%" height="100%">			
			<tr><td width="10%" valign="center"  class="grid_right"><br>
				<table border="0" cellpadding="0" cellspacing="0" class="login" height="100%" width="100%">
					<tr>
						<td valign="top">
	  					<FORM name="login" method="post" action="publicView.php" onSubmit="javascript:return validate()">
							<table border="0" cellpadding="0" cellspacing="0" class="tableLogin">
								<tr>
									<td width="66" class="loginLabel" align="center"><b>Organização</b><br><input type="text" value="<?=$organization?>" name="organization" size="15"></td>
								</tr>
								<tr>									
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td width="136" align="center" class="loginLabel"><b>Usuário</b><br><input name="user" size="15" value="<?=$user?>"></td>
								</tr>
								<tr>									
									<td height="16">&nbsp;</td>
								</tr>
								<tr>
									<td width="135" colspan="2" align="center">						
									<input type="submit" value="Visualizar" name="submitit" class=button >
									</td>
									</tr>
							</table>
							</FORM>
						</td>
					</tr>
				</table>								
			</td>
				<td width="90%" valign="top">
					<iframe  width="100%" height="95%" src="../../../index.php?menuaction=calendar.uipublicview.publicView<?=$params?>"
						marginwidth="0" marginheight="0"  topmargin="0" leftmargin="0" frameborder="0" scrolling="auto" noresize>
					</iframe>
			</td></tr>
		</table>
	  </div>
	</body>
</html>