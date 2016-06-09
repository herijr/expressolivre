	/************************************************************************************\
	* Expresso Administração                 										    *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)   *
	* ----------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it			*
	*  under the terms of the GNU General Public License as published by the			*
	*  Free Software Foundation; either version 2 of the License, or (at your			*
	*  option) any later version.														*
	\************************************************************************************/

function load_lang(){
	cExecute ('$this/inc/load_lang', handler_load_lang);
}

var global_langs = new Array();

function handler_load_lang(data)
{
	global_langs = eval(data);
}

function get_lang(key_raw)
{
	if (typeof(key_raw)=='undefined')
		return 'Problemas com o lang';
	
	key = key_raw.replace(/ /g,"_");
	key = key.replace(/-/g,"");
	key = key.replace(/\./g,"");
	
	lang = eval("global_langs."+key.toLowerCase());
	
	if (typeof(lang)=='undefined')
		return key_raw + '*';
	else
		return lang;
	
}

function emailSuggestion_expressoadmin(use_suggestion_in_logon_script, concatenateDomain)
{
	if ( concatenateDomain != 'true' ) document.forms[0].mail.value = document.forms[0].uid.value;
	else Domain.redraw();
	
	Profile.checkMailEvent();
	
	if (use_suggestion_in_logon_script == 'true')
		document.forms[0].sambalogonscript.value = document.forms[0].uid.value+'.bat';
	document.forms[0].sambahomedirectory.value = '/home/'+document.forms[0].uid.value+'/';
}	

function FormataValor(event, campo)
{
	separador1 = '(';
	separador2 = ')';
	separador3 = '-';
		
	vr = campo.value;
	tam = vr.length;

	if ((tam == 1) && (( event.keyCode != 8 ) || ( event.keyCode != 46 )))
		campo.value = '';

	if ((tam == 3) && (( event.keyCode != 8 ) || ( event.keyCode != 46 )))
		campo.value = vr.substr( 0, tam - 1 );
	
	if (( tam <= 1 ) && ( event.keyCode != 8 ) && ( event.keyCode != 46 ))
 		campo.value = separador1 + vr;
		
	if (( tam == 3 ) && ( event.keyCode != 8 ) && ( event.keyCode != 46 ))
		campo.value = vr + separador2;
			
	if (( tam == 8 ) && (( event.keyCode != 8 ) && ( event.keyCode != 46 )))
		campo.value = vr + separador3;

	if ((( tam == 9 ) || ( tam == 8 )) && (( event.keyCode == 8 ) || ( event.keyCode == 46 )))
		campo.value = vr.substr( 0, tam - 1 );
}

function FormataCPF(event, campo)
{
	if (event.keyCode == 8)
		return;
	
	vr = campo.value;
	tam = vr.length;
	
	var RegExp_onlyNumbers = new RegExp("[^0-9.-]+");
	if ( RegExp_onlyNumbers.test(campo.value) )
		campo.value = vr.substr( 0, (tam-1));
	
	if ( (campo.value.length == 3) || (campo.value.length == 7) )
	{
		campo.value += '.';
	}
	
	if (campo.value.length == 11)
		campo.value += '-';
	return;
	
	
	alert(campo.value);
	return;
	
	separador1 = '.';
	separador2 = '-';
		
	vr = campo.value;
	tam = vr.length;

	if ((tam == 1) && (( event.keyCode != 8 ) || ( event.keyCode != 46 )))
		campo.value = '';

	if ((tam == 3) && (( event.keyCode != 8 ) || ( event.keyCode != 46 )))
		campo.value = vr.substr( 0, tam - 1 );
	
	if (( tam <= 1 ) && ( event.keyCode != 8 ) && ( event.keyCode != 46 ))
 		campo.value = separador1 + vr;
		
	if (( tam == 3 ) && ( event.keyCode != 8 ) && ( event.keyCode != 46 ))
		campo.value = vr + separador2;
			
	if (( tam == 8 ) && (( event.keyCode != 8 ) && ( event.keyCode != 46 )))
		campo.value = vr + separador3;

	if ((( tam == 9 ) || ( tam == 8 )) && (( event.keyCode == 8 ) || ( event.keyCode == 46 )))
		campo.value = vr.substr( 0, tam - 1 );
}

load_lang();
