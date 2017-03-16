/*
* File: layout.js
* 
* Contains functions on a higher level which act on groups of facets and slots.
* 
*/

var transaction_layout_slots = Array();
// **********************************************************************************************************************************
/*
* Function: layout_spawn_default_facets
* 
* Description:
* Creates all default facets and renders and attaches them to the document. Called at initial page load.
* 
* 
* 
*/
function layout_spawn_default_facets() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var slot = 0;
	var facet_obj;
	for(var facet_key in facets) {
		
		for(var key in view_state.facet_default_facets) {
			if(facets[facet_key]["id"] == view_state.facet_default_facets[key].id) {
				facet_obj = {
				"id" : facets[facet_key].id,
				"dom_id" : "facet_"+facets[facet_key].id,
				"title" : facets[facet_key].display_title,
				"displayed_in_ui" : true,
				"slot_id" : slot,
				"type" : facets[facet_key].facet_type,
				"color" : facets[facet_key].color
				};
				
				//facet_create_facet(facet_obj);
				slot++;
			}
		}
		/*
		if($.inArray(facets[facet_key]["id"], view_state.facet_default_facets) != -1) {
		
			//facet_create(facets[facet_key].id, facets[facet_key].display_title, Array(), true, slot);
			
			facet_obj = {
			"id" : facets[facet_key].id,
			"dom_id" : "facet_"+facets[facet_key].id,
			"title" : facets[facet_key].display_title,
			"displayed_in_ui" : true,
			"slot_id" : slot,
			"type" : facets[facet_key].facet_type,
			"color" : facets[facet_key].color
			};
			
			facet_create_facet(facet_obj);
			slot++;
		}
		*/
	}
}

// **********************************************************************************************************************************
/*
* Function: layout_preview_move_dragged_facet
* 
* Description:
* Updates the volatile/tmp array of facet and slot objects to contain new facet positions when a facet is being dragged over a slot.
* 
* Parameters:
* dest_slot_id - Destination slot ID.
* drag_facet_id - ID of the facet being dragged.
* 
*/
function layout_preview_move_dragged_facet(dest_slot_id, drag_facet_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	//attach dragged facet to the slot it's hovering over
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(drag_facet_id, true);
	facet_obj.slot_id = dest_slot_id;
	
	//update slot
	var slot_obj = slot_get_slot_by_id(dest_slot_id, true);
	slot_obj.facet_id = drag_facet_id;
}

// **********************************************************************************************************************************
/*
* Function: layout_record_transaction_operation
* 
* Description:
* Records a move of facets to be committed if/when the user drops the facet in another slot than its current home slot.
* 
* Parameters:
* slot_id - The ID of the slot being potentially dropped on.
* facet_id - The ID of the facet being dragged.
* direction - The direction, relative to the starting point, the facet has been dragged.
* 
* See also:
* <layout_transaction_commit>
*/
function layout_record_transaction_operation(slot_id, facet_id, direction) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var slot_obj = slot_get_slot_by_id(slot_id, true);
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id, true);
	
	var move_to_slot = 0;
	if(direction == "up") {
		move_to_slot = slot_get_previous_slot(facet_obj.slot_id).id;
	}
	else if(direction == "down") {
		move_to_slot = slot_get_next_slot(facet_obj.slot_id).id;
	}
	
	//msg("Transaction pair: s/f: "+move_to_slot+" - "+facet_id, false, true);
	transaction_layout_slots[move_to_slot] = facet_id;
}

// **********************************************************************************************************************************
/**
* Function: layout_transaction_commit
* 
* Description:
* Commits the current transaction regarding moving of facets - but does not save the new layout to permanent memory.
* 
* See also:
* <layout_record_transaction_operation>
*/
function layout_transaction_commit() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var slot_obj;
	var facet_obj;
	var key;
	
	for(var slot_id in transaction_layout_slots) {
		var facet_id = transaction_layout_slots[slot_id];
		//var facet_object = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
		
		
		//update slot information
		for(key in slot_objects_tmp) {
			if(slot_objects_tmp[key].id == slot_id) {
				slot_obj = slot_get_slot_by_id(slot_id, true);
				slot_objects_tmp[key].facet_id = facet_id;
				
				//copy over dimensions as well
				//slot_objects_tmp[key].width = facet_object.width;
				//slot_objects_tmp[key].height = facet_object.height;
			}
		}
		
		//update facet information
		for(key in facet_presenter.facet_list.facets_saved_state) {
			if(facet_presenter.facet_list.facets_saved_state[key].id == facet_id) {
				facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id, true);
				facet_presenter.facet_list.facets_saved_state[key].slot_id = parseInt(slot_id, 10);
			}
		}
		
		
		//perform graphical update
		//msg(slot_obj.dom_id, false, true);
		$("#"+facet_obj.dom_id).animate(
		{
		"left" : $("#"+slot_obj.dom_id).position().left,
		"top" : $("#"+slot_obj.dom_id).position().top
		},
		200
		);
	}
	
	transaction_layout_slots = Array();
	
	var slot_obj_tmp;
	for(key in facet_presenter.facet_list.facets_saved_state) {
		/*
		facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(slot_objects_tmp[key].facet_id);
		slot_objects_tmp[key].width = facet_obj.width;
		slot_objects_tmp[key].height = facet_obj.height;
		*/
		slot_obj = slot_get_slot_by_id(facet_presenter.facet_list.facets_saved_state[key].slot_id);
		slot_obj_tmp = slot_get_slot_by_id(facet_presenter.facet_list.facets_saved_state[key].slot_id, true);
		//msg("Setting slot "+slot_obj.id+" to dim of "+facet_presenter.facet_list.facets_saved_state[key].id);
		slot_obj.height = facet_presenter.facet_list.facets_saved_state[key].height;
		slot_obj.width = facet_presenter.facet_list.facets_saved_state[key].width;
		slot_obj_tmp.height = facet_presenter.facet_list.facets_saved_state[key].height;
		slot_obj_tmp.width = facet_presenter.facet_list.facets_saved_state[key].width;
		
		
		
	}
	/*
	for(key in slot_objects) {
		$("#"+slot_objects_tmp[key].dom_id).css("width", slot_objects[key].width+"px");
		$("#"+slot_objects_tmp[key].dom_id).css("height", slot_objects[key].height+"px");
		//$("#"+slot_objects_tmp[key].dom_id).css("width", slot_objects_tmp[key].width+"px");
		//$("#"+slot_objects_tmp[key].dom_id).css("height", slot_objects_tmp[key].height+"px");
	}
	msg("commit");
	facet_refresh_facet_positions();
	*/
}

// **********************************************************************************************************************************
/*
* Function: layout_preview_move_facets
* 
* Description:
* Called when a facet is dragged over a slot other than its home slot. Will figure out how to manipulate the facets to create a preview of the new ordering of facets if the facet should be dropped in that slot.
* 
* Parameters:
* transaction_id - A sequence number to keep track of transactions.
* drop_slot_id - The ID of the slot which the facet may be dropped in.
* drop_facet_id - The ID of the facet which sits in the slot which the dragged facet may be dropped in.
* drag_slot_id - The ID of the current home slot of the dragged facet.
* drag_facet_id - The ID of the facet being dragged.
* 
*/
function layout_preview_move_facets(transaction_id, drop_slot_id, drop_facet_id, drag_slot_id, drag_facet_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	if(TRANSACTION_DEBUG) {
		msg("TRANSACTION "+transaction_id+" START ", false, true);
		msg("Transaction concerns: dragf/drags/dropf/drops:"+drag_facet_id+"/"+drag_slot_id+"/"+drop_facet_id+"/"+drop_slot_id, false, true);
	}
	preview_moving = true;
	
	//get temp/volatile equivalents
	var drop_slot = slot_get_slot_by_id(drop_slot_id, true);
	var drag_slot = slot_get_slot_by_id(drag_slot_id, true);
	
	var drop_facet = facet_presenter.facet_list.facet_get_facet_by_id(drop_facet_id, true);
	var drag_facet = facet_presenter.facet_list.facet_get_facet_by_id(drag_facet_id, true);
	
	drop_slot_id = drop_slot.id;
	drag_slot_id = drag_slot.id;
	
	drop_facet_id = drop_facet.id;
	drag_facet_id = drag_facet.id;
	
	
	//if drop slot is below drag slot, then push all facets up which are higher or equal to the drop slot position
	if(TRANSACTION_DEBUG) {
		msg("Processing facets to be pushed up:", false, true);
	}
	
	var key;
	
	if(drop_slot.chain_number > drag_slot.chain_number) {
		//msg("above", false, true);
		for(key in slot_objects_tmp) {
			if(TRANSACTION_DEBUG) {
				msg("Processing slot "+slot_objects_tmp[key].id, false, true);
			}
			if(slot_objects_tmp[key].chain_number <= drop_slot.chain_number && slot_objects_tmp[key].chain_number > drag_slot.chain_number && slot_objects_tmp[key].facet_id !== false) {
				if(TRANSACTION_DEBUG) {
					msg(" - moving slot/facet: "+slot_objects_tmp[key].id+"/"+slot_objects_tmp[key].facet_id+" up", false, true);
				}
				
				layout_record_transaction_operation(slot_objects_tmp[key].id, slot_objects_tmp[key].facet_id, "up");
				//layout_preview_move_facet_in_slot(slot_objects_tmp[key].id, slot_objects_tmp_copy[key].facet_id, "up");
				//layout_preview_move_dragged_facet(slot_objects_tmp[key].id, drag_facet.id);
			}
			else {
				if(TRANSACTION_DEBUG) {
					msg(" - no action slot/facet: "+slot_objects_tmp[key].id+"/"+slot_objects_tmp[key].facet_id, false, true);
				}
			}
		}
	}
	
	//if drop slot is above drag slot, then push all facets down which are below or equal to the drop slot position
	if(TRANSACTION_DEBUG) {
		msg("Processing facets to be pushed down:", false, true);
	}
	if(drop_slot.chain_number < drag_slot.chain_number) {
		//msg("below", false, true);
		for(key in slot_objects_tmp) {
			if(TRANSACTION_DEBUG) {
				msg("Processing slot: "+slot_objects_tmp[key].id, false, true);
			}
			if(slot_objects_tmp[key].chain_number >= drop_slot.chain_number && slot_objects_tmp[key].chain_number < drag_slot.chain_number && slot_objects_tmp[key].facet_id !== false) {
				if(TRANSACTION_DEBUG) {
					msg(" - moving slot/facet: "+slot_objects_tmp[key].id+"/"+slot_objects_tmp[key].facet_id+" down", false, true);
				}
				
				layout_record_transaction_operation(slot_objects_tmp[key].id, slot_objects_tmp[key].facet_id, "down");
				//layout_preview_move_facet_in_slot(slot_objects_tmp_copy[key].id, slot_objects_tmp_copy[key].facet_id, "down");
				//layout_preview_move_dragged_facet(slot_objects_tmp_copy[key].id, drag_facet.id);
			}
			else {
				if(TRANSACTION_DEBUG) {
					msg(" - no action slot/facet: "+slot_objects_tmp[key].id+"/"+slot_objects_tmp[key].facet_id, false, true);
				}
			}
		}
	}
	
	if(TRANSACTION_DEBUG) {
		msg("setting dragf/drops: "+drag_facet.id+"/"+drop_slot.id, false, true);
	}
	layout_preview_move_dragged_facet(drop_slot.id, drag_facet.id);
	
	if(TRANSACTION_DEBUG) {
		print_slots_and_facets(transaction_id);
	}
	
	preview_moving = false;
	//layout_transaction_commit();
	
	if(TRANSACTION_DEBUG) {
		msg("Ready to commit:", false, true);
		msg("----------------", false, true);
		for(var slot_id in transaction_layout_slots) {
			msg("s/f: "+slot_id+" - "+transaction_layout_slots[slot_id], false, true);
		}
		msg("----------------", false, true);
	}
	
	//msg("Transaction pair: s/f: "+move_to_slot+" - "+facet_id, false, true);
	layout_transaction_commit();
	
	//print_slots_and_facets(transaction_id);
	
	if(TRANSACTION_DEBUG) {
		msg("TRANSACTION "+transaction_id+" END", false, true);
	}
	
}

// **********************************************************************************************************************************
/**
* Function: layout_save
* 
* Description:
* Stores the current layout of facets/slots in permanent memory by copying over the volatile/temp layout to the stable layout.
* Normally triggered when a facet is dropped in a slot (not triggered while it's still being dragged).
* 
* 
*/
function layout_save() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	//first of all, check if there's any change at all to how the previous layout was
	var change_detected = false;
	for(var key in facet_presenter.facet_list.facets) {
		if(typeof(facet_presenter.facet_list.facets_saved_state[key]) == "undefined") {
			change_detected = true;
		}
		else if(facet_presenter.facet_list.facets[key].slot_id != facet_presenter.facet_list.facets_saved_state[key].slot_id) {
			change_detected = true;
		}
	}
	
	if(change_detected == false) {
		return false;
	}
	
	
	slot_objects = slot_objects_tmp;
	facet_presenter.facet_list.facets = facet_presenter.facet_list.facets_saved_state;
	
	var slot_obj;
	for(var key in facet_presenter.facet_list.facets) {
		for(var slot_key in slot_objects) {
			if(facet_presenter.facet_list.facets[key].slot_id == slot_objects[slot_key].id) {
				slot_objects[slot_key].width = facet_presenter.facet_list.facets[key].width;
				slot_objects[slot_key].height = facet_presenter.facet_list.facets[key].height;
				$("#"+slot_objects[slot_key].dom_id).css("height", slot_get_render_height(slot_objects[slot_key].height)+"px");
			}
		}
	}
	
	return true;
}

// **********************************************************************************************************************************
/*
* Function: layout_move_facets_to_layout
* 
* Description:
* Moves (visually) all facets to the positions of their slots according to the permanent/stable layout and reloads the volatile layout from the permanent.
* 
*/
function layout_move_facets_to_layout() {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	//msg("layout_move_facets_to_layout", false, true);
	//$.dump(facet_presenter.facet_list.facets);
	for(var key in facet_presenter.facet_list.facets) {
		
		var facet_dom_obj = $("#"+facet_presenter.facet_list.facets[key].dom_id);
		var slot_obj = slot_get_slot_by_id(facet_presenter.facet_list.facets[key].slot_id);
		var slot_dom_obj = $("#"+slot_obj.dom_id);
		
		facet_dom_obj.animate(
		{
		"top" : slot_dom_obj.position().top,
		"left" : slot_dom_obj.position().left
		},
		200
		);
	}
	
	slot_reload_slot_objects_tmp();
	facet_reload_facet_objects_tmp();
}

