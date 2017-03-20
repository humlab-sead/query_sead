/*
* File: facet.discrete.js
* 
* Contains functions specific of the discrete type of facet.
* 
*/


/*
* Function: facet_render_dead_space_units
* 
* Helper function for rendering the content of facets of the discrete type. Returns an HTML table row containing the specified number of 'dead space' units. These dead space units are the units which are rendered outside the view port of the facet to give the scrollbar the appropriate behaviour, and which are replaced dynamically with real content when the user scrolls in the facet.
* 
* Parameters:
* dead_space_units - The number of units (rows) to render. Basically determines the height of the HTML table row returned.
* 
* Returns:
* An HTML table row in the form of a jQuery object.
* 
* See also:
* <facet_render_data_discrete>
*/

function facet_render_dead_space_units(_dead_space_units) {
    trace_call_arguments(arguments);
    return $("<tr><td><div style=\"height:"+(_dead_space_units*facet_item_row_height)+"px;\"><span>&nbsp;</span></div></td></tr>");
}

/*
* Function: facet_render_data_discrete
* 
* Renders the content of a discrete facet.
* 
* Parameters:
* facet_obj - The facet object to render in.
* xml - The data received from the server to render.
* 
* See also:
* <facet_render_data_map>
* <facet_render_data_range>
*/
function facet_discrete_render_data(facet_obj, xml) {

        trace_call_arguments(arguments);
	
	if(facet_obj.rendering_data == false) {
		facet_obj.rendering_data = true;
	}
	else {
		msg("Other rendering... aborting");
		return;
	}
	
        //$("#status_area").find(".content_container").html($(xml).find("report").text());
	
	var request_id = $(xml).find("request_id").text();
	
	//don't bother rendering this request if it's older than another already rendered request - but always render requests with an id of -1 since they're exempted from this ordering
	if(parseInt(request_id, 10) != -1 && parseInt(facet_obj.last_rendered_request, 10) > parseInt(request_id, 10)) {
		//msg("dropped request "+request_id);
		facet_obj.rendering_data = false;
		return false;
	}
	else {
		//msg("render request "+request_id);
	}
	facet_obj.last_rendered_request = request_id;
	
	var facet_obj_tmp = facet_presenter.facet_list.facet_get_facet_by_id(facet_obj.id, true);
	var row_id = 0;
	var row_name;
	var direct_counts;
	var row_obj;
	var start_row = $(xml).find("start_row").text();
	var rows_num = $(xml).find("rows_num").text();
	
	if(facet_obj.row_position == null && facet_obj.text_search_mode == true) {
		facet_obj.row_position = parseInt(start_row);
		msg("setting text search mode position to "+facet_obj.row_position);
	}
	
	var total_number_of_rows = $(xml).find("total_number_of_rows").text();
	facet_obj_tmp.total_number_of_rows = total_number_of_rows;
	facet_obj.total_number_of_rows = total_number_of_rows;
	facet_obj.loaded_rows = 0;
	
	delete facet_obj_tmp.contents;
	facet_obj_tmp.contents = [];
	
	//clear all rows
	facet_discrete_reset_contents(facet_obj_tmp);
	
	
	row_id = start_row;
	$(xml).find("row").each(function() {
		
		if(typeof(facet_obj_tmp.contents[row_id]) == "undefined" || facet_obj_tmp.contents[row_id]['data_loaded'] === false) {
			row_obj = $(this);
			row_value = row_obj.find("value").text();
			row_name = row_obj.find("name").text();
			direct_counts = row_obj.find("direct_counts").text();
			
			facet_obj_tmp.contents[row_id] = [];
			facet_obj_tmp.contents[row_id]['selected'] = false;
			facet_obj_tmp.contents[row_id]['data_loaded'] = true;
			facet_obj_tmp.contents[row_id]['id'] = row_id;
			facet_obj_tmp.contents[row_id]['value'] = row_value;
			facet_obj_tmp.contents[row_id]['dom_id'] = facet_obj.id+"_row_"+row_id;
			facet_obj_tmp.contents[row_id]['name'] = row_name;
			facet_obj_tmp.contents[row_id]['direct_counts'] = direct_counts;
			//msg("recording data for row "+row_id);
		}
		row_id++;
	});
	
	//load selections from facet object in rows array
	var sel_key;
	for(sel_key in facet_obj.selections) {
        	for(var row_key in facet_obj_tmp.contents) {
			if(facet_obj.selections[sel_key] == facet_obj_tmp.contents[row_key].value) {
				facet_obj_tmp.contents[row_key].selected = true;
                                break;
			}
		}
	}
	
	facet_obj.contents = facet_obj_tmp.contents;
	
	///FIXME: maybe this should be in facet_render_item_list instead
	var content_container = $("#"+facet_obj.dom_id).find(".facet_content_container_table > tbody");
	content_container.html("");
	var row_dom_obj;
	var key;
	var dead_space_units = 0;
	var table_dom_obj = $("<tbody/>");
        var row_height = "height:" + facet_item_row_height + "px;";
        var number_of_observation_text=t("Antal observationer");
	for(key in facet_obj.contents) {
		var currrent_facet_content=facet_obj.contents[key];
		if(currrent_facet_content.direct_counts == "") {
			currrent_facet_content.direct_counts = 0;
		}
		
		if(currrent_facet_content.data_loaded) {
			//msg("data row "+currrent_facet_content.id);
			
			if(dead_space_units > 0) {
				table_dom_obj.append(facet_render_dead_space_units(dead_space_units));
				dead_space_units = 0;
			}
			var tooltip_text="";
                        row_dom_obj = $("<tr id=\""+currrent_facet_content.dom_id+"\" class=\"facet_discrete_row_tr\" style=\"height:"+facet_item_row_height+"px;\"><td class=\"facet_discrete_row_text\">"+currrent_facet_content.name+"</td><td class=\"facet_discrete_row_counts\"> ("+currrent_facet_content.direct_counts+")</td></tr>");
			if(currrent_facet_content.name.length > 20 && $("#row_dom_obj").find(".facet_discrete_row_text").width()>facet_discrete_default_width) {
				 tooltip_text=currrent_facet_content.name;
				currrent_facet_content.name = currrent_facet_content.name.substr(0, 20)+"...";

			}
			
			
                        
			//row_dom_obj = $("<tr/>", { id: currrent_facet_content.dom_id, class: "facet_discrete_row_tr", style: row_height })
                         //   .append("<td/>", { class: "facet_discrete_row_text", text: currrent_facet_content.name })
                          //  .append("<td/>", { class: "facet_discrete_row_counts", text: "("+currrent_facet_content.direct_counts+")"});

			if (tooltip_text!="" && $("#row_dom_obj").find(".facet_discrete_row_text").width()>facet_discrete_default_width)
			{
				var context=$(row_dom_obj).find(".facet_discrete_row_text");
				facet_add_tooltip(tooltip_text,context);
			}

			var context=$(row_dom_obj).find(".facet_discrete_row_counts");
			facet_add_tooltip(t(facet_obj.counting_title),context);
                        

			row_dom_obj.bind("click", function() {
				facet_row_clicked_callback(facet_obj.id, $(this).attr("id"));
			});
			
			if(currrent_facet_content.selected === true) {
				row_dom_obj.addClass("facet_item_row_selected");
			}
			table_dom_obj.append(row_dom_obj);
		}
		else {
			dead_space_units++;
		}
		
	}
	
        content_container.append(table_dom_obj.children());
	
	if(dead_space_units > 0) {
		content_container.append(facet_render_dead_space_units(dead_space_units));
		dead_space_units = 0;
	}
	
	//if this is a dataset for a text search request, jump the facet scroll to the given row number
	
	
	if($(xml).find("action_type").text() == "populate_text_search") {
		result_object.disable_next_scroll_event = true;
		facet_discrete_scroll_to_row(facet_obj.id, parseInt($(xml).find("scroll_to_row").text()));
	}
	else {
		//this line is needed for chrome - and doesn't matter for other browsers

		facet_discrete_scroll_to_row(facet_obj.id, facet_obj.view_port.start_row);

		var init_facet=	facet_obj.last_request.facet_cause ;
		if (facet_obj.id==init_facet)
		{
			if (facet_obj.last_request.action_type== "selection_change")
			{
				
				var facet_dom_obj = $("#"+facet_obj.dom_id);
				// if the requesst facet is the same as facet_init then jump to position which each should, the happens when selecting/deselecting row.
				$("#facet_"+facet_obj.id+"_content_container", facet_dom_obj).scrollTop(facet_obj.scroll_top);
			}

			
		}


	}
	
	facet_obj.rendering_data = false;
}

/*
* Function: facet_scroll_to_row
* 
* EXPERIMENTAL. Scrolls the given discrete facet to the row number.
* 
* Parameters:
* facet_id - Sys ID of the facet.
* row - Row number to scroll to.
* 
*/
function facet_discrete_scroll_to_row(facet_id, row) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	var facet_dom_obj = $("#"+facet_obj.dom_id);
	
	var scroll_to = row*facet_item_row_height;
	$("#facet_"+facet_id+"_content_container", facet_dom_obj).scrollTop(scroll_to);
	
	//msg("setting system induced false");
	//facet_obj.next_scroll_event_is_system_induced = false;
	
}

/*
* Function: facet_discrete_reset_contents
* 
* Resets the content of the facet (normally to prepare for rendering of new data) to rows with default data.
* 
* Parameters:
* facet_obj - The facet object to operate on.
* 
* See also:
* <facet_render_data_discrete>
*/
function facet_discrete_reset_contents(facet_obj) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var row_id = 0;
	while(row_id < facet_obj.total_number_of_rows) {
		facet_obj.contents[row_id] = [];
		facet_obj.contents[row_id]['selected'] = false;
		facet_obj.contents[row_id]['data_loaded'] = false;
		facet_obj.contents[row_id]['id'] = row_id;
		facet_obj.contents[row_id]['value'] = null;
		facet_obj.contents[row_id]['dom_id'] = facet_obj.id+"_row_"+row_id;
		facet_obj.contents[row_id]['name'] = null;
		facet_obj.contents[row_id]['direct_counts'] = null;
		row_id++;
	}
}

/*
* Function: facet_row_clicked_callback
* 
* This function gets called whenever a row is clicked in a facet of the discrete type. It then selects/deselects the row clicked on and requests new data.
* 
* Parameters:
* facet_id - The system ID of the facet where the click took place.
* 
*/
function facet_row_clicked_callback(facet_id, row_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	var slot_obj;
	// get the scroll_top position 
	var facet_dom_obj = $("#"+facet_obj.dom_id);
	facet_obj.scroll_top =$("#facet_"+facet_id+"_content_container", facet_dom_obj).scrollTop();
	
	for(var key in facet_obj.contents) {
		if(facet_obj.contents[key].dom_id == row_id) {
			if(facet_obj.contents[key].selected === false) {
				$("#"+row_id).addClass("facet_item_row_selected");
				facet_obj.contents[key].selected = true;
				facet_obj.selections.push(facet_obj.contents[key].value);
				
				//send out data requests for all facets with a slot with a higher chain number
				slot_obj = slot_get_slot_by_id(facet_obj.slot_id);
                          
                                facet_request_data_for_chain(slot_obj.chain_number, "selection_change");
                                
                                
			}
			else {
				$("#"+row_id).removeClass("facet_item_row_selected");
				facet_obj.contents[key].selected = false;
				var selection_index=jQuery.inArray(facet_obj.contents[key].value,facet_obj.selections);
				//var selection_index = facet_obj.selections.indexOf(facet_obj.contents[key].value);
				facet_obj.selections.splice(selection_index, 1);
				slot_obj = slot_get_slot_by_id(facet_obj.slot_id);
                           
				facet_request_data_for_chain(slot_obj.chain_number, "selection_change");
			}
		}
	}
	
	facet_obj.selections_ready = true;
	
	//facet_reload_facet_objects_tmp();
}


/*
* Function: facet_viewport_item_capacity
* 
* Returns the number of rows a discrete facet can show in its visible space.
* 
* Parameters:
* facet_id - The system ID of the facet.
* 
* Returns:
* The number of rows the facet can show in its visible space.
* 
*/
function facet_viewport_item_capacity(facet_id) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	var facet_capacity = $("#"+facet_obj.dom_id).innerHeight();
	return parseInt(facet_capacity / facet_item_row_height, 10);
}




/*
* Function: facet_text_search_callback
* 
* Acts on typing in the text search box in a facet.
* 
* Parameters:
* facet_id - The system ID of the facet.
* search_text - The typed text in the search box.
* 
*/
function facet_text_search_callback(facet_id, search_text) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(ownName);
	}
	
	search_text=search_text.replace('&', ''); // remove any "&" sign since it brakes the url and arguments see bug http://bugs.humlab.umu.se/view.php?id=119
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	$("#"+facet_obj.dom_id).find(".facet_loading_indicator").attr('src','applications/'+application_name+'/theme/images/loadingf.gif');

	//$("#"+facet_obj.dom_id).find(".facet_loading_indicator").html("<img src=\"applications/"+application_name+"/theme/images/loadingf.gif\" class=\"facet_load_indicator\" />");
	
	//var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	facet_obj.text_search = search_text;
	
	var load_obj = {
		"facet_requesting" : facet_id, //the id of the facet requesting this data
		"action_reason" : "populate_text_search", //reason for update
		"facet_cause" : facet_id, //the facet which triggered the update - the facet in which something was selected in case of an selection
		"start_row" : facet_obj.last_request.start_row,
		"rows_num" : facet_obj.last_request.rows_num,
		"request_id" : ++global_request_id
	};
	
	var request = facet_build_xml_request(load_obj);
	facet_obj.last_request = load_obj; // store the load_obj here also



	$.ajax({
		//data : "facet="+facet_id+"&search="+search_text,
		data : "xml="+request+"&application_name="+application_name,
		dataType : "xml",
		processData : false,
		type : "POST",
		url: "http://" + application_address + application_prefix_path + "api/load_facet.php",
		success : function(xml) {
			facet_handle_data_callback(xml);
			//msg($(xml).text());
		}
	});
	
}

function facet_discrete_get_current_start_row(facet_id) {
	
	facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	var viewport_size = $("#"+facet_obj.dom_id).find(".facet_content_container").innerHeight();
	var items_per_viewport = Math.floor(viewport_size / facet_item_row_height);
	var start_row = $("#"+facet_obj.dom_id).find(".facet_content_container").scrollTop() / facet_item_row_height;
	//var end_row = start_row + items_per_viewport;
	
	start_row = Math.round(start_row);
	
	return start_row;
}

function facet_discrete_build_xml_for_request(facet_id) {
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	var selection_id;
	var xml = "";
	if(facet_obj.selections.length > 0) {
		xml += "<selection_group>";
		for(selection_key in facet_obj.selections) {
			selection_id = facet_obj.selections[selection_key];
			xml += "<selection>";
			xml += "<selection_type>discrete</selection_type>";
			xml += "<selection_value>"+selection_id+"</selection_value>";
			xml += "</selection>";
		}
		xml += "</selection_group>";
	}
	
	return xml;
}


