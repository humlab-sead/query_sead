/*
* File: slot.js
* 
* Contains functions for everything to do with slots.
* 
*/

/*
* Function: slot_get_facet_id
* 
* Gets the system ID of the facet attached to this slot.
* 
* Parameters:
* slot_id - The system ID of the slot.
* volatile - Whether to operate on the volatile layout or not.
* 
* Returns:
* The system ID of the facet attached to this slot.
* 
* See also:
* 
*/
function slot_get_facet_id(slot_id, volatile) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	//slot_id is supposed to be an ID according to the API, but just in case we were handed a slot object instead...
	slot_id = typeof(slot_id) == "object" ? slot_id.attr("id") : slot_id;
	
	var slot_objects_array;
	if(volatile) {
		slot_objects_array = slot_objects_tmp;
	}
	else {
		slot_objects_array = slot_objects;
	}
	
	for(var key in slot_objects_array) {
		if(slot_objects_array[key].id == slot_id) {
			return slot_objects_array[key].facet_id;
		}
	}
}

/*
* Function: slot_get_dom_id
* 
* Gets the DOM ID of a slot given the sys ID.
* 
* Parameters:
* sys_id - The sys ID of the slot.
* 
* 
* Returns:
* The DOM ID of the slot.
* 
* See also:
* <slot_get_sys_id>
* <facet_get_dom_id>
*/
function slot_get_dom_id(sys_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	//FIXME: kinda ugly, don't ya think?
	return "facet_slot_"+sys_id;
}

/*
Function: slot_render_facet_slot

Renders the HTML for a facet slot and attaches the new DOM-object to the facet workspace area.

Parameters:
facet_slot - An associative array containing the keys "top", "left", "width", "height". Which will define the slots position and size.

See also:
<layout_create_facet_slots>
<layout_handle_moving_facet>
*/
function slot_render_facet_slot(facet_slot) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	//msg("render facet slot:"+facet_slot["id"]);
	//the basic HTMl of the slot-object
	//msg(facet_slot.dom_id);
	
	var render_height = slot_get_render_height(facet_slot.height);
	
	var html = "";
	html += "<div id=\""+facet_slot.dom_id+"\" class=\"facet_slot resizable\" style=\"margin-top:"+facet_slot_margin_top+";margin-bottom:"+facet_slot_margin_bottom+";margin-left:"+facet_slot_margin_left+";margin-right:"+facet_slot_margin_right+";width:"+facet_slot.width+"px; height:"+render_height+"px;\">";
	
	//html += "<div style=\"background-color:red;width:100%;height:100%;\"></div>";
	
	html += "</div>";
	
	//creating a dom-object of the html
	var facet_slot_dom_obj = $(html);
	
	//adding the slot object to the #facet_workspace area in the document DOM
	$("#facet_workspace").append(facet_slot_dom_obj);
	
}

function slot_get_render_height(height) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var slot_render_height = height;
	return slot_render_height;
}
/*
Function: slot_copy_slot_object

Description: Copies all slots into an array. Is used to keep track of positions when facets are moves around with drag and drop.

*/
function slot_copy_slot_object(slot_object) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var copy = [];
	for(var key in slot_object) {
		copy[key] = slot_object[key];
	}
	return copy;
}

/*
Function: slot_create_facet_slots

Description: Creates all the facet slots as defined in <client_ui_definitions.js>.

See also:
<layout_render_facet_slot>
*/
function slot_create_facet_slots() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	for(var i = 0; i < facet_default_slots_num; i++) {
		slot = {
			"id" : i,
			"dom_id" : "facet_slot_"+i,
			"facet_id" : false,
			"width" : facet_default_width,
			"height" : facet_default_height,
			"chain_number" : i
		};
		
		slot_create_slot(slot);
	}
}
/*
Function: slot_create_slots

Description: Creates the slot that will hold a facet.

See also:
<slot_copy_slot_object>

<slot_render_facet_slot>

<slot_init_droppable>
*/
function slot_create_slot(slot_obj) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var slot_last_index = -1;
	for(var key in slot_objects) {
		slot_last_index = key;
	}
	
	var slot_index = parseInt(slot_last_index, 10) + 1;
	
	slot_objects[slot_index] = slot_obj;
	slot_objects_tmp[slot_index] = slot_copy_slot_object(slot_obj);
	
	slot_render_facet_slot(slot_obj);
	
	slot_init_droppable(slot_obj);
	
	return slot_index;
}

/*
Function: slot_get_sys_id

Description: Converts a dom-id to a sys-id.

*/

function slot_get_sys_id(dom_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	//var slot_id_parts = dom_id.split("_");
	//var slot_id = slot_id_parts[2];
	
	for(var key in slot_objects) {
		if(slot_objects[key].dom_id == dom_id) {
			return slot_objects[key].id;
		}
	}
	
	//return slot_id;
}
/*
Function: slot_action_callback

Description: 

See also:
<layout_preview_move_facets>
*/
function slot_action_callback(ev, ui, drop_slot, type) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	//msg("event: "+type, false, true);
	var drop_facet_value = null;
	
	var slot_sys_id = slot_get_sys_id($(drop_slot).attr("id"));
	for(var slot_key in slot_objects_tmp) {
		if(slot_objects_tmp[slot_key].id == slot_sys_id) {
			drop_facet_value = slot_objects_tmp[slot_key].facet_id;
		}
	}
	
	drop_slot = slot_get_sys_id($(drop_slot).attr("id"));
	var drop_facet = drop_facet_value; //this may be empty - if the destination slot doesn't hold a facet
	
	var drag_facet = facet_get_sys_id($(ui.draggable).attr("id"));
	
	var drag_slot = null;
	for(var facet_key in facet_presenter.facet_list.facets_saved_state) {
		if(facet_presenter.facet_list.facets_saved_state[facet_key].id == drag_facet) {
			drag_slot = facet_presenter.facet_list.facets_saved_state[facet_key].slot_id;
		}
	}
	/*
	msg(type+"Drop slot: "+drop_slot, true, true);
	msg(type+"Drop facet: "+drop_facet, false, true);
	msg(type+"Drag slot: "+drag_slot, false, true);
	msg(type+"Drag facet: "+drag_facet, false, true);
	*/
	if(type == "drop") {
		//layout_move_facets(drop_slot, drop_facet, drag_slot, drag_facet);
	}
	else if(type == "over") {
		//msg("Calling layout_preview_move_facets", false, true);
		layout_preview_move_facets(transaction_id, drop_slot, drop_facet, drag_slot, drag_facet);
		transaction_id++;
	}
	else if(type == "out") {
		//msg("deactivate on slot drags/drops/dragf/dropf: "+drag_slot+"/"+drop_slot+"/"+drag_facet+"/"+drop_facet, false, true);
		/*
		reload_facet_layout_from_perm_memory = true;
		var drop_facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(drop_facet);
		$("#"+drop_facet_obj.dom_id).find(".facet_item_list_container").html(out_seq);
		out_seq++;
		
		layout_revert_preview_move_facets(transaction_id, drop_slot, drop_facet, drag_slot, drag_facet);
		transaction_id++;
		*/
		//layout_move_facets_to_layout();
	}
	
	return Array(drop_slot, drop_facet, drag_slot, drag_facet);
	
	//return;
}

/*
Function: slot_init_droppable_all

Description: Setting upp the droppable functionality on the slots, which enables things (facets) being dropped in this area. This is done separately from the init of slots since slots should only be droppable when they contain a facet.

See also:
<slot_action_callback>
*/

function slot_init_droppable_all() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	for(var key in slot_objects) {
		var slot = $("#"+slot_objects[key].dom_id);
		slot.droppable( {
		accept: '.facet_container',
		activeClass: 'droppable-active',
		hoverClass: 'droppable-hover',
		tolerance: "pointer",
		drop: function(ev, ui) {
			//msg("slot event: drop", false, true);
			var drop_slot = this;
			slot_action_callback(ev, ui, drop_slot, "drop");
		},
		over: function(ev, ui) {
			//msg("slot event: over", false, true);
			var drop_slot = this;
			slot_action_callback(ev, ui, drop_slot, "over");
			//alert("over "+this.id);
		},
		deactivate: function(ev, ui) {
			//msg("slot event: deactivate", false, true);
			var drop_slot = this;
			slot_action_callback(ev, ui, drop_slot, "out");
		}
		});
	}
}

/*
Function: slot_init_droppable

Description: 

See also:
<slot_action_callback>
*/
function slot_init_droppable(slot_obj) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var slot = $("#"+slot_obj.dom_id);
	slot.droppable( {
	accept: '.facet_container',
	activeClass: 'droppable-active',
	hoverClass: 'droppable-hover',
	tolerance: "pointer",
	drop: function(ev, ui) {
		//msg("slot event: drop", false, true);
		var drop_slot = this;
		slot_action_callback(ev, ui, drop_slot, "drop");
	},
	over: function(ev, ui) {
		//msg("slot event: over", false, true);
		var drop_slot = this;
		slot_action_callback(ev, ui, drop_slot, "over");
		//alert("over "+this.id);
	},
	deactivate: function(ev, ui) {
		//msg("slot event: deactivate", false, true);
		var drop_slot = this;
		slot_action_callback(ev, ui, drop_slot, "out");
	}
	});

}


function slot_get_slot_by_id(slot_sys_id, volatile) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var slot_objects_array;
	if(volatile) {
		slot_objects_array = slot_objects_tmp;
	}
	else {
		slot_objects_array = slot_objects;
	}
	
	for(var key in slot_objects_array) {
		if(slot_objects_array[key].id == slot_sys_id) {
			return slot_objects_array[key];
		}
	}
	return false;
}

function slot_print_slot_object(slot_obj) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var out = "";
	
	out += "<table><tbody><tr><td style=\"vertical-align:top;font-size:10px;\">";
	out += "id:"+slot_obj.id+"<br />";
	out += "facet_id:"+slot_obj.facet_id+"<br />";
	out += "</td></tr></tbody></table>";
	
	return out;
}

function slot_reload_slot_objects_tmp() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(ownName);
	}
	for(var key in slot_objects) {
		var slot_copy = slot_copy_slot_object(slot_objects[key]);
		slot_objects_tmp[key] = slot_copy;
	}
}

function slot_destroy(slot_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var slot_obj = slot_get_slot_by_id(slot_id);
	
	$("#"+slot_obj.dom_id).remove();
	
	for(var key in slot_objects) {
		if(slot_objects[key].id == slot_id) {
			delete slot_objects[key];
		}
	}
	
}

function slot_reload_slot_objects_tmp() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	delete slot_objects_tmp;
	slot_objects_tmp = [];
	
	for(var key in slot_objects) {
		var slot_copy = slot_copy_slot_object(slot_objects[key]);
		slot_objects_tmp[key] = slot_copy;
	}
}

function slot_reset_slot_id_seq() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var new_slot_objects = [];
	var facet_obj;
	var i = 0;
	for(var key in slot_objects) {
		slot_dom_obj = $("#"+slot_objects[key].dom_id);
		facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(slot_objects[key].facet_id);
		facet_obj.slot_id = i;
		slot_objects[key].id = i;
		slot_objects[key].dom_id = "facet_slot_"+i;
		new_slot_object = slot_copy_slot_object(slot_objects[key]);
		
		$(slot_dom_obj).css("id", slot_objects[key].dom_id);
		
		delete slot_objects[key];
		slot_objects[i] = new_slot_object;
		
		i++;
	}
}

function slot_get_next_slot(from_slot_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var get_next_one = false;
	for(var key in slot_objects) {
		if(get_next_one) {
			return slot_objects[key];
		}
		if(slot_objects[key].id == from_slot_id) {
			get_next_one = true;
		}
	}
}

function slot_get_previous_slot(from_slot_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var slot_last_key;
	for(var key in slot_objects) {
		if(slot_objects[key].id == from_slot_id) {
			return slot_objects[slot_last_key];
		}
		slot_last_key = key;
	}
}

function slot_get_next_seq_id() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var slot_highest_key = -1;
	for(var key in slot_objects) {
		if(slot_objects[key].id > slot_highest_key) {
			slot_highest_key = slot_objects[key].id
		}
	}
	var slot_next_key = slot_highest_key + 1;
	
	return slot_next_key;
}
