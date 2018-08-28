<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <title>.:: API ::.</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">

<script type="application/javascript" src="./plugins/iscroll/src/iscroll.js"></script>

<link rel="stylesheet" href="./css/estilo.css"/>
<link rel="stylesheet" href="./css/smoothness/jquery-ui-1.9.2.custom.css"/>
<link rel="stylesheet" href="./plugins/prettify/prettify.css"/>

<!--JavaScript-->
<script type="text/javascript" src="./plugins/jquery/jquery-1.9.0.min.js"></script>
<script type="text/javascript" src="./plugins/jquery/jquery-ui-1.9.2.custom.min.js"></script>
<script type="text/javascript" src="./plugins/ejs/ejs.js"></script>

<script type="text/javascript" src="./plugins/ejs/view.js"></script>
<script type="text/javascript" src="./plugins/prettify/prettify.js"></script>
<script type="text/javascript" src="./js/functions.js"></script>
<script type="text/javascript" src="./js/execute.js"></script>
</head>
<style>


</style>
<body id="mainAppPageContent" >
  <div>

  <div id="page" class="left">
    <header id="pageHeader" class="topHeader">

      <span href="#" class="button" id="toggle-login"></span>

      <div id="login">
        <div id="triangle"></div>
        <h1>Configura&ccedil;&otilde;es</h1>
        <form>
          <div id="key_auth_server_expresso">
            <span style="font-weight:bold;color:red; font-size: 10px;">USU&Aacute;RIO N&Atilde;O AUTENTICADO</span>
            <input type="hidden">
          </div>
          <input type="text" id="serverAPI" name="serverAPI" placeholder="API" value="<? echo 'http://' . $_SERVER['HTTP_HOST'] . '/'; ?>" />
        </form>
      </div>

      <div id="pageMessage"></div>
<!--       <ul class="left topButtons">
        <li><a id="menuButton" href="#" class="menu">Menu</a></li>
      </ul>  --> 
      
      <div id="rightMenu"> 
        
      </div>

    </header>
    <div class="main">
      <div id="content" class="left">

      </div>
      <div id="contentDetail" class="right">

      </div>
    </div>
  </div>
</div>
</body>
</html>