/*
* File: facet.js
* 
* Contains functions applicable to all types of facets.
* 
* Client facet_xml post:
* (see ../applications/ships/natural_doc_img/facet_post_element_data_post.jpg)
*
*  server xml-response:
* (see ../applications/ships/natural_doc_img/facet_reponse_xml.jpg)
*
* The following section describes how the client executes code depending on what action the user has performed. (This
* is an extention of the information in <index.php> where a general description is presented.)
*
* - Add a facet:   When the user clicks a search filter the <control_bar_click_callback>-function checks if the facet already exists by using <facet_presenter.facet_list.facet_get_facet_by_id> to retrieve all active facets (then removes it if active, how see below), if it doesn't already exits a slot is created by calling <slot_get_next_seq_id>. The facet object is then defined and created with <facet_create_facet>. The <facet_create_facet>-function  creates a new facet object as well as a slot object to hold it, and then renders the facet to the document by calling <facet_view.Render> and finally retrieves the content of the facet with <facet_load_data>. The slot is created by calling <slot_create_slot> which in its turn calls <slot_copy_slot_object>, <slot_render_facet_slot>, <slot_get_render_height> and <slot_init_droppable>. The <facet_view.Render>-function determines what type of facet (geo, range, discrete) to be rendered and calls type specific methods <facet_discrete_render_content_container>, <facet_range_render_content_container> or <facet_geo_render_content_container>. It renders the facet i.e. creates the html, puts together frame images, activates buttons and attaches it to the document by using the following methods: <facet_text_search_callback>, <facet_add_tooltip> , <facet_remove_facet>, <facet_collapse_toggle>. <facet_load_data> makes an ajax request to the server in xml-format (<facet_build_xml_request>) and calls upon <facet_handle_data_callback> to handle the data sent from the server. <facet_handle_data_callback> determines which type specific method should render the recieved data: <facet_render_data_discrete>, <facet_render_data_map> or  <facet_render_data_range>. It also calls <result_load_data> to update a result tab.
* - - - - - - - - - -
* - Remove a facet:   A facet can be removed in two ways: 1. Clicking the associated greyed out search filter link or 2. activating the [X]-button on the top of the facet. Clicking the link calls the <control_bar_click_callback>-function which checks if the facet already exists by using <facet_presenter.facet_list.facet_get_facet_by_id> to retrieve all active facets. If the facet exists it calls <facet_remove_facet>. Clicking the close button calls <facet_remove_facet> directly. The <facet_remove_facet>-function calls upon <slot_reload_slot_objects_tmp> and <facet_reload_facet_objects_tmp> to ensure that the remaining facets and their slots are positioned right in corralation to each other and <facet_request_data_for_chain> to update the data in facets below the deleted facet since they might have been affected by a selection made in the removed facet and the result section is also updated for the same reason.
* - - - - - - - - - -
* - Change the facet's order by drag and drop: Drag and drop actions triggers the <slot_action_callback>-function. Then ...
* - - - - - - - - - -
* - Minimize a facet: Clicking the minimizing button triggers <facet_collapse_toggle>.
* - - - - - - - - - -
* - Restore a facet to original size: Clicking the maximizing button triggers <facet_collapse_toggle>.
* - - - - - - - - - -
* - Select a value in a descrete facet: <facet_row_clicked_callback> is called and ...
* - - - - - - - - - -
* - Deselect av value in a descrete facet: <facet_row_clicked_callback> is called and ...
* - - - - - - - - - -
* - Select value in a range facet: <facet_range_changed_callback> is being called from the flash-component and ...
* - - - - - - - - - -
* - Remove value in a range facet (erasing value in input box): <facet_range_changed_callback> is being called from the flash-component and ...
* - - - - - - - - - -
* - Select an area in the geo facet: By adding a rectangle in the map-filter the <facet_geo_marker_tool_click_callback>-function is called when the selection rectangle is completed. Then ...
* - - - - - - - - - -
* - Deselect an area in the geo facet: Methods involved - <facet_geo_get_marker_pair_by_marker> , <facet_geo_points_is_within_critical_proximity> and <facet_geo_destroy_marker_pair>
* - - - - - - - - - -
*/

/*
* Function: facet_get_sys_id
* 
* Gets the system ID of a facet given the DOM ID (without the #).
* 
* Parameters:
* dom_id - The DOM ID.
*
* Returns:
* The system ID of the facet.
*
* See also:
* <facet_get_dom_id>
*
* <facet_presenter.facet_list.facet_get_facet_by_id>
*/
function facet_get_sys_id(dom_id) {
        trace_call_arguments(arguments);
	var facet_id_parts = dom_id.split("_");
	var facet_id = "";
	for(var i = 1; i < facet_id_parts.length; i++) {
		facet_id += facet_id_parts[i]+"_";
	}
	
	facet_id = facet_id.substr(0, facet_id.length-1);
	
	return facet_id;
}

/*
* Function: facet_get_dom_id
* 
* Gets the DOM ID of a facet given the system ID.
* 
* Parameters:
* sys_id - The system ID.
*
* Returns:
* The DOM ID of the facet.
*
* See also:
* <facet_get_sys_id>
* <facet_presenter.facet_list.facet_get_facet_by_id>
* 
* TODO: Move to FacetList
*/
function facet_get_dom_id(sys_id) {
        trace_call_arguments(arguments);
	for(var key in facet_presenter.facet_list.facets) {
		if(facet_presenter.facet_list.facets[key].id == sys_id) {
			return facet_presenter.facet_list.facets[key].dom_id;
		}
	}
}



// TODO: Move to FacetMasterList(?) (Build from server returned facet list)
function facet_get_facet_info_by_id(facet_sys_id) {
	
	for(var k in facets) {
		if(facets[k].id == facet_sys_id) {
			return facets[k];
		}
	}
	
	return false;
}


/*
* Function: facet_add_tooltip
* 
* Experimental.
*/
// TODO: Move to ViewUtility class (FacetView), rename to generic add_tooltip
function facet_add_tooltip(text, context) {
        trace_call_arguments(arguments);
	//we use the qTip library for this (which in turn uses jQuery)

	$(context).tooltip({
		delay: 0,
		showURL: false, 
		bodyHandler: function() {
			return text;
		} 
	});
}

/*
Function:
checks if the facets has selection
Different routines for different range facets, same procedure for discrete and geo-facet

// TODO: Create Facet class, move to facet class
        
*/
function facet_has_selection(facet_id)
{
    var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
    var has_selection = false;
    if (facet_obj.type != 'range')
    {
        if (facet_obj.selections.length > 0)
        {
            has_selection = true;
        }
    }
    else
    {
        has_selection = true;
    }

    return has_selection;
}

/*
* Function: facet_build_xml_request
* 
* Builds the XML document for requesting data for a facet.
* 
* Parameters:
* load_obj - An object/data structure describing the request to be made to the server.
* 
*	"facet_requesting" : facet_obj.id, //the id of the facet requesting this data
*	"action_reason" : "populate", //reason for update - "populate" with data or "selection_change"
*	"facet_cause" : facet_obj.id, //the facet which triggered the update - the facet in which something was selected in case of an selection
*	"start_row" : 0,
*	"rows_num" : 100000,
*	"request_id" : ++global_request_id
*	
* 
* Returns:
* An XML request document for sending to server.
* 
* See also:
*  <facet_build_xml_view_state>
*  
*  // TODO: Move to presenter, create XML build service ot FacetList.ToXML(), replace with JSON
*/
function facet_build_xml_request(load_obj, get_only_facets) {

    trace_call_arguments(arguments);
	
	var xml = "";
	xml += "<data_post>";
	xml += "<client_language>"+client_language+"</client_language>";
	if(get_only_facets != true) {
		xml += "<f_action>";
		xml += "<f_code>"+load_obj.facet_cause+"</f_code>";
		xml += "<action_type>"+load_obj.action_reason+"</action_type>";
		xml += "</f_action>";
		xml += "<requested_facet>"+load_obj.facet_requesting+"</requested_facet>";
		xml += "<request_id>"+load_obj.request_id+"</request_id>";
	}
	
	//loop through facets...
	for(var key in facet_presenter.facet_list.facets) {
		var slot_obj = slot_get_slot_by_id(facet_presenter.facet_list.facets[key].slot_id);
		
		if(typeof(load_obj) != "undefined" && load_obj.facet_requesting == facet_presenter.facet_list.facets[key].id) {
			start_row = load_obj.start_row;
			rows_num = load_obj.rows_num;
		}
		else if(typeof(facet_presenter.facet_list.facets[key].load_obj) != "undefined"){
			start_row = facet_presenter.facet_list.facets[key].load_obj.start_row;
			rows_num = facet_presenter.facet_list.facets[key].load_obj.rows_num;
		}
		else {
			//msg(facet_presenter.facet_list.facets[key].id + " : set start_row to 0 since no load_obj could be found");
			start_row = 0;
			rows_num = facet_load_items_num;
		}
		
		xml += "<facet>";
		xml += "<f_code>"+facet_presenter.facet_list.facets[key].id+"</f_code>";
		xml += "<facet_position>"+slot_obj.chain_number+"</facet_position>";
		xml += "<facet_start_row>"+start_row+"</facet_start_row>";
		xml += "<facet_number_of_rows>"+rows_num+"</facet_number_of_rows>";
		xml += "<facet_text_search>"+facet_presenter.facet_list.facets[key].text_search+"</facet_text_search>";
		xml += "<facet_filters>";
		xml += "";
		xml += "</facet_filters>";
		
		
		var result = facet_module_invoke(facet_presenter.facet_list.facets[key].type, "build_xml_for_request", facet_presenter.facet_list.facets[key].id);
		
		if(result != -1) {
			xml += result;
		}
			
		xml += "</facet>";
	}
	
	xml += "</data_post>";
	
	return xml;
}


function facet_save_facet_xml(facet_xml)
{
    // do ajax request and post the facet_xml
    var facet_state_id;
    
    
    return facet_state_id;
}

/*
* Function: facet_handle_data_callback
* 
* Dispatches incoming facet data from the server to the appropriate function.
* 
* Parameters:
* xml - The XML document from the server.
* 
* See also:
* <facet_load_data>
* <facet_render_data_discrete>
* <facet_render_data_map>
* <facet_render_data_range>
*/
function facet_handle_data_callback(xml) {
    
        trace_call_arguments(arguments);
	
	
	var xml_resp = $(xml);
	var facet_obj;
	
	if($(xml).find("action_type").text() == "populate_text_search") {
		// do what ???
	}
	
      
var facet_id;
	$(xml).find("f_code").each(function() {
		facet_id = $.trim( $(this).text() );
		//var facet_id = $(this).text();
		facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id, false);
		
		if(facet_obj === false) {
			//we received data for a facet which doesn't exist - so just ignore it
			//msg("Received data for non-existing facet: "+facet_id);
			return;
		}
		
	
		
		if(facet_obj.type == "discrete") {
			facet_discrete_render_data(facet_obj, xml);
		}
		if(facet_obj.type == "geo") {
			facet_geo_render_data(facet_obj, xml);
		}
		if(facet_obj.type == "range") {
			facet_range_render_data(facet_obj, xml);
		}
		
		var request_id = $(xml).find("request_id").text();
		request_id = parseInt(request_id);
		
		
	
		
		//msg(request_id+" - "+last_chain_final_request_id);
		if(request_id != 0 && request_id == last_chain_final_request_id) {
			if(system.do_not_initiate_result_load_data == false) {
				//result_load_data(); Fredrik 2013-10-24
                                // notify
                               // notifier.notify("facet-change")
                              // var facet_xml=facet_build_xml_request({}, true);
                              // notifier.notify("facet-change",facet_xml);
			}
		}
		

		//facet_render_data_callback($(this).text(), xml);
	});
//	console.log(facet_obj.total_number_of_rows);
	if (facet_obj.total_number_of_rows<10 && filter_by_text=="" )
	{	
		var facet_dom_obj = $("#"+facet_obj.dom_id);
		facet_dom_obj.find(".facet_text_search_box").hide();
	}
	else
	{
		var facet_dom_obj = $("#"+facet_obj.dom_id);
		facet_dom_obj.find(".facet_text_search_box").show();
	}
	
	if (facet_obj.use_text_search=='NO' && filter_by_text==""  )
	{
		var facet_dom_obj = $("#"+facet_obj.dom_id);
		facet_dom_obj.find(".facet_text_search_box").hide();

	}

       //init_select_refresh_selection(facet_id, facet_id);
	
	
}

function facet_set_loading_indicator_state(facet_id, state) {
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	state = state.toLowerCase();
	
	if(state == "on") {
		$("#"+facet_obj.dom_id).find(".facet_aux_control_area").html("<img src=\"applications/"+application_name+"/theme/images/loadingf.gif\" class=\"facet_load_indicator\" />");
	}
	else if(state == "off") {
		$("#"+facet_obj.dom_id).find(".facet_aux_control_area").html("<img src=\"applications/"+application_name+"/theme/images/loaded.gif\" class=\"facet_load_indicator\" />");
	}
}

/*
* Function: facet_load_data
* 
* Sends a request to the server for new data for a single facet.
* 
* Parameters:
* load_obj - Object containing all needed information for building the request XML document.
* 
* See also:
* <facet_build_xml_request>
* <facet_handle_data_callback>
* 
*/
function facet_load_data(load_obj) {

    trace_call_arguments(arguments);
	
	facet_set_loading_indicator_state(load_obj.facet_requesting, "on");
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(load_obj.facet_requesting);
	if(load_obj.action_reason == "populate" || load_obj.action_reason == "populate_text_search") {
		//$("#"+facet_obj.dom_id).find(".facet_aux_control_area").html("<img src=\"applications/"+application_name+"/theme/images/loadingf.gif\" class=\"facet_load_indicator\" />");
		//facet_set_loading_indicator_state(facet_obj.id, "on");
	}
	
	if(facet_obj.type == "discrete") {
		load_obj.start_row = typeof(load_obj.start_row) == "undefined" ? 0 : load_obj.start_row;
		load_obj.rows_num = typeof(load_obj.rows_num) == "undefined" ? 50 : load_obj.rows_num;
	}
	
	facet_obj.last_request = load_obj;
	
	var xml_request_document = facet_build_xml_request(load_obj);
	refresh_selection_info(load_obj);
	request = $.ajax({
		type: "POST",
		url: "http://" + application_address + application_prefix_path + "api/load_facet.php?"+load_obj.facet_requesting,
		cache: false,
		dataType: "xml",
		processData: false,
		data: "xml="+xml_request_document+"&application_name="+application_name,
		global: false,
		success: function(xml){
			facet_handle_data_callback(xml);
                        facet_reload_facet_objects_tmp();
                        facet_set_loading_indicator_state(facet_obj.id, "off");
                        refresh_selection_info_callback(xml);
                   //    refresh_selection_info(load_obj);
                     
		
		}
	});
}


function is_facet_load_needed(current_chain_pos, chain_start_number, facet_type, action_type)
{
    
    if (facet_type=='range' || action_type=='layout_change'  && current_chain_pos>= chain_start_number)
    {
       return true;
    }
    else
    {
        if (current_chain_pos> chain_start_number)
        {
            return true;
        }    
    }
    return false;
}

function reload_facet_content(facet_obj,init_facet,action_type,slot_key)
{
    
    var start_row;
    var rows_num;
    var action_reason;
    var facets_loaded_num = 500;
    var last_load_obj;

    if (action_type=='layout_change')
       action_type='selection_change';
        //msg("facet_req_chain:"+slot_objects[slot_key].chain_number);
        var load_obj;
        if(facet_obj.type == "discrete") {
                start_row = typeof(facet_obj.last_request) == "undefined" ? 0 : facet_obj.last_request.start_row;
                rows_num = typeof(facet_obj.last_request) == "undefined" ? facet_load_items_num+facet_load_items_num : facet_obj.last_request.rows_num;
                facet_obj.next_scroll_event_is_system_induced = true;
                action_reason = typeof(facet_obj.last_request) == "undefined" ? "populate" : facet_obj.last_request.action_reason;
                load_obj = {
                        "facet_requesting" : slot_objects[slot_key].facet_id, //the id of the facet requesting this data
                        "action_reason" : action_type,// action_reason, //reason for update - "populate" with data or "selection_change"
                        "action_type":action_type,
                        "facet_cause" : init_facet, //the facet which triggered the update - the facet in which something was selected in case of an selection
                        "start_row" : start_row,
                        "rows_num" : rows_num,
                        "request_id" : ++global_request_id,
                        "request_complete" : false
                }
        }
        else if(facet_obj.type == "range") {
                load_obj = {
                        "facet_requesting" : slot_objects[slot_key].facet_id, //the id of the facet requesting this data
                        "action_reason" : action_type,//"populate", //reason for update - ranges are always "populate"
                        "facet_cause" : init_facet, //the facet which triggered the update - the facet in which something was selected in case of an selection
                        "start_value" : facet_obj.selections.start,
                        "end_value" : facet_obj.selections.end,
                        "request_id" : ++global_request_id,
                        "request_complete" : false
                }

        }
        else if(facet_obj.type == "geo") {
                load_obj = {
                        "facet_requesting" : slot_objects[slot_key].facet_id, //the id of the facet requesting this data
                        "action_reason" : action_type,//"populate", //reason for update - ranges are always "populate"
                        "facet_cause" : init_facet, //the facet which triggered the update - the facet in which something was selected in case of an selection
                        "start_value" : "null",
                        "end_value" : "null",
                        "request_id" : ++global_request_id,
                        "request_complete" : false
                }
        }


        facets_loaded_num++;
        facet_load_data(load_obj);
        
       // last_load_obj=load_obj;
}

function init_select_refresh_selection(facet_id, init_facet)
{
    var load_obj;
    load_obj = {
                "facet_requesting" : facet_id, //the id of the facet requesting this data
                "facet_cause" : init_facet, //the facet which triggered the update - the facet in which something was selected in case of an selection
                }
                
   //console.log(load_obj);
     
    refresh_selection_info(load_obj);
     
}
function refresh_selection_info(load_obj)
{
    // make a separete request to upload selection tooltip since server know this best. (keeping selections on client to increase usabilty...)
    trace_call_arguments(arguments);
	
	//facet_set_loading_indicator_state(load_obj.facet_requesting, "on");
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(load_obj.facet_requesting);
	if(facet_obj.type == "discrete") {
		load_obj.start_row = typeof(load_obj.start_row) == "undefined" ? 0 : load_obj.start_row;
		load_obj.rows_num = typeof(load_obj.rows_num) == "undefined" ? 500 : load_obj.rows_num;
	}
	
	facet_obj.last_request = load_obj;
	
	var xml_request_document = facet_build_xml_request(load_obj);
	
	request = $.ajax({
		type: "POST",
		url: "http://" + application_address + application_prefix_path + "api/facet_load_selection_info.php?"+load_obj.facet_requesting,
		cache: false,
		dataType: "xml",
		processData: false,
		data: "xml="+xml_request_document+"&application_name="+application_name,
		global: false,
		success: function(xml){
                        
			refresh_selection_info_callback(xml);
                      
		}
	});
   
    
}

function refresh_selection_info_callback(xml)
{
     var facet_id
    $(xml).find("f_code").each(function() {
        facet_id = $.trim( $(this).text() );
    });
    
    var  facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
  //  facet_set_loading_indicator_state(facet_obj.id, "off");
    
    if (facet_obj.type != "range") {
            if ($(xml).find("count_of_selections").text()!='')
            {
                $("#facet_span_"+facet_obj.id+"_facet_count_selection").text(' ('+$(xml).find("count_of_selections").text()+')');
                $("#facet_span_"+facet_obj.id+"_facet_count_selection").tooltip({
                               delay: 0, 
                               showURL: false, 
                               bodyHandler: function() {
                                       return $(xml).find("report_html").text();
                               }
               });
            }
            else
            {
                $("#facet_span_"+facet_obj.id+"_facet_count_selection").text('');
                 $("#facet_span_"+facet_obj.id+"_facet_count_selection").tooltip({
                                delay: 0, 
                                showURL: false, 
                                bodyHandler: function() {
                                        return '';
                                }
                });
            }
            if ($(xml).find("count_of_selections").text()=='')
            {
                $("#"+facet_obj.dom_id).find(".facet_aux_control_area").html("<img src=\"applications/"+application_name+"/theme/images/loaded.gif\" class=\"facet_load_indicator\" />");
            }
            else
            {
                      $("#"+facet_obj.dom_id).find(".facet_aux_control_area").html("<img src=\"applications/"+application_name+"/theme/images/button_clear.png\" />");
                 }
            facet_add_tooltip(t("Ta bort alla val"), $("#"+facet_obj.dom_id).find(".facet_aux_control_area"));
            $("#"+facet_obj.dom_id).find(".facet_aux_control_area").unbind("click"); // remove the any events first

            $("#"+facet_obj.dom_id).find(".facet_aux_control_area").bind("click", function() {
                    facet_erase_selections(facet_obj.id);
            });

    }
    else {
        $("#facet_span_"+facet_obj.id+"_facet_count_selection").text("");
        //facet_set_loading_indicator_state(facet_obj.id, "on");
        }
		
  
   
}
/*
* Function: facet_request_data_for_chain
* 
* Sends server requests for data to all facets starting with the specified chain number and down.
* 
* Parameters:
* chain_start_number - The slot chain number which decides where in the facet order to start requesting data.
* action_type - "populate" or "selection_change". Hard to say what these actually do anymore.
* 
*/
function facet_request_data_for_chain(chain_start_number, action_type) {
    
        trace_call_arguments(arguments);

	var init_facet;
	var facet_obj;

	//console.log(action_type);
       
	for(var slot_key in slot_objects) {
		
		if(slot_objects[slot_key].chain_number == chain_start_number) {
			init_facet = slot_objects[slot_key].facet_id;
		}
		// if(slot_objects[slot_key].chain_number >= chain_start_number)
                
                facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(slot_objects[slot_key].facet_id);
              
                if (is_facet_load_needed(slot_objects[slot_key].chain_number, chain_start_number,  facet_obj.type, action_type))
		{
                   reload_facet_content(facet_obj,init_facet,action_type,slot_key);
                 //  init_select_refresh_selection(facet_obj.id, init_facet);
                   
                
		}
                else  if (slot_objects[slot_key].chain_number>= chain_start_number &&  facet_obj.type!='range')
                {
                    init_select_refresh_selection(facet_obj.id, init_facet);
                }
	}
	
        if (/*facets_loaded_num == 0 && */!system.do_not_initiate_result_load_data)
        {
            // notify event to notifyservice
                var xml_request_document = facet_build_xml_request({}, true);
                 //notifier.notify($(xml).find("facet_state_id").text());
                 facet_notifier.notify("facet-change",xml_request_document);
        }
	
	last_chain_final_request_id = global_request_id;
}

/*
* Function: facet_erase_selections
* 
* Removes a selections in a facet
* 
* Parameters:
* facet_id - The system ID of the facet.
* 
* See also:
*/
function facet_erase_selections(facet_id) {
        trace_call_arguments(arguments);

	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	var start_position;

	for(key in slot_objects) {
	 if(slot_objects[key].facet_id == facet_obj.id) {
			start_position = key;
		}
	}
	
	if(typeof(start_position) == "undefined") {
		start_position = 0;
	}
	
	
	if (facet_obj.type=='geo')
	{
		facet_geo_destroy_all_marker_pair(facet_id);
		facet_obj.selections=[];
		facet_request_data_for_chain(start_position, "selection_change");
	}

	if (facet_obj.type=='discrete')
	{
		facet_obj.selections=[];
                if (filter_by_text=="")
                {
                    $("#"+facet_obj.dom_id).find(".facet_text_search_box").attr("value",""); // erase any text search data
                }
                else
                {
                    $("#"+facet_obj.dom_id).find(".facet_text_search_box").attr("value","%"); // erase any text search data
                }
		facet_request_data_for_chain(start_position, "layout_change");
	}
	

	if (facet_obj.type=='range')
	{
		var state = {
			start: parseInt(facet_obj.facet_range_min_value),
			end: parseInt(facet_obj.facet_range_max_value),

		};
		
		facet_range_set_values(facet_obj.id, facet_obj.facet_range_max_value, facet_obj.facet_range_min_value);
		
		$("#"+facet_obj.id+"_slider_container").slider("values", 0, facet_obj.facet_range_min_value);
		$("#"+facet_obj.id+"_slider_container").slider("values", 1, facet_obj.facet_range_max_value);
	}
	
	
}


/*
* Function: facet_remove_facet
* 
* Removes a facet.
* 
* Parameters:
* facet_id - The system ID of the facet.
* 
* See also:
*/
function facet_remove_facet(facet_id) {
        trace_call_arguments(arguments);

	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	var key;
	var had_selection=facet_has_selection(facet_id);
	//next_position stores the value of the key for the facet following the deleted facet.
	//see facet_request_data_for_chain and the calculation of the init_facet for details on
	//why this is needed.
	//delete value in array also remove the value's key. 
	// see http://bugs.humlab.umu.se/view.php?id=38

	
	var next_position;
	var deleted = false;
	//remove slot
	for(key in slot_objects) {
		if(deleted){
			next_position = key;
			break;
		}
		else if(slot_objects[key].facet_id == facet_obj.id) {
			$("#"+slot_objects[key].dom_id).remove();
			delete slot_objects[key];
			delete slot_objects_tmp[key];
			//slot_reset_slot_id_seq();
			
			slot_reload_slot_objects_tmp();
			deleted = true;
		}
		
	}
	
	if(typeof(next_position) == "undefined") {
		next_position = 0;
	}
	
	//remove facet
	for(key in facet_presenter.facet_list.facets) {
		if(facet_presenter.facet_list.facets[key].id == facet_obj.id) {
			$("#"+facet_obj.dom_id).remove();
			delete facet_presenter.facet_list.facets[key];
			delete facet_presenter.facet_list.facets_saved_state[key];
			facet_reload_facet_objects_tmp();
		}
	}
	
	setTimeout("layout_move_facets_to_layout();", 300);
	
	$("#facet_"+facet_id+"_bar_button").removeClass("facet_control_bar_button_clicked");
	
	facet_control_item_set_status(facet_id, "unchecked", true);
	
	//msg("request data for ... "+next_position);

	// if there where selection then update everything....
	if (had_selection)
	{
		facet_request_data_for_chain(next_position, "selection_change");
	}

}

/*
* Function: facet_refresh_facet_positions
* 
* OBSOLETE. Resets the positioning of all the facets to their standard positions based on the slots they are attached to. You might want to use layout_move_facets_to_layout instead here, since it basically does exactly the same thing, but with animation.
* 
* See also:
* <layout_move_facets_to_layout>
*/
function facet_refresh_facet_positions() {
        trace_call_arguments(arguments);

	var slot_obj;
	var slot_dom_obj;
	for(var key in facet_presenter.facet_list.facets) {
		slot_obj = slot_get_slot_by_id(facet_presenter.facet_list.facets[key].slot_id);
		slot_dom_obj = $("#"+slot_obj.dom_id);
		
		$("#"+facet_presenter.facet_list.facets[key].dom_id).css("top", slot_dom_obj.position().top);
		$("#"+facet_presenter.facet_list.facets[key].dom_id).css("left", slot_dom_obj.position().left);
	}
}

/*
* Function: facet_copy_facet_object
* 
* Creates a copy of a facet object.
* 
* Parameters:
* facet_obj - The facet object to copy.
* 
* Returns:
* An identical copy of the facet object.
* 
*/
function facet_copy_facet_object(facet_obj) {
    
        trace_call_arguments(arguments);

	var copy = [];
	for(var key in facet_obj) {
		copy[key] = facet_obj[key];
	}
	
	return copy;
}

/*
* Function: facet_reload_facet_objects_tmp
* 
* Reloads the volatile/tmp array of facet objects from the permanent/stable facet objects array.
* 
* See also:
* <slot_reload_slot_objects_tmp>
*/
function facet_reload_facet_objects_tmp() {
    
        trace_call_arguments(arguments);
	
	delete facet_presenter.facet_list.facets_saved_state;
	facet_presenter.facet_list.facets_saved_state = [];
	
	for(var key in facet_presenter.facet_list.facets) {
		var facet_copy = facet_copy_facet_object(facet_presenter.facet_list.facets[key]);
		facet_presenter.facet_list.facets_saved_state[key] = facet_copy;
	}
}

/*
* Function: facet_collapse_toggle
* 
* Toggles the collapse of facets.
* 
* Parameters:
* facet_id - The system ID of the facet to toggle collapse on.
* 
*/
function facet_collapse_toggle(facet_id) {
    
        trace_call_arguments(arguments);
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	if(facet_obj.collapsed == true) {

		facet_expand_facet(facet_obj);
	}
	else {
		facet_collapse_facet(facet_obj); 
		
	}
}

function facet_expand_facet(facet_obj) {
    
        trace_call_arguments(arguments);
	
	var facet_height;
	if(facet_obj.type == "range") {
		facet_height = facet_range_default_height;
	}
	else if(facet_obj.type == "geo") {
		facet_height = facet_geo_default_height;
	}
	else {
		facet_height = facet_default_height;
	}
	var slot_obj = slot_get_slot_by_id(facet_obj.slot_id);
	var facet_dom_obj = $("#"+facet_obj.dom_id);
	
	facet_dom_obj.find(".facet_collapse_button").attr("src", "applications/"+application_name+"/theme/images/button_minimize.png");
	
	facet_dom_obj.find(".facet_table").css("height", facet_height+"px");
	
	var table=facet_dom_obj.find(".facet_table");
	var tbody=table.find(".facet_table");
	tbody.css('height',"0px"); // FIX For IE
	

	facet_dom_obj.find(".facet_middle_middle_cell").css("display", "table-cell");
	facet_dom_obj.find(".facet_middle_left_cell").css("display", "table-cell");
	facet_dom_obj.find(".facet_middle_right_cell").css("display", "table-cell");
	facet_dom_obj.find(".facet_content_container_table").css("display", "table");
	facet_dom_obj.find(".facet_content_container_table").css("height", facet_obj.item_list_container_table_height);
	facet_dom_obj.find(".facet_content_container_table").css("width", "100%");
	
	facet_dom_obj.css("height", facet_height+"px");
	$("#"+slot_obj.dom_id).css("height", slot_get_render_height(facet_height)+"px");
	
	facet_obj.collapsed = false;
	facet_obj.height = facet_height;
	slot_obj.height = facet_height;
	//facet_refresh_facet_positions();
	
	facet_reload_facet_objects_tmp();
	slot_reload_slot_objects_tmp();
	layout_move_facets_to_layout();
	
	//enable text search in this facet
	facet_dom_obj.find(".facet_text_search_box").attr("disabled", false);
	
	//if this is a range facet we need to re-create the value bar object for it to function properly
	
	/*
	if(facet_obj.type == "range") {
		//remove old value bar
		facet_dom_obj.find(".facet_content_container_flash").html(""); // empty only the flash container, keeping the htmlcontainer for the flash content
		delete facet_obj.value_bar_obj;
		//facet_obj.value_bar_initialized = false; // 2011-06-01 already done when collapsed
		facet_obj.render_complete = false;
		//create a new value bar
		//facet_range_render_contents(facet_obj, 0, 10);
		value_bar = facet_render_value_bar(facet_obj, facet_obj.selections.end, facet_obj.selections.start);
		facet_obj.value_bar_obj = value_bar;
		//draw bars from old values

		facet_range_populate(facet_obj.id);
	}
	*/
	
	facet_obj.collapsed = false;
}

function facet_collapse_facet(facet_obj) {
    
        trace_call_arguments(arguments);
	
	var facet_height;
	if(facet_obj.type == "range") {
		facet_height = facet_range_default_height;
	}
	else if(facet_obj.type == "geo") {
		facet_height = facet_geo_default_height;
	}
	else {
		facet_height = facet_default_height;
	}
	var slot_obj = slot_get_slot_by_id(facet_obj.slot_id);
	var facet_dom_obj = $("#"+facet_obj.dom_id);
	
	if(facet_obj.type == "range") {
		var vb_state = facet_range_get_current_values(facet_obj.id);
		facet_obj.selections.end = vb_state["upper"];
		facet_obj.selections.start = vb_state["lower"];
	}
	
	//collapse
	facet_obj.item_list_container_table_height = facet_dom_obj.find(".facet_content_container_table").css("height");
	
	facet_dom_obj.find(".facet_collapse_button").attr("src", "applications/"+application_name+"/theme/images/button_max.png");
	
	facet_dom_obj.find(".facet_table").css("height", "0px");

	var table=facet_dom_obj.find(".facet_table");
	var tbody=table.find(".facet_table");
	tbody.css('height',"0px"); //FIX for IE9

	

	facet_dom_obj.find(".facet_middle_middle_cell").css("display", "none");
	facet_dom_obj.find(".facet_middle_left_cell").css("display", "none");
	facet_dom_obj.find(".facet_middle_right_cell").css("display", "none");
	facet_dom_obj.find(".facet_content_container_table").css("display", "none");
	
	facet_obj.height = facet_header_height + facet_footer_height;
	slot_obj.height = facet_header_height + facet_footer_height;
	
	facet_dom_obj.css("height", "45px");
	var table=facet_dom_obj.find(".facet_table");
	var tbody=table.find(".facet_table");
	tbody.css('height',"0px"); // fix to work in IE9
	
	$("#"+slot_obj.dom_id).css("height", slot_get_render_height(slot_obj.height)+"px");
	
	facet_obj.collapsed = true;
	
	//facet_refresh_facet_positions();
	
	facet_reload_facet_objects_tmp();
	slot_reload_slot_objects_tmp();
	layout_move_facets_to_layout();
	
	//disable text search in this facet
	facet_dom_obj.find(".facet_text_search_box").attr("disabled", true);
	
	
	facet_obj.collapsed = true;
}




/*
* Function: facet_create_facet
* 
* Creates a new facet object as well as a slot object to hold it. Then renders the facet to the document by calling facet_view.Render (where most of the heavy lifting is done).
* 
* Takes an object as an argument for creating the facet.
* The properties which the argument object may contain are listed below.
* Some options only apply to a certain type of facet.
* 
* "id" : <the system ID of the facet>
* "dom_id" : <the DOM ID of the facet>,
* "title" : <display title>,
* "contents" : <an array of rows... you probably don't want to use this>,
* "displayed_in_ui" : <true or false>,
* "slot_id" : <the ID of the slot to place the facet in>,
* "width" : <graphical width>,
* "height" : <graphical height>,
* "top" : <this is no place for this...>,
* "left" : <this is no place for this...>,
* "selections" : <an array of... who knows... rows?>,
* "type" : <can be "range", "discrete" or "map">,
* "total_number_of_rows" : <numeric... not sure why you would use this though>
* 
* Parameters:
* facet_obj - The facet object describing the facet to create.
* 
* See also:
* <slot_create_slot>
* <facet_view.Render>
*/

function facet_create_facet(facet_obj) {
        trace_call_arguments(arguments);
	
	facet_obj.render_complete = false;
	facet_obj.value_bar_initialized = false;
	
	//some things are required...
	if(typeof(facet_obj.id) == "undefined") {
		return false;
	}
	if(typeof(facet_obj.slot_id) == "undefined") {
		return false;
	}
	
	//set some defaults...
	if(typeof(facet_obj.type) == "undefined") {
		facet_obj.type = "discrete";
	}
	if(typeof(facet_obj.collapsed) == "undefined") {
		facet_obj.collapsed = false;
	}
	if(typeof(facet_obj.displayed_in_ui) == "undefined") {
		displayed_in_ui = true;
	}
	if(typeof(facet_obj.selections) == "undefined") {
		facet_obj.selections = [];
	}
	if(typeof(facet_obj.width) == "undefined") {
		facet_obj.width = facet_default_width;
	}
	if(typeof(facet_obj.contents) == "undefined") {
		facet_obj.contents = Array();
	}
	
	facet_obj.rendering_data = false;
	facet_obj.selections_ready = false; //this indicator is used when loading in selections from view state - which takes an arbitrary amount of time with range facets since the flash needs to init first
	
	//initialize some different parameters depending on type of facet
	if(facet_obj.type == "discrete") {
		facet_obj.height = facet_discrete_default_height;
		facet_obj.width = facet_discrete_default_width;
		facet_obj.view_port = {"start_row" : 0, "end_row" : 0 }
		facet_obj.selections = [];
	}
	if(facet_obj.type == "range") {
		facet_obj.height = facet_range_default_height;
		facet_obj.width = facet_range_default_width;
		if(typeof(facet_obj.selections.start) == "undefined") {
			facet_obj.selections.start = 0;
		}
		if(typeof(facet_obj.selections.end) == "undefined") {
			facet_obj.selections.end = -1;
		}
	}
	if(facet_obj.type == "geo") {
		facet_obj.height = facet_geo_default_height;
		facet_obj.width = facet_geo_default_width;
		facet_obj.geo_marker_pairs=new Array();
		//facet_obj.map=new Object();
		//facet_obj.map.geo_filter_tool=false;
		facet_obj.map_title="";
	}
	
	
	
	var slot = {
		"id" : facet_obj.slot_id,
		"dom_id" : "facet_slot_"+facet_obj.slot_id,
		"facet_id" : facet_obj.id,
		"width" : facet_default_width,
		"height" : facet_obj.height,
		"chain_number" : facet_obj.slot_id,
		"collapsed" : false
	};
	
	slot_create_slot(slot);
	
	facet_presenter.facet_list.facets.push(facet_obj);
	/*
	var facet_obj_key = 0;
	for(var key in facet_presenter.facet_list.facets) {
		if(typeof(facet_presenter.facet_list.facets[key]) == "undefined") {
			facet_presenter.facet_list.facets[key] = facet_obj;
			facet_obj_key = key;
		}
	}
	*/
	//var facet_obj_copy = facet_copy_facet_object(facet_obj);

	/*
	if (facet_obj.type == "geo") {
		//facet_presenter.facet_list.facets_saved_state.push(facet_obj);
		facet_presenter.facet_list.facets_saved_state[facet_obj_key] = facet_obj;
	}
	else {
		//facet_presenter.facet_list.facets_saved_state.push(facet_obj_copy);
		facet_presenter.facet_list.facets_saved_state[facet_obj_key] = facet_obj_copy;
	}
	*/
	//facet_presenter.facet_list.facets_saved_state.push(facet_obj_copy);
	
	facet_view.Render(facet_obj);
	
	if (facet_obj.type == "geo")
	{
			google.maps.event.trigger(facet_obj.map , 'resize'); //2013-03-05 2013-04-25
			facet_obj.map.setCenter(new  google.maps.LatLng(facet_geo_default_point.lat, facet_geo_default_point.lng));
	}

	
	
	// refresh the tmp list after rendering (of course so all new add stuff becomes part of the tmp-list... why do it before....)
	facet_reload_facet_objects_tmp();

	//msg("fld3");
	facet_load_data({
	"facet_requesting" : facet_obj.id, //the id of the facet requesting this data
	"action_reason" : "populate", //reason for update - "populate" with data or "selection_change"
	"facet_cause" : facet_obj.id, //the facet which triggered the update - the facet in which something was selected in case of an selection
	"start_row" : 0,
	"rows_num" : facet_load_items_num,
	"request_id" : ++global_request_id,
	"start_value" : facet_obj.selections.start,
	"end_value" : facet_obj.selections.end
	});

	// Fredrik says: added load_result here instead of each facet_load, since it is only when adding new one there is need to refresh current_filter
	if(system.do_not_initiate_result_load_data == false) {
	//	result_load_data(); // use event instead
        
	}
}


function facet_module_invoke(module_name, callback_func, args) {
    
        trace_call_arguments(arguments);
	
	var function_name = "facet_"+module_name+"_" + callback_func;
	
	var result = -1;
	
	if(eval("typeof " + function_name + " == 'function'")) {
		result = eval(function_name+"(\""+args+"\");");
	}
	
	return result;
}

function facet_module_invoke_all(callback_func, args) {
    
        trace_call_arguments(arguments);
        
	var results = {};
	
	for(var key in result_object.result_modules) {
		var function_name = "facet_"+system.facet_modules[key].id+"_" + callback_func;
		if(eval("typeof " + function_name + " == 'function'")) {
			results[key] = eval(function_name+"(\""+args+"\");");
		}
	}
	
	return results;
}
