var Domain = new function() {
	var _$context           = null;
	var _$ldap_base         = null;
	var _$default_domain    = null;
	var _searchTimeout      = null;
	var _lastCheck          = undefined;
	var _lastSend           = undefined;
	var _lastResult         = undefined;
	var _redrawHandler      = undefined;
	var _changed            = false;
	var _prevDN             = '';
	
	this.init = function( obj_$context, obj_$ldap_base, obj_$default_domain ) {
		_$context           = obj_$context;
		_$ldap_base         = obj_$ldap_base;
		_$default_domain    = obj_$default_domain;
		_prevDN             = _$context.val();
	};
	
	this.getBasicDomain = function() {
		
		// First level ou name
		var org_name = _$context.val().toLowerCase()
			.replace( /(,?[^=]+=)([^=,]+)/g, '.$2' ).slice( 1 )                                                         // ldap_dn2ufn
			.replace( new RegExp( '([^.]*\\.)*('+(                                                                      // get first domain only
				_$ldap_base.val().replace( /([.?*+^$[\]\\(){}|-])/g, '\\$1' )                                           // Escape ldap context to regexp
			)+')?$' ), '$1' ).slice( 0, -1 );
		
		// Join organizaton name (if exists) and default domain (if not set, use base dn).
		return ( ( org_name != '' )? org_name+'.' : '' ) +
			( ( _$default_domain.val() != '' )? _$default_domain.val() : _$ldap_base.val() );
	};
	
	this.getDomains = function() {
		clearTimeout(_searchTimeout);
		_searchTimeout = setTimeout( Domain.preLoadDomains, 800 );
	};
	
	this.preLoadDomains = function() {
		var dn = _$context.val();
		if ( dn == _lastCheck ) return;
		_lastCheck = dn;
		if ( dn != _lastSend ) Domain.loadDomains( dn );
		else Domain.redraw();
	};
	
	this.loadDomains = function( dn ) {
		var basic_domain = Domain.getBasicDomain();
		var lastResult   = _lastResult;
		_lastSend        = dn;
		_lastResult      = undefined;
		
		$.ajax({
			url: '/expressoAdmin1_2/controller.php',
			dataType: 'json',
			data: {
				'action': '$this.db_functions.get_suggested_domains',
				'dn': dn
			},
			success: function( result ){
				if ( result && !result.error ) {
					_lastResult = result;
					if ( _lastResult.indexOf( basic_domain ) < 0 ) _lastResult.push( basic_domain );
				} else {
					_lastResult = [ basic_domain ];
					alert( result.error? result.error : 'unknown error' );
				}
			},
			error: function(){
				_lastResult = [ basic_domain ];
			},
			complete: function(){
				_changed = Domain.checkChanges( lastResult, _lastResult );
				Domain.redraw();
			}
		});
	};
	
	this.redraw = function( handler ) {
		if ( handler != undefined ) _redrawHandler = handler;
		if ( _redrawHandler != undefined ) {
			var changed = _changed;
			_changed = false;
			return _redrawHandler( _lastResult, changed );
		}
		return false;
	};
	
	this.checkChanges = function( before, after ) {
		if ( before == undefined && after != undefined ) return true;
		if ( before != undefined && after == undefined ) return true;
		if ( before == undefined && after == undefined ) return false;
		return before.toString() != after.toString();
	};
}
