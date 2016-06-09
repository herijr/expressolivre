
<!-- BEGIN m_w_table -->

<table id="calendar_m_w_table" class="calendar_m_w_table" border="0" width="100%">
	<!-- from month_header.tpl -->
	{row}
</table>
<!-- END m_w_table -->


<table>
<!-- BEGIN month_daily -->
<tr><td style="font-size: 10px; font-weight: bold; text-decoration: underline;" width="10px">{day_number}</td>
<td>{daily_events}</td>
<!-- END month_daily -->

<!-- BEGIN day_event -->

<td>{events}</td>
<!-- END day_event -->

<!-- BEGIN event -->
<td>{day_events}</td></tr>
<!-- END event -->
<br />
</table>
