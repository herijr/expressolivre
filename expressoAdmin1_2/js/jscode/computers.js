function search_organization(event, key)
{
	var organizations = document.getElementById('ea_combo_org_info');
	var RegExp_org = new RegExp("\\b"+key, "i");
	var k = event? event.which || event.keyCode : 0;
	var inc = ( k == 38 )? -1 : 1;
	var ini = ( k == 13 || k == 38 || k == 40 )? $(organizations)[0].selectedIndex + inc : 0;
	
	for ( i = ini; i < organizations.length && i >= 0; i += inc )
	{
		if (RegExp_org.test(organizations[i].text))
		{
			organizations[i].selected = true;
			return;
		}
	}
	
	if ( k == 13 ) search_organization( undefined, key );
}