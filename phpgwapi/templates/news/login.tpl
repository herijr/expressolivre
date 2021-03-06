<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<!-- BEGIN login_form -->
	<head>
		<title>{website_title} - {lang_login}</title>
		<meta http-equiv="Content-Type" content="text/html; charset={charset}">
		<meta name="AUTHOR" content="dGroupWare http://www.eGroupWare.org">
		<meta NAME="description" CONTENT="{website_title} login screen, working environment powered by eGroupWare">
		<meta NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
		<meta NAME="keywords" CONTENT="{website_title} login screen, eGroupWare, groupware, groupware suite">
		<link rel="stylesheet" type="text/css" href="./phpgwapi/templates/news/css/login.css"/>
		<link rel="stylesheet" type="text/css" href="./prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css" />
		<link rel="stylesheet" type="text/css" href="./prototype/plugins/jquery.keyboard/jquery.keypad.alt.css">
	</head>
	<body>
		<div id="container">
			<div id="main">
				<div id="login">
					<h1 id="logo-expresso">Expresso Livre</h1>
					<form id="loginForm" name="flogin" method="post" action="{login_url}" {autocomplete}>
						<input type="hidden" name="_token" value="{token_expresso}">
						<input type="hidden" name="passwd_type" value="text">
				      	<input type="hidden" name="account type" value="u">
				      	<input type="hidden" name="login">
				      	<input type="hidden" name="show_kbd" value="{show_kbd}">
						<div id="captcha">{captcha}</div>
						<label for="user">{lang_username}</label>
						<input type="text" name="user" id="user"  value="{cookie}"/>
						<label for="passwd">{lang_password}</label>
						<input type="password" name="passwd" id="passwd" value=""/><br/>
						<input type="submit" name="submitit" id="submitit" value="{lang_login}" onclick="loginExpresso.setLogin();" />
					</form>
					<div id="msg-login" class="sucesso" style="display:none;">{cd}</div>
				</div>
				<div id="information-login"></div>
			</div>
			<center>
				<div id="footer">
					{login_logo_file}
				</div>
			</center> 
		</div>
		<div>{applet}</div>
		<!-- JavaScript -->
		<script type="text/javascript" src="./prototype/plugins/jquery/jquery-latest.min.js"></script>
		<script type="text/javascript" src="./prototype/plugins/jquery/jquery-migrate.min.js"></script>
		<script type="text/javascript" src="./prototype/plugins/jquery/jquery-ui-latest.min.js"></script>
		<script type="text/javascript" src="./prototype/plugins/ejs/ejs.js"></script>
		<script type="text/javascript" src="./prototype/plugins/ejs/ejs_production.js"></script>
		<script type="text/javascript" src="./prototype/plugins/ejs/view.js"></script>
		<script type="text/javascript" src="./prototype/plugins/jquery.cycle/jquery.cycle.js"></script>
		<script type="text/javascript" src="./prototype/plugins/jquery.keyboard/jquery.keypad.js"></script>
		<script type="text/javascript" src="./prototype/plugins/jquery.keyboard/jquery.keypad-pt-BR.js"></script>
		<script type="text/javascript" src="./phpgwapi/templates/news/js/slider.js"></script>
		<script type="text/javascript" src="./phpgwapi/templates/news/js/loginExpresso.js"></script>
	</body>
<!-- END login_form -->
</html>
