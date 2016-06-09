<!-- BEGIN list -->

<!--CSS -->
<link rel="stylesheet" type="text/css" href="./prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css" />

<div style="margin-left:50px;text-align:center">
	<h2>{lang_total}: {total}<h2>
	<div id="description" >{description}</div>
	<button onClick="document.location.href='{back_url}'">{lang_back}</button>

</div>
<!-- JavaScript -->
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-latest.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-migrate.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-ui-latest.min.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		$(this).find('button').button();
		$("#description > label")
			.css("margin","10px 0px")
			.css("font-size","10pt");
	});
</script>
<!-- END list -->