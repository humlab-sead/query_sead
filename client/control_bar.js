/*
* File: control_bar.js
* 
* Contains functions regarding the facet add/remove-panel.
* 
*/

var facet_control_registry = [];
/*
function: facet_control_init
Populates the controlbar (facets)
see also <facet_control_populate_registry>

and 
<facet_control_render_control>


*/
function facet_control_init() {
	
	facet_control_populate_registry();
	
//	result_object.facet_control_orientation='vertical';
	facet_control_render_control(result_object.facet_control_orientation);
	
}
/*
Function: facet_control_populate_registry
Populate the facet_control registry from the facet list which is rendered in <js_facet_def.php>
*/
function facet_control_populate_registry() {
	
	facet_control_registry = [];
	
	for(var key in facets) {
		
		var children = Array();
		for(var child in facets) {
			if($.inArray(facets[key].id, facets[child].parents) > -1) {
				children.push({
					"id" : facets[child].id
				});
			}
		}
		
		facet_control_registry.push({
			"id" : facets[key].id,
			"dom_id" : "facet_control_item__"+facets[key].id,
			"title" : facets[key].display_title,
			"type" : facets[key].facet_type,
			"use_text_search" : facets[key].use_text_search,
                        "counting_title":facets[key].counting_title,


			"default" : false, //this should be: facets[key].default - but IE doesn't like the use of the keyword 'default' here, so we need to name this property/member something else!
			"checked" : false, //this should be: facets[key].default - but IE doesn't like the use of the keyword 'default' here, so we need to name this property/member something else!
			"parents" : facets[key].parents,
			"children" : children
		});
	}
	
	//$.dump(facet_control_registry);
}


// **********************************************************************************************************************************
/*
   Function: control_bar_click_callback

   Description:

   Parameters: 
	facet_array -  a facet object 

	button - 

   see also:
   <facet_presenter.facet_list.facet_get_facet_by_id>

   <facet_remove_facet>

   <slot_get_next_seq_id>

   <facet_create_facet>
*/
function facet_control_click_callback(facet_array) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_array.id);
	
	var facet_active = false;
	var key;
	for(key in facet_presenter.facet_list.facets) {
		if(facet_presenter.facet_list.facets[key].id == facet_array.id) {
			facet_active = true;
		}
	}
	
	if(facet_active) {
		//facet is active - so remove it
		facet_remove_facet(facet_array.id);
	}
	else {
		//facet is not active - create/add it
		var new_slot_id = slot_get_next_seq_id();
		
		//create facet
		//setting up the facet object
		facet_obj = {
			"id" : facet_array.id,
			"dom_id" : "facet_"+facet_array.id,
			"title" : facet_array.display_title,
			"contents" : [],
			"displayed_in_ui" : true,
			"slot_id" : new_slot_id,
			"width" : facet_default_width,
			"height" : facet_default_height,
			"top" : 0,
			"left" : 0,
			"facet_range_max_value": facet_array.max,
			"facet_range_min_value": facet_array.min,
			"selections" : [],
			"type" : facet_array.facet_type,
			"use_text_search": facet_array.use_text_search,
                        "counting_title":facet_array.counting_title,
			"total_number_of_rows" : 0,
			"color" : facet_array.color
		};
		
//	facet_obj.selections.end = facet_array.max_value;
//	facet_obj.selections.start = facet_array.min_value;

		facet_obj.selections.end = facet_array.max;
		facet_obj.selections.start = facet_array.min;

		facet_create_facet(facet_obj);
		facet_reload_facet_objects_tmp();
		//$(button).addClass("facet_control_bar_button_clicked");
	}
}
// **********************************************************************************************************************************
/*
   Function: control_bar_render_facet_button

   Description:

   Parameters: 
   facet_array - 

   Returns:
   facet_button_obj - 

   see also:
   <control_bar_click_callback>
*/
function facet_control_render_facet_button(facet_array) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var out = "<table style=\"border-collapse:collapse;\"><tbody><tr>";

	out += "<td style=\"vertical-align:top;\"><img style=\"height:12px;width:12px;\" src=\"applications/"+application_name+"/theme/images/button_close.png\" /></td>"; 
	out += "<td>&nbsp;<div id=\"facet_"+facet_array.id+"_bar_button\" class=\"facet_control_bar_button\">";
	out += facet_array.name;
	out += "</div></td>";
	
	out += "</tr></tbody></table>";
	
	var facet_button_obj = $(out);
	
	if(facet_array['default'] == 1) {
		$(".facet_control_bar_button", facet_button_obj).addClass("facet_control_bar_button_clicked");
	}
	
	facet_button_obj.bind("click", function() {
		facet_control_click_callback(facet_array, this);
	});
	
	return facet_button_obj;
}


/*
   Function: control_bar_populate

   Description:

   see also:
   <control_bar_render_facet_button>
*/
function facet_control_populate_OLD() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	for(var facet_key in facets) {
		var facet_button_obj = facet_control_render_facet_button(facets[facet_key]);
		$("#facet_control").find(".content_container").append(facet_button_obj);
	}
	
}



function facet_control_render_control(facet_control_orientation) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	$("#facet_control").html("");

	if (facet_control_orientation=="vertical")
	{
		var table_obj=$("<Table></Table>");
		var row_obj=$("<TR></TR>");
		$(table_obj).append(row_obj);
		$("#facet_control").append(table_obj);
	}
	else
	{
		$("#facet_control").append("<h2>"+t("Sök")+"</h2>");
	}

	var facet_control_items = [];
	
	for(var key in facet_control_registry) {
		
		for(var parents_key in facet_control_registry[key].parents) {
			
			if(facet_control_registry[key].parents[parents_key] == "ROOT") {
				
				var has_children = false;
				if(facet_control_registry[key].children.length > 0) {
					has_children = true;
				}
				
				var facet_control_obj = facet_control_render_item(facet_control_registry[key], has_children, 0);
				
				if (facet_control_orientation=="vertical")
				{
					var cell_html_obj = $("<TD></TD>");
					$(cell_html_obj).css('vertical-align','top');
					$(cell_html_obj).append(facet_control_obj);
					$(row_obj).append(cell_html_obj);
				}
				else
				{
					$("#facet_control").append(facet_control_obj);
				}

				

				
			}
			else {
				
				var facet_control_obj = facet_control_render_item(facet_control_registry[key], false, 1);
				
				var parent_item = facet_control_registry_get_item_by_id(facet_control_registry[key].parents[parents_key]);
				
				$("#facet_control").find("."+parent_item.dom_id).find(".facet_control_child_container").first().append(facet_control_obj);
			}
		}

	
		facet_control_items.push(facet_control_obj);
		//msg(facet_control_registry[key].id+" : "+facet_control_registry[key].checked);
		if(facet_control_registry[key].checked == 1) {
			
			//facet_control_toggle(facet_control_registry[key].id, "on");
			$("."+facet_control_registry[key].dom_id).find(".facet_control_title").removeClass("facet_control_deselected_item");
			$("."+facet_control_registry[key].dom_id).find(".facet_control_title").addClass("facet_control_selected_item");
		}
		else {
			//facet_control_toggle(facet_control_registry[key].id, "off");
			$("."+facet_control_registry[key].dom_id).find(".facet_control_title").removeClass("facet_control_selected_item");
			$("."+facet_control_registry[key].dom_id).find(".facet_control_title").addClass("facet_control_deselected_item");
		}
	}
	
	$(".facet_control_item_parent").each(function() {
		
		var facet_control_container = this;
		
		$(".facet_control_item_tree_btn", this).bind("click", function() {
			
			if($(facet_control_container).find(".facet_control_child_container").is(":hidden") == true) {
				$(facet_control_container).find(".facet_control_child_container").show(150);
				$(facet_control_container).find(".facet_control_item_tree_btn").attr("src", "applications/"+application_name+"/theme/images/tree_btn_open.png");
			}
			else {
				$(facet_control_container).find(".facet_control_child_container").hide(150);
				$(facet_control_container).find(".facet_control_item_tree_btn").attr("src", "applications/"+application_name+"/theme/images/tree_btn_closed.png");
			}
		});
		if (facet_control_orientation=='vertical')
		{
			$(this).find(".facet_control_child_container").show();
		}
		else
			$(this).find(".facet_control_child_container").hide();

		
	});
	
	
	facet_control_perform_self_check_evaluation_on_all();
	
}


function facet_control_perform_self_check_evaluation_on_all(event_obj) {
	
	var checking_debug = false;
	
	var checked_children = 0;
	
	//$.dump(facet_control_registry);
	
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
					if(checking_debug) { msg("checking "+item.id+" because "); }
				}
				else {
					result_variable_toggle(child_event_obj, "off");
				}
			}
		}
	}
}


function facet_control_toggle(item_id, state) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var facet_control_item = facet_control_registry_get_item_by_id(item_id);
	
	
	if(typeof(state) != "undefined") {
		if(state.toLowerCase() == "on") {
			facet_control_item_set_status(item_id, "checked");
		}
		else if(state.toLowerCase() == "off") {
			facet_control_item_set_status(item_id, "unchecked");
		}
	}
	else {
		//check/uncheck self
		if(facet_control_item.checked == 1) {
			facet_control_item_set_status(item_id, "unchecked");
		}
		else {
			facet_control_item_set_status(item_id, "checked");
		}
	}
}


function facet_control_item_set_status(item_id, state, visual_only) {
	
	state = state.toLowerCase();
	
	var item = facet_control_registry_get_item_by_id(item_id);
	
	
	if(state == "check" || state == "checked") {
		$("."+item.dom_id).find(".facet_control_title").removeClass("facet_control_deselected_item");
		$("."+item.dom_id).find(".facet_control_title").addClass("facet_control_selected_item");
		item.checked = 1;
	}
	else if(state == "uncheck" || state == "unchecked") {
		$("."+item.dom_id).find(".facet_control_title").removeClass("facet_control_selected_item");
		$("."+item.dom_id).find(".facet_control_title").addClass("facet_control_deselected_item");
		item.checked = 0;
	}
	
	if(visual_only != true) {
		var facet_data = "";
		for(var key in facets) {
			if(facets[key].id == item_id) {
				facet_data = facets[key];
			}
		}
		
		
		var facet_array = {
			"id" : facet_data.id,
			"display_title" : facet_data.display_title,
			"max" : facet_data.max,
			"min" : facet_data.min,
			"facet_type" : facet_data.facet_type,
			"use_text_search":facet_data.use_text_search,
                        "counting_title":facet_data.counting_title,
			"color" : facet_data.color
		};
		
		facet_control_click_callback(facet_array);
	}
}


function facet_control_render_item(item, has_children, level) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var trigger_class="";
	var classes = item.dom_id+" facet_control_item";
	var parent_icon_html = "";
	if(has_children) {
		trigger_class=" parent_trigger_area ";
		classes += " facet_control_item_parent";
		parent_icon_html += "<img class=\"facet_control_item_tree_btn\" src=\"applications/sead/theme/images/tree_btn_closed.png\" />&nbsp;";
	}
	else {
		trigger_class=" child_trigger_area ";
		classes += " facet_control_item_child";
		parent_icon_html += "<img src=\"applications/"+application_name+"/theme/images/tree_btn_placeholder.png\" />&nbsp;";
		if(level == 1) {
		parent_icon_html += "<img src=\"applications/"+application_name+"/theme/images/tree_btn_placeholder.png\" />&nbsp;";
		}
	}
	
	var title_cut = false;
	var item_modified_name = "";
	var item_title_translated = t(item.title);
	
	if(item_title_translated.length > 25) {
		item_modified_name = cut_string_to_length(item_title_translated, 25);
		title_cut = true;
	}
	else {
		item_modified_name = item_title_translated;
	}
	

	var html = "<div class=\""+classes+"\">"+parent_icon_html+"<span class=\""+item.dom_id+"_trigger checkbox_trigger_area "+trigger_class+"\"><span class=\"facet_control_title\">"+item_modified_name+"</span></span><div class=\"facet_control_child_container\"></div></div>";
	
	var html_obj = $(html);
	

	if (has_children)
	{
		facet_add_tooltip(t(item.title)+", "+t("klicka för att expandera/minimera ")+"  " , $(".facet_control_title", html_obj));
	}
	else
	{
		facet_add_tooltip(t(item.title)+",  "+t("klicka för att lägga till/ta bort"), $(".facet_control_title", html_obj));
	}
	
	
	if(item.checked == 1) {
		$(".facet_control_title", html_obj).addClass("facet_control_selected_item");
	}
	else {
		$(".facet_control_title", html_obj).addClass("facet_control_deselected_item");
	}
	
	if(has_children) {
		$(".checkbox_trigger_area", html_obj).bind("click", function() {
			//msg("trigger 1");
			var classes = $(html_obj).attr("class").split(" ");
			var item = facet_control_registry_get_item_by_dom_id(classes[0]);
			
			if($(".facet_control_child_container", "."+item.dom_id).is(":hidden") == true) {
				$(".facet_control_child_container", "."+item.dom_id).show(150);
				$(".facet_control_item_tree_btn", "."+item.dom_id).attr("src", "applications/"+application_name+"/theme/images/tree_btn_open.png");
			}
			else {
				$(".facet_control_child_container", "."+item.dom_id).hide(150);
				$(".facet_control_item_tree_btn", "."+item.dom_id).attr("src", "applications/"+application_name+"/theme/images/tree_btn_closed.png");	
			}
			
		});
	}
	else {
		$(".checkbox_trigger_area", html_obj).bind("click", function() {
			//msg("trigger 2");
			var classes = $(html_obj).attr("class").split(" ");
			var item = facet_control_registry_get_item_by_dom_id(classes[0]);
			
			facet_control_toggle(item.id);
			
			facet_control_perform_self_check_evaluation_on_all({"item": item});
		});
	}
	
	return html_obj;
}

function facet_control_registry_get_item_by_id(id) {
	
	for(var key in facet_control_registry) {
		if(facet_control_registry[key].id == id) {
			return facet_control_registry[key];
		}
	}
	
	return false;
}

function facet_control_registry_get_item_by_dom_id(dom_id) {
	
	for(var key in facet_control_registry) {
		if(facet_control_registry[key].dom_id == dom_id) {
			return facet_control_registry[key];
		}
	}
	
	return false;
}
