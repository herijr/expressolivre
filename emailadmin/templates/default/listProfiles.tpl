<!-- BEGIN main -->

<!--CSS -->
<link rel="stylesheet" type="text/css" href="./prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css" />
<link rel="stylesheet" type="text/css" href="./emailadmin/templates/default/css/listProfiles.css" />

<!-- Tables -->
<table id="tables_profiles">
    <thead>
    	<tr>
        	<th scope="col">{lang_description}</th>
            <th scope="col">{lang_Configuration_SMTP}</th>
            <th scope="col">{lang_Configuration_IMAP}</th>
            <th scope="col">{lang_edit}</th>
            <th scope="col">{lang_remove}</th>
        </tr>
    </thead>
    <tfoot>
    	<tr>
        	<td colspan="5">
        		<ul>
        			<li><a href="{link_previous}">{lang_previous}</a></li>
        			<li><a href="{link_next}">{lang_next}</a></li>
        		<ul>
        	</td>
        </tr>
    </tfoot>
    <tbody>
    	{rowsTable}
    </tbody>
</table>

<div id="navigation">
	<button id="back_page">{lang_back_page}</button>
	<button id="add_profile">{lang_add_profile}</button>
</div>

<!-- JavaScript -->
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-latest.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-migrate.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-ui-latest.min.js"></script>
<script type="text/javascript">
	//Back Page
	$("#back_page").button().click(function(){
		window.location = '{link_back_page}';
	});
	
	//Add Profile
	$("#add_profile").button().click(function(){
		window.location = '{link_add_new}';
	});
</script>

<!-- END main -->