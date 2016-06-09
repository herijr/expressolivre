<!-- BEGIN footer_table -->
       <hr clear="all">
       <font size="-1">
       <table border="0" width="100%" cellpadding="0" cellspacing="0">
        <tr>
{table_row}
	</tr>
       </table>
<!-- END footer_table -->
<!-- BEGIN footer_row -->
         <td valign="top" width="30%">
          <font size="-1">
           <form action="{action_url}" method="post" name="{form_name}">
            <B>{label}:</B>
			{hidden_vars}
            <select name="{form_label}" onchange="{form_onchange}">
	     {row}
	    </select>
	    <input type="hidden" name="user" value="{user}">
	    <input type="hidden" name="dia_ini" value="{dia_ini}">
            <noscript><input type="submit" value="{go}"></noscript>
	   </form>
	  </font>
	 </td>
<!-- END footer_row -->
<!-- BEGIN blank_row -->
         <td>
         {b_row}
         </td>
         <td>
         {b_row2}
         </td>
<!-- END blank_row -->

<!-- BEGIN num_dias -->
<td valign="top" width="30%">
 <font size="-1">
    <form action="{acao}" method="post" name="{formname}">
    <span style="display: {display}" >
 
	<label>
		<b>{num_dias_label}:</b>
	</label>
	<select name="{num_dias_name}" onchange="{formonchange}"/>
		{row_qtd}
	</select>
	<br />
	<span style="font-size: 9px;">
		{tip}
	</span>
	<br /> <br />
    </span>
<span style="display: {display}">
	<b>{day_ini_label}:</b>
	<select name="{day_ini_name}" onchange="{formonchange}">
	{row_ini}
	</select>
	</span>
<input type="hidden" name="user" value="{user}">
	<noscript><input type="submit" value="{go}"></noscript>
     </form>
 </font>
</td>
<!-- END num_dias -->
