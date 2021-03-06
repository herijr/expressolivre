var ExpressoAPI = new function() {
	
	var _id;
	
	var _context = "";
	
	var _crossdomain = "";
	
	var _auth = "";
	
	var _debug;
	
	var _data = {};
	
	this.id = function(value) {
		if(value == undefined) return _id;
		var int = parseInt(String(value))
		_id = (isNaN(int))? 0 : int;
		_data[_id] = {};
		this.resource('').type('POST').params({}).done().always().fail(this.defaultErrorCallback);
		return this;
	};
	
	this.defaultErrorCallback = function(response) {
		if(response && response.error && response.error.message) alert(response.error.message);
	};
	
	this.crossdomain = function(value) {
		if(value == undefined) return _crossdomain;
		_crossdomain = String(value);
	};
	
	this.context = function(value) {
		if(value == undefined) return _context;
		_context = (!value)?'.':((value=='/')?'':String(value).replace(/\/+$/g,''));
		return this;
	};
	
	this.auth = function(value) {
		if(value == undefined) return _auth;
		_auth = String(value);
		return this;
	};
	
	this.debug = function(value) {
		if(value == undefined) return _debug;
		_debug = (String(value).toLowerCase() == 'true');
		return this;
	};
	
	this.resource = function(value) {
		if(value == undefined) return _data[_id].resource;
		_data[_id].resource = '/' + String(value).replace(/^\/*|\/*$/g,'');
		return this;
	};
	
	this.type = function(value) {
		if(value == undefined) return _data[_id].type;
		_data[_id].type = (value.toUpperCase() == 'GET')? 'GET' : 'POST';
		return this;
	};
	
	this.params = function(value) {
		if(value == undefined) return _data[_id].params;
		_data[_id].params = value;
		return this;
	};
	
	this.done = function(value) {
		_data[_id].done = value;
		return this;
	};
	
	this.fail = function(value) {
		_data[_id].fail = value;
		return this;
	};
	
	this.always = function(value) {
		_data[_id].always = value;
		return this;
	};
	
	this.url = function() {
		return this.context() + this.resource() + ((_crossdomain)? '?crossdomain=' + _crossdomain : '');
	};
	
	this.options = function(value) {
		for (var method in value)
			if(this.hasOwnProperty(method))
				this[method](value[method]);
		return this;
	};
	
	this.conf = function() {
		
		_data[_id].send = {};
		_data[_id].send.id = _id;
		_data[_id].send.params = this.params();
		if (_auth) _data[_id].send.params.auth = _auth;
		
		var conf = {};
		conf.id		= _id;
		conf.type	= this.type();
		conf.url	= this.url();
		conf.data	= _data[_id].send;
		return conf;
	};
	
	this.execute = function() {
		var conf = this.conf();
		
		if (_debug) {
			console.log('ExpressoAPI - Execute:' + this.resource());
			console.log(conf);
		}
		
		jQuery.ajax(conf).done(function(response) {
			if (response && response.result) {
				if (_debug) {
					console.log('ExpressoAPI - DONE callback');
					console.log(JSON.stringify(response));
				}
				if (response.result.auth) ExpressoAPI.auth(response.result.auth);
				if (_data[this.id].resource=='/Logout') ExpressoAPI.auth("");
				if (_data[this.id].done) _data[this.id].done(response.result,_data[this.id].send);
			} else {
				if (_debug) {
					console.log('ExpressoAPI - ERROR callback');
					console.log(JSON.stringify(response));
				}
				if (_data[this.id].fail) _data[this.id].fail(response,_data[this.id].send);
			}
		}).fail(function(response) {
			if (_debug) {
				console.log('ExpressoAPI - FAIL callback');
				console.log(JSON.stringify(response));
			}
			if (_data[this.id].fail) _data[this.id].fail(response,_data[this.id].send);
		}).always(function() {
			if (_debug) console.log('ExpressoAPI - ALWAYS callback');
			if (_data[this.id].always) _data[this.id].always(_data[this.id].send);
			delete _data[this.id];
		});
		this.id(_id+1);
		return this;
	};
}
ExpressoAPI.id(0);
