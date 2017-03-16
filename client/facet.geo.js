/*
* File: facet.geo.js
* 
* Contains functions specific of the geo type of facet.
*/



//var filter_tool_mouse_move_event_listener; // FIX ME, could be part of the facet_obj
var filter_tool_event_listener; // global object for listener to rectange selection, could be part of facet_obj
var marker_currently_being_dragged = "undefined"; // FIX ME, could be part of the facet_obj


// **********************************************************************************************************************************
/*
   Function: facet_geo_marker_tool_toggle_callback

   Description: Activates/deactivates the tool depending on previous status.

   Parameters: 
   map -

   tool_btn -

   see also:
   <facet_presenter.facet_list.facet_get_facet_by_id>

   <facet_geo_destroy_marker_pair>

   <facet_geo_marker_tool_disable>

   <facet_geo_marker_tool_enable>
*/
function facet_geo_marker_tool_toggle_callback(map, tool_btn) {

    trace_call_arguments(arguments);
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(map.facet_id);
	
	
	//var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(map.facet_id);
	
	//disable tool button while marker is being dragged
	if(facet_obj.facet_geo_drag_in_progress) {
		facet_geo_marker_tool_disable(facet_obj);
		facet_geo_destroy_marker_pair(facet_obj, marker_currently_being_dragged);

		return;
	}
	
	var tool_btn_dom = $(tool_btn).children()[0];
	
	if(facet_obj.map.facet_geo_filter_tool.state == true) {
		facet_geo_marker_tool_disable(facet_obj);
//alert ('state = true rad 126');
	}
	else {
		facet_geo_marker_tool_enable(facet_obj);
//alert ('else rad 130');
	}
}
// **********************************************************************************************************************************
/*
   Function: facet_geo_marker_tool_enable

   Description: Enables user to draw map filter (rectangle), adds listener to map. Stores listener into facet_obj
   disables dragging, sets cursor to crosshair

   Parameters: 
   facet_obj - 

   Returns:

   see also:
   <facet_geo_marker_tool_click_callback>
*/
function facet_geo_marker_tool_enable(facet_obj) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	facet_obj.map.facet_geo_filter_tool.state = true;
	facet_obj.map.setOptions({draggable:false}); // disable dragging while making selections
	// V2 facet_obj.map.disableDragging();
	
	var tool_btn_dom = $("#filter_tool_btn")[0];
	
	$(tool_btn_dom).css("background-image", "url('applications/"+application_name+"/theme/images/geo_filter_tool_btn_selected.png')");
	var m = $("#geo_canvas").children()[0];
	$(m).css("cursor", "crosshair");
	
// var filter_tool_event_listener is  part of facet_obj
/*
// V2

	facet_obj.filter_tool_event_listener = GEvent.addListener(facet_obj.map, "click", function(overlay, position, overlay_position) {
		//msg("eevent");
		facet_geo_marker_tool_click_callback(facet_obj, facet_obj.map, position, overlay_position);
	});

	*/
	
	// adds listener for click on the filter map
	
	facet_obj.filter_tool_event_listener = google.maps.event.addListener(facet_obj.map,'click', function(event) {
									  var point;
									  if (event) {
										  point = event.latLng;
									  }
									 facet_geo_marker_tool_click_callback(facet_obj, facet_obj.map, point); // send the point where the user clicked
												  
								}); 

	
	

	
}
// **********************************************************************************************************************************
/*
   Function: facet_geo_marker_tool_disable

   Description: Disables the marker tool, removes the listener.

*/
function facet_geo_marker_tool_disable(facet_obj) {
    trace_call_arguments(arguments);
	facet_obj.map.facet_geo_filter_tool.state = false;
	//V2 facet_obj.map.enableDragging();
	
	facet_obj.map.setOptions({draggable:true});
	
	var tool_btn_dom = $("#filter_tool_btn")[0];
	
	$(tool_btn_dom).css("background-image", "url('applications/"+application_name+"/theme/images/geo_filter_tool_btn_deselected.png')");
	
	/* V2
	if(typeof(filter_tool_event_listener) != "undefined") {
		GEvent.removeListener(filter_tool_event_listener);
	}
	*/
	
	google.maps.event.removeListener(facet_obj.filter_tool_event_listener);
	
}
/*
function: facet_geo_create_marker

Description: makes marker of two types, handle or close.


*/
function facet_geo_create_marker(position_input, type, text) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	if(type == "handle") {
		var marker_icon_url="applications/"+application_name+"/theme/images/geo_marker.png"
		 // V2 new GIcon(false, "applications/"+application_name+"/theme/images/geo_marker.png");
		 
	}
	else if(type == "close") {
		var marker_icon_url="applications/"+application_name+"/theme/images/button_close.png"
		
		//v2 var marker_icon = new GIcon(false, "applications/"+application_name+"/theme/images/button_close.png");
	}
	
	
	
	  var marker = new google.maps.Marker({
	  position: position_input,
	  map: result_object.map_obj,
	  anchorPoint: new google.maps.Point(0,-18),
	  icon: {url:marker_icon_url,
			anchor:new google.maps.Point(11,18)},
	   title: text
	});
	
	
	//V2 marker_icon.iconSize = new GSize(12, 12);
	//V2 marker_icon.iconAnchor = new GPoint(6, 6);
	/* V2
	var marker_options = {
		"icon" : marker_icon, 
		"draggable" : true, 
		"bouncy" : false, 
		"dragCrossMove" :false, 
		"bounceGravity" : 2, 
		"title" : text
	}
	*/
	//V2 var marker = new GMarker(position, marker_options);
	
	return marker;
}

// **********************************************************************************************************************************
/*
   Function: facet_geo_marker_tool_click_callback

   Description: Fetches the position of the click and creates the selection, if it is the first click then it creates the first handle
   and starts drawing the rectangle until the next click.
   The function is used for both first and second click.
   

   Parameters: 
   facet_obj - the filter

   map - the map in the filter

   position - the position where the user clicked




   see also:
   <facet_geo_marker_tool_disable>

   <facet_geo_marker_tool_drag_marker_callback>

   <facet_geo_get_marker_pair_by_marker>

   <facet_geo_points_is_within_critical_proximity>

   <facet_geo_destroy_marker_pair>

	<facet_geo_refresh_selections>

	<slot_get_slot_by_id>

   <facet_request_data_for_chain>
*/
function facet_geo_marker_tool_click_callback(facet_obj, map, position) {
    trace_call_arguments(arguments);

	//console.log('facet_obj.facet_geo_drag_in_progress '+facet_obj.facet_geo_drag_in_progress );
		
	if(facet_obj.facet_geo_drag_in_progress != true) {
		//msg("seed");
		//console.log("seed");
		// first click for the rectangle, makes the first marker, prepares for the second click
		facet_geo_marker_tool_clicked_callback_seed(facet_obj, position);
		}
	else {
	
	//msg("plant");
		//console.log("plant");
		// second click for the rectangle, adds the second corner and the close button and refreshes the filters.
		facet_geo_marker_tool_clicked_callback_plant(facet_obj, position);
		
		
	}
	
	
	if(facet_obj.facet_geo_drag_in_progress) {
		
	}
	else {
		
	}
	
}
/*
function: facet_geo_marker_tool_clicked_callback_seed 

Description: first click for the rectangle, makes the first marker, prepares for the second click

Parameters: 
   facet_obj - the filter

   position - the position of the mouse click


*/

function facet_geo_marker_tool_clicked_callback_seed(facet_obj, position) {
    trace_call_arguments(arguments);

	
	facet_obj.facet_geo_drag_in_progress = true;
	
	var handle_marker = facet_geo_create_marker(position, "handle", t("Dra för att ändra markeringen."));
	var close_marker = facet_geo_create_marker(position, "close", t("Klicka för att ta bort markeringen.")); 
	
	handle_marker.setDraggable(true);
	
	google.maps.event.addListener(close_marker, "click", function() {
	//alert('closeme');
	
			facet_geo_destroy_marker_pair(facet_obj, this); //could have been any marker...given that there is two
			var geo_facet_slot = slot_get_slot_by_id(facet_obj.slot_id);
			facet_request_data_for_chain(geo_facet_slot.chain_number, "populate");
			
		});
	
	facet_obj.geo_marker_pairs.push({
		"start_marker" : handle_marker,
		"close_marker" : close_marker
	});
	
	
	// V2
	
	/*
	facet_obj.filter_tool_mouse_move_event_listener = GEvent.addListener(facet_obj.map, "mousemove", function(position) {
		//msg("setting zone in anticipation of plant-marker");
		facet_geo_marker_tool_drag_marker_callback(facet_obj, handle_marker, position);
	});
*/
	facet_obj.filter_tool_mouse_move_event_listener= google.maps.event.addListener(facet_obj.map, "mousemove", function(event) {
		//msg("setting zone in anticipation of plant-marker");
		// filter, marker and mouse position
		var point;
		if (event)
		{
			  point = event.latLng;
		}
		//console.log(point);
		facet_geo_marker_tool_drag_marker_callback(facet_obj, handle_marker, point);
	});
	
		
	
	facet_obj.marker_currently_being_dragged = handle_marker;
	
	google.maps.event.addListener(handle_marker, "drag", function() {
		//msg("drag seed "+handle_marker);
		facet_geo_marker_tool_drag_marker_callback(facet_obj, handle_marker, position);
	});
	
	google.maps.event.addListener(handle_marker, "dragend", function() {
		//msg("dragend seed"+handle_marker);
		facet_geo_refresh_selections(facet_obj);
		var slot_obj = slot_get_slot_by_id(facet_obj.slot_id);
		facet_request_data_for_chain(slot_obj.chain_number, "populate");
	});
	
	handle_marker.setMap(facet_obj.map);
	
	return handle_marker
}

/*
function: facet_geo_marker_tool_clicked_callback_plant


Description:

This function handles the second click of the rectangle selection

*/
function facet_geo_marker_tool_clicked_callback_plant(facet_obj, position) {
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var pairs_num = facet_obj.geo_marker_pairs.length;
	
	// check the the position is different from the start_marker
	//console.log(facet_obj.geo_marker_pairs[pairs_num-1].start_marker.getLatLng());
	//console.log("points are equal "+position.equals(facet_obj.geo_marker_pairs[pairs_num-1].start_marker.getLatLng()));
	
		
	var handle_marker = facet_geo_create_marker(position, "handle", t("Dra för att ändra markeringen"));
	
	handle_marker.setDraggable(true);
			
	facet_obj.geo_marker_pairs[pairs_num-1].end_marker = handle_marker;
	
	//V2 GEvent.removeListener(facet_obj.filter_tool_mouse_move_event_listener);
	google.maps.event.removeListener(facet_obj.filter_tool_mouse_move_event_listener);
	
	
	facet_obj.marker_currently_being_dragged = false;
	
	facet_geo_refresh_selections(facet_obj); // Update the selection storage in the facet object
	var slot_obj = slot_get_slot_by_id(facet_obj.slot_id);
	facet_request_data_for_chain(slot_obj.chain_number, "populate"); // reload new data for all relevant filter
	
	facet_geo_marker_tool_disable(facet_obj);
	facet_obj.facet_geo_drag_in_progress = false;
	
	google.maps.event.addListener(handle_marker, "drag", function() {
		//msg("drag plant "+handle_marker);
		//console.log("drag plant "+handle_marker);
		facet_geo_marker_tool_drag_marker_callback(facet_obj, handle_marker, position);
	});
	
	
	google.maps.event.addListener(handle_marker, "dragend", function() {
		//msg("dragend plant "+handle_marker);
		facet_geo_refresh_selections(facet_obj);
		var slot_obj = slot_get_slot_by_id(facet_obj.slot_id);
		facet_request_data_for_chain(slot_obj.chain_number, "selection_change");
	});
	
	handle_marker.setMap(facet_obj.map); // adds marker to map
	facet_obj.selections_ready = true;
}


function facet_geo_destroy_all_marker_pair(facet_id)
{
	if(FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}

	var facet_obj=facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
		var map=facet_obj.map;
	for(var marker_key in facet_obj.geo_marker_pairs) {
			
			
			facet_obj.geo_marker_pairs[marker_key].start_marker.setMap(null);
	
			if(typeof(facet_obj.geo_marker_pairs[marker_key].end_marker) != "undefined") {
				
				facet_obj.geo_marker_pairs[marker_key].end_marker.setMap(null);
				
				
			}
			if(typeof(facet_obj.geo_marker_pairs[marker_key].close_marker) != "undefined") {
				facet_obj.geo_marker_pairs[marker_key].close_marker.setMap(null);
			}
			
			facet_obj.geo_marker_pairs[marker_key].overlay.setMap(null);
			
			delete facet_obj.geo_marker_pairs[marker_key];
			// delete elements after they have been disconnected from the  map.
			//delete facet_presenter.facet_list.facets[key].selections[right_key];
		}
		
		
}


// **********************************************************************************************************************************
/*
   Function: facet_geo_destroy_marker_pair

   Description:

   Parameters: 
   map -

   marker - 

   see also:
   <facet_geo_refresh_selections>
*/
function facet_geo_destroy_marker_pair(facet_obj, marker) {
    trace_call_arguments(arguments);

	//console.log('destroy marker pair');
	var marker_key = "";
	// FIND the right rectangle to be removed based on marker int argument of the function
	for(var key in facet_obj.geo_marker_pairs) {
		
		if(facet_obj.geo_marker_pairs[key].close_marker == marker || facet_obj.geo_marker_pairs[key].start_marker == marker || facet_obj.geo_marker_pairs[key].end_marker == marker) {
			marker_bundle = facet_obj.geo_marker_pairs[key];
			marker_key = key;
		//	console.log("found marker to destroy "+key);
		}
		else {
			//msg("not destroying pair "+key);
		}
	}
	
	facet_obj.facet_geo_drag_in_progress = false;
	
	// V2 GEvent.removeListener(facet_obj.filter_tool_mouse_move_event_listener);
	
	marker_currently_being_draggged = "undefined";
	
	
	if(typeof(facet_obj.geo_marker_pairs[marker_key].start_marker) != "undefined")
	{
	// V2 facet_obj.map.removeOverlay(facet_obj.geo_marker_pairs[marker_key].start_marker);
	// remove start_marker
		facet_obj.geo_marker_pairs[marker_key].start_marker.setMap(null);
	}
	
	if(typeof(facet_obj.geo_marker_pairs[marker_key].end_marker) != "undefined") {
		
		// V2 facet_obj.map.removeOverlay(facet_obj.geo_marker_pairs[marker_key].end_marker);
		facet_obj.geo_marker_pairs[marker_key].end_marker.setMap(null);
	}
	if(typeof(facet_obj.geo_marker_pairs[marker_key].close_marker) != "undefined") {
	
		// V2 facet_obj.map.removeOverlay(facet_obj.geo_marker_pairs[marker_key].close_marker);
		facet_obj.geo_marker_pairs[marker_key].close_marker.setMap(null);
	}
	
	
	// V2 facet_obj.map.removeOverlay(facet_obj.geo_marker_pairs[marker_key].overlay);
	facet_obj.geo_marker_pairs[marker_key].overlay.setMap(null)
	// Remove the element from the facet_object rectangle list
	delete facet_obj.geo_marker_pairs[marker_key];
	
	
	//delete facet_presenter.facet_list.facets[key].selections[right_key];
	// refresh the selections based on the selection in the map (start_marker and end_marker)
	var current_facet_obj = facet_presenter.facet_list.facet_get_facet_by_id('geo', false);	
	facet_geo_refresh_selections(current_facet_obj);//FIXED?? 2010-05-12

//		facet_geo_refresh_selections(facet_presenter.facet_list.facets[0]);//FIXME

}
// **********************************************************************************************************************************
/*
   Function: facet_geo_refresh_selections

   Description: Refresh the selection of the facet with "id" = geo
   so the all marker selection are also stored in the facet_obj.selection

   Parameters: 
   facet_obj - 

   Returns:

   see also:
   <facet_presenter.facet_list.facet_get_facet_by_id>
*/
function facet_geo_refresh_selections(facet_obj) {
    trace_call_arguments(arguments);


	// override input from client... Hack to make sure we have the right object.
	// Should be FIXME.... 2010-05-17 /Fredrik
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id('geo', false);

	
	//msg("facet_map_refresh_selections");
	facet_obj.selections = Array();
	
	var east_lat, west_lat, north_lng, south_lng;
	
	var i = 0;

	for(var key in facet_obj.geo_marker_pairs) {
		var start_lat = facet_obj.geo_marker_pairs[key].start_marker.getPosition().lat();
		var start_lng = facet_obj.geo_marker_pairs[key].start_marker.getPosition().lng();
		
		var end_lat = facet_obj.geo_marker_pairs[key].end_marker.getPosition().lat();
		var end_lng = facet_obj.geo_marker_pairs[key].end_marker.getPosition().lng();
		
		if(start_lat >= end_lat) {
			north_lat = start_lat;
			south_lat = end_lat;
		}
		else {
			south_lat = start_lat;
			north_lat = end_lat;
		}
		if(start_lng >= end_lng) {
			east_lng = start_lng;
			west_lng = end_lng;
		}
		else {
			west_lng = start_lng;
			east_lng = end_lng;
		}
		
		facet_obj.selections[i] = Array();
		facet_obj.selections[i].rectangle_id=key; // store the key of the rectangle selection for use in the post arguments
		facet_obj.selections[i].sw = {
			lat : south_lat,
			lng : west_lng
		}
		facet_obj.selections[i].ne = {
			lat : north_lat,
			lng : east_lng
		}
		
		i++;
	}
}
// **********************************************************************************************************************************
/*
   Function: facet_geo_marker_tool_drag_marker_callback

   Description:
   This function is called to update the rectangle selection
   Trigged by:
   * moving the mouse after the the first click when creating the rectangle
   * Dragging any of the two handles of the rectangle

   Parameters: 
   map -

   marker - 

   mouse_position -
*/
function facet_geo_marker_tool_drag_marker_callback(facet_obj, marker, mouse_position) {
    trace_call_arguments(arguments);

	
	var start_marker, end_marker, marker_overlay, pairs_key,close_marker;
	//var facet_obj=facet_presenter.facet_list.facet_get_facet_by_id(map.facet_id);
	
	//find buddy marker
	var pairs_key; // key of last rectangle used after the loop
	for(var key in facet_obj.geo_marker_pairs) {
		if(marker == facet_obj.geo_marker_pairs[key].start_marker) {
			start_marker = facet_obj.geo_marker_pairs[key].start_marker;
			end_marker = facet_obj.geo_marker_pairs[key].end_marker;
			marker_overlay = facet_obj.geo_marker_pairs[key].overlay;
			close_marker=facet_obj.geo_marker_pairs[key].close_marker;
			pairs_key = key;
			
		}
		if(marker == facet_obj.geo_marker_pairs[key].end_marker) {
			start_marker = facet_obj.geo_marker_pairs[key].start_marker;
			end_marker = facet_obj.geo_marker_pairs[key].end_marker;
			marker_overlay = facet_obj.geo_marker_pairs[key].overlay;
			close_marker=facet_obj.geo_marker_pairs[key].close_marker;
			pairs_key = key;
		}
	}
	
	if(typeof(pairs_key) == "undefined") {
		return;
	}
	
	start_coords = start_marker.getPosition();
	
	if(typeof(end_marker) == "undefined") {
		end_coords = mouse_position;
	}
	else {
		end_coords = end_marker.getPosition();
		
	}
//	console.log(end_coords);
	
	start_lat = start_coords.lat();
	start_lng = start_coords.lng();
	end_lat = end_coords.lat();
	end_lng = end_coords.lng();
	
	var high_lat, low_lat, high_lng, low_lng;
	
	if(start_lat >= end_lat) {
		high_lat = start_lat;
		low_lat = end_lat;
	}
	else {
		// end marker is about the startmarker, adjust the offset of close_marker
		high_lat = end_lat;
		low_lat = start_lat;
		close_marker_offset=10; //was 24
	}
	if(start_lng >= end_lng) {
		high_lng = start_lng;
		low_lng = end_lng;
	}
	else {
		high_lng = end_lng;
		low_lng = start_lng;
	}
	

	// update the position of the close_marker.
	var close_marker=facet_obj.geo_marker_pairs[pairs_key].close_marker;
	
	// V2 var close_position = new GLatLng(high_lat, high_lng);
	
	
	
	
	var close_position= new google.maps.LatLng(high_lat, high_lng)
	
	
	if(typeof(close_marker) != "undefined") {
		if ((close_position.lat()==start_coords.lat() &&  close_position.lng()==start_coords.lng()) ||  (close_position.lat()==end_coords.lat() &&  close_position.lng()==end_coords.lng()) )
		{
			// V2 close_marker.getIcon().iconAnchor = new GPoint(24,8);
			close_marker.getIcon().anchor = new google.maps.Point(24,8);
		}
		else {
		
		//V2	close_marker.getIcon().iconAnchor = new GPoint(8,8);
			close_marker.getIcon().anchor = new google.maps.Point(8,8);
		}
		close_marker.setPosition(close_position);
		
		
		
		close_marker.setMap(facet_obj.map);
	}
	



	// make a rectangle
	var corners = Array();
	//sw
	corners.push(new google.maps.LatLng(low_lat, low_lng));
	//nw
	corners.push(new google.maps.LatLng(low_lat, high_lng));
	//ne
	corners.push(new google.maps.LatLng(high_lat, high_lng));
	//se
	corners.push(new google.maps.LatLng(high_lat, low_lng));
	//repeat 1(sw) to close
	corners.push(new google.maps.LatLng(low_lat, low_lng));
	// V2
	// V2 var poly = new GPolygon(corners, "#5f6df9", 2, 1, "#5f6df9", 0.2);
	
	
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
	
}


function facet_geo_filter_controlV3(container_div, map)
{
    container_div.style.padding = '5px';

    var container = $("<div id=\"filter_tool_btn_container\"></div>")[0];
    var tool_btn = $(container).append("<div id=\"filter_tool_btn\" style=\"width:27px;height:24px;background-image:url('applications/" + application_name + "/theme/images/geo_filter_tool_btn_deselected.png');position:relative;top:4px;left:-5px;\"></div>")[0];
    container_div.appendChild(container);

    google.maps.event.addDomListener(tool_btn, 'click', function() {
        facet_geo_marker_tool_toggle_callback(map, tool_btn); //FIXME: this function requires the facet_obj as argument, not the map, but we don't have the facet_obj here
    });

    $(container).tooltip({
        delay: 0,
        showURL: false,
        bodyHandler: function() {
            return t("Lägg till område, placera hörn med musklickningar");
        }
    });

    return container;
}


// **********************************************************************************************************************************
/*
* Function: facet_geo_render_data
* 
* Description:
* should assign all close_markers with metadata about the selection
* Make the rectangle blue of if there is observations for the rectangle
* Make the rectangle red if there is not any observations given the actual filter

 need to copy all close marker and new marker with updated information.


* 
* Parameters:
* facet_obj - The facet object to render in.
* xml - The data received from the server to render.
* 
* Returns:
* 
* See also:
* <facet_discrete_render_data>
* <facet_range_render_data>
*/
function facet_geo_render_data(facet_obj, xml) {
    trace_call_arguments(arguments);

	


	var request_id = $(xml).find("request_id").text();
	
	var info_container = $("#"+facet_obj.dom_id).find(".facet_info");
	if (facet_obj.selections.length>0)
	{
			info_container.html((facet_obj.selections.length));
	}
	else
			info_container.html("");

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
var row_id=0;
	var counts_info=new Array(); // store the counts into an array
	$(xml).find("row").each(function() {
		
			row_obj = $(this);
			row_value = row_obj.find("value").text();
			row_name = row_obj.find("name").text();
			direct_counts = row_obj.find("direct_counts").text();
			
			counts_info[row_value]=direct_counts;
			//msg("recording data for row "+row_id);
		
		row_id++;
	});
	// $.dump(counts_info);

	for (var key in facet_obj.geo_marker_pairs)
	{

		
		prev_close_marker=facet_obj.geo_marker_pairs[key].close_marker;
		// get the lat and lng
		//prev_lat=prev_close_marker.getlatLng().lat();
		//prev_lng=prev_close_marker.getlatLng().lng();
		var close_icon=facet_obj.geo_marker_pairs[key].close_marker.getIcon();
		
		var close_position=prev_close_marker.getPosition();

		// create new title

		// check for undefined data which means there is no observation in the rectangle given filters
		// update the style of the polygon
		if (counts_info[key]==undefined || counts_info[key]==0 )
		{
			var title_text=t("Det finns inga observationer för detta område");
			facet_obj.geo_marker_pairs[key].overlay.setOptions({ strokeColor: "#FF0000",strokeWeight: 3,strokeOpacity:0.8}); 
			
		}
		else
		{
			var title_text=t("Det finns !num_obs observationer i detta område", {"!num_obs" : counts_info[key]});

			// V2 facet_obj.geo_marker_pairs[key].overlay.setStrokeStyle({color: "#5f6df9",weight: 3,opacity:0.8}); 
			facet_obj.geo_marker_pairs[key].overlay.setOptions( {strokeColor: "#5f6df9",strokeWeight: 3,strokeOpacity:0.8}); 
			
		}
		
		//var close_marker = new google.maps.Marker(close_position, {icon : close_icon , draggable : false,  title : t("Ta bort detta område från urvalet.")+" "+title_text});

		
		facet_obj.geo_marker_pairs[key].close_marker.setTitle(t("Ta bort detta område från urvalet.")+" "+title_text);
		
	// add the close marker to selection-box
		
		
	}

//	$("#"+facet_obj.dom_id).find(".facet_loading_indicator").html("<img src=\"applications/"+application_name+"/theme/images/loaded.gif\" class=\"facet_load_indicator\" />");
}


function facet_geo_build_xml_for_request(facet_id) {
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	var xml = "";
	
	// refresh selection...
	facet_geo_refresh_selections(facet_obj);
	if(facet_obj.selections.length > 0) {
		for(selection_key in facet_obj.selections) {
			xml += "<selection_group>";
				xml += "<selection>";
			xml += "<selection_type>rectangle_id</selection_type>"; //id of the box-selection
			xml += "<selection_value>"+facet_obj.selections[selection_key].rectangle_id+"</selection_value>";
			xml += "</selection>";
			xml += "<selection>";
			xml += "<selection_type>e1</selection_type>"; //lng
			xml += "<selection_value>"+facet_obj.selections[selection_key].sw.lng+"</selection_value>";
			xml += "</selection>";
			xml += "<selection>";
			xml += "<selection_type>n1</selection_type>"; //lat
			xml += "<selection_value>"+facet_obj.selections[selection_key].sw.lat+"</selection_value>";
			xml += "</selection>";
			xml += "<selection>";
			xml += "<selection_type>e2</selection_type>";  //lng
			xml += "<selection_value>"+facet_obj.selections[selection_key].ne.lng+"</selection_value>";
			xml += "</selection>";
			xml += "<selection>";
			xml += "<selection_type>n2</selection_type>";  //lat
			xml += "<selection_value>"+facet_obj.selections[selection_key].ne.lat+"</selection_value>";
			xml += "</selection>";
			xml += "</selection_group>";
		}
	}
	
	return xml;
}
