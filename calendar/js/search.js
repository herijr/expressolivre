var searchTimeout;

function lang(key){
	return document.getElementById("txt_"+key).value;
}

function remove_user(id){
	if(!confirm(lang("confirm")))
		return true;	
	var handler_rem_user = function(data){
		if(data) {
			var tr = document.getElementById(id);
			tr.parentNode.removeChild(tr);
		}
	}	
	cExecute ('calendar.uigroup_access.rem_user&id='+id, handler_rem_user);
}
function add_user(){
	var select_users = document.getElementById("user");
	var select_groups = document.getElementById("group");
	var a_rights = new Array("L","A","E","R","P");
	var rights = '';
	for(var z = 0; z < a_rights.length; z++){
		var check = document.getElementById("right_"+a_rights[z]);
		if(check.checked){
			rights += check.value;
		}
	}
	if(!select_users.value){
		alert(lang("nouser"));
		return;
	}
	else if(!select_groups.value){
		alert(lang("nogroup"));
		return;
	}
	else if(!rights){
		alert(lang("nopermissiontype"));
		return;
	}
	
	var str = select_users.value+";"+select_groups.value;
	var handler_add_user = function(data){
		
		if(data){
			var t = document.getElementById('tbody_list');
			var tr = document.createElement("TR");
			tr.bgColor = "#DCDCDC";
			var td1 = document.createElement("TD");
			var td2 = document.createElement("TD");
			var td3 = document.createElement("TD");
			var td4 = document.createElement("TD");
			tr.id = str;
			td1.innerHTML = "<b>&nbsp;&nbsp;"+select_users[select_users.selectedIndex].text+"</b>";
			td2.innerHTML = rights;
			td2.align = "center";
			td3.innerHTML = "&nbsp;&nbsp;"+select_groups[select_groups.selectedIndex].text;
			td4.innerHTML = "<button  title='remove' type='button' onClick='javascript:remove_user(\""+str+"\");'><img src='"+document.getElementById("template_set").value+"/images/delete.png' style='vertical-align: middle;'/></button>";
			tr.appendChild(td1);
			tr.appendChild(td2);
			tr.appendChild(td3);
			tr.appendChild(td4);
			t.appendChild(tr);
			alert(lang("success"));
		}
		else{
			alert(lang("exist"));
		}
	}
	cExecute ('calendar.uigroup_access.add_user&id='+str+"&rights="+rights, handler_add_user);
	return true;
		
}
function search_object(input, id_span, id_select, type)
{
	clearTimeout(searchTimeout);	
	var spam = document.getElementById(id_span);
	if (input.value.length <= 3){
		spam.innerHTML = lang("typemoreletters").replace("X",4 - input.value.length);
	}else{
		spam.innerHTML = lang("searching")+'...';
		searchTimeout = setTimeout("search_ldap('"+input.id+"','"+id_span+"','"+id_select+"','"+type+"')",750);
	}
}

function search_ldap(id_input, id_span, id_select, type)
{
	var search = document.getElementById(id_input).value;
	
	var handler_search_user = function(data)
	{
		var spam = document.getElementById(id_span);
		select_available_users = document.getElementById(id_select);
		if (data.status == 'false')
		{
			spam.innerHTML = data.msg;
			// Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
			select_available_users.innerHTML = '#';
			select_available_users.outerHTML = select_available_users.outerHTML;
			return;
		}	
		spam.innerHTML = '';
		// Necessario, pois o IE6 tem um bug que retira o primeiro options se o innerHTML estiver vazio.
		select_available_users.innerHTML = '#' + data;
		select_available_users.outerHTML = select_available_users.outerHTML;
	}
	cExecute ('calendar.uigroup_access.search_user&search='+search+"&type="+type, handler_search_user);
}