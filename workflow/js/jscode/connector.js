/****************************************** Public variables *************************************************/
var debug_controller =false;
var files = new Array();
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
		is_ie5 = false;
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
	};
	
	cConnector.prototype.buildBar = function()
		{			
			div = document.getElementById('divProgressBar');
		
			if(! div) {												
				div = document.createElement("DIV");
				div.style.visibility	= "hidden";		
				div.style.width = "103px";
				div.id = 'divProgressBar';
				div.align = "center";
				div.innerHTML = '&nbsp;&nbsp;<font face="Verdana" size="2" color="WHITE">'+document.getElementById('txt_loading').value+'...</font>&nbsp;';
				div.style.background = "#cc4444";
				div.style.position = 'fixed';
				div.style.top = '0px';
				div.style.right = '0px';
				document.body.appendChild(div);																
				div = document.getElementById('divProgressBar');				
				
				if(is_ie) {
					var elem = document.all[div.id]; 
					elem.style.position="absolute";
					var root = document.body;
					var posX = elem.offsetLeft-root.scrollLeft;
					var posY = elem.offsetTop-root.scrollTop;
					root.onscroll = function() {
						//elem.style.left = (posX + root.scrollLeft) + "px";
						elem.style.right = '0px';
						elem.style.top = (posY + root.scrollTop) + "px";
					};
					document.body.insertAdjacentHTML("beforeEnd", '<iframe id="divBlank" src="about:blank" style="position:absolute; visibility:hidden" scrolling="no" frameborder="0"></iframe>');
					
				}
				
				if(debug_controller) {
					div = document.createElement("DIV");
					div.style.width	= "800px";
					div.style.height= "400px";
					div.id = "debug_controller";
					div.align='right';
					document.body.appendChild(div);																
				}
			}								
	};	
//------------------------------------ BEGIN: Functions for Connector HTTPRequest  -------------------------------------------------//	
	// Serialize Data Method
	cConnector.prototype.serialize = function(data)
	{	var _thisObject = this;		
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
								str_data += 'i:' + i + ';' + _thisObject.serialize(data[i]);
								break;
	
							case 'string':
								str_data += 's:' + i.length + ':"' + i + '";' + _thisObject.serialize(data[i]);
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
		};
	
		var sdata = f(data);
		return sdata;
	};
	
	// Unserialize Data Method
	// discuss at: http://phpjs.org/functions/unserialize/
	cConnector.prototype.unserialize = function( data, isUTF8 )
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
	
	//Create XMLHTTP object Method
	cConnector.prototype.createXMLHTTP = function ()
	{	
		try
		{ 
			this.oxmlhttp = new XMLHttpRequest();
			this.oxmlhttp.overrideMimeType('text/xml');
		}
		catch (e)
		{ 
			try
			{
				this.oxmlhttp = new ActiveXObject('Msxml2.XMLHTTP');
			}
			catch (e1)
			{ 
				try
				{
					this.oxmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
				}
				catch (e2)
				{
					this.oxmlhttp = null;
				}
			}
		}
	
	};
	
	// Request Constructor Connector	
	cConnector.prototype.newRequest = function (id, target, method, handler, data)
	{				
		
		if (this.requests[id]) {
			return false;
		}
	
		this.createXMLHTTP();
		var oxmlhttp = this.oxmlhttp;
		var _thisObject = this;		
		
		if (! oxmlhttp)		
			return false;
				
		this.requests[id] = oxmlhttp;
		this.buildBar();		
		this.showProgressBar();
		
		var sub_handler = function ()
		{			
			var progressBar = _thisObject.progressBar;
			
			try
			{
				if (oxmlhttp.readyState == 4 )
				{
					
					switch (oxmlhttp.status)
					{
						
						case 200:
							if (typeof(handler) == 'function')
							{																
								_thisObject.hideProgressBar();
								var isUTF8 = ( oxmlhttp.getResponseHeader('Content-Type').search(/utf-8/i) !== -1);
								var data = _thisObject.unserialize( oxmlhttp.responseText, isUTF8 );
								if(debug_controller) {
									document.getElementById("debug_controller").innerHTML += oxmlhttp.responseText;
									document.getElementById("debug_controller").innerHTML += "<br>-------------------------------------------------------------------------------------<br>";
								}									
								handler(data, oxmlhttp);
								delete _thisObject.requests[id];								
								_thisObject.requests[id] = null;
							}

							break;

						case 404:
							
							alert('Page Not Found!');
							break;

						default:												
					}
				}
			}
			catch (e)
			{			
				_thisObject.hideProgressBar();
				if(debug_controller)
					alert(e+"\n"+oxmlhttp.responseText);
			}
						
		};

		try
		{ 
			
			if (method == '' || method == 'GET')
			{								
				oxmlhttp.open("GET",target,true);
				if (typeof(handler) == 'function')
				{	
					oxmlhttp.onreadystatechange =  sub_handler;					
					oxmlhttp.send(null);					
				}		
				
			}
			else if (method == 'POST')
			{
				oxmlhttp.open("POST",target, true);
				oxmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
				if (typeof(handler) == 'function')
				{
					oxmlhttp.onreadystatechange = sub_handler;
					oxmlhttp.send(data);
				}				
				
			}
		}
		catch(e)
		{	
			_thisObject.hideProgressBar();
			if(debug_controller)
				alert(e);		 
		}
						
		return true;
	};
	// Cancel Request Connector
	cConnector.prototype.cancelRequest = function (id)
	{
		if (!this.requests[id])
		{
			return false;
		}

		this.requests[id].abort();
	};
//------------------------------------  END: Functions for Connector HTTPRequest  -------------------------------------------------//

//	----------------------------------- BEGIN: Functions for build Bar Progress ---------------------------------------------------------//
	cConnector.prototype.hideProgressBar = function ()
	{
		div = document.getElementById('divProgressBar');
		div.style.visibility = 'hidden';
	
		if(is_ie) {
			divB = document.getElementById('divBlank');	
			divB.style.visibility = 'hidden';
		}
		this.isVisibleBar = false;
	};
	
	cConnector.prototype.showProgressBar = function(){
		div.style.visibility = 'visible';			

		this.isVisibleBar = true;
	};
//------------------------------------  END: Functions for Progress Bar  -------------------------------------------------//



	// Default Controller File
	var DEFAULT_URL = _web_server_url + '/workflow/controller.php?action=';
	// connector object
	var connector = new cConnector();
	var isExecuteForm = false;
	var id = null;

	// 	Function executes AJAX
	// 	cExecute (url, handler, params)
	//	url: 'module.class.method'
	//  handle: function handle() receive response.
	//  params: parameters for POST method
	//	form: form element (for upload files)	
	function cExecute(url, handler, params, form) {
		isExecuteForm = false;
		if(form) {
			cExecuteForm(url, form);
			return;
		}
		
		url = DEFAULT_URL + url;
			
		if(params)		 
			method = "POST";
			 	
		 else 
			method = "GET";
			 
		 id = url;
		connector.newRequest(id, url, method, handler, params);
	}

	/*
		Esta função pode ser utilizada para executar um submit de uma form de forma
		transparente para o usuário, de forma que o retorno do submit seja tratado pelo
		handler ajax
	*/ 	
	function cExecuteFormData(url, form, handler){
        connector.buildBar();
        isExecuteForm = true;
        connector.showProgressBar();
        if(! (divUpload = document.getElementById('divUpload'))) {
            divUpload       = document.createElement('DIV');
            divUpload.id    = 'divUpload';
            document.body.appendChild(divUpload);
        }

		if(! (el = document.getElementById('_form_data'))) {			
			el			= document.createElement('input');
			el.type	= 'hidden';
			el.id		= '_form_data';	
			el.name	= '_form_data';
			form.appendChild(el);
		}

        form._form_data.value = url;
		divUpload.innerHTML= "<iframe onload=\"cExecute('$this.ajax.getLastAjaxResponse',"+handler+");\"  style='display:"+(debug_controller ? "" : "none")+";width:"+(debug_controller ? 400 : 0)+";height:"+(debug_controller ? 400 : 0)+";' name='ifrmAjax'></iframe>";
        form.action =_web_server_url + '/workflow/controller.php';
		form.target = 'ifrmAjax';
        form.submit();
	}

	
	// This function executes submit values to Controller (POST)
	// The return is void.
	// 	cExecuteForm (url, form)
	//	url: 'module.class.method'
	//	form: form element (for upload files)	
	function cExecuteForm(url, form, handler){
		connector.buildBar();
		isExecuteForm = true;
		connector.showProgressBar();
		if(! (divUpload = document.getElementById('divUpload'))) {
			divUpload		= document.createElement('DIV');		
			divUpload.id	= 'divUpload';
			document.body.appendChild(divUpload);
		}

		if(! (el = document.getElementById('_action'))) {			
			el			= document.createElement('input');
			el.type	= 'hidden';
			el.id		= '_action';	
			el.name	= '_action';
			form.appendChild(el);
		}

		if(countFiles) {			
			el			= document.createElement('input');
			el.type	= 'hidden';	
			el.name	= 'countFiles';
			el.value 	= countFiles;
			form.appendChild(el);						
		}		

		form._action.value = url;
		divUpload.innerHTML= "<iframe onload=\"cExecute('$this.functions.getReturnExecuteForm',"+handler+");\"  style='display:"+(debug_controller ? "" : "none")+";width:"+(debug_controller ? 400 : 0)+";height:"+(debug_controller ? 400 : 0)+";' name='uploadFile'></iframe>";
		form.action ="controller.php";
		form.target ="uploadFile";		
		form.submit();
	}	
	
	
	document.onmousedown=alertBut;

	function alertBut( e, evElement ) {
    	if( !e ) {
        	if( window.event ) {
            	//DOM
	            e = window.event;
    	    } else {
        	    //TOTAL FAILURE, WE HAVE NO WAY OF REFERENCING THE EVENT
            	return;
	        }
    	}
	    if( typeof( e.which ) == 'number' ) {
	        //Netscape compatible
	        e = e.which;
	    } else {
	        if( typeof( e.button ) == 'number' ) {
	            //DOM
	            e = e.button;
	   		} 
	   		else {
	            //TOTAL FAILURE, WE HAVE NO WAY OF OBTAINING THE BUTTON
	        	return;
	        }
	    }
	    if( !evElement ) { evElement = this; }
	
		if(isExecuteForm) {
	    	if(confirm("Existe uma ação que ainda está sendo processada. Suspender ação?")) {
		    	connector.hideProgressBar();
		    	isExecuteForm = false;
		    	delete connector.requests[id];								
				connector.requests[id] = null;
	    		stop();		    		    		
	    		return;
	    	}
    		else
    			return false;
	    }
	    
	    
	}
