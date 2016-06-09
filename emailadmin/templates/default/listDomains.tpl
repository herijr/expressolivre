<!-- BEGIN main -->

<!--CSS -->
<link rel="stylesheet" type="text/css" href="./prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css" />
<link rel="stylesheet" type="text/css" href="./emailadmin/templates/default/css/emailadmin.css" />
<link rel="stylesheet" type="text/css" href="./emailadmin/templates/default/css/listDomains.css" />

<!-- Lang -->
<input type="hidden" name="lang_added_domain" value="{lang_added_domain}">
<input type="hidden" name="lang_edited_domain" value="{lang_edited_domain}">
<input type="hidden" name="lang_label_profile" value="{lang_label_profile}">
<input type="hidden" name="lang_label_domain" value="{lang_label_domain}">
<input type="hidden" name="lang_label_ous" value="{lang_label_ous}">
<input type="hidden" name="lang_admin_add_domain" value="{lang_admin_add_domain}">
<input type="hidden" name="lang_admin_move_domain" value="{lang_admin_move_domain}">
<input type="hidden" name="lang_confirm_domain" value="{lang_confirm_domain}">
<input type="hidden" name="lang_confirm_move_domain" value="{lang_confirm_move_domain}">
<input type="hidden" name="lang_enter_domain" value="{lang_enter_domain}">
<input type="hidden" name="lang_erro_add_domain" value="{lang_erro_add_domain}">
<input type="hidden" name="lang_close" value="{lang_close}">
<input type="hidden" name="lang_domain_success" value="{lang_domain_success}">
<input type="hidden" name="lang_move" value="{lang_move}">
<input type="hidden" name="lang_msg_move_domain" value="{lang_msg_move_domain}">
<input type="hidden" name="lang_save" value="{lang_save}">
<input type="hidden" name="lang_add" value="{lang_add}">
<input type="hidden" name="lang_remove" value="{lang_remove}">

<div class="list_domains">
	<fieldset>
		<div>
            <form method="POST" action="{action_url}">
    			<label class="search_domain">{lang_search_domain}</label>
    			<input type="text" size="50" name="input_search_domain">
                <input type="submit" name="button_search_domain" value="{lang_search}">
            </form>
		</div>
        <div>
            <table id="tables_domains">
                <thead>
                    <tr>
                        <th scope="col">{lang_profile}</th>
                        <th scope="col">{lang_domain}</th>
                        <th scope="col">{lang_delete}</th>
                        <th scope="col">{lang_move_domain}</th>
                        <th scope="col">{lang_ou_list}</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <ul>
                                <li> {lang_domains_found} : <span>{countDomains}</span> </li>
                            <ul>
                        </td>
                    </tr>
                </tfoot>
                <tbody>
                    {rowsTable}
                </tbody>
            </table>
        </div>

        <div id="navigation">
            <button id="back_page">{lang_back_page}</button>
            <button id="add_domain">{lang_add}</button>
        </div>
	</fieldset>
</div>

<!-- Dialogs -->
<div id="div_add_domain" style="display:none;"></div>
<div id="div_confirm_domain" style="display:none;"></div>
<div id="div_move_domain" style="display:none;"></div>

<!-- JavaScript -->
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-latest.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-migrate.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-ui-latest.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/ejs/ejs_production.js"></script>
<script type="text/javascript" src="./prototype/plugins/ejs/view.js"></script>
<script type="text/javascript" src="./emailadmin/js/listDomains.js"></script>
<script type="text/javascript">
	// Button Back Page
	$("#back_page").button().click(function(){
		window.location = '{link_back_page}';
	});
</script>

<!-- END main -->