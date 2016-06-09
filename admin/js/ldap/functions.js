(function()
{
	var _conn = '';
	var _xtools = '';

	function addGroup(group)
	{
		var select_ldap = document.getElementById('groups_ldap');
		for(var i = 0; i < select_ldap.options.length ; i++)
		{
			if( select_ldap.options[i].selected )
			{
				var select_grp =  document.getElementById(group);
				var flag = false;
				for(var j = 0; j < select_grp.options.length ; j++ )
				{
					if( select_grp.options[j].value === select_ldap.options[i].value )
						flag = true;
				}
				if ( !flag ) {
					var option = select_ldap.options[i].value.split(";");
					select_grp.options[select_grp.length] = new Option(option[0], select_ldap.options[i].value, false, true);
					var name = option[0];
					var id = option[1];
					var li = $('#grps_hidden > li[id=grp_'+id+']').first();
					if (li.length == 0) {
						li = $('#grps_hidden > li[id=grp_default]').first().clone();
						$(li).attr('id','grp_'+id);
						$(li).find('.grp_title').html(name);
						$(li).find('.grp_passwd').attr('name','grp_passwd['+id+']');
						$(li).find('.grp_user').attr('name','grp_user['+id+']');
						$(li).find('.grp_priority').attr('name','grp_priority['+id+']');
					}
					$(li).appendTo('#grps_sortable');
				}
			}
		}
		$('#grps_sortable').find('.grp_priority').each(function(idx){$(this).val(idx);});
	}
	
	function createObject()
	{
		if ( typeof(_conn) != "object")
			_conn = new ADMConnector(path_adm + 'admin' );	

		if ( typeof(_xtools) != "object" )
			_xtools = new ADMXTools(path_adm + 'admin');
	}

	function CompleteSelect(data)
	{
		var select_ldap = document.getElementById('groups_ldap');
		data = _xtools.convert(data);

		while( select_ldap.hasChildNodes())
			select_ldap.removeChild(select_ldap.firstChild);
		
		try
		{
			if ( data && data.documentElement && data.documentElement.hasChildNodes() )
			{
				data = data.documentElement.firstChild;
				
				while(data)
				{
					var option = data.firstChild.nodeValue.split(";");
					select_ldap.options[select_ldap.options.length] = new Option(option[0],data.firstChild.nodeValue, false, false); 
					data = data.nextSibling;
				}
			}
		}catch(e){}

		styleVisible('hidden');
	}
	
	function LTrim(value)
	{
		var w_space = String.fromCharCode(32);
		var strTemp = "";
		var iTemp = 0;
		
		if(v_length < 1)
			return "";
	
		var v_length = value ? value.length : 0;
		
		while(iTemp < v_length)
		{
			if(value && value.charAt(iTemp) != w_space)
			{
				strTemp = value.substring(iTemp,v_length);
				break;
			}
			iTemp++;
		}	
		return strTemp;
	}
	
	
	function SearchOu(select,action)
	{
		createObject();
		var organization = "";
		
		if( arguments.length > 0 )
		{
			var element = arguments[0];
			styleVisible('visible');
		}

		if( element.options.length > 0 )
			for(var i = 0; i < element.options.length ; i++ )
				if( element.options[i].selected )
					organization = 'ou=' + element.options[i].value;

		_conn.go(action, CompleteSelect, organization);
	}
	
	function Selected(group)
	{
		var select_grp = document.getElementById(group);
		for( var i = 0 ; i < select_grp.options.length; i++ )
			select_grp.options[i].selected = true;
	}

	function styleVisible(pVisible)
	{
		document.getElementById('admin_span_loading').style.visibility = pVisible;
	}
	
	function removeGroup(group)
	{
		var select_grp = document.getElementById(group);
		
		for(var i = 0 ; i < select_grp.options.length; i++ )
		{
			if( select_grp.options[i].selected )
			{
				var id = $(select_grp.options[i]).val().split(";")[1];
				$('#grps_sortable').find('li[id=grp_'+id+']').appendTo('#grps_hidden');
				select_grp.options[i].parentNode.removeChild(select_grp.options[i]);
				i--;
			}
		}
		$('#grps_hidden').find('.grp_priority').val(-1);
		$('#grps_sortable').find('.grp_priority').each(function(idx){$(this).val(idx);});
	}
	
	function validateEmail()
	{
		if( arguments.length > 0 )
		{
			var element = arguments[0];
			var validate = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			
			if(LTrim(element.value) != "" && element.value != "")
			{
				if(!validate.test(element.value))
				{
					alert('Email field is not valid' + '.');
					element.focus();
					return false;
				}
			}
		}
	}

	function Ldap()
	{
	
	}

	Ldap.prototype.search	= SearchOu;
	Ldap.prototype.add		= addGroup;
	Ldap.prototype.remove	= removeGroup;
	Ldap.prototype.select_	= Selected;
	Ldap.prototype.validateEmail = validateEmail;
	window.ldap = new Ldap;

})();

$(document).ready(function() {
	$('#grps_sortable').sortable({
		stop: function( event, ui ) {
			$('#grps_sortable').find('.grp_priority').each(function(idx){$(this).val(idx);});
		}
	});
});
