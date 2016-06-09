/******************************************************************************\
|**************************   CALENDAR MODULE   *******************************|
|********** INCLUDE PARTICIPANTS - PLUGIN USING AJAX EXPRESSOMAIL COMPONENT  **|
\******************************************************************************/
var contacts = '';
var expresso_offline = '';
var array_lang = new Array();
var finderTimeout = '';
// onUnload edit message
if(!document.all)
{
	var beforeunload = window.onbeforeunload;
	window.onbeforeunload = function()
	{
		if ( typeof beforeunload == 'function' )
			beforeunload();
	};
}
	
// Verifica versão do Firefox
var agt = navigator.userAgent.toLowerCase();
var is_firefox_0 = agt.indexOf('firefox/1.0') != -1 && agt.indexOf('firefox/0.') ? true : false;
/*
	Semelhante a função get_avaiable_users, porém trás apenas usuários, ignorando os grupos...
*/
function get_available_only_users(path,context,type) {
	var handler_get_available_users2 = function(data)
	{	
		select_available_users = document.getElementById('user_list_in');
		
		for(var i=0; i<select_available_users.options.length; i++){
			select_available_users.options[i] = null;
			i--;
		}
		var options = '###';
		if (data.users && data.users.length > 0) {
			
			options += '<option  value="-1" disabled>------------------- '+document.getElementById("txt_users").value+' ------------------ </option>' + data.users;
			
			if(is_firefox_0)
				fixBugInnerSelect(select_available_users,options);
			else
				select_available_users.innerHTML = options;

			select_available_users.outerHTML = select_available_users.outerHTML;
			select_available_users.disabled = false;
			select_available_users_clone = document.getElementById('user_list_in').cloneNode(true);
			document.getElementById('cal_input_searchUser').value = '';
		}
	}	

	document.getElementById('combo_org').value = context;
	cExecute (path+'.ldap_functions.get_available_users&context='+context+'&type='+type, handler_get_available_users2);	
}

var handler_get_available_users = function(data)
{	
	select_available_users = Element('user_list_in');
	
	for(var i=0; i<select_available_users.options.length; i++){
		select_available_users.options[i] = null;
		i--;
	}
	var options = '###';
	if (data) {
		if(data.groups && data.groups.length > 0) {
			data.groups = '<option  value="-1" disabled>------------------- '+Element("txt_groups").value+' ------------------ </option>' + data.groups;
		}		
		if(data.users && data.users.length > 0) {
			data.users = '<option  value="-1" disabled>------------------- '+Element("txt_users").value+' ------------------ </option>' + data.users;
		}
		options +=  data.groups && data.groups.length > 0 ? data.groups : '';
		options +=  data.users  && data.users.length  > 0 ? data.users  : '';
		
		if(is_firefox_0)
			fixBugInnerSelect(select_available_users,options);
		else
			select_available_users.innerHTML = options;

		select_available_users.outerHTML = select_available_users.outerHTML;
		select_available_users.disabled = false;
		select_available_users_clone = Element('user_list_in').cloneNode(true);		
	}
}	

function get_available_users(module,context,type, autoSearch){
	Element('cal_input_searchUser').value = '';
	if(autoSearch != 'True'){
		return true;
	}
	var context = document.getElementById('combo_org').value;	
	cExecute (module+'.ldap_functions.get_available_users&context='+context+'&type='+type, handler_get_available_users);
}

function optionFinderTimeout(obj, numMin, type, autoSearch){
	var oWait = Element("cal_span_searching");
	oWait.innerHTML = 'Buscando...';
	clearTimeout(finderTimeout);		
	
	if(autoSearch == "True"){
		finderTimeout = setTimeout("optionFinderLocal('"+obj.id+"')",500);
	}
	else if (obj.value.length >= numMin){
		finderTimeout = setTimeout("optionFinderLdap('"+obj.id+"','"+numMin+"','"+type+"')",500);
	}else {
		oWait.innerHTML = 'Mínimo de '+numMin+' letras para pesquisa';
		var select_available_users_tmp = document.getElementById('user_list_in');
		for(var i = 0;i < select_available_users_tmp.options.length; i++)
		select_available_users_tmp.options[i--] = null;
	}
}
// Pesquisa Javascript
function optionFinderLocal(id){
	var oText = Element(id);
	var oWait = Element("cal_span_searching");
	var select_available_users_tmp = Element('user_list_in');
	for(var i = 0;i < select_available_users_tmp.options.length; i++)
		select_available_users_tmp.options[i--] = null;
	var RegExp_name = new RegExp("\\b"+oText.value, "i");

	for(i = 0; i < select_available_users_clone.length; i++){
		if (RegExp_name.test(select_available_users_clone[i].text) || select_available_users_clone[i].value =="-1")
		{
			sel = select_available_users_tmp.options;
			option = new Option(select_available_users_clone[i].text,select_available_users_clone[i].value);
			if( select_available_users_clone[i].value == "-1") option.disabled = true;
			sel[sel.length] = option;
		}
	}
	oWait.innerHTML = '&nbsp;';	
}

// Pesquisa LDAP
function optionFinderLdap(id,numMin, type){
		var oWait = Element("cal_span_searching");
		var oText = Element(id);
			
		if (oText.value.length < numMin)
		{
				oWait.innerHTML = '';
				var select_available_users_tmp = document.getElementById('user_list_in');
				for(var i = 0;i < select_available_users_tmp.options.length; i++)
					select_available_users_tmp.options[i--] = null;
		}
		
		if (oText.value.length >= numMin)
		{
			var context = document.getElementById('combo_org').value;
			cExecute ('expressoMail1_2.ldap_functions.search_users&context='+(context)+'&type='+(type == '' ? 'list' : 'search')+'&filter='+oText.value+'&use_my_org_units=true', handler_get_available_users);
			oWait.innerHTML = '&nbsp;';
		}
}

function add_user()
{
	var select_available_users = document.getElementById('user_list_in');
	var select_users = document.getElementById('user_list');
	var count_available_users = select_available_users.length;
	var count_users = select_users.options.length;
	var new_options = '';
	
	for (i = 0 ; i < count_available_users ; i++) {
		if (select_available_users.options[i].selected)	{
			if(document.all) {
				if ( (select_users.innerHTML.indexOf('value='+select_available_users.options[i].value)) == '-1' ) {
					new_options +=  '<option value='
								+ select_available_users.options[i].value
								+ '>'
								+ select_available_users.options[i].text
								+ '</option>';
				}
			}
			else if ( (select_users.innerHTML.indexOf('value="'+select_available_users.options[i].value+'"')) == '-1' ) {
					new_options +=  '<option value='
								+ select_available_users.options[i].value
								+ '>'
								+ select_available_users.options[i].text
								+ '</option>';
			}
		}
	}

	if (new_options != '') {

		if(is_firefox_0)
			fixBugInnerSelect(select_users,'###' + new_options + select_users.innerHTML);
		else
			select_users.innerHTML = '###' + new_options + select_users.innerHTML;

		select_users.outerHTML = select_users.outerHTML;
	}
}

function remove_user(){
	select_users = document.getElementById('user_list');
	
	for(var i = 0;i < select_users.options.length; i++)
		if(select_users.options[i].selected)
			select_users.options[i--] = null;
}

function submitValues(){
	var typeField = document.getElementById('cal[type]');
	if (typeField && typeField.value == 'hourAppointment') {
		if(document.getElementsByName('categories[]')[0].value == ""){
			alert(alert_msg);
			return false;
		}
	}
	var select_in = document.getElementById('user_list');
	for(i = 0; i < select_in.length; i++)
	 	select_in.options[i].selected = true;
}	

function Element(id){
	return document.getElementById(id);
}

function loadScript(scriptPath){

	if(!connector)
		throw new Error("Error : Connector is not loaded.");
	
	if(connector.oxmlhttp==null)
		connector.createXMLHTTP();
		
  	if (document.getElementById('uploadscript_'+scriptPath)) {
  		return;
   	}
  	
	connector.oxmlhttp.open("GET", scriptPath, false);
    connector.oxmlhttp.setRequestHeader('Content-Type','text/plain');
	connector.oxmlhttp.send(null);
	if(connector.oxmlhttp.status != 0 && connector.oxmlhttp.status != 200 || 	connector.oxmlhttp.status == 0 && connector.oxmlhttp.responseText.length == 0)
		throw new Error("Error " + connector.oxmlhttp.status + "("+connector.oxmlhttp.statusText+") when loading script file '"+scriptPath+"'");
	
	var head = document.getElementsByTagName("head")[0];
	var script = document.createElement("SCRIPT");
	script.id = 'uploadscript_'+scriptPath;
	script.type = 'text/javascript';		
	script.text = connector.oxmlhttp.responseText;
	head.appendChild(script);
	return;	
}

function showExParticipants(el,path)
{
	$("#tbl_ext_participants").css("display","");
	$(el).css("display","none");
	loadScript(path+"/js/DropDownContacts.js");
	loadScript("calendar/js/plugin_participants_extern.js");
	if( !contacts )
	{
		cExecute ( path + '.db_functions.get_dropdown_contacts', initPluginContacts );
	}
}

function hideExParticipants(el,path)
{
	Element('a_ext_participants').style.display = '';
	Element('tbl_ext_participants').style.display ='none';
}

function initPluginContacts( data )
{
	contacts = data;

	$.PluginParticipantExtern.init( { 'contacts' : data , 'elements' : $("#ex_participants") } );		
}

function fixBugInnerSelect(objeto,innerHTML){
/******
* select_innerHTML - altera o innerHTML de um select independente se é FF ou IE
* Corrige o problema de não ser possível usar o innerHTML no IE corretamente
* Veja o problema em: http://support.microsoft.com/default.aspx?scid=kb;en-us;276228
* Use a vontade mas coloque meu nome nos créditos. Dúvidas, me mande um email.
* Versão: 1.0 - 06/04/2006
* Autor: Micox - Náiron José C. Guimarães - micoxjcg@yahoo.com.br
* Parametros:
* objeto(tipo object): o select a ser alterado
* innerHTML(tipo string): o novo valor do innerHTML
*******/
    objeto.innerHTML = ""
    var selTemp = document.createElement("micoxselect")
    var opt;
    selTemp.id="micoxselect1"
    document.body.appendChild(selTemp)
    selTemp = document.getElementById("micoxselect1")
    selTemp.style.display="none"
    if(innerHTML.toLowerCase().indexOf("<option")<0){//se não é option eu converto
        innerHTML = "<option>" + innerHTML + "</option>"
    }
    innerHTML = innerHTML.replace(/<option/g,"<span").replace(/<\/option/g,"</span")
    selTemp.innerHTML = innerHTML
    for(var i=0;i<selTemp.childNodes.length;i++){
        if(selTemp.childNodes[i].tagName){
            opt = document.createElement("OPTION")
            for(var j=0;j<selTemp.childNodes[i].attributes.length;j++){
                opt.setAttributeNode(selTemp.childNodes[i].attributes[j].cloneNode(true))
            }
            opt.value = selTemp.childNodes[i].getAttribute("value")
            opt.text = selTemp.childNodes[i].innerHTML
            if(document.all){ //IEca
                objeto.add(opt)
            }else{
                objeto.appendChild(opt)
            }                    
        }    
    }
    document.body.removeChild(selTemp)
    selTemp = null
}

function changeViewMode(eltype){
	var chValue = eltype;
	switch (chValue){
		case 'hourAppointment':
			var names=new Array('title','priority','location','alarmhours','alarmminutes','recur_type','rpt_use_end','recur_interval');
			for (var i=0; i < names.length; i++)
			{
				var Field = document.getElementsByName('cal['+names[i]+']');
				if (Field[0])
					Field[0].parentNode.parentNode.style.display = "none";
			}
			Field = document.getElementById('rpt_label');
			Field.parentNode.parentNode.style.display = "none";
			Field = document.getElementsByName('participants[]');
			Field[0].parentNode.parentNode.style.display = "none";
			Field[1].parentNode.parentNode.style.display = "none";
			Field = document.getElementById('txt_loading');
			Field.parentNode.parentNode.style.display = "none";
			Field = document.getElementsByName('cal[rpt_day][]');
			Field[0].parentNode.parentNode.style.display = "none";
			break;
		case 'privateHiddenFields':
			var names=new Array('title','priority','location','alarmhours','alarmminutes','recur_type','rpt_use_end','recur_interval');
			for (var i=0; i < names.length; i++)
			{
				var Field = document.getElementsByName('cal['+names[i]+']');
				if (Field[0])
					Field[0].parentNode.parentNode.style.display = "";
			}
			Field = document.getElementById('rpt_label');
			Field.parentNode.parentNode.style.display = "";
			Field = document.getElementsByName('participants[]');
			Field[0].parentNode.parentNode.style.display = "none";
			Field[1].parentNode.parentNode.style.display = "none";
			Field = document.getElementById('txt_loading');
			Field.parentNode.parentNode.style.display = "none";
			Field = document.getElementsByName('cal[rpt_day][]');
			Field[0].parentNode.parentNode.style.display = "";
			break;
		default:
			var names=new Array('title','priority','location','alarmhours','alarmminutes','recur_type','rpt_use_end','recur_interval');
			for (var i=0; i < names.length; i++)
			{
				var Field = document.getElementsByName('cal['+names[i]+']');
				if (Field[0])
					Field[0].parentNode.parentNode.style.display = "";
			}
			Field = document.getElementById('rpt_label');
			if(Field) Field.parentNode.parentNode.style.display = "";
			Field = document.getElementsByName('participants[]');
			if(Field[0]) Field[0].parentNode.parentNode.style.display = "";
			if(Field[1]) Field[1].parentNode.parentNode.style.display = "";
			Field = document.getElementById('txt_loading');
			Field.parentNode.parentNode.style.display = "";
			Field = document.getElementsByName('cal[rpt_day][]');
			if(Field[0]) Field[0].parentNode.parentNode.style.display = "";
			break;
	}

}
function updateTitleField(select){
	var typeField = document.getElementsByName('cal[type]');
	if (typeField[0].value != 'hourAppointment')
		return;
	var titleField = document.getElementsByName('cal[title]');
	var optionsArray = select.childNodes;
	titleField[0].value = '';
	for(option in optionsArray)
		if (optionsArray[option].selected)
			titleField[0].value += optionsArray[option].text + ' ';
}

var __onLoad = window.onload;
window.onload = function(){ 
	__onLoad();
	var cal_type_element = document.getElementById('cal[type]');
	var cal_type = (cal_type_element) ? cal_type_element.value : null;
	changeViewMode(cal_type);
	if(cal_type == 'hourAppointment'){
		clearTimeout(timeout_get_available_users);
	}
};
