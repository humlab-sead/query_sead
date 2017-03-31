/*
file: result.js
Handles general result functionality
- for map  <map_module.js (SHIPS)>
- for diagram also <diagram_module.js (SHIPS)>
- for list/table <list_module.js (SHIPS)>
*/
//***************************************************************************************************************************************************

/*
   Function: result_init

   Description:
   This function initiates the result area and its content.
   Depending on which tab is active different content is loaded.

   see also:
   <result_maximize>

   <result_render_control>

   <result_module_invoke_all>

   <result_switch_view>

*/
function result_init() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	//console.log(client_language);
	var result_workspace = $("#result_workspace");
// 	result_workspace.css("z-index", "100");
	
	var result_workspace_cc = $("#result_workspace_content_container");
//	$("#show_active_filters_link").text(t("Visa aktuella val"));
	
	result_object.result_workspace_saved_css = {};
	result_object.result_workspace_saved_css.left = result_workspace.css("left");
	result_object.result_workspace_saved_css.top = result_workspace.css("top");
	result_object.result_workspace_saved_css.width = result_workspace_cc.css("width");
	result_object.result_workspace_saved_css.height = result_workspace_cc.css("height");
	
	
	//load the result variable definitons from the server into an organized registry
	result_object.result_variables_registry = Array();
	for(var key in result_variable_definitions) {
		
		var checked = false;
		if(result_variable_definitions[key]['default']) {
			checked = true;
		}
		else {
			checked = false;
		}
		
		var children = Array();
		for(var child in result_variable_definitions) {
			if($.inArray(result_variable_definitions[key].id, result_variable_definitions[child].parents) > -1) {
				children.push({
					"id" : result_variable_definitions[child]['id'],
					"title" : result_variable_definitions[child]['name'],
					"dom_id" : result_variable_definitions[child]['dom_id']
				});
			}
		}
		
		result_object.result_variables_registry.push({
			"id" : result_variable_definitions[key]['id'],
			"title" : result_variable_definitions[key]['name'],
			"dom_id" : result_variable_definitions[key]['dom_id'],
			"parents" : result_variable_definitions[key]['parents'],
			"children" : children,
			"checked" : checked,
			"hiding_in_the_closet" : false,
			"disabled" : false,
			"type" : result_variable_definitions[key]['type']
		});
	}
	
	
	//load the result variable aggregation type definitions from the server into a registry
	result_object.result_variable_aggregation_types = Array();
	for(var key in result_variable_aggregation_types) {
		result_object.result_variable_aggregation_types.push({
			"id" : result_variable_aggregation_types[key]['id'],
			"title" : result_variable_aggregation_types[key]['title'],
			"selected" : result_variable_aggregation_types[key]['activated'],
			"type" : result_variable_aggregation_types[key]['aggregation_type'],
			"enabled" : true
		});
	}
	
	
	for(var key in result_modules) {
		result_object.result_modules.push(result_module_invoke(result_modules[key], "info"));
	}
	
	
	$("#result_max_min_button").bind("click", function() {
		result_maximize_toggle();
	});
	
	$("#status_area_content_container").hide();
	
	$("#show_active_filters_link").bind("click", function() {
		
		var container = $("#status_area_content_container");
		
		if(container.is(":visible")) {
			container.hide(200);
			$("#show_active_filters_link").text(t("Visa aktuella val"));
		}
		else {
			container.show(200);
			$("#show_active_filters_link").text(t("Dölj aktuella val"));
		}
		
	});
	
	result_render_control();
	result_render_tabs();
	result_module_invoke_all("init");
	if(system.do_not_initiate_result_load_data == false) {
		result_load_data();
	}
	facet_add_tooltip(t("Klicka för att maximera/återställa"), $("#result_max_min_button_cell"));
//	("#result_max_min_button_cell")
	//$.dump(result_variable_aggregation_types);
}

function result_render_tabs() {
	
	for(var key in result_object.result_modules) {
		var html = "";
		html += "<td class=\"result_workspace_tab\">";
		html += "<div id=\"result_"+result_object.result_modules[key].id+"_button\" class=\"result_view_button\">"+result_object.result_modules[key].title+"</div>";
		html += "</td>";
		var html_obj = $(html);
		
		if(result_object.view == result_object.result_modules[key].id) {
			$(html_obj).addClass("result_workspace_tab_active");
			$("div", html_obj).css("color", "#000000");
		}
		
		html_obj.bind("click", function() {
			var dom_id = $("div", this).attr("id");
			var dom_id_parts = dom_id.split("_");
			var module_id = dom_id_parts[1];
			result_object.view = module_id;
			result_switch_view();
		});
		
		$("#result_workspace_tab_area").prepend(html_obj);
	}
	
}

function result_switch_view() {
	var clicked_tab = "";
	
	$(".result_workspace_tab").each(function() {
		$(this).removeClass("result_workspace_tab_active");
		$(this).addClass("result_workspace_tab_inactive");
		$("div", this).css("color", "#ffffff");
		
		var tab_id = $("div", this).attr("id");
		
		var parts = tab_id.split("_");
		if(parts[1] == result_object.view) {
			clicked_tab = this;
		}
		
		
	});
	
	$(clicked_tab).removeClass("result_workspace_tab_inactive");
	$(clicked_tab).addClass("result_workspace_tab_active");
	$("div", clicked_tab).css("color", "#000000");
	
	result_module_invoke_all("pre_switch_view");
	result_module_invoke_all("hide");
	result_module_invoke(result_object.view, "show");
	result_module_invoke_all("post_switch_view");
	if(system.do_not_initiate_result_load_data == false) {
		result_load_data();
	}
	
}


//***************************************************************************************************************************************************
/*
   Function: result_load_data

   Description:
   This function requests data from the server and loads the data for all result tabs, and activates the tab selected by the user.

   Types of requests:
	* map
	* diagram
	* list

   see also:
   <result_module_invoke_all>

   <facet_build_xml_request>

   <result_get_selected_list_items>

   <result_get_map_items>

   <result_get_result_variable_selections_as_xml>

   <result_render_view>

*/


function result_load_data() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	
	//check that all facets are ready
	var all_range_facets_ready = true;
	for(var key in facet_presenter.facet_list.facets) {
		if(facet_presenter.facet_list.facets[key].type == "range" && facet_presenter.facet_list.facets[key].selections_ready != true) {
			//msg(facet_presenter.facet_list.facets[key].id+" not ready");
			all_facets_ready = false;
		}
	}
	
	if(all_range_facets_ready == false) {
		//msg("result_load_data waiting for all facets to become ready");
		setTimeout("result_load_data();", 250);
	}
	
	
	global_result_request_id++;
	
	result_loading_indicator_set_state("on");
	
	
	var module_additions = result_module_invoke(result_object.view, "data_loading_params");
	//var module_additions = result_module_invoke_all("data_loading_params");
	var facet_xml =result_object.global_facet_xml;  //"";// facet_build_xml_request({}, true);
	// get map list items
	
	//var selected_item_arr = result_get_selected_list_items();
//	if(typeof(selected_item_arr) == "undefined" || selected_item_arr.length == 0) {
		//return false;
//	}
	
	/*
	var c = result_map_get_current_year();
	
	if(c !== false) {
		time_bar_current = c;
	}
	*/
	
	var result_xml = "";
	result_xml += "<data_post>";
	result_xml += "<request_id>";
	result_xml += global_result_request_id;
	result_xml += "</request_id>";
	result_xml += "<session_id>";
	result_xml += currentUser.sessionKey;
	result_xml += "</session_id>";
	result_xml += "<result_input>";
	result_xml += "<view_type>";
	result_xml += result_object.view;
	result_xml += "</view_type>";
	
	
	///TODO: need to fix this somehow
	/*var selected_map_item_arr = result_get_map_items();
	
	for(var i = 0; i < selected_map_item_arr.length; i++){
		result_xml += "<map_selected_item>";
		result_xml += selected_map_item_arr[i];
		result_xml += "</map_selected_item>";
	}
	*/
	
	result_xml += result_get_result_variable_selections_as_xml();
	result_xml += "</result_input>";
	result_xml += "</data_post>";
	
	var post_data = "facet_xml="+facet_xml+"&result_xml="+result_xml+"&application_name="+application_name;
	
	/*
	for(var key in module_additions) {
		for(var k in module_additions[key]) {
			post_data += "&"+k+"="+module_additions[key][k];
		}
	}
	*/
	
	for(var key in module_additions) {
		post_data += "&"+key+"="+module_additions[key];
	}
        // FIX empty container before request is sent.
	$("#status_area_content_container").html(t("Laddar"));
	
	$.ajax({
		type: "POST",
		url: "http://" + application_address + application_prefix_path + "api/load_result.php",
		cache: false,
		dataType: "xml",
		processData: false,
		data: post_data,
		global: false
	}).done(
        function(xml, textStatus, jqXHR){
            result_object.result_xml = xml;
            if(parseInt($("request_id", xml).text()) == global_result_request_id) {
                //msg("Received result_xml "+$("request_id", xml).text()+" which matches global req_id "+global_result_request_id);
                result_loading_indicator_set_state("off");
            }
            else {
                //msg("Dropping result_xml "+$("request_id", xml).text()+" because global req_id is "+global_result_request_id);
                return;
            }
            result_module_invoke_all("stop_loading_data");
            result_module_invoke(result_object.view, "update");
            result_loading_indicator_set_state("off");
            result_update_status_area(xml);
            }
    ).fail(function( jqXHR, textStatus, errorThrown ) {
        console.log(textStatus);
    });
}

function result_update_status_area(xml) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	//$.dump($("current_selections", xml).text());
	
	var contents = $("current_selections", xml).text();
	
	if(contents == "") {
		contents = t("Inga vald gjorda");
	}
	
	$("#status_area_content_container").html(contents);
}

function result_loading_indicator_set_state(state) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	state = state.toLowerCase();
	
	if(state == "on") {
		$(".result_loading_indicator").html("<img style=\"margin-top:2px;\" src=\"client/theme/images/loadingf.gif\" class=\"facet_load_indicator\" /> <span style=\"position:relative;top:-5px;\">"+t("Laddar")+"...</span>");
		
		$(".result_loading_indicator").animate({
			"opacity" : "1.0"
		}, 200);
	}
	else if(state == "off") {
		$(".result_loading_indicator").html("&nbsp;");
		
		$(".result_loading_indicator").animate({
			"opacity" : "0.0"
		}, 200);
	}
}

//***************************************************************************************************************************************************
/*
   Function: result_get_result_variable_selection_as_array
	help function for other functions
   get the selected result items as  list 
 */ 
function result_get_result_variable_selection_as_array()
{
var selected_items = Array();
	$("#result_control").find("input[type=checkbox]").each(function() {
		if($(this).prop("checked") == true && $(this).parent().hasClass("result_variable_parent") == false) {
			
			if($.inArray($(this).attr("name"), selected_items) == -1) {
				selected_items.push($(this).attr("name"));
			}
		}
	});
	
	return selected_items;
}
//***************************************************************************************************************************************************
/*
   Function: result_get_result_variable_selections_as_xml

   Description:
   This function compiles an xml formatted list of which aggregation level and result variables are selected.

   Returns:
   xml - the xml formatted list

*/
function result_get_result_variable_selections_as_xml() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var xml = "";
	var agg_mode;
	$("#result_control").find("input[name=\"result_variable_aggregation_type\"]").each(function() {
		if($(this).prop("checked") == true) {
			agg_mode=$(this).attr("value");

			xml += "<aggregation_code>"+$(this).attr("value")+"</aggregation_code>"; // make the aggregation code one expclitiy xml-tag and the aggregation item olds holds some variables 
		}
	});
	var selected_items=result_get_result_variable_selection_as_array();
	for(var key in selected_items) {
		xml += "<selected_item>"+selected_items[key]+"</selected_item>";
	}


	
	return xml;
}
/*
Function: result_get_result_variable_selections
get the selected variables as composite array with id and name of the result items selected
*/
function result_get_result_variable_selections()
{
	var selected_items=result_get_result_variable_selection_as_array();
	var return_items=Array();
	for(var key in selected_items) {
	   var selected_item=selected_items[key];
	   for(var child in result_variable_definitions)
	   {
		   		   // loop through the selected and get the title and id
		   if (selected_item==result_variable_definitions[child].id)
		   {

				return_items[result_variable_definitions[child].id]=Array();
		    	return_items[result_variable_definitions[child].id].id=result_variable_definitions[child].id;
				return_items[result_variable_definitions[child].id].name=result_variable_definitions[child].name;

			  //console.log(result_variable_definitions[child].name);
		   }

	   }
}
	
	return return_items;

}
//***************************************************************************************************************************************************
/*
   Function: result_render_view

   Description:
   This function activate the correct workspace area depending on which tab has been selected (i.e. the map, the diagram or the table).

   see also:
   <result_render_view_list>

   <result_render_view_map>

   <result_render_view_diagram>
*/
function result_render_view_OBSOLETE() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var request_id = $(result_object.result_xml).find("request_id").text();

	// update status area 2
	
	//$("#status_area").find(".content_container").html($(result_object.result_xml).find("current_selections").text());
	
	$("#status_area_content_container").html($(result_object.result_xml).find("current_selections").text());
	
	if(parseInt(request_id, 10) != -1 && parseInt(global_result_request_id, 10) > parseInt(request_id, 10)) {
		//msg("dropped result request "+request_id);
		//msg(parseInt(global_result_request_id, 10));
		return false;
	}
	
	
	$("#result_workspace_content_container").css("width", result_diagram_workspace_container_fixed_width+"px");
	$("#result_workspace_content_container").css("height", result_diagram_workspace_container_fixed_height+"px");
	
	if (result_object.view == 'list') {
		$("#result_map_workspace_container").hide(0);
		$("#result_diagram_workspace_container").hide(0);
		$("#result_list_workspace_container").show(0);
		result_render_view_list();
	}
	else if (result_object.view == 'map') {
		$("#result_list_workspace_container").hide(0);
		$("#result_diagram_workspace_container").hide(0);
		$("#result_map_workspace_container").show(0);
		result_render_view_map();
		// wait for flash
		/*
		if (result_object.time_bar!=undefined)
		{
			var state = {
				current: result_object.time_bar_current 
			};

			setTimeout(function() {
				result_object.time_bar.broadcast('timebar.setState', state);
				result_map_update_time_bar();
			}, 800);
		}
		*/
	
	}
	else if (result_object.view == 'diagram') {
		$("#result_map_workspace_container").hide(0);
		$("#result_list_workspace_container").hide(0);
		$("#result_diagram_workspace_container").show(0);
		result_render_view_diagram();
	}

	//after rendering is complete by the appropriate result module, make sure the result_workspace_content_container has a sane size
	// - but only if not maximized
	if(result_object.maximized == false) {
		result_adjust_workspace_content_container_size();
	}
}

function result_adjust_workspace_content_container_size() {
	if($("#result_workspace_content_container").width() < result_diagram_workspace_container_min_width) {
		$("#result_workspace_content_container").css("width", result_diagram_workspace_container_min_width+"px");
	}
	if($("#result_workspace_content_container").width() > result_diagram_workspace_container_max_width) {
		$("#result_workspace_content_container").css("width", result_diagram_workspace_container_max_width+"px");
	}
	
	if($("#result_workspace_content_container").height() < result_diagram_workspace_container_min_height) {
		$("#result_workspace_content_container").css("height", result_diagram_workspace_container_min_height+"px");
	}
	if($("#result_workspace_content_container").height() > result_diagram_workspace_container_max_height) {
		$("#result_workspace_content_container").css("height", result_diagram_workspace_container_max_height+"px");
	}
}


function result_render_result_variable_item(item, has_children, level) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var classes = item.dom_id+" result_variable";
	var parent_icon_html = "";
	if(has_children) {
		classes += " result_variable_parent";
		parent_icon_html += "<img class=\"result_variable_tree_btn\" src=\"client/theme/images/tree_btn_closed.png\" />&nbsp;";
		

	}
	else {
		parent_icon_html += "<img src=\"client/theme/images/tree_btn_placeholder.png\" />&nbsp;";
		if(level == 1) {
		parent_icon_html += "<img src=\"client/theme/images/tree_btn_placeholder.png\" />&nbsp;";
		}
	}
	
	var checkbox_html = "<input name=\""+item.id+"\" class=\""+item.dom_id+"_checkbox\" type=\"checkbox\" />";
	
	var title_cut = false;
	var item_modified_name = "";
	item.title=t(item.title); // Translate the variable here, since there are rules for length etc.

	if(item.title.length > 20) {
		item_modified_name = cut_string_to_length(item.title, 20);
		title_cut = true;
	}
	else {
		item_modified_name = item.title;
	}
	
	var html = "<div class=\""+classes+"\">"+parent_icon_html+""+checkbox_html+"<span class=\""+item.dom_id+"_trigger checkbox_trigger_area\"><span class=\"result_variable_title\">"+item_modified_name+"</span></span><div class=\"result_variable_child_container\"></div></div>";
	
	var html_obj = $(html);
	

	facet_add_tooltip(t("Klicka för expandera/minimera"), $(".result_variable_tree_btn",html_obj ));
	facet_add_tooltip(item.title+", "+t("klicka för att välja/välja bort."), $(".result_variable_title", html_obj));

	facet_add_tooltip(item.title+", "+t("klicka för att välja/välja bort."), $("[type=\"checkbox\"]", html_obj));

	
	$(html_obj).find("[type=\"checkbox\"]").bind("click", function() {
		
		var classes = $(html_obj).attr("class").split(" ");
		//soft_alert(classes[0]);
		//result_variable_trigger(classes[0]);
		var event_obj = { "event_type" : "click", "class_id" : classes[0] };
		
		result_variable_event(event_obj);
	});
	
	if(item.checked) {
		$(html_obj).find("[type=\"checkbox\"]").prop("checked", true);
	}
	
	
	$(".checkbox_trigger_area", html_obj).bind("click", function() {
		//soft_alert(this);
		var classes = $(html_obj).attr("class").split(" ");
		//soft_alert(classes[0]);
		//var item = result_variable_registry_get_item_by_dom_id(classes[0]);
		
		var event_obj = { "event_type" : "click", "class_id" : classes[0] };
		result_variable_event(event_obj);
		
	});
	
	
	return html_obj;
}


function result_variable_event(event_obj) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	//$.dump(event_obj);
	
	var registry_item = {};
	for(var key in result_object.result_variables_registry) {
		if(result_object.result_variables_registry[key].dom_id == event_obj.class_id) {
			event_obj.item = result_object.result_variables_registry[key];
		}
	}
	
	if(event_obj.item.disabled == true && event_obj.item.hiding_in_the_closet == false) {
		soft_alert(t("Det går inte att avmarkera alla resultatvariabler. Minst en måste vara förbockad."));
		return;
	}
	else if(event_obj.item.disabled == true && event_obj.item.hiding_in_the_closet == true) {
		soft_alert(t("Den här resultatvariabeln kan inte användas på den här summeringsnivån och är därför avstängd för tillfället. Välj en annan summeringsnivå för att aktivera den."));
		return;
	}
	
	//copy over the now existing result variable registry to have a pre-event reference
	event_obj.pre_event_registry = Array();
	for(var key in result_object.result_variables_registry) {
		event_obj.pre_event_registry[key] = {};
		for(var obj_key in result_object.result_variables_registry[key]) {
			event_obj.pre_event_registry[key][obj_key] = result_object.result_variables_registry[key][obj_key];
		}
	}
	
	
	
	result_variable_toggle(event_obj);
	
	result_variable_perform_self_check_evaluation_on_all(event_obj);
	result_variable_perform_self_enable_evaluation_on_all();
	
	//result_update_aggregation_control();

	// result_module_invoke_all("result_variable_event", result_object.view);
	// ask the component if the request needs to be send
	// component is responsible of maintaint the content of the controlers by either getting data from the client or request data from server by the component it self.


//	
var event_str="result_variable_";
var result_variable_id_str="";
result_variable_id_str+=event_obj.item.id;
if (event_obj.item.checked)
{
	event_str+="checked";
//	console.log(result_variable_id_str+"  "+event_str);

}
else
{
	//alert(event_obj.item.checked+"Unchecked");
	event_str+="unchecked";
//	console.log(result_variable_id_str+"  "+event_str);
}

	result_module_invoke(result_object.view,"result_variable_event", result_variable_id_str+";"+event_str);

	if(system.do_not_initiate_result_load_data == false) {
		result_load_data();
	}
	system.do_not_initiate_result_load_data=false;
}

/*
Function: result_variable_toggle
check/uncheck self
*/

function result_variable_toggle(event_obj, value) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	if(typeof(value) != "undefined") {
		if(value.toLowerCase() == "on") {
			result_variable_set_status("checked", event_obj.item.id);
		}
		else if(value.toLowerCase() == "off") {
			result_variable_set_status("unchecked", event_obj.item.id);
		}
	}
	else {
		//check/uncheck self
		if(event_obj.item.checked) {
			result_variable_set_status("unchecked", event_obj.item.id);
		}
		else if($("."+event_obj.item.dom_id+"_checkbox").prop("disabled") == false) {
			result_variable_set_status("checked", event_obj.item.id);
		}
	}
}

function result_aggregation_type_perform_self_enable_evaluation_on_all() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	/*
	var single_sum_items_selected = 0;
	var sum_items_selected = 0;
	for(var key in result_object.result_variables_registry) {
		if(result_object.result_variables_registry[key].type == "single_sum_item") {
			single_sum_items_selected++;
		}
		else if(result_object.result_variables_registry[key].type == "sum_item") {
			sum_items_selected++;
		}
	}
	
	if(sum_items_selected == 0) {
		//$("[name=\"result_variable_aggregation_type\"]");
	}
	else {
		
	}
	*/
}

function result_aggregation_type_set_status(status_change, id) {
	
	var item;
	for(var key in result_object.result_variable_aggregation_types) {
		//msg(result_object.result_variable_aggregation_types[key].id);
		if(result_object.result_variable_aggregation_types[key].id == id) {
			item = result_object.result_variable_aggregation_types[key];
			
		}
	}
	
	if(typeof(item) == "undefined") {
		msg("Received order to set status \""+status_change+"\" of aggregation type control, but couldn't read ID: "+id);
		return false;
	}
	
	status_change = status_change.toLowerCase();
	
	if(status_change == "disable" || status_change == "disabled") {
		$("#result_aggregation_type_"+item.id).prop("disabled", true);
		item.enabled = false;
		result_module_invoke_all("result_aggregation_type_disabled", id);
	}
	else if(status_change == "enable" || status_change == "enabled") {
		$("#result_aggregation_type_"+item.id).prop("disabled", false);
		item.enabled = true;
		result_module_invoke_all("result_aggregation_type_enabled", id);
	}
	else if(status_change == "select" || status_change == "selected") {
		$("#result_aggregation_type_"+item.id).prop("checked", true);
		
		
		for(var key in result_object.result_variable_aggregation_types) {
			if(result_object.result_variable_aggregation_types[key].id == id) {
				result_object.result_variable_aggregation_types[key].selected = true;
			}
			else {
				result_object.result_variable_aggregation_types[key].selected = false;
			}
		}
		
		result_module_invoke_all("result_aggregation_type_selected", id);
	}
	
}

function result_variable_perform_self_enable_evaluation_on_all() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var disabling_debug = false;
	
	var single_sum_items_checked = 0;
	var total_items_checked = 0;
	
	for(var key in result_object.result_variables_registry) {
		var item = result_object.result_variables_registry[key];
		
		if(item.checked == true && item.type == "single_sum_item") {
			single_sum_items_checked++;
		}
		
		if(item.checked == true && item.children.length == 0) {
			total_items_checked++;
		}
	}
	
	
	if(single_sum_items_checked == total_items_checked) {
		//disable agg types other than parish
		for(var key in result_object.result_variable_aggregation_types) {
			if(result_object.result_variable_aggregation_types[key].type == "has_aggregation") {
				//disable this
				//msg("Set disabled on "+result_object.result_variable_aggregation_types[key].id);
				result_aggregation_type_set_status("disabled", result_object.result_variable_aggregation_types[key].id);
			}
			else {
				//msg("Set enabled on "+result_object.result_variable_aggregation_types[key].id);
				result_aggregation_type_set_status("enabled", result_object.result_variable_aggregation_types[key].id);
			}
		}
	}
	else {
		for(var key in result_object.result_variable_aggregation_types) {
			result_aggregation_type_set_status("enabled", result_object.result_variable_aggregation_types[key].id);
		}
	}
	
	
	if(disabling_debug) { msg("enabling all"); };
	for(var key in result_object.result_variables_registry) {
		var item = result_object.result_variables_registry[key];
		result_variable_set_status("enabled", item.id);
	}
	
	//single_sum type items may not be enabled when an aggmode other than 'parish' is selected
	var active_aggregation_type = null;
	for(var key in result_object.result_variable_aggregation_types) {
		if(result_object.result_variable_aggregation_types[key].selected) {
			active_aggregation_type = result_object.result_variable_aggregation_types[key];
		}
	}
	
	
	
	if(single_sum_items_checked > 0 && total_items_checked > 1 && active_aggregation_type.type == "has_aggregation") {
		//disable all single_sum_items - but keep them checked
		for(var key in result_object.result_variables_registry) {
			var item = result_object.result_variables_registry[key];
			if(item.type == "single_sum_item") {
				result_variable_set_status("disabled", item.id);
			}
		}
	}
	
	
	var parents_to_consider_for_disablement = [];
	
	for(var key in result_object.result_variables_registry) {
		if(active_aggregation_type.type == "has_aggregation" && result_object.result_variables_registry[key].type == "single_sum_item" && result_object.result_variables_registry[key].disabled == false) {
			result_variable_set_status("disabled", result_object.result_variables_registry[key].id);
			if(disabling_debug) { msg("disabling "+item.id+" because of invalid aggmode"); }
			
			for(var pkey in result_object.result_variables_registry[key].parents) {
				
				if(typeof(parents_to_consider_for_disablement[result_object.result_variables_registry[key].parents[pkey]]) == "undefined") {
					parents_to_consider_for_disablement[result_object.result_variables_registry[key].parents[pkey]] = 0;
				}
				
				parents_to_consider_for_disablement[result_object.result_variables_registry[key].parents[pkey]]++;
				
			}
			
		}
	}
	
	/*
	for(var id in parents_to_consider_for_disablement) {
		var item = result_variable_registry_get_item_by_id(id);
		if(item.children.length == parents_to_consider_for_disablement[id]) {
			result_variable_set_status("disabled", item.id);
			if(disabling_debug) { msg("disabling "+item.id+" because all children are disabled 1"); }
		}
	}
	
	
	var total_number_of_checked_items = 0;
	for(var key in result_object.result_variables_registry) {
		var item = result_object.result_variables_registry[key];
		if(item.checked && item.children.length == 0) {
			total_number_of_checked_items++;
		}
	}
	
	
	for(var key in result_object.result_variables_registry) {
		var item = result_object.result_variables_registry[key];
		
		if(item.checked && total_number_of_checked_items < 2 && item.children.length == 0) {
			result_variable_set_status("disabled", item.id);
			if(disabling_debug) { msg("disabling "+item.id+" because totnum of checked < 2 and has no children"); }
		}
	}
	
	
	for(var key in result_object.result_variables_registry) {
		var item = result_object.result_variables_registry[key];
		
		checked_children = 0;
		
		if(item.children.length > 0) {
			var checked_and_disabled_children = 0;
			var checked_children = 0;
			for(var child_key in item.children) {
				var child_item = result_variable_registry_get_item_by_id(item.children[child_key].id);
				if(child_item.disabled && child_item.checked) {
					checked_and_disabled_children++;
				}
				if(child_item.checked) {
					checked_children++;
				}
			}
			
			if(checked_and_disabled_children == checked_children && item.checked) {
				result_variable_set_status("disabled", item.id);
				if(disabling_debug) { msg("disabling "+item.id+" because all children are disabled 2"); }
			}
		}
	}
	
	
	//perform uncheck scenario evaluation
	
	for(var key in result_object.result_variables_registry) {
		var item = result_object.result_variables_registry[key];
		var children_checked = 0;
		for(var child_key in item.children) {
			var child_item = result_variable_registry_get_item_by_id(item.children[child_key].id);
			if(child_item.checked) {
				children_checked++;
			}
		}
		
		if(children_checked == total_number_of_checked_items) {
			result_variable_set_status("disabled", item.id);
			if(disabling_debug) { msg("disabling "+item.id+" because numof children checked == totnum checked items"); }
		}
	}
	
	*/
}


function result_variable_perform_self_check_evaluation_on_all(event_obj) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	
	var checking_debug = false;
	
	var checked_children = 0;
	
	
	if(typeof(event_obj) != "undefined") {
		//if this is a parent object
		
		if(event_obj.item.children.length > 0) {
			for(var key in event_obj.item.children) {
				var child_event_obj = { };
				var child_item = result_variable_registry_get_item_by_id(event_obj.item.children[key].id);
				child_event_obj.item = child_item;
				//msg(event_obj.item.id+" >>> "+child_item.id+" : "+child_item.checked);
				if(event_obj.item.checked) {
					result_variable_toggle(child_event_obj, "on");
					if(checking_debug) { msg("checking "+child_event_obj.item.id+" because parent was actively checked"); }
				}
				else {
					result_variable_toggle(child_event_obj, "off");
					if(checking_debug) { msg("unchecking "+child_event_obj.item.id+"  because parent was actively unchecked"); }
				}
			}
		}
	}
	
	for(var key in result_object.result_variables_registry) {
		//check if this has any children which are checked
		var item = result_object.result_variables_registry[key];
		
		checked_children = 0;
		
		for(var child_key in item.children) {
			var child_item = result_variable_registry_get_item_by_id(item.children[child_key].id);
			if(child_item.checked == true) {
				checked_children++;
			}
		}
		
		if(item.children.length > 0 && checked_children == 0 && item.checked == true) {
			var sec_event_obj = { "event_type" : "click", "class_id" : item.dom_id };
			sec_event_obj.item = item;
			result_variable_toggle(sec_event_obj, "off");
			if(checking_debug) { msg("unchecking "+sec_event_obj.item.id+" because has children but they are all unchecked"); }
		}
		else if(item.children.length > 0 && checked_children > 0 && item.checked == false) {
			var sec_event_obj = { "event_type" : "click", "class_id" : item.dom_id };
			sec_event_obj.item = item;
			result_variable_toggle(sec_event_obj, "on");
			if(checking_debug) { msg("checking "+sec_event_obj.item.id+" because has children and some are checked"); }
		}
	}
	
	
	var active_aggregation_type = null;
	for(var key in result_object.result_variable_aggregation_types) {
		if(result_object.result_variable_aggregation_types[key].selected) {
			active_aggregation_type = result_object.result_variable_aggregation_types[key];
		}
	}
	
	var parents_to_consider_for_closet = [];
	
	for(var key in result_object.result_variables_registry) {
		if(active_aggregation_type.id != "parish_level" && result_object.result_variables_registry[key].type == "single_sum_item") {
			if(result_object.result_variables_registry[key].checked == true) {
				result_object.result_variables_registry[key].hiding_in_the_closet = true;
				result_variable_set_status("unchecked", result_object.result_variables_registry[key].id);
				if(checking_debug) { msg("unchecking "+result_object.result_variables_registry[key].id+" because is single_sum_item and change in aggmode to non-parish_level"); }
				
				$("."+result_object.result_variables_registry[key].dom_id).css("color", "#990000");
				
				
				
				
				for(var pkey in result_object.result_variables_registry[key].parents) {
					
					var parent_item = result_variable_registry_get_item_by_id(result_object.result_variables_registry[key].parents[pkey]);
					
					if(typeof(parents_to_consider_for_closet[parent_item.id]) == "undefined") {
						parents_to_consider_for_closet[parent_item.id] = 0;
					}
				
					parents_to_consider_for_closet[parent_item.id]++;
					
				}
				
			}
		}
	}
	
	
	//go through all checked parents checking if they have any checked children (how many chucks can a woodchuck chuck?) 
	for(var key in result_object.result_variables_registry) {
		var item = result_object.result_variables_registry[key];
		if(item.children.length > 0) {
			var checked_children_num = 0;
			var total_children_num = 0;
			for(var ckey in item.children) {
				var child_item = result_variable_registry_get_item_by_id(item.children[ckey].id);
				//msg(child_item.id + ":::" + child_item.checked);
				if(child_item.checked) {
					checked_children_num++;
				}
				
				total_children_num++;
			}
			
			if(checked_children_num == 0 && item.checked) {
				result_variable_set_status("unchecked", item.id);
				if(checking_debug) { msg("unchecking "+item.id+" because of you-know-what"); }
			}
		}
	}
}

function result_variable_set_status(status_change, id) {
	
	var item = result_variable_registry_get_item_by_id(id);
	
	status_change = status_change.toLowerCase();
	
	if(status_change == "disable" || status_change == "disabled") {
		$("."+item.dom_id+"_checkbox").prop("disabled", true);
		item.disabled = true;
		result_module_invoke_all("result_variable_disabled", id);
	}
	else if(status_change == "enable" || status_change == "enabled") {
		$("."+item.dom_id+"_checkbox").prop("disabled", false);
		item.disabled = false;
		result_module_invoke_all("result_variable_enabled", id);
	}
	else if(status_change == "check" || status_change == "checked") {
		$("."+item.dom_id+"_checkbox").prop("checked", true);
		item.checked = true;
		result_module_invoke_all("result_variable_checked", id);
	}
	else if(status_change == "uncheck" || status_change == "unchecked") {
		$("."+item.dom_id+"_checkbox").prop("checked", false);
		item.checked = false;
		result_module_invoke_all("result_variable_unchecked", id);
	}
}

function result_variable_registry_get_item_by_id(item_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	for(var key in result_object.result_variables_registry) {
		if(result_object.result_variables_registry[key].id == item_id) {
			return result_object.result_variables_registry[key];
		}
	}
}

function result_variable_registry_get_item_by_dom_id(item_dom_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	for(var key in result_object.result_variables_registry) {
		if(result_object.result_variables_registry[key].dom_id == item_dom_id) {
			return result_object.result_variables_registry[key];
		}
	}
}

function result_agg_type_change_trigger(id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	//msg("result_agg_type_change_trigger: "+id);
	
	for(var key in result_object.result_variable_aggregation_types) {
		if(result_object.result_variable_aggregation_types[key].id == id) {
			result_object.result_variable_aggregation_types[key].selected = true;
			
			//if this agg type is of type "no_aggregation", then get all the result_variables out of the closet
			if(result_object.result_variable_aggregation_types[key].type == "no_aggregation") {
				for(var rkey in result_object.result_variables_registry) {
					if(result_object.result_variables_registry[rkey].hiding_in_the_closet == true) {
						$("."+result_object.result_variables_registry[rkey].dom_id).css("color", "#000");
						result_variable_set_status("check", result_object.result_variables_registry[rkey].id);
						result_object.result_variables_registry[rkey].hiding_in_the_closet = false;
					}
				}
			}
			
		}
		else {
			result_object.result_variable_aggregation_types[key].selected = false;
		}
	}
	
	result_variable_perform_self_check_evaluation_on_all();
	result_variable_perform_self_enable_evaluation_on_all();
	
	if(system.do_not_initiate_result_load_data == false) {
		result_load_data();
	}
}



//***************************************************************************************************************************************************
/*
   Function: result_render_control

   Description:
     This function renders the selectable content for the result variable area.
	 There are two types possible interactions: checkboxes and radiogroup. (This is defined in client_ui_definition.js in client/theme )

   see also:
   <result_switch_view>
*/
function result_render_control() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}

	$("#result_control").append("<h2 id=\"result_variable_header\">"+t("Visa")+"</h2>");
	
	var result_variable_objs = Array();
	
	for(var key in result_object.result_variables_registry) {
		
		for(var parents_key in result_object.result_variables_registry[key].parents) {
			
			if(result_object.result_variables_registry[key].parents[parents_key] == "ROOT") {
				
				var has_children = false;
				
				if(result_object.result_variables_registry[key].children.length > 0) {
					has_children = true;
				}
				
				var result_variable_obj = result_render_result_variable_item(result_object.result_variables_registry[key], has_children, 0);
				
				for(var child_k_key in result_object.result_variables_registry[key].children) {
					
					var child_obj = result_render_result_variable_item(result_object.result_variables_registry[key].children[child_k_key], false, 1);
					
					$(".result_variable_child_container", result_variable_obj).first().append(child_obj);
				}
				
				result_variable_objs.push(result_variable_obj);
			}
		}
	}
	
	//$.dump(result_object.result_variables_registry);
	
	for(var key in result_variable_objs) {
		$("#result_control").append(result_variable_objs[key]);
	}
	
	
	$(".result_variable_parent").each(function() {
		
		var result_variable_container = this;
		
		$(".result_variable_tree_btn", this).bind("click", function() {
			
			if($(result_variable_container).find(".result_variable_child_container").is(":hidden") == true) {
				$(result_variable_container).find(".result_variable_child_container").show(150);
				$(result_variable_container).find(".result_variable_tree_btn").attr("src", "client/theme/images/tree_btn_open.png");
			}
			else {
				$(result_variable_container).find(".result_variable_child_container").hide(150);
				$(result_variable_container).find(".result_variable_tree_btn").attr("src", "client/theme/images/tree_btn_closed.png");
			}
		});
		
		$(this).find(".result_variable_child_container").hide();
		
	});
	
	
	//render aggregation type controls
	
	for(var key in result_object.result_variable_aggregation_types) {
		var html_obj = result_render_result_variable_aggregation_type_control(result_object.result_variable_aggregation_types[key]);
		
		//$.dump(result_object.result_variable_aggregation_types[key]);
		
		$("#result_control").prepend(html_obj);
	}
	
	$("#result_control").prepend("<div id=\"aggregation_header\"><h2>"+t("Sammanfatta")+"</h2></div>");
	
	/*
	var unique_items_selected = Array();
	var number_of_unique_items_selected = 0;
	for(var key in result_object.result_variables_registry) {
		var current_item = result_object.result_variables_registry[key];
		if(current_item.checked && current_item.children.length == 0 && $.inArray(current_item.id, unique_items_selected) == -1) {
			unique_items_selected.push(current_item.id);
			number_of_unique_items_selected++;
		}
	}
	*/
	
	for(var key in result_object.result_variables_registry) {
		if(result_object.result_variables_registry[key].checked) {
			result_variable_set_status("checked", result_object.result_variables_registry[key].id)
		}
	}
	
	result_variable_perform_self_check_evaluation_on_all();
	result_variable_perform_self_enable_evaluation_on_all();
	
}

function result_render_result_variable_aggregation_type_control(item) {
	
	var html = "";
	
	var radio_selected = "";
	
	if(item['selected']) {
		radio_selected = "checked=\"checked\"";
	}
	else {
		radio_selected = "";
	}
	
	html += "<div><input id=\"result_aggregation_type_"+item['id']+"\" type=\"radio\" name=\"result_variable_aggregation_type\" value=\""+item['id']+"\" "+radio_selected+" /><span class=\"result_variable_aggregation_type_text\">"+t(item['title'])+"</span></div>";
	
	var html_obj = $(html).bind("click", function() {
		result_agg_type_change_trigger(item['id']);
	});
	
	return html_obj;
}

//***************************************************************************************************************************************************
/*
   Function: result_get_selected_list_items

   Description:
	This function gets the selected items from the checkbox list in the result variable area.
	(Returns this when building xml in conjunction to posting request for new result data.)

	Returns:
	selectedItems - array of the selected variables 

*/

function result_get_selected_list_items() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var result_control = $("#result_control");
	var selectedItems = [];
	var counter=0;
	var selected_items = Array();
	$("#result_control").find("input[type=checkbox]").each(function() {
		if($(this).prop("checked") == true && $(this).parent().hasClass("result_variable_parent") == false) {
			
			if($.inArray($(this).attr("name"), selected_items) == -1) {
				//selected_items.push($(this).attr("name"));
//				console.log($(this).attr("name"));
//				console.log($(this).attr("id"));
            var result_code=$(this).attr("name");
            var check_box_dom_id="result_variable_"+result_code+"_trigger";
            //console.log(check_box_dom_id);
//          	console.log($("#result_control").find("."+check_box_dom_id).find(".result_variable_title").text());
            var checkbox=   $("."+check_box_dom_id).find(".result_variable_title").text();
			counter++;
			selected_items[counter]=new Array();
			selected_items[counter]["id"]= result_code;
			selected_items[counter]["title"]=checkbox;
           // console.log(checkbox);
				
			}
		}
	});
	

	
	
	return selected_items;
}
//***************************************************************************************************************************************************
/*
   Function: result_initialize_result_time_bar

   Description: 
   Creates the time bar next to the result map.

   see also:
   <result_render_value_bar>
*/

function result_initialize_result_time_bar() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	$('#result_time_bar_container').show();
	
	//highest_year_value (and lowest) are defined in client_definitions.js
	result_render_value_bar(highest_year_value, lowest_year_value);
}
//***************************************************************************************************************************************************
/*
   Function: result_maximize

   Description:
   Maximizes the result area so it utilizes the entire available browser window area.

   see also:
   <result_render_view>
*/
function result_maximize_toggle() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	if(result_object.maximized == false) {
		result_module_invoke_all("pre_maximize");
		result_maximize();
		$("#result_max_min_button").attr("src", "client/theme/images/button_contract.png");
		$("#result_max_min_button").attr("alt", "client/theme/images/button_contract.png");
		//result_module_invoke_all("post_maximize"); //this will be invoked as a callback at the end of the animation instead
	}
	else {
		//result_module_invoke_all("pre_minimize");
		result_minimize();
		$("#result_max_min_button").attr("src", "client/theme/images/button_expand.png");
		$("#result_max_min_button").attr("alt", "client/theme/images/button_expand.png");

		//result_module_invoke_all("post_minimize"); //this will be invoked as a callback at the end of the animation instead
	}
}

function result_maximize() {
	$("body").append("<div id=\"background_overlay\"></div>");
	var result_workspace = $("#result_workspace");
	result_workspace.css("z-index", "100");
	result_workspace.css("position", "absolute");
	result_workspace.animate({
		"left" : "20px",
		"top" : "20px"
	}, 300);
	
	$(".result_workspace_tab").each(function() {
		var btn = $(this).find("div");
		if(btn.attr("id") != "result_"+result_object.view+"_button") {
			$(this).hide();
		}
	});
	
	
	$("#result_workspace_content_container").animate({
		"width" : $(window).width()-150,
		"height" : $(window).height()-140
	}, 300, "linear", function() {
			result_object.maximized = true;
			result_module_invoke_all("post_maximize");
		});
	
	
}

function result_minimize() {
	$("#background_overlay").remove();
	var result_workspace = $("#result_workspace");
	result_workspace.css("z-index", "0");
	result_workspace.css("position", "static");
	
	//result_workspace.animate({
	//	"left" : result_object.result_workspace_saved_css.left,
	//	"top" : result_object.result_workspace_saved_css.top
	//}, 300);
	
	$(".result_workspace_tab").each(function() {
		var btn = $(this).find("div");
		if(btn.attr("id") != "result_"+result_object.view+"_button") {
			$(this).show();
		}
	});
	
	
	$("#result_workspace_content_container").animate({
		"width" : result_object.result_workspace_saved_css.width,
		"height" : result_object.result_workspace_saved_css.height
	}, 300, "linear", function() {
			result_object.maximized = false;
			result_module_invoke_all("post_minimize");
		});
}

//***************************************************************************************************************************************************
/*
   Function: result_module_invoke_all

   Description:
   This function calls a type of function in all modules containing that specific function. The function to be called is determined by the hook/prefix 
   combined with the postfix. The incoming parammeter contains the postfix and the prefix is picked from the array of result modules available. i.e. 
   if the parameter contains "init" the functions "geo_init, map_init", "list_init" are called.

   Parameters: 
   callback_func - contains the postfix of the function name (Some of the values it can have: init, prepare_for_load_data, stop_loading_data)

   Returns:
   results - contains returned values from the invoked functions. 

*/
function result_module_invoke_all(callback_func, args) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName+" - "+callback_func);		
	}
	
	var results = {};
	
	for(var key in result_object.result_modules) {
		var function_name = "result_"+result_object.result_modules[key].id+"_" + callback_func;
		if(eval("typeof " + function_name + " == 'function'")) {
			results[key] = eval(function_name+"(\""+args+"\");");
		}
	}
	
	return results;
}

function result_module_invoke(module_name, callback_func, args) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName+" - "+module_name+" "+callback_func);		
	}
	
	var function_name = "result_"+module_name+"_" + callback_func;
	
	if(eval("typeof " + function_name + " == 'function'")) {
		result = eval(function_name+"(\""+args+"\");");
	}
	
	return result;
}

