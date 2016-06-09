<!-- BEGIN footer_table -->
       <hr clear="all">
       <font size="-1">
       <table border="0" width="100%" cellpadding="0" cellspacing="0">
        <tr>
{table_row}
	</tr>
	<tr style="display: {display}">
		<td colspan="4"><hr clear="all"></td>
	</tr>
       	{options}
        

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
	<tr style="display: {display}">
		<td>
			<font size="-1">
				<span >
					<b>{day_ini_label}:</b>
					<select id="{day_ini_name}" name="{day_ini_name}" onchange="{formonchange}">
						{row_ini}
					</select>
				</span>
	    		<br /> <br />
	    		<span style="display: {display}" >
					<label>
						<b>{num_dias_label}:</b>
					</label>
					<select id="{num_dias_name}" name="{num_dias_name}" onchange="{formonchange}"/>
						{row_qtd}
					</select>
					<br />
					<span style="font-size: 9px;">
						{tip}
					</span>
	   			</span>
    		</font>
			<input type="hidden" name="user" value="{user}">
			<noscript><input type="submit" value="{go}"></noscript>
		</td>
	</tr>
	<tr style="display: {display}">
		<td colspan='4' align='center'>{lang_print_in}: <a href='javascript:void(0)' onClick="javascript:window.open('index.php?menuaction=calendar.uicalendar.month&year={year}&month={month}&friendly=1&day_ini='+
						document.getElementById('{day_ini_name}').value+'&num_dias='+
						document.getElementById('{num_dias_name}').value+'&classic_print=0','','width=600,height=600,toolbar=no,scrollbars=yes,resizable=no');" 
						onMouseOver="window.status = '{lines}'">[{lines}]</a> | 
						<a href='javascript:void(0)' onClick="javascript:window.open('index.php?menuaction=calendar.uicalendar.month&year={year}&month={month}&friendly=1&day_ini='+
						document.getElementById('{day_ini_name}').value+'&num_dias='+
						document.getElementById('{num_dias_name}').value+'&classic_print=1','','width=600,height=600,toolbar=no,scrollbars=yes,resizable=no');" 
						onMouseOver="window.status = '{columns}'">[{columns}]</a>
						
						</td>
	</tr>
<!-- END num_dias -->
