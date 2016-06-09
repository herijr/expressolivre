/*
Begin moo.fx, simple effects library built with prototype.js (http://prototype.conio.net).
by Valerio Proietti (http://mad4milk.net) MIT-style LICENSE.
for more info (http://moofx.mad4milk.net).
10/24/2005
v(1.0.2)
*/
var Class = {
  create: function() {
    return function() { 
      this.initialize.apply(this, arguments);
    }
  }
}

Object.extend = function(destination, source) {
  for (property in source) {
    destination[property] = source[property];
  }
  return destination;
}

Function.prototype.bind = function(object) {
  var __method = this;
  return function() {
    return __method.apply(object, arguments);
  }
}

function $() {
  var elements = new Array();

  for (var i = 0; i < arguments.length; i++) {
    var element = arguments[i];
    if (typeof element == 'string')
      element = document.getElementById(element);

    if (arguments.length == 1) 
      return element;

    elements.push(element);
  }

  return elements;
}


if (!window.Element) {
  var Element = new Object();
}

Object.extend(Element, {
  remove: function(element) {
    element = $(element);
    element.parentNode.removeChild(element);
  },

  hasClassName: function(element, className) {
    element = $(element);
    if (!element)
      return;
    var a = element.className.split(' ');
    for (var i = 0; i < a.length; i++) {
      if (a[i] == className)
        return true;
    }
    return false;
  },

  addClassName: function(element, className) {
    element = $(element);
    Element.removeClassName(element, className);
    element.className += ' ' + className;
  },
  
  removeClassName: function(element, className) {
    element = $(element);
    if (!element)
      return;
    var newClassName = '';
    var a = element.className.split(' ');
    for (var i = 0; i < a.length; i++) {
      if (a[i] != className) {
        if (i > 0)
          newClassName += ' ';
        newClassName += a[i];
      }
    }
    element.className = newClassName;
  },
  
  // removes whitespace-only text node children
  cleanWhitespace: function(element) {
    element = $(element);
    for (var i = 0; i < element.childNodes.length; i++) {
      var node = element.childNodes[i];
      if (node.nodeType == 3 && !/\S/.test(node.nodeValue)) 
        Element.remove(node);
    }
  }
});
//base
var fx = new Object();
fx.Base = function(){};
fx.Base.prototype = {
	setOptions: function(options) {
	this.options = {
		duration: 500,
		onComplete: ''
	}
	Object.extend(this.options, options || {});
	},

	go: function() {
		this.duration = this.options.duration;
		this.startTime = (new Date).getTime();
		this.timer = setInterval (this.step.bind(this), 13);
	},

	step: function() {
		var time  = (new Date).getTime();
		var Tpos   = (time - this.startTime) / (this.duration);
		if (time >= this.duration+this.startTime) {
			this.now = this.to;
			clearInterval (this.timer);
			this.timer = null;
			if (this.options.onComplete) setTimeout(this.options.onComplete.bind(this), 10);
		}
		else {
			this.now = ((-Math.cos(Tpos*Math.PI)/2) + 0.5) * (this.to-this.from) + this.from;
			//this time-position, sinoidal transition thing is from script.aculo.us
		}
		this.increase();
	},

	custom: function(from, to) {
		if (this.timer != null) return;
		this.from = from;
		this.to = to;
		this.go();
	},

	hide: function() {
		this.now = 0;
		this.increase();
	},

	clearTimer: function() {
		clearInterval(this.timer);
		this.timer = null;
	}
}
//fader
fx.Opacity = Class.create();
fx.Opacity.prototype = Object.extend(new fx.Base(), {
	initialize: function(el, options) {
		this.el = $(el);
		this.now = 1;
		this.increase();
		this.setOptions(options);
	},

	increase: function() {
		if (this.now == 1) this.now = 0.9999;
		if (this.now > 0 && this.el.style.visibility == "hidden") this.el.style.visibility = "visible";
		if (this.now == 0) this.el.style.visibility = "hidden";
		if (window.ActiveXObject && this.el.style.backgroundImage == '') this.el.style.filter = "alpha(opacity=" + this.now*100 + ")";
		this.el.style.opacity = this.now;
	},

	toggle: function() {
		if (this.now > 0) this.custom(1, 0);
		else this.custom(0, 1);
	}
});
/*
End moo.fx, simple effects library built with prototype.js (http://prototype.conio.net).
by Valerio Proietti (http://mad4milk.net) MIT-style LICENSE.
for more info (http://moofx.mad4milk.net).
10/24/2005
v(1.0.2)
*/

fx.buildBanner = function( _width, _height, content, expDays) {
	if(expDays) {
		var last_loginid =	GetCookie("last_loginid");
		if(GetCookie("showBanner_"+last_loginid) == 'false') {
			return;
		}
		var expires = new Date();
		expires.setTime(expires.getTime() + (expDays*24*60*60*1000)); 
		document.cookie = "showBanner_"+last_loginid+"=false"+
 						  ";expires=" + expires.toGMTString()+
 						  ";path=/";		
	}

	var div_banner = document.createElement("DIV");
	div_banner.align = "center";
	div_banner.id = "warning_msg";
	div_banner.style.backgroundImage = "url(./templates/default/images/balloon.png)";
	
	div_banner.style.visibility = "hidden";
	div_banner.style.position 	= "absolute";
	div_banner.style.left 		= 0;
	div_banner.style.top  		= 0;
	div_banner.style.width 		= _width;
	div_banner.style.height 	= _height;
	div_banner.style.padding	= "0px";
	
	var a = document.createElement("A");
	a.href = 'javascript:void(0)';
	a.onclick = function (){ myOpacity.toggle() };
	a.title = get_lang("Close");
	div_banner.appendChild(a);
	
	var a_img = new Image();
	a_img.style.margin = "30px";
	a_img.align = "right";
	a_img.src = "../phpgwapi/templates/default/images/close.png";	
	a.appendChild(a_img);
	
	var div_text = document.createElement("DIV");
	div_text.innerHTML = content;
	div_text.style.marginTop = "50px";
	div_text.style.marginLeft = "30px";
	div_text.style.marginRight = "30px";
	div_text.align = "center";	
	div_banner.appendChild(div_text);

	var spanLink = document.createElement("DIV");
	spanLink.innerHTML = get_lang("Update my telephone");	
	spanLink.style.cursor = "pointer";
	spanLink.style.fontWeight = "bold";
	spanLink.style.fontSize = "11px";
	spanLink.style.color = "darkblue";
	spanLink.style.textDecoration = 'underline';	
	spanLink.onclick= function() { myOpacity.toggle();QuickAddTelephone.update_telephonenumber("span_telephonenumber")};
	
	div_banner.appendChild(spanLink);
	
	spanLink = document.createElement("DIV");
	spanLink.style.marginTop = "5px";
	spanLink.innerHTML = get_lang("Close this warning");
	spanLink.style.cursor = "pointer";
	spanLink.style.fontWeight = "bold"
	spanLink.style.color = "darkblue";
	spanLink.style.fontSize = "11px";
	spanLink.style.textDecoration = 'underline';	
	spanLink.onclick = function (){ myOpacity.toggle() };	
	div_banner.appendChild(spanLink);

	document.body.appendChild(div_banner);
	myOpacity = new fx.Opacity('warning_msg', {duration: 600});	
	document.getElementById("warning_msg").style.visibility = 'hidden';	
	myOpacity.now = 0;
	setTimeout("fx.start()",2000);
}

fx.start = function(){	
	var div_banner = Element("warning_msg");
	var span_telephonenumber = Element("span_telephonenumber");	
	if(Element("toolbar").style.visibility != "hidden" && div_banner && span_telephonenumber){
		div_banner.style.left = findPosX(span_telephonenumber);
		div_banner.style.top  = findPosY(span_telephonenumber) + 10;
		myOpacity.toggle();
	}	
}

// Balloon content (HTML or text)
var content = 	'<div nowrap><u><b>'+get_lang("Warning for users")+'</b></u></div><p style="text-align:justify">'+ 
				'<img style="margin-right:10px" src="./templates/default/images/phone.gif" align="left">'+
				get_lang('text_Warning')+"</p>";

// Expiration Date (in days)
var expDays = 7;

// Balloon Size
var _width 	= 300;
var _height = 240;
// User with no permission to edit telephone. Cancel it.
if(!preferences.blockpersonaldata) {
	fx.buildBanner(_width, _height, content, expDays);
}
