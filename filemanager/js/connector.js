var progressBar;

if (document.all)
{
	navigator.userAgent.toLowerCase().indexOf('msie 5') != -1 ? is_ie5 = true : is_ie5 = false;
	is_ie = true;
	is_moz1_6 = false;
	is_mozilla = false;
	is_ns4 = false;
}
else if (document.getElementById)
{
	navigator.userAgent.toLowerCase().match('mozilla.*rv[:]1\.6.*gecko') ? is_moz1_6 = true : is_moz1_6 = false;
	is_ie = false;
	is_ie5 = false;
	is_mozilla = true;
	is_ns4 = false;
}
else if (document.layers)
{
	is_ie = false;
	is_ie5 = false
	is_moz1_6 = false;
	is_mozilla = false;
	is_ns4 = true;
}

/****************************************** Connector Class *************************************************/
// Constructor
function cConnector()
{
	this.requests = new Array();
	this.oxmlhttp = null;
	this.isVisibleBar = false;
	this.tid = 0;
	this.progressBar = null;
	this.oldX = 0;
	this.oldY = 0;
	this.updateVersion = "";
}
cConnector.prototype.buildBar = function()
{
	var div = document.getElementById('divProgressBar');

	if(! div) {
		div = document.createElement("DIV");
		//div.style.visibility	= "hidden";
		div.style.width = "103px";
		div.id = 'divProgressBar';
		div.align = "center";
		div.innerHTML = '&nbsp;&nbsp;<font face="Verdana" size="2" color="WHITE">'+get_lang('loading')+'...</font>&nbsp;';
		div.style.background = "#cc4444";
		div.style.position = 'fixed';
		div.style.top = '0px';
		div.style.right = '0px';
		document.getElementById('divAppboxHeader').appendChild(div);

		if(is_ie) {
			var elem = document.all[div.id];
			elem.style.position="absolute";
			var root = document.body;
			var posX = elem.offsetLeft-root.scrollLeft;
			var posY = elem.offsetTop-root.scrollTop;
			root.onscroll = function() {
				elem.style.right = '0px';
				elem.style.top = (posY + root.scrollTop) + "px";
			};
		}
	}
}

cConnector.prototype.hideProgressBar = function ()
{
	var div = document.getElementById('divProgressBar');
	if (div != null)
		div.style.visibility = 'hidden';
	else
		setTimeout('connector.hideProgressBar()',100);
	this.isVisibleBar = false;
}

cConnector.prototype.showProgressBar = function(){
	var div = document.getElementById('divProgressBar');
	if (! div){
		connector.buildBar();
		return;
	}

	div.style.visibility = 'visible';

	this.isVisibleBar = true;
}

	function XMLTools()
	{
		this.path = "";
	}
var connector = new cConnector();

function cExecuteForm_(form, handler){
	connector.showProgressBar();
	
	if( ! ( divUpload = document.getElementById('divUpload') ) )
	{
		divUpload		 = document.createElement('DIV');                
		divUpload.id		 = 'divUpload';
		document.body.appendChild(divUpload);
	}

	handlerExecuteForm = handler;
	
	var form_handler = function (data)
	{
		handlerExecuteForm(data);
		handlerExecuteForm = null;
	}
	
	divUpload.innerHTML= "<iframe onload=\"connector.hideProgressBar();cExecute_('./index.php/index.php?menuaction=filemanager.uifilemanager.getReturnExecuteForm',"+form_handler+");\"  style='display:none;width:0;height:0;' name='uploadFile'></iframe>";
	
	form.target ="uploadFile";
	form.submit();
}

function cExecute_( requestURL, handler, params)
{
	if (connector.isVisibleBar == true)
	{
		setTimeout('cExecute_("'+requestURL+'",'+handler+')',150);
		return;
	}
	
	connector.showProgressBar();
	
	var AjaxRequest = function () 
	{
		Ajax = false;
		if (window.XMLHttpRequest) //Gecko
			Ajax = new XMLHttpRequest();
		else
			if (window.ActiveXObject) //Other nav.
				try
				{
					Ajax = new ActiveXObject("Msxml12.XMLHTTP");
				} catch (e)
		{
			Ajax = new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	
	var responseRequest = function()
	{
		try
		{
			if ( Ajax.readyState == 4 )
			{
				switch ( Ajax.status )
				{
					case 200:
						if (typeof(handler) == 'function')
						{																
							connector.hideProgressBar();
							var data = Ajax.responseText;
							handler(data);
						}

						break;

					case 404:
						
						alert(get_lang('Page Not Found!'));
						break;

					default:												
				}
			}
		}
		catch (e)
		{			
			connector.hideProgressBar();
			// View Exception in Javascript Console
			throw(e);
		}
	}
	
	AjaxRequest();
	
	if (!Ajax){
		throw("No connection");
		return;
	}

	if( typeof(params) == 'undefined' )
	{
		Ajax.open('GET', requestURL, true);
		Ajax.onreadystatechange = responseRequest;
		Ajax.send(null);
	}	
	else
	{
		Ajax.open("POST", requestURL, true);
		Ajax.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		Ajax.onreadystatechange = responseRequest;
		Ajax.send( params );
	}
}

//Unserialize Data Method
//discuss at: http://phpjs.org/functions/unserialize/
function unserialize( data, isUTF8 )
{
	var that = this;
	isUTF8 = !!(isUTF8);
	
	var utf8Overhead = function( chr ) {
		var code = chr.charCodeAt( 0 );
		if ( code < 0x0080 ) return 0;
		if ( code < 0x0800 ) return 1;
		return 2;
	};
	
	var error = function( type, msg, filename, line ) {
		console.log( type + msg );
	};
	
	var read_until = function( data, offset, stopchr ) {
		var i = 2,
		buf = [],
		chr = data.slice( offset, offset + 1 );
		
		while ( chr != stopchr ) {
			if ( ( i + offset ) > data.length ) error('Error', 'Invalid');
			buf.push( chr );
			chr = data.slice( offset + ( i - 1 ), offset + i );
			i += 1;
		}
		return [buf.length, buf.join('')];
	};
	
	var read_chrs = function( data, offset, length ) {
		var i, chr, buf;
		
		buf = [];
		for ( i = 0; i < length; i++ ) {
			chr = data.slice( offset + ( i - 1 ), offset + i );
			buf.push( chr );
			if ( isUTF8 ) length -= utf8Overhead( chr );
		}
		return [buf.length, buf.join('')];
	};
	
	var _unserialize = function( data, offset ) {
		var dtype, dataoffset, keyandchrs, keys,
			length, array, readdata, readData, ccount,
			stringlength, i, key, kprops, kchrs, vprops,
			vchrs, value, chrs = 0,
			typeconvert = function( x ) { return x; };
		
		if ( !offset ) offset = 0;
		dtype = ( data.slice( offset, offset + 1 ) ).toLowerCase();
		
		dataoffset = offset + 2;
		
		switch ( dtype ) {
			case 'i':
				typeconvert = function( x ) { return parseInt( x, 10 ); };
				readData = read_until( data, dataoffset, ';' );
				chrs = readData[0];
				readdata = readData[1];
				dataoffset += chrs + 1;
				break;
			case 'b':
				typeconvert = function( x ) { return parseInt( x, 10 ) !== 0; };
				readData = read_until( data, dataoffset, ';' );
				chrs = readData[0];
				readdata = readData[1];
				dataoffset += chrs + 1;
				break;
			case 'd':
				typeconvert = function( x ) { return parseFloat( x ); };
				readData = read_until( data, dataoffset, ';' );
				chrs = readData[0];
				readdata = readData[1];
				dataoffset += chrs + 1;
				break;
			case 'n':
				readdata = null;
				break;
			case 's':
				ccount = read_until( data, dataoffset, ':' );
				chrs = ccount[0];
				stringlength = ccount[1];
				dataoffset += chrs + 2;
				readData = read_chrs( data, dataoffset + 1, parseInt( stringlength, 10 ) );
				chrs = readData[0];
				readdata = readData[1];
				dataoffset += chrs + 2;
				if ( chrs != parseInt( stringlength, 10 ) && chrs != readdata.length )
					error( 'SyntaxError', 'String length mismatch' );
				break;
			case 'a':
				readdata = new Array();
				keyandchrs = read_until( data, dataoffset, ':' );
				chrs = keyandchrs[0];
				keys = keyandchrs[1];
				dataoffset += chrs + 2;
				length = parseInt( keys, 10 );
				for ( i = 0; i < length; i++ ) {
					kprops = _unserialize( data, dataoffset );
					kchrs = kprops[1];
					key = kprops[2];
					dataoffset += kchrs;
					vprops = _unserialize( data, dataoffset );
					vchrs = vprops[1];
					value = vprops[2];
					dataoffset += vchrs;
					readdata[key] = value;
				}
				dataoffset += 1;
				break;
			default:
				error( 'SyntaxError', 'Unknown / Unhandled data type(s): ' + dtype );
				break;
		}
		return [dtype, dataoffset - offset, typeconvert( readdata )];
	};
	return _unserialize( ( data + '' ), 0 )[2];
}

// Serialize Data Method
function serialize(data)
{
	var f = function(data)
	{
		var str_data;

		if (data == null ||
				(typeof(data) == 'string' && data == ''))
		{
			str_data = 'N;';
		}

		else switch(typeof(data))
		{
			case 'object':
				var arrayCount = 0;

				str_data = '';

				for (i in data)
				{
					if (i == 'length')
					{
						continue;
					}

					arrayCount++;
					switch (typeof(i))
					{
						case 'number':
							str_data += 'i:' + i + ';' + f(data[i]);
							break;

						case 'string':
							str_data += 's:' + i.length + ':"' + i + '";' + f(data[i]);
							break;

						default:
							showMessage(Element('cc_msg_err_serialize_data_unknown').value);
							break;
					}
				}
				if (!arrayCount)
				{
					str_data = 'N;';        
				}
				else
				{
					str_data = 'a:' + arrayCount + ':{' + str_data + '}';
					}

					break;

					case 'string':
					str_data = 's:' + data.length + ':"' + data + '";';
					break;

					case 'number':
					str_data = 'i:' + data + ';';
					break;

					case 'boolean':
					str_data = 'b:' + (data ? '1' : '0') + ';';
					break;

					default:
					showMessage(Element('cc_msg_err_serialize_data_unknown').value);
					return null;
				}

				return str_data;
		}

		return f(data);
	}
