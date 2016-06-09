/**************************************************************************\
 Início 
\**************************************************************************/
	function Preferences(){
		this.prefeW 		= new Array;
	}

	// Salva uma unica preferencia
	Preferences.prototype.save = function(key, value){
		var _this = this;               
		var handler_preferences = function(data){
			if(data && data.success)
				return;                 
			else
			alert(data);
		}
		preferences[key] = value;
		var args   = "$this.db_functions.update_preferences";
		var params = "prefe_string="+url_encode(connector.serialize(preferences));
		cExecute(args,handler_preferences,params);
	}

	Preferences.prototype.delete_dynamic_contacts = function(){
		var handler = function(data){}
		var args   = "$this.dynamic_contacts.delete_dynamic_contacts";
		var params = "";
		cExecute(args,handler,params);
	}
// Cria o objeto	
	var prefe;
	prefe = new Preferences();
