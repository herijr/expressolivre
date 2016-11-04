<script src="../../../prototype/plugins/jquery.mask-phone/jquery.mask-phone.js"></script>
<input type="hidden" name="sms_enabled" value="{sms_enabled}"/>
<input type="hidden" name="sms_send_number" value="{sms_send_number}"/>
<input type="hidden" name="sms_list_checked" value="{sms_list_checked}"/>
<br>
<center>{messages}</center>
<form method="POST" action="{form_action}">
	<table border="0">
		<tr>
			<td>{lang_birthday}:</td>
			<td>
				<input
					type="text"
					name="datanascimento"
					title="{lang_birthday}"
					size="13"
					maxlength="10"
					style="text-align: center;"
					value="{datanascimento}"
					onkeyup="formatDate(this)";
				>
			</td>
		</tr>
		<tr>
			<td>{lang_commercial_telephonenumber}:</td>
			<td>
				<input
					type="input"
					name="telephonenumber"
					id="telephonenumber"
					title="{lang_commercial_telephonenumber}"
					size=13
					maxlength="14"
					autocomplete="off"
					value="{telephonenumber}"
				>
				(xx)xxxx-xxxx
			</td>
		</tr>
		<tr>
			<td colspan="2" width="60px">
				<p style="text-align:justify; width:350px;"><b><font color='red'>{lang_ps_commercial_telephonenumber}</font></b></p>
			</td>
		</tr>
		<tr><td colspan=2 height="20px"></td></tr>
		<tr>
			<td>{lang_homephone_telephonenumber}:</td>
			<td>
				<input
					type="input"
					name="homephone"
					title="{lang_homephone_telephonenumber}"
					size=13
					maxlength="13"
					autocomplete="off"
					value="{homephone}"
					onkeyUp="FormatTelephoneNumber(event, this, 8);"
				>
				(xx)xxxx-xxxx
			</td>
		</tr>
		<tr>
			<td>{lang_mobile_telephonenumber}:</td>
			<td>
				<input
					type="input"
					name="mobile"
					title="{lang_mobile_telephonenumber}"
					size=13
					maxlength="14"
					autocomplete="off"
					value="{mobile}"
					onkeyUp="FormatTelephoneNumber(event, this, 9);"
				>
				(xx)xxxxx-xxxx
			</td>
			<td id="use_chk_code" style="display: none;">
				<style>
					.ft_left{ float:left; margin-left:5px; }
					.cl_red{color: #FF0000;}
					.bt_disabled{color: #BBBBBB; pointer:not-allowed;}
				</style>
				<input class="tr_recv_code ft_left" type="submit" name="send_new_code" value="{lang_send_new_code}" title="{lang_send_new_code}">
				<div class="tr_recv_code ft_left" style="margin-top: 2px;">{lang_ins_code}:</div>
				<img id="right_arrow" class="ft_left" src="./templates/default/images/right_arrow.png"/>
				<input class="tr_send_code ft_left cl_red" type="submit" name="send_code" value="{lang_send_code}" title="{lang_send_code}">
				<input class="tr_recv_code ft_left cl_red" type="input" name="mobile_code" title="{lang_code_title}" size=5 maxlength="5">
				<input type="hidden" name="mobile_msg" value="{lang_mobile_msg}">
			</td>
		</tr>
		<tr class="sms_opts" style="display: none;">
			<td>{lang_sms_auth}:</td>
			<td>
				<select name="mobile_autz">
					<option value="0" {sms_auth_no}>{lang_no}</option>
					<option value="1" {sms_auth_yes}>{lang_yes}</option>
				</select>
			</td>
		</tr>
		<tr><td colspan=2 height="20px"></td></tr>
		<tr>
			<td colspan="3">
				<div style="display: none;">
					<span id="confirm_send_new_code">{lang_confirm_send_new_code}</span>
					<span id="confirm_mobile_autz">{lang_confirm_mobile_autz}</span>
				</div>
				<table cellspacing="0">
					<tr>
						<td><input type="submit" name="change" value="{lang_change}"></td>
						<td>&nbsp;&nbsp;</td>
						<td><input type="submit" name="cancel" value="{lang_cancel}"></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
<script type="text/javascript">
    $("#telephonenumber").off("blur").on("blur", function(){ $(this).maskPhone(); })
</script>
<br>
<pre>{sql_message}</pre>
