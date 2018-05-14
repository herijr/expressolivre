(function($)
{
    var loadScriptPhoneMask     = -1;

    var arrayScriptPhoneMask    = [];

    var maskList = null;

    $.initPhoneMask = function( fileJson, callBack )
    {
        if( loadScriptPhoneMask == 1 ){  return callBack(); } 
        else { arrayScriptPhoneMask.push(callBack); if( loadScriptPhoneMask == 0 ) return; }

        loadScriptPhoneMask = 0;

        $.getScript("../prototype/plugins/jquery.mask-phone/jquery.bind-first.js", function()
        {
            $.getScript("../prototype/plugins/jquery.mask-phone/jquery.inputmask-multi.js", function()
            {
                $.getScript("../prototype/plugins/jquery.mask-phone/jquery.inputmask.js", function()
                {
                    loadScriptPhoneMask = 1;

                    var fileDefault = ( $.trim(fileJson.toLowerCase()) === "default" ? "phone-codes-default.json" : "phone-codes.json" );

                  	maskList = $.masksSort($.masksLoad("../prototype/plugins/jquery.mask-phone/"+fileDefault ), ['#'], /[0-9]|#/, "mask");

                    for( var i in arrayScriptPhoneMask )
                    {
                        arrayScriptPhoneMask[i]();
                    }
                });
            });
        });
    };

    $.fn.maskPhone = function()
    {
        var that = this;
				
				var fileJson = ( arguments.length > 0 ? arguments[0] : "" );

        $.initPhoneMask( fileJson, function()
        {
            var maskOpts =
            {
                inputmask :
                {
                    definitions : { '#': { validator: "[0-9]", cardinality: 1 } },
                    clearIncomplete: false,
                    showMaskOnHover: true,
                    autoUnmask: true
                },
                match: /[0-9]/,
                replace: '#',
                list: maskList,
                listKey: "mask",
                onMaskChange: function(maskObj, determined)
                {
                    $(this).attr("placeholder", $(this).inputmask("getemptymask"));
                }
            };

            $(that).inputmasks(maskOpts);
        });
    };

})(jQuery);
