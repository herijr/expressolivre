var Glang = new function() {
	
	var _data = {};
	
	this.load = function() {
		jQuery.getJSON( './expressoAdmin1_2/controller.php', { action: '$this/inc/load_lang' } )
		.done(function( response ) { _data = response; })
		.always(function() { $(document).trigger('GlangLoaded'); });
		return this;
	};
	
	this.get = function( key ) {
		var lang = _data[key.replace(/ /g,"_").toLowerCase()];
		return ( lang == undefined )? key+'*' : lang;
	};
}
Glang.load();
