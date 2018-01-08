var Profile = new function() {
	var _init				= false;
	var _searchTimeout		= null;
	var _isMainMailValid	= false;
	var _isAltrMailValid	= false;
	var _isForwMailValid	= false;
	var _isProfileChanged	= false;
	var _lastCheck			= undefined;
	var _lastSend			= undefined;
	var _lastResult			= undefined;
	var _prevMail			= '';
	var _prevId				= 0;
	var _prevName			= '';
	var _forwCount			= 0;
	
	this.init = function() {
		if ( _init ) return;
		_init				= true;
		_prevMail			= $('input[name=mail]').val();
		_prevName			= $('input[name=profile_descr]').val();
		var num				= parseInt($('input[name=profile_id]').val());
		_prevId				= isNaN(num)? 0 : num;
		_isMainMailValid	= Profile.parseMail($('input[name=mail]'));
		$('input[name=mail]').bind('keyup.pfl input.pfl paste.pfl',Profile.checkMailEvent);
		$('#addmailalternateaddress').bind('click.pfl',Profile.rebindAlterMail);
		$('#addmailforwardingaddress').bind('click.pfl',Profile.rebindForwarMail);
		Profile.rebindAlterMail();
		Profile.rebindForwarMail();
		Profile.countForwarMail();
	};
	
	this.checkMailEvent = function( e ) {
		if ( e ) $('input[name=mail]').data( 'auto', false );
		$('input[name=mail]').css('color','');
		clearTimeout(_searchTimeout);
		_searchTimeout = setTimeout(Profile.checkMail,800);
	};
	
	this.checkMail = function() {
		var mail = $('input[name=mail]').val();
		if ( mail == _lastCheck ) return;
		_lastCheck = mail;
		
		_isMainMailValid = Profile.parseMail($('input[name=mail]'));
		if ( _isMainMailValid && (mail != _lastSend) ) Profile.getProfile($('input[name=mail]').val());
		else Profile.redraw();
	};
	
	this.getProfile = function(mail) {
		_lastSend	= mail;
		_lastResult	= undefined;
		$('input[type=button]').prop('disabled', true);
		cExecute('$this.imap_functions.get_profile_info&mail='+mail,function(result){
			_lastResult = result;
			Profile.redraw();
			$('input[type=button]').prop('disabled', false);
		});
	};
	
	this.parseMail = function(input) {
		var result	= /^([a-zA-Z0-9_\-])+(\.[a-zA-Z0-9_\-]+)*\@([a-zA-Z0-9_\-])+(\.[a-zA-Z0-9_\-]+)*$/.test($(input).val());
		$(input).css('color',result?'':'#FF0000');
		return result;
	};

	this.parseMailInput = function(event) {
		return Profile.parseMail(event.currentTarget);
	};
	
	this.redraw = function() {
		$('input[name=mail]').parents('table').first().find('input[name!=mail]').prop('disabled', !_isMainMailValid);
		$('#profile-descr-label').html(Profile.getLabel());
		Profile.setVisibility($('.w-mx'),Profile.isProfileValid());
		$('#profile-msg-lost-share').toggle(Profile.hasLostShare());
		if ( Profile.getDefaultUserQuota() != undefined )
			$('input[name=mailquota]').val(Profile.getDefaultUserQuota());
	};
	
	this.setVisibility = function(obj,value) {
		$(obj).css('visibility',value?'visible':'collapse');
	};
	
	this.getLabel = function() {
		var mail = $('input[name=mail]').val();
		if ( mail == ''        ) return '';
		if ( !_isMainMailValid ) return get_lang('Email field is not valid');
		if ( mail == _prevMail ) return _prevName;
		if ( _lastResult       ) return _lastResult.profile_descr;
		return get_lang('Profile not found');
	};
	
	this.getDelimiter = function() {
		return _lastResult ? _lastResult.profile_delim : '';
	};
	
	this.getDefaultUserQuota = function() {
		return ( _lastResult && _lastResult.defaultUserQuota )? _lastResult.defaultUserQuota : undefined;
	};
	
	this.isProfileValid = function() {
		return _isMainMailValid && (($('input[name=mail]').val() == _prevMail)? _prevId > 0 : _lastResult !== false );
	};
	
	this.rebindAlterMail = function() {
		$('input[name^=mailalternateaddress]')
			.unbind('keyup.pfl input.pfl paste.pfl', function( e ) { Profile.parseMailInput(e); return true; })
			.bind('keyup.pfl input.pfl paste.pfl', function( e ) { Profile.parseMailInput(e); return true; });
	};
	
	this.rebindForwarMail = function() {
		$('input[name^=mailforwardingaddress]')
			.unbind('keyup.pfl input.pfl paste.pfl',Profile.countForwarMail)
			.bind('keyup.pfl input.pfl paste.pfl',Profile.countForwarMail);
	};
	
	this.countForwarMail = function() {
		_forwCount = 0;
		$('input[name^=mailforwardingaddress]').each(function(){
			if (Profile.parseMail(this)) _forwCount++;
		});
		Profile.redraw();
	};
	
	this.hasDeleteAction = function() {
		return (_prevId > 0 && _lastResult === false)
	};

	this.hasPerfil = function(){
		return Boolean(_lastResult);
	};
	
	this.hasLostShare = function(){
		if ( _lastResult == undefined ) return false;
		return _prevId > 0 && Profile.isProfileValid() && _lastResult && _lastResult.profile_id != _prevId;
	};
}
