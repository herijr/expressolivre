
<!-- BEGIN m_w_table -->

<table id="calendar_m_w_table" class="calendar_m_w_table" border="0" width="100%">
	<! from month_header.tpl -->
	{row}
</table>
<!-- END m_w_table -->

<!-- BEGIN month_daily -->
<span class="screen_only">[ </span><span class="calendar_m_w_table_daynumber" >{day_number}</span><span class="screen_only"> ]</span>{new_event_link}<br />
    {daily_events}
<!-- END month_daily -->

<!-- BEGIN day_event -->
{events}
<!-- END day_event -->

<!-- BEGIN event -->
{day_events}
<!-- END event -->
