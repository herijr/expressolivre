<!-- BEGIN main -->

<!--CSS -->
<link rel="stylesheet" type="text/css" href="./prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css" />
<link rel="stylesheet" type="text/css" href="./emailadmin/templates/default/css/listConfigurations.css" />

<div id="body">
	<div id="configurations">
		<ul>
			<li>
				<a href="{link_profile_server}">
					<img src="./emailadmin/templates/default/images/profiles-icon.png">
					<span>{lang_manage_profile}</span>
				</a>
			</li>
			<li>
				<a href="{link_server_mx}">
					<img src="./emailadmin/templates/default/images/computer-add-icon.png">
					<span>{lang_manage_domains}</span>
				</a>
			</li>
		</ul>
	</div>

	<div id="navigation">
		<button>{lang_back}</button>
	</div>
</div>

<!-- JavaScript -->
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-latest.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-migrate.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-ui-latest.min.js"></script>
<script type="text/javascript">
	$("div button").button().click(function(){
		window.location = '{link_back_page}';
	});
</script>

<!-- END main -->