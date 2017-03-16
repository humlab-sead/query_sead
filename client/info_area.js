/*
function:info_area_handle_login_callback
Not used but could be used for handling login
*/
function info_area_handle_login_callback(xml) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	alert(xml);
}
/*
function info_area_render_login_box
Not used but could be used for handling login
*/
function info_area_render_login_box() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var box;
	
	box += "<div id=\"background_overlay\"></div>";
	box += "<div id=\"login_box\">";
	box += "<div id=\"login_box_close_button\" style=\"color:red;position:absolute;left:224px;top:24px;background-color:#00639f;cursor:pointer;\"><img src=\"applications/ships/theme/images/button_close.png\"></div>";
	box += "<table id=\"login_table\" class=\"generic_table\"><tbody>";
	box += "<tr>";
	box += "<td class=\"generic_table_top_left\"></td><td class=\"generic_table_top_middle\"></td><td class=\"generic_table_top_right\"></td>"; //top row
	box += "</tr>";
	box += "<tr>";
	box += "<td class=\"generic_table_middle_left\"></td><td class=\"generic_table_middle_middle\">";
	
	box += "Användarnamn<br />";
	box += "<input type=\"text\" /><br />";
	box += "Lösenord<br />";
	box += "<input type=\"password\" /><br />";
	box += "<input id=\"login_submit_button\" type=\"submit\" value=\"Logga in\">";
	
	box += "</td><td class=\"generic_table_middle_right\"></td>"; //middle row
	box += "</tr>";
	box += "<tr>";
	box += "<td class=\"generic_table_bottom_left\"></td><td class=\"generic_table_bottom_middle\"></td><td class=\"generic_table_bottom_right\"></td>"; //bottom row
	box += "</tr>";
	box += "</tbody></table>";
	box += "</div>";
	
	
	var login_box = $(box);
	
	login_box.find("#login_box_close_button").bind("click", function() {
		$(document).find("#background_overlay").remove();
		$(this).parent().remove();
	});
	
	
	
	login_box.find("#login_submit_button").bind("click", function() {
		
		var xml_request_document = "";
		
		$.ajax({
		type: "POST",
		url: "http://" + application_address + application_prefix_path + "server/user_auth.php",
		cache: false,
		dataType: "xml",
		processData: false,
		data: "xml="+xml_request_document,
		global: false,
		success: function(xml){
			info_area_handle_login_callback(xml);
		}
		});
	});
	
	//login_box.find("#login_box").draggable();
	$("body").append(login_box);
	//alert("render");
}


/*
function: info_area_init
Initialize the infoarea handling view_states etc
*/
function info_area_init() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	$("#contact_button").bind("click", function() {
		
	});
	
	$("#load_view_state_button").bind("click", function() {
		
		if($("#viewstate_load_popup").length > 0) {
			info_area_close_view_state_load_box();
		}
		else {
			info_area_open_view_state_load_box();
		}
		
		//user_get_view_state(last_saved_view_state.id);
	});
	
	$("#save_view_state_button").bind("click", function() {
		if($("#viewstate_save_popup").length > 0) {
			info_area_close_view_state_save_box();
		}
		else {
			info_area_open_view_state_save_box();
			user_save_view_state();
		}
		//
	});
	
	$("#login_button").bind("click", function() {
		info_area_render_login_box();
	});

	$("#hide_controllers_button").bind("click", function() {
		$("#facet_workspace").hide();	
		$("#facet_controller_outer").hide();	
		$("#result_controller_outer").hide();	

	});
	
	$("#show_controllers_button").bind("click", function() {
		$("#facet_workspace").show();	
		$("#facet_controller_outer").show();	
		$("#result_controller_outer").show();	

	});


	//about_ships_button // about_project_button
	
	

	
	
	$("#language_selection_button").bind("click", function() {
		if($("#language_selection_dialog").length > 0) {
			info_area_close_language_selection_dialog();
		}
		else {
			info_area_open_language_selection_dialog();
		}
	});
	
}
/*
function info_area_open_view_state_save_box
Rnders the save-box to save a view-state
*/
function info_area_open_view_state_save_box() {
	$("#save_view_state_button").css("color", "#bbb");
	
	//var html = "<div id=\"viewstate_save_popup\"><span style=\"font-size:12px;font-weight:bold;\">"+t("Vynamn")+"</span><br/><input id=\"view_state_name_box\" type=\"text\" style=\"width:120px;\" /><input id=\"view_state_save_button\" type=\"submit\" value=\""+t("Spara")+"\" /></div>";
	
	
	var html = "<div id=\"viewstate_save_popup\">";
	html += "<img id=\"viewstate_save_popup_close_btn\" style=\"float:right;\" src=\"applications/ships/theme/images/button_close.png\" />";
	html += "<div class=\"content_container\"></div></div>";
	
	var html_obj = $(html);
	
	$(html_obj).css("position", "absolute");
	$(html_obj).css("top", 40);
	$(html_obj).css("left", 400);
	$(html_obj).bind("change", function() {
		var view_state_name = $("#view_state_name_box").val();
		user_save_view_state(view_state_name);
	});
	
	$("#view_state_save_button", html_obj).bind("click", function() {
		var view_state_name = $("#view_state_name_box").val();
		user_save_view_state(view_state_name);
	});
	
	$("#viewstate_save_popup_close_btn", html_obj).bind("click", function() {
		$("#viewstate_save_popup").remove();
	});
	
	$("body").append(html_obj);
}


/*
function: info_area_close_view_state_save_box
close the save box
*/
function info_area_close_view_state_save_box() {
	$("#save_view_state_button").css("color", "#fff");
	$("#viewstate_save_popup").remove();
}

/*
function: info_area_open_view_state_load_box
Load the view states available in the session
*/
function info_area_open_view_state_load_box() {
	
	$("#load_view_state_button").css("color", "#bbb");
	
	var html = "<div id=\"viewstate_load_popup\"><img id=\"viewstate_load_popup_close_btn\" style=\"float:right;\" src=\"applications/ships/theme/images/button_close.png\" /><span style=\"font-size:12px;font-weight:bold;\">"+t("Vynummer")+"</span><br/><input id=\"view_state_select_box\" type=\"text\" style=\"width:60px;\" value=\""
	+current_view_state_id+"\" /><input type=\"submit\" value=\""+t("Ladda")+"\" /><hr /><div><span style=\"font-weight:bold;\">"+t("Dina senaste vyer")+"</span><div id=\"latest_view_states_container\"></div></div></div>";
	
	var html_obj = $(html);
	
	
	$(html_obj).css("position", "absolute");
	$(html_obj).css("top", 40);
	$(html_obj).css("left", 500);
	$(html_obj).bind("change", function() {
		//$("input", this).val();
		var view_state_id = $("#view_state_select_box").val();
		
		user_get_view_state(view_state_id);
		
	});
	
	$("#viewstate_load_popup_close_btn", html_obj).bind("click", function() {
		$("#viewstate_load_popup").remove();
	});
	
	$("body").append(html_obj);
	
	
	$(html_obj).draggable();
	
	info_area_refresh_view_state_list();
	
	return html;
}
/*
function: info_area_close_view_state_load_box
Close the load-box
*/
function info_area_close_view_state_load_box() {
	$("#load_view_state_button").css("color", "#fff");
	$("#viewstate_load_popup").remove();
}

/*
function: info_area_refresh_view_state_list
Get the update view_state list from server
see also <info_area_populate_view_state_list>
*/
function info_area_refresh_view_state_list() {
	$.ajax({
		type: "POST",
		url: "http://" + application_address + application_prefix_path + "server/view_state.php",
		cache: false,
		dataType: "xml",
		processData: false,
		data: "xml="+"&application_name="+application_name,
		global: false,
		success: function(xml){
			info_area_populate_view_state_list(xml);
		}
	});
}
/*
function: info_area_populate_view_state_list
Creates a list of the xml from server and add that to a hmtl-div
Append event to the items 
see <user_get_view_state>

*/
function info_area_populate_view_state_list(xml) {
	
	var out = "";
	
	out += "<table><thead><tr style=\"font-style:italic;\"><td>"+t("Vynummer")+"</td><td>"+t("Tidpunkt")+"</td></tr></thead><tbody>";
	
	$("view_state", xml).each(function() {
		out += "<tr><td><span view_state_id=\""+$("id", this).text()+"\" class=\"saved_view_state_link\">"+$("id", this).text()+"</span></td><td>"+$("created", this).text().substr(0, 16)+"</td></tr>";
	});
	
	out += "</tbody></table>";
	
	$("#latest_view_states_container").html(out);
	
	$(".saved_view_state_link").bind("click", function() {
		//msg($(this).attr("view_state_id"));
		$("#view_state_select_box").val($(this).attr("view_state_id"));
		user_get_view_state($(this).attr("view_state_id"));
		
	});
}

/*
function: info_area_open_language_selection_dialog
Open diaglog for language selection, showing a list of languages and link to the admin interface
*/
function info_area_open_language_selection_dialog() {
	
	$("#language_selection_button").css("color", "#bbb");
	
	var html = "<div id=\"language_selection_dialog\"><img id=\"language_selection_dialog_close_btn\" style=\"float:right;cursor:pointer;\" src=\"applications/ships/theme/images/button_close.png\" />";
	
	html += "<table ><tbody>";
	html += "<tr><td style=\"vertical-align:middle;\"><span class=\"language_selection_language_title\">"+t("Välj språk")+"</span></td></tr>";
	
	var bg_color = "";
	
	for(var key in language_definitions) {
		
		if(client_language == language_definitions[key]['language']) {
			bg_color = "#ddddff";
		}
		else {
			bg_color = "#ffffff";
		}
		
		html += "<tr style=\"background-color:"+bg_color+";\" id=\"language_button_"+language_definitions[key]['language']+"\" value=\""+language_definitions[key]['language']+"\"><td style=\"vertical-align:middle;\"><span class=\"language_selection_language_title\">"+language_definitions[key]['language_name']+"</span></td></tr>";
	//	html += "<br>";
	}
	
	
	html += "</tbody></table>";
	
	html += "<span id=\"translations_link\"><a href=\"admin/index.php?application_name="+application_name+"\">"+t("Översättningar")+"</a></span>";
	
	html += "</div>";
	
	var html_obj = $(html);
	
	
	$(html_obj).css("position", "absolute");
	$(html_obj).css("top", 40);
	$(html_obj).css("right", 10);
	
	
	$("#language_selection_dialog_close_btn", html_obj).bind("click", function() {
		$("#language_selection_dialog").remove();
	});
	
	
	$("body").append(html_obj);
	
	
	for(var key in language_definitions) {
	
		$("#language_button_"+language_definitions[key]['language']).bind("click", function() {
			
			client_language = $(this).attr("value");
			call_view_state = true;
			$("#language_selection_dialog").remove();
			
			var old_view_state_id = current_view_state_id;
			
			//save the viewstate to trigger a refresh of everything - to get the new translations in
			user_save_view_state(); //the saved viewstate is automatically loaded asap
                       // alert(client_language);
			// CLEAR the webpage when switching language
			$("#title_bar_container").empty();
			$("#facet_workspace").empty();
			$("#result_workspace").empty();
			$("#facet_controller_outer").empty();
			$("#result_controller_outer").empty();
			$("#show_active_filters_link").html(t("Laddar"));
//			$("info_area").empty();



			
			
			//wait_to_activate_last_view_state(old_view_state_id);
			
			
			/*
			while(old_view_state_id == current_view_state_id) {
				setTimeout(function() { info_area_call_view_state(view_state_id); }, 100);
			}
			*/
			/*
			setTimeout(function() {
				user_get_view_state(view_state_id);
			}, 3000);
			*/
			
			//info_area_call_view_state(view_state_id);
			
			//window.location.href = "http://galactica.humlab.umu.se/ships/trunk/?view_state="+;
			//info_area_refresh_view_state_list();
		
		});
	}
	
	
	
	return html;
	
}

function wait_to_activate_last_view_state(old_view_state_id) {
	if(old_view_state_id == current_view_state_id) {
		setTimeout(function() { wait_to_activate_last_view_state(old_view_state_id); }, 100);
	}
	else {
		info_area_call_view_state(current_view_state_id);
	}
}

/*
function: info_area_call_view_state
Put the view_state parameter to the url and load the page with this viewstate.
*/
function info_area_call_view_state(view_state_id) {
	
	if(call_view_state) {
		call_view_state = false;
		window.location = "?view_state="+view_state_id+"&application_name="+application_name;
	}
	
}
/*
function:info_area_close_language_selection_dialog
closes the language dialoge box
*/
function info_area_close_language_selection_dialog() {
	
	$("#language_selection_dialog").remove();
	
}

function info_area_open_about(link)
{
	window.open(link);
	//window.location.href =link;
}


