

function getResourcesExpresso(s_callback) {

	var server = "";
	$.ajax({
		type: "POST",
		url: "./inc/client_rest.php",
		data:
		{
			id: "1",
			params: {},
			serverUrl: "/Documentation",
			methodType: "POST"
		},
		beforeSend: function () {

		},
		success: function (response) {
			var obj = jQuery.parseJSON(response);

			if (obj.error) {
				$("#error").html("<font style='font-weight:bold'>Error code : </font> " +
					( obj.error.code ? obj.error.code : "code" ) + "<br><font style='font-weight:bold'>Error msg : </font>" + ( obj.error.message ? obj.error.message : "message_error"));
			} else {
				s_callback(obj.result);
			}

		},
		error: function (response) {
			var obj = JQuery.parseJSON(response);

		}
	});
}


function loadPage(pageName, param) {

	var s_callback = function (response) {

		divResources = $("#contentDetail");

		divResources.html(
					new EJS({ url: './templates/' + pageName + '.ejs' }, false)
						.render({
								params: param, 
								resources: response.resources, 
								possible_errors: response.possible_errors, 
								apis_expresso: response.apis
							})
					);

		$("#page").height("100%");
	}

	getResourcesExpresso(s_callback);
}

function selectResource(resourceName) {

	var s_callback = function (response) {

		var resources_expresso = response.resources;

		divResources = $("#contentDetail");

		var newResource = {};
		for (var i in resources_expresso) {
			if (resources_expresso[i].rest == resourceName) {
				newResource[0] = resources_expresso[i];
			}
		}
		divResources.html(new EJS({ url: './templates/resources.ejs' }).render({ resources: newResource }));

		$("#" + newResource[0].id + "_auth").val($("#key_auth_server_expresso").find("input[type=hidden]").val());

	}

	getResourcesExpresso(s_callback);

}

(function () {
	var divResources = null;

	function addResources() {

		var s_callback = function (response) {

			var resources_expresso = response.resources;

			divScroller = $("#content");
			divScroller.html(new EJS({ url: './templates/resourceListItem.ejs' }).render({ resources: resources_expresso }));

			loadPage('pagina1');
			$('#toggle-login').click(function () {
				$('#login').toggle();
			});
		}

		getResourcesExpresso(s_callback);

	}

	function ajax() {

		if (arguments.length > 0) {
			var rest = arguments[0];

			var s_callback = function (response) {

				var resources_expresso = response.resources;

				//Div prams
				$("#param_" + resources_expresso[rest].id).css("display", "none");
				$("#param_" + resources_expresso[rest].id).html();
				//Div Error
				$("#error_" + resources_expresso[rest].id).css("display", "none");
				$("#error_" + resources_expresso[rest].id).html();
				//Div return
				$("#return_" + resources_expresso[rest].id).css("display", "none");
				$("#return_" + resources_expresso[rest].id).html();

				$("#json_" + resources_expresso[rest].id).css("display", "none");
				$("#json_" + resources_expresso[rest].id).html();

				$("#tabs").css("display", "none");

				if (resources_expresso[rest]) {
					var server = "/" + resources_expresso[rest].rest;

					var obj = eval("({" + getParams(resources_expresso[rest]) + "})");

					var idResource = "1";

					if (obj.id) {
						idResource = obj.id;

						delete obj.id;
					}

					// Login
					if (rest == "login") {
						delete obj.auth;
					}

					// Logout
					if (rest == "logout") {
						$("#key_auth_server_expresso").find("span").html("USU&Aacute;RIO N&Atilde;O AUTENTICADO");
						$("#key_auth_server_expresso").find("input[type=hidden]").val("");

						// Get all auth
						for (var i in resources_expresso) {
							$("#" + i + "_auth").val("");
						}
					}

					$.ajax(
						{
							type: "POST",
							url: "./inc/client_rest.php",
							data:
							{
								id: idResource,
								params: JSON.stringify(obj),
								serverUrl: server,
								methodType: "POST"
							},
							beforeSend: function () {
								var divSend = $("#param_" + resources_expresso[rest].id);

								divSend.toggle("blind", {}, 1000);

								if (idResource != "") {
									divSend.html("<pre class='prettyprint' style='border:0px;'>id=" + idResource + "&amp;params=" + JSON.stringify(obj) + "</pre>");
								}
								else {
									divSend.html("<pre class='prettyprint' style='border:0px;'>id=" + idResource + "&amp;params=" + JSON.stringify(obj) + "</pre>");
								}
							},
							success: function (response) {
								var obj = jQuery.parseJSON(response);

								if (obj.error) {

									var divError = $("#error_" + resources_expresso[rest].id);

									divError.accordion({ collapsible: true, heightStyle: "content", collapsible: true });

									divError.toggle("blind", {}, 1000);

									if (obj.error.code == 5) {
										$("#key_auth_server_expresso").find("span").html("&nbsp;");
										$("#key_auth_server_expresso").find("input[type=hidden]").val("");

										// Get all auth
										for (var i in resources_expresso) {
											$("#" + i + "_auth").val("");
										}
									}

									divError.find("div").html("<font style='font-weight:bold'>Error code : </font> " +
										obj.error.code + "<br><font style='font-weight:bold'>Error msg : </font>" + obj.error.message);
								} else {

									$("#tabs").toggle("blind", {}, 1000);

									var divReturn = $("#return_" + resources_expresso[rest].id);

									if (obj.result.auth) {
										$("#key_auth_server_expresso").find("span").html(obj.result.auth);
										$("#key_auth_server_expresso").find("input[type=hidden]").val(obj.result.auth);

										// Get all auth
										for (var i in resources_expresso) {
											$("#" + i + "_auth").val(obj.result.auth);
										}
									}

									var jsonFormatted = JSON.stringify(obj, undefined, 2);

									divReturn.html("<pre class=prettyprint'>" + jsonFormatted + "</pre>");

									var divJson = $("#json_" + resources_expresso[rest].id);
									divJson.html(jsonFormatted);

									$("#tabs").tabs();

									prettyPrint();
								}
							},
							error: function (response) {
								var obj = JQuery.parseJSON(response);

								$("#key_auth_server_expresso").find("span").html("USU&Aacute;RIO N&Atilde;O AUTENTICADO");
								$("#key_auth_server_expresso").find("input[type=hidden]").val("");

								var divError = $("#error_" + resources_expresso[rest].id);

								divError.accordion({ collapsible: true, heightStyle: "content", collapsible: true });

								divError.toggle("blind", {}, 1000);

								divError.find("div").html("Error : <br> " + obj.code);
							}
						});


				}
				else {
					alert("RECURSO NÃO ENCONTRADO");
				}
			}

			getResourcesExpresso(s_callback);
		}
	}

	function getParams(resource) {
		var params = resource.params;
		var result = "";

		for (var i in params) {
			if (params[i][3] || $.trim($('#' + resource.id + '_' + i).val()) != "") {
				result += "\"" + i + "\":" + "\"" + $('#' + resource.id + '_' + i).val() + "\",";
			}
		}

		return result.substr(0, (result.length - 1));
	}

	function execute() {
		$(document).ready(function () {
			addResources();
		});
	}

	execute.prototype.ajax = ajax;

	window.execute = new execute();

})();
