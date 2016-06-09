
<!-- BEGIN link_event_pict -->
{picture}
<!-- END link_event_pict -->

<!-- BEGIN link_event_open -->
<table>

<!--<div id="calendar_event_entry" style="overflow:hidden;">-->
<!--<a class="event_entry" href="{link_link}" onMouseOver="window.status='{lang_view}'; return true;" onMouseOut="window.status=''; return true;" title="{desc} {location}"><br />--><br />
<!-- END link_event_open -->

<!-- BEGIN event_pict -->
<!--<img src="{pic_image}" width="{width}" height="{height}" title="{title}" border="0" />-->
<!-- END event_pict -->

<!-- BEGIN link_event_text_old -->
<!--<nobr>&nbsp;{time}&nbsp;</nobr> {title}&nbsp;{users_status}: <i>{desc}</i>({location}) -->
<!-- END link_event_text_old -->

<!-- BEGIN link_event_text -->
	<tr style="font-size: 10px;background: #dddddd;">
		<td valign="top" style="color: black; font-size: 11px;">
			<span>
				{time}
			</span>
		</td>
		<td style="font-size: 11px; border: #E8F0F0 1px solid;" colspan="3">
			<span><b>{title}</b></span>
			<br />
			<span><i>{desc}</i></span>
			<span>{location}</span>
			<br />
		</td>
	</tr>
<!-- END link_event_text -->

<!-- BEGIN link_event_close -->
<!--</a></div><br />-->
</table>
<!-- END link_event_close -->
