

/*
  file: user.js
  User class. Handles user interface activity, including
  showing previously made search delimits (possibly).
 
  code exchange for is defined as the following.
  flat xml format with the following elements.
  <sessionid> specified the generated session id. This is set automatically
  from the server at interface startup.
  <uid> The user id in the database, if logged in. Is set at time of login.
  <query> defines which query to run. Must match the set implemented in
  the user.php file.
 
  return values is wrapped in a xml file of the following format (any object can
  but must not necessarily be included):
  <uid> found user id, if not set set user id to empty string.
  <saved> grouping tag for saved queries.
  <query> the value of a saved query. this tag must be wrapped in <saved> tags.
 
 
 */
// **********************************************************************************************************************************
/*
   Function: User

   Description:

   Parameters: 

   Returns:

   see also:
*/
function User(sessionKey) {
    this.id = '';
    this.userName = '';
    this.sessionKey = sessionKey;
    this.GUEST = true;

    this.getNotLoggedIn = function() { return "<span>You are currently not logged in.<br />" +
            "<span id=\"user_login_button\">Log in</span> for more functionality.</span>";}
    this.getLoggedIn = function() { return "<span>You are logged in as " + this.userName + " " +
        "<span id=\"user_login_button\">Log out</span></span>";}

    this.loginError = "<span id=\"login_error\" class=\"error\">Login failed. Check name and password.</span>"

    this.setUserName = function(uName){
        this.userName = uName;
        this.GUEST = uName == '';
    };

    this.getSessionVarsXML = function(){
        return "<sessionKey>" + this.sessionKey + "</sessionKey>";
    }

    this.getSavedQueries = function(){
        if(this.id == ''){
            return false;
        }

        var xml_request =  this.getSessionVarsXML() +
                            "<query>saved</query>";
        $.ajax({
            type: "POST",
            url: "server/user.php",
            cache: false,
            dataType: "xml",
            processData: false,
            data: "xml="+user_wrapXML(xml_request)+"&application_name="+application_name,
            global: false,
            success: function(xml){
                this.handleResponse(xml);
            }
        });
        return false;
    }

    this.logout = function(){
        this.setUserName('');
        location.reload();
    }

    this.handleRespose = function(xml){
        if(xml == ''){
            return false;
        }
        return false;
    }
}
// **********************************************************************************************************************************
/*
   Function: user_render_user_area

   Description:

   Parameters: 

   Returns:

   see also:
*/
function user_render_user_area(currentUser){
    var userArea;
    if(currentUser.GUEST){
        userArea = $(currentUser.getNotLoggedIn());
    } else {
        userArea = $(currentUser.getLoggedIn());
    }
    userArea.find("#user_login_button").bind("click", function(){
       if(currentUser.GUEST){
           user_show_login_form(false);
       } else {
           currentUser.logout();
       }
    });
    var userAreaContainer = $("#user_area");
    userAreaContainer.children().remove();
    userAreaContainer.append(userArea);
    
}
// **********************************************************************************************************************************
/*
   Function: user_show_login_form

   Description:

   Parameters: 

   Returns:

   see also:
*/
function user_show_login_form(showError){
    var userArea = $("#user_area");
    if(typeof(userArea.find("#user_login_form") === "undefined")){
        var form = "<div id=\"user_login_form\">" +
        "name: <input type=\"text\" name=\"user_name\" id=\"user_login_form_name\" size=\"15\" /><br />" +
        "passwd: <input type=\"password\" name=\"user_password\" id=\"user_login_form_passw\" size=\"15\" /><br />" +
        "<input type=\"submit\" value=\"login\" id=\"user_login_form_submit\" />" +
        "</div>";
        form = $(form);
        form.find("#user_login_form_submit").bind("click", function() {user_form_clicked()});
        userArea.children().remove();
        userArea.append(form);
    }
    if(showError){
        var error = $(currentUser.loginError);
        userArea.append(error);
    } else if(typeof(userArea.find("#login_error")) == "undefined"){
        userArea.remove("#login_error");
    }
}
// **********************************************************************************************************************************
/*
   Function: user_form_clicked

   Description:

   Parameters: 

   Returns:

   see also:
*/
function user_form_clicked(){
		var form = $("#user_area").find("#user_login_form");
		var name = form.find("#user_login_form_name").val();
		var passw = form.find("#user_login_form_passw").val();
		/**
		 * the contract for login is set as:
		 * <userName> pass the user name
		 * <userPassword> pass the user password
		 * <query>login<query> mark the query as being a login query.
		 *
		 * The result is either empty, for unsuccessful login.
		 * or contain the following tags
		 * <sessionKey> the current session key
		 * <userName> the user name
		 * <select> a group of <query> items that represent a
		 * set of saved queries. (to be implemented).
		 */
		var xml_request = "<userName>" + name + "</userName>" +
				"<userPassword>" + passw + "</userPassword>" +
				currentUser.getSessionVarsXML() + 
				"<query>login</query>";
		request = $.ajax({
		   type: "POST",
			url: "server/user.php",
			cache: false,
			dataType: "xml",
			processData: false,
			data: "xml="+user_wrapXML(xml_request)+"&application_name="+application_name,
			global: false,
			success: function(xml){
				xml = request.responseText;
				var pos= xml.indexOf('<userName>');
				if(xml != '' && pos != -1){
					//var xml_obj = $(xml);
					//var userName = xml_obj.find("userName").val();
					var userName = xml.substring(pos + "<userName>".length, xml.indexOf('</userName>'));
					currentUser.setUserName(userName);
					renderUserArea(currentUser);
				} else {
					user_show_login_form(true);
				}
			},
			error: function(xml){
				user_show_login_form(true);
			}
		});
}
// **********************************************************************************************************************************
/*
   Function: user_wrapXML

   Description:

   Parameters: 

   Returns:

   see also:
*/
function user_wrapXML(xml){
    return "<user_xml>" + xml + "</user_xml>";
}

/*
function: user_save_view_state
Saves the view state by packing, serializing the view state information using <user_pack_view_state> and saving it using <save_view_state.php>
*/
function user_save_view_state(view_state_name) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	
	var post_data = user_pack_view_state();
	//$.dump(facet_presenter.facet_list.facets);
	//var view_state_struct = user_unpack_view_state(post_data.data);
	
	
	$.ajax({
		type: "POST",
		url: "http://" + application_address + application_prefix_path + "api/save_view_state.php",
		cache: false,
		dataType: "text",
		processData: false,
		data: "view_state="+post_data.data+"&application_name="+application_name,
		global: false,
		success: function(text) {
			//msg("saved view state : "+text);
			last_saved_view_state.id = parseInt(text);
			current_view_state_id = parseInt(text);
			
			if($("#viewstate_load_popup").length > 0) {
				$("#view_state_select_box").val(current_view_state_id);
				info_area_refresh_view_state_list();
			}
			
			var view_state_url_link = "http://" + application_address + application_prefix_path + "?view_state="+text+"&application_name="+application_name;
			var view_state_url_box = "<br /><input type=\"text\" style=\"width:360px;\" value=\""+view_state_url_link+"\" />";
			
			$("#viewstate_save_popup").find(".content_container").html(t("Din vy sparades med vynummer")+" <span style=\"font-weight:bold;\">"+text+
				"</span>.<br />"+t("Använd den här länken för att ladda den här vyn i framtiden:")+"<br /><span style=\"font-weight:bold;\"><a href=\""+view_state_url_link+"\">"+view_state_url_link+"</a></span>"+view_state_url_box);
			
			info_area_call_view_state(last_saved_view_state.id);
		}
	});
	
}

/*
function: user_view_state_init
Init the view state from the view_state_object??????
facets
*/
function user_view_state_init() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var slot = 0;
	var slot_ticker = 0;
	var taken_slots = [];
	
	for(var facet_key in facets) { 
		if(facets[facet_key]['default'] == 1) {
			
			if(facets[facet_key].slot == "") {
				while($.inArray(slot, taken_slots) != -1) {
					slot = slot_ticker;
					slot_ticker++;
				}
			}
			else {
				slot = facets[facet_key].slot;
			}
			
			taken_slots.push(slot);
			
			view_state.facets.push({
				"id" : facets[facet_key].id,
				"slot_id" : slot,
				"selections" : [],
				"scroll" : 0, //discrete facets only
				"coords" : {"lat" : 0, "lng" : 0}, //geo facets only
			});
		}
	}
}



/*
function user_view_state_unpack
Creates  javascript composite object from the view state xml

*/
function user_view_state_unpack() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var active_result_variables = [];
	$("checked_result_variables", view_state_xml).children().each(function() {
		active_result_variables.push($(this).text());
	});

	

	view_state = {
	"client_language" : $("client_language", view_state_xml).text(),
	"result_maximized" : $("maximized", view_state_xml).text(),
	"result_view" : $("view", view_state_xml).text(),
	"result_aggregation_mode" : $("aggregation_mode", view_state_xml).text(),
	"result_active_result_variables" : active_result_variables,
	"result_variables_registry" : [],
	//"facet_default_facets" : ["has_polygon", "geo", "parish"],
	"facets" : [], 
	};
	
	
	result_module_invoke_all('user_view_state_unpack');


	$("facet_objects", view_state_xml).children().each(function() {
		
		//msg($("selections", this).text());
		
		var facet_type = $("type", this).text();
		
		var selections = [];
		$("selections", this).children().each(function() {
			if(facet_type == "geo") {
				var rect_id = $("rectangle_id", this).text();
				selections[rect_id] = [];
				selections[rect_id]["ne"] = [];
				selections[rect_id]["ne"]["lat"] = $("ne", this).find("lat").text();
				selections[rect_id]["ne"]["lng"] = $("ne", this).find("lng").text();
				
				selections[rect_id]["sw"] = [];
				selections[rect_id]["sw"]["lat"] = $("sw", this).find("lat").text();
				selections[rect_id]["sw"]["lng"] = $("sw", this).find("lng").text();
			}
			else {
				selections.push($(this).text()); // used to to do a parseint by all selection are not numbers.
			}
		});
		
		if(facet_type == "range") {
			//order the selections so that the lowest limit is located at index 0 (this may not always be the case)
			if(parseInt(selections[0]) >parseInt(selections[1])) {
				var temp = selections[1];
				selections[1] = selections[0];
				selections[0] = temp;
			}
		}
		
		//$.dump(selections);
		
		view_state.facets.push({
			"id" : $("id", this).text(),
			"slot_id" : parseInt($("slot_id", this).text()),
			"selections" : selections,
			"scroll" : 0, //discrete facets only
			"coords" : {"lat" : 0, "lng" : 0}, //geo facets only
		
		});
		
	});

	view_state.is_visible_facet_workspace=$("is_visible_facet_workspace", view_state_xml).text();
	view_state.is_visible_result_controllers=$("is_visible_result_controllers", view_state_xml).text();
	view_state.is_visible_facet_controllers=$("is_visible_facet_controllers", view_state_xml).text();
	
}




/*
function: user_view_state_activate
Activates the view using view_state information in view state object
- facets and slots
- which result view that is active

*/
function user_view_state_activate() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	if(view_state.client_language == "") {
//		console.log(
//		alert("client_language was empty in viewstate");
	}
	else {	
		client_language = view_state.client_language;
	}

//		result_init();


	
	//alert(client_language);
	//msg("activate view state");
	
	//$.dump(view_state.facets);
	
	system.do_not_initiate_result_load_data = true;
	
	//prepare/clean
	for(var key in facet_presenter.facet_list.facets) {
		facet_remove_facet(facet_presenter.facet_list.facets[key].id);
	}
	
	var slots = [];
	var facets_to_create = [];
	for(var key in view_state.facets) {
		
		var facet_info = facet_get_facet_info_by_id(view_state.facets[key].id);
		
		facet_obj = {
		"id" : view_state.facets[key].id,
		"dom_id" : "facet_"+view_state.facets[key].id,
		"title" : facet_info.display_title,
		"displayed_in_ui" : true,
		"slot_id" : view_state.facets[key].slot_id, //FIXME: slot_id is not honored, order is what matters
		"type" : facet_info.facet_type,
		"facet_range_max_value": facet_info.max,
		"facet_range_min_value": facet_info.min,
		"color" : facet_info.color,
		"use_text_search":facet_info.use_text_search,
                "counting_title" : facet_info.counting_title,
		};
		
		facets_to_create.push(facet_obj);
		
		slots.push(view_state.facets[key].slot_id);
		
		
	}
	
	for(var reg_key in facet_control_registry) {
		var keeper = false;
		for(var key in facets_to_create) {
			if(facet_control_registry[reg_key].id == facets_to_create[key].id) {
				keeper = true;
				//msg("keep "+facet_control_registry[reg_key].id);
			}
		}
		
		if(keeper) {
			facet_control_registry[reg_key].checked = 1;
		}
		else {
			facet_control_registry[reg_key].checked = 0;
		}
		
	}
	
	
	slots.sort();
	
	//$.dump(facets_to_create);
	
	for(var slot_key in slots) {
		for(var key in facets_to_create) {
			if(slots[slot_key] == facets_to_create[key].slot_id) {
				
				facet_create_facet(facets_to_create[key]);
				
				
				if(facets_to_create[key].type == "discrete") {
					var tmp_facet_obj=facet_presenter.facet_list.facet_get_facet_by_id(view_state.facets[key].id);
					for(var selection_key in view_state.facets[key].selections) {
			
						tmp_facet_obj.selections.push(view_state.facets[key].selections[selection_key]); // 2011-10-05 FIX by Fredrik And MARITA
						
					}
					
					if(view_state.facets[key].selections.length == 0) {
						var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(view_state.facets[key].id);
						facet_obj.selections_ready = true;
					}
				}
				
				if(facets_to_create[key].type == "range") {
					//$.dump(view_state.facets[key].selections);
					//$.dump(view_state.facets[key]);
					user_view_state_select_item(view_state.facets[key].id, view_state.facets[key].selections);
				}
				
				if(facets_to_create[key].type == "geo") {
					//$.dump(view_state.facets[key].selections);
					for(var selection_key in view_state.facets[key].selections) {
						//user_view_state_select_item(view_state.facets[key].id, view_state.facets[key].selections[selection_key]);
						
						user_view_state_select_geo_items(view_state.facets[key].id, view_state.facets[key].selections[selection_key]);
					}
					
					if(view_state.facets[key].selections.length == 0) {
						var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(view_state.facets[key].id);
						facet_obj.selections_ready = true;
					}
				}
			}
		}
	}
	
	
	
	//set up result area
	
	result_object.view = view_state.result_view;
	result_switch_view();
	
	
	facet_control_render_control(result_object.facet_control_orientation);
	
	system.do_not_initiate_result_load_data = false;
	
// turn all off	
	for(var key in result_object.result_variables_registry) {
		if (result_object.result_variables_registry[key].checked)
		{
			var event_obj = { "item" : {
			"id" : result_object.result_variables_registry[key].id,
			"dom_id" : result_object.result_variables_registry[key].dom_id
			}};
		
			result_variable_toggle(event_obj, "off");

		}
	}


	for(var key in view_state.result_active_result_variables) {
		var event_obj = { "item" : {
			"id" : view_state.result_active_result_variables[key],
			"dom_id" : "result_variable_"+view_state.result_active_result_variables[key]
		}};
		
		result_variable_toggle(event_obj, "on");
	}
	
	result_variable_perform_self_enable_evaluation_on_all();
	
	result_aggregation_type_set_status("select", view_state.result_aggregation_mode);
	
	// shared parameters for all apps
	
	view_state.result_map_bbox=$("map_bbox", view_state_xml).text();
//	view_state.result_map_center_lat= parseFloat($("map_center_lat", view_state_xml).text());
//	view_state.result_map_center_lng= parseFloat($("map_center_lng", view_state_xml).text());
//	view_state.result_map_zoom_level= parseInt($("map_zoom_level", view_state_xml).text());

// result modules will do the specifics for activating the view state, such map, zoomlevel etc
	
	 result_module_invoke_all("user_view_state_activate");


	facet_request_data_for_chain(0,"selection_change"); // 2011-11-24 Fredrik
//	result_load_data(); // use event facet_change instead
        
       
//	result_object.map_obj.setCenter(new  google.maps.LatLng(view_state.result_map_center_lat, view_state.result_map_center_lng));

	//result_object.map_obj.setCenter(new GLatLng(view_state.result_map_center_lat, view_state.result_map_center_lng));
//	result_object.map_obj.setZoom(view_state.result_map_zoom_level);
	
	//alert(view_state.is_visible_facet_workspace);


	if (view_state.is_visible_result_controllers=='false')
	{
		$("#result_controller_outer").hide();

	}
	else
		$("#result_controller_outer").show();


	if (view_state.is_visible_facet_controllers=='false')
	{
		$("#facet_controller_outer").hide();

	}
	else
		$("#facet_controller_outer").show();
	


	if (view_state.is_visible_facet_workspace=='false')
	{
		$("#facet_workspace").hide();

	}
	else
		$("#facet_workspace").show();



	//translate_html_elements();

}
/*
function: user_view_state_select_geo_items
- Render the google markers in the geo-filter based on the selected item in that filter.
- draws the rectangles for the selection in the map-filter

*/
function user_view_state_select_geo_items(facet_id, item) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	//item is coords for a single selection - item['ne']['lat'] item['sw']['lng']
	
	//$.dump(item);
	
	item["ne"]["lat"] = parseFloat(item["ne"]["lat"]);
	item["ne"]["lng"] = parseFloat(item["ne"]["lng"]);
	item["sw"]["lat"] = parseFloat(item["sw"]["lat"]);
	item["sw"]["lng"] = parseFloat(item["sw"]["lng"]);
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
        
	var ne = new google.maps.LatLng(item["ne"]["lat"], item["ne"]["lng"]);
	var sw = new google.maps.LatLng(item["sw"]["lat"], item["sw"]["lng"]);
	
	var marker = facet_geo_marker_tool_clicked_callback_seed(facet_obj, ne);
	
	
	var pairs_key;
	var close_marker;
	for(var key in facet_obj.geo_marker_pairs) {
		if(marker == facet_obj.geo_marker_pairs[key].start_marker) {
			pairs_key = key;
			close_marker = facet_obj.geo_marker_pairs[key].close_marker;
		}
		if(marker == facet_obj.geo_marker_pairs[key].end_marker) {
			pairs_key = key;
			close_marker = facet_obj.geo_marker_pairs[key].close_marker;
		}
	}
	
	
	var start_lat = item["ne"]["lat"];
	var start_lng = item["ne"]["lng"];
	var end_lat = item["sw"]["lat"];
	var end_lng = item["sw"]["lng"];
	
	var high_lat, low_lat, high_lng, low_lng;
	
	if(start_lat >= end_lat) {
		high_lat = start_lat;
		low_lat = end_lat;
	}
	else {
		// end marker is about the startmarker, adjust the offset of close_marker
		high_lat = end_lat;
		low_lat = start_lat;
		close_marker_offset=24;
	}
	if(start_lng >= end_lng) {
		high_lng = start_lng;
		low_lng = end_lng;
	}
	else {
		high_lng = end_lng;
		low_lng = start_lng;
	}
	
	var corners = Array();
	corners.push(new google.maps.LatLng(low_lat, low_lng));
	corners.push(new google.maps.LatLng(low_lat, high_lng));
	corners.push(new google.maps.LatLng(high_lat, high_lng));
	corners.push(new google.maps.LatLng(high_lat, low_lng));
	corners.push(new google.maps.LatLng(low_lat, low_lng));
	
       var poly= new google.maps.Polygon({
							paths: corners,
							strokeColor: '##5f6df9',
							strokeOpacity: 1,
							strokeWeight: 2,
							fillColor: '##5f6df9',
							fillOpacity: 0.20,
							clickable:false
		        });//symbology of the polygon 
	
	
	// remove previous rectangle if exists
	if(typeof(marker_overlay) != "undefined") {
	// remove the marker_overlay from the map
		marker_overlay.setMap(null);
	// V2 	facet_obj.map.removeOverlay(marker_overlay);
	}
	
	facet_obj.geo_marker_pairs[pairs_key].overlay = poly;
	
	
	// V2 facet_obj.map.addOverlay(poly);
	
	poly.setMap(facet_obj.map); // add polygon to map
        
        /* v2
	var poly = new GPolygon(corners, "#5f6df9", 2, 1, "#5f6df9", 0.2);
	
	facet_obj.geo_marker_pairs[pairs_key].overlay = poly;
	
	facet_obj.map.addOverlay(poly);
	
	var close_position = new GLatLng(high_lat, high_lng);
    */
	/* v2
	if ((close_position.lat()==start_lat &&  close_position.lng()==start_lng) ||  (close_position.lat()==end_lat &&  close_position.lng()==end_lng) )
	{
		close_marker.getIcon().iconAnchor = new GPoint(24,8);
	}
	else {
		close_marker.getIcon().iconAnchor = new GPoint(8,8);
	}
	close_marker.setLatLng(close_position);
    */
   
        var close_position= new google.maps.LatLng(high_lat, high_lng)
	
	
       	
	if ((close_position.lat()==start_lat &&  close_position.lng()==start_lng) ||  (close_position.lat()==end_lat &&  close_position.lng()==end_lng) )
	{
		close_marker.getIcon().iconAnchor =  new google.maps.Point(24,8);
	}
	else {
		close_marker.getIcon().iconAnchor = new google.maps.Point(8,8);
	}

	close_marker.setPosition(close_position);
	
	
	facet_geo_marker_tool_clicked_callback_plant(facet_obj, sw);
	
	facet_obj.selections_ready = true;
	
}
/*
function: user_view_state_select_item
Add a selection to the facet one by one
Also trigger the the functions that would have happen as if a user made each selection. (view state function acts as robot...)

*/
function user_view_state_select_item(facet_id, item) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	var check_back_later = false;
	
	if(facet_obj.type == "discrete" && (facet_obj.rendering_data || typeof(facet_obj.last_rendered_request) == "undefined")) {
		check_back_later = true;
	}
	
	if(facet_obj.type == "range" && facet_obj.value_bar_initialized == false) {
		check_back_later = true;
		//msg("check back later for selections-population on range facet");
	}
	
	if(check_back_later) {
		//msg("viewstate wait");
		setTimeout(function() { user_view_state_select_item(facet_id, item); }, 500);
		return;
	}
	
	if(facet_obj.type == "discrete") {
		for(var key in facet_obj.contents) {
			if(facet_obj.contents[key].value == item) {
				facet_row_clicked_callback(facet_id, facet_obj.contents[key].dom_id); // BUG HERE, it only selects visible rows FP 2011-10-05
			}
		}
	}
	
	if(facet_obj.type == "range") {
		
		//msg("set range facet selections");
		
		var state = {};
		
		if(item[0] < item[1]) {
			state = {
			"start" : item[0],
			"end" : item[1]
			};
		}
		else {
			state = {
			"start" : item[1],
			"end" : item[0]
			};
		}
		
		$("#facet_"+facet_obj.id+"_content_container_lower_text_box").val(item[0]);
		$("#facet_"+facet_obj.id+"_content_container_upper_text_box").val(item[1]);
		
		$("#"+facet_obj.id+"_slider_container").slider("values", 0, item[0]);
		$("#"+facet_obj.id+"_slider_container").slider("values", 1, item[1]);
		
	}
	
	
	facet_obj.selections_ready = true;
}



function user_view_state_xml_to_struct() {
	
	
}

/*
function: user_pack_view_state
Pack the view state into xml by using <user_view_state_struct_to_xml
result_object - all result parameters
facet_presenter.facet_list.facets - all facet information but excluding heavy objects such googlemap objects

*/
function user_pack_view_state() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var view_state = {};
	
	var zoom_level = result_object.map_obj.getZoom();
	if (zoom_level>=22)
	{
		// do a zoom out since google client can not compute the center if you are over zoomlevel 23.
		var center = result_object.map_obj.getCenter();
	}
	else
	{
		var center = result_object.map_obj.getBounds().getCenter();
	}
	
	//alert("at pack: "+client_language);
	view_state.client_language = client_language;
	
	result_object.map_center_lat = center.lat();
	result_object.map_center_lng = center.lng();
	result_object.map_zoom_level = zoom_level;
	
	view_state.result_object = result_object;
	view_state.facet_objects = facet_presenter.facet_list.facets;

	view_state.is_visible_facet_controllers=$("#facet_controller_outer").is(":visible");
	view_state.is_visible_result_controllers=$("#result_controller_outer").is(":visible");
	view_state.is_visible_facet_workspace=$("#facet_workspace").is(":visible");
	
	var xml = {"data" : ""};
	var exclude_keys = ["global_facet_xml","result_xml", "map", "map_obj", "time_bar", "value_bar", "value_bar_obj", "tile_layer_overlay", "chart_obj", "map", "contents", "geo_marker_pairs", "filter_tool_mouse_move_event_listener"];
	user_view_state_struct_to_xml(view_state, exclude_keys, xml);
	
	xml.data = "<xml>"+xml.data+"</xml>";
	
	return xml;
}

/*
function: user_view_state_struct_to_xml
Create xml document from a structure composite objecte.
It excludes all object with the key in the exclude_keys in the argument list.

Parameter: 
struct - the compositet data
exclude_keys - object to exclude/ignore
xml - ??
*/

function user_view_state_struct_to_xml(struct, exclude_keys, xml) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	for(var key in struct) {
		
		
		
		if($.inArray(key, exclude_keys) == -1) {
			if(key == "result_variables_registry") {
				//$.dump(struct[key]);
				var reg = struct[key];
				xml.data += "<checked_result_variables>";
				for(var k in reg) {
					if(reg[k].checked == true) {
						xml.data += "<item>"+reg[k].id+"</item>";
					}
				}
				xml.data += "</checked_result_variables>";
				
			}
			else if(key == "result_variable_aggregation_types") {
				var agg_types = result_object.result_variable_aggregation_types;
				for(var k in agg_types) {
					if(agg_types[k].selected == true) {
						xml.data += "<aggregation_mode>"+agg_types[k].id+"</aggregation_mode>";
					}
				}
			}
			else {
				
				var xml_key = "";
				if(is_numeric(key)) {
					xml_key = "num_"+key;
				}
				else {
					xml_key = key;
				}
				
				
				xml.data += "<"+xml_key+">";
				if($.isArray(struct[key]) || typeof(struct[key]) == "object") {
					user_view_state_struct_to_xml(struct[key], exclude_keys, xml);

				}
				else {
					xml.data += struct[key];
				}
				
				xml.data += "</"+xml_key+">\n";
			}
		}
		else {
			//msg("skipping "+key);
		}
	}
}

/*
function:  user_get_view_state
- Gets the xml from server using a id using <get_view_state.php>
- then unpacking the xml using <user_view_state_unpack>
- then activating the view state by <user_view_state_activate>
*/
function user_get_view_state(view_state_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	//msg("vsi: "+view_state_id);
	
	if(isNaN(parseInt(view_state_id))) {
		soft_alert(t("Ogiltigt vy-nummer."));
		return;
	}
	else {
		current_view_state_id = parseInt(view_state_id);
	}
	
	
	if(current_view_state_id < 0) {
		msg("invalid view state id");
		return false;
	}
	
	$.ajax({
		type: "POST",
		url: "http://" + application_address + application_prefix_path + "api/get_view_state.php",
		cache: false,
		dataType: "xml",
		processData: false,
		data: "view_state_id="+current_view_state_id+"&application_name="+application_name,
		global: false,
		success: function(xml) {
			//msg("get-ret");
			//xml = $(":first-child", xml).children();
			//var struct = {};
			//user_view_state_xml_to_struct(xml, struct, "root", 0);
			//$.dump(struct);
			view_state_xml=xml;
			user_view_state_unpack(view_state_xml);
			user_view_state_activate();
                        
                        
                    
		}
	});
}
