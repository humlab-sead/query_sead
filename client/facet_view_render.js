
function FacetRenderLocator()
{
    this.Locate = function(_facet_type)
    {
        return new FacetRender(_CreateTypeRenderer(_facet_type));
    }
 
    function _CreateTypeRenderer(_facet_type)
    {
     //  console.log(_facet_type);
    	if(_facet_type == "discrete") {
            return new FacetRender_Discrete();
        }
        if(_facet_type == "range") {
                return new FacetRender_Range();
        }
        if(_facet_type == "geo") {
                return new FacetRender_Geo();
        }
        return new FacetRender_Null();
    }
}  
        
function FacetRender(_type_render)
{   
    this.This = this;
    this.type_renderer = _type_render;
    
    this.create_title_object = function(_facet)
    {
        var title;
        var title_was_truncated;

        var facet_object_translated_title = t(_facet.title);

        if (_facet.title.length > max_title_size) {
            title = facet_object_translated_title.substr(0, max_title_size - 3) + "...";
            //this determines if we should set a tooltip for this title or not
            title_was_truncated = true;
        }
        else {
            title = facet_object_translated_title;
            //this determines if we should set a tooltip for this title or not
            title_was_truncated = false;
        }

        //create a span DOM-object of the title
        var title_obj = $("<span class=\"facet_title\">" + title + "  &nbsp;</span>");

        //if the title was truncated we assign a tooltip which activates on hover over to display the full title
        if (title_was_truncated) {
            facet_add_tooltip(t("Dra för att flytta") + "  " + t(_facet.title), title_obj);
        }
        else
            facet_add_tooltip(t("Dra för att flytta"), title_obj);
    
        return title_obj;
    }
    
    this.create_facet_dom_object = function(_facet)
    {
        var html = "";
        //this is the outer object of the facet, which is a div
        html += "<div id=\"facet_" + _facet.id + "\" class=\"facet_container\">\n";
        //this table holds the main structure of the facet - the cells holds background images and content like title, buttons and the result list
        html += "<table id=\"facet_" + _facet.id + "_table\" class=\"facet_table\"><tbody class=\"facet_table\">\n";

        html += "<tr id=\"facet_" + _facet.id + "_drag\" class=\"facet_top_row\">\n"; //top row start
        html += "<td class=\"facet_top_left_cell\"></td>\n"; //top left cell
        html += "<td class=\"facet_top_middle_cell\"></td>\n"; //top middle cell
        html += "<td class=\"facet_top_right_cell\"></td>\n"; //top right cell
        html += "</tr>\n"; //top row end

        html += "<tr>\n"; //middle row start
        html += "<td class=\"facet_middle_left_cell\"></td>\n"; //middle left cell
        html += "<td id=\"facet_" + _facet.id + "_list_cell\" class=\"facet_middle_middle_cell\"></td>\n"; //middle middle cell, this will contain the item list, which is attached further down
        html += "<td class=\"facet_middle_right_cell\"></td>\n"; //middle right cell
        html += "</tr>"; //middle row end

        html += "<tr>\n"; //bottom row start
        html += "<td class=\"facet_bottom_left_cell\"></td>\n"; //bottom left cell
        html += "<td class=\"facet_bottom_middle_cell\"></td>\n"; //bottom middle cell
        html += "<td class=\"facet_bottom_right_cell\"></td>\n"; //bottom right cell
        html += "</tr>"; //bottom row end

        html += "</tbody></table>\n";
        html += "</div>\n";

        var facet_dom_obj = $(html);

        facet_dom_obj.css("width", _facet.width + "px");
        facet_dom_obj.css("height", _facet.height + "px");

        facet_dom_obj.find(".facet_table").css("width", facet_dom_obj.css("width"));
        facet_dom_obj.find(".facet_table").css("height", facet_dom_obj.css("height"));

        facet_dom_obj.find(".facet_table").find(".facet_table").css('height', "0px");

        return facet_dom_obj;
    };
    
    this.render_header = function(facet_dom_obj, _facet)
    {
        var top_middle_cell = facet_dom_obj.find(".facet_top_middle_cell");

        //setting up containers for title, buttons and search form to top of facet
        top_middle_cell.append(
                "<table class=\"facet_controls_bar\"><tbody class=\"facet_controls_bar\"><tr><td><span id=\"facet_" + _facet.id + "_title\" class=\"facet_title\"></span></td><td class=\"facet_text_search_container\"></td><td style=\"text-align:right;\"><span class=\"facet_aux_control_area\"></span>\n\
                            <span><img class=\"facet_collapse_button\" src=\"client/theme/images/button_minimize.png\" /></span>\n\
                            <span><img class=\"facet_close_button\" src=\"client/theme/images/button_close.png\" /></span></td>\n\
                    </tbody>\n\
                </table>");

        if (this.type_renderer.render_search_container != undefined) {
            this.type_renderer.render_search_container(_facet, top_middle_cell);  
        }
    };
    
}

/*
* Function: facet_view.Render
* 
* Renders a facet and attaches it to the document.
* 
* Parameters:
* facet_object - The facet object to render. This object describes the facet.
* 
* Return:
* The rendered HTML of the facet.
*/
FacetRender.prototype.Render = function(_facet) {

    trace_call_arguments(arguments);

    //Global variables:
    //max_title_size; //defined in includes/client_ui_definitions.js
    //max_facet_list_item_size; //defined in includes/client_ui_definitions.js

    var facet_content_container_obj = this.type_renderer.render_content_container(_facet);
    var facet_dom_obj = this.create_facet_dom_object(_facet);
    this.render_header(facet_dom_obj, _facet);

    var item_list_obj = facet_dom_obj.find(".facet_middle_middle_cell");
    $(item_list_obj).append(facet_content_container_obj);

    var context_id = $(facet_dom_obj).find(".facet_close_button");

    facet_add_tooltip(t('Ta bort filter'), context_id);

    $(facet_dom_obj).find(".facet_close_button").bind("click",
    
    function(f) {
        return function() {
            facet_remove_facet(f);
        }
    }(_facet.id)
            );

    //activating collapse button
    var context_id = $(facet_dom_obj).find(".facet_collapse_button");
    

    facet_add_tooltip(t('minimera/maximera'), context_id);

    $(facet_dom_obj).find(".facet_collapse_button").bind("click",
            function(f) {
                return function()
                {
                    facet_collapse_toggle(f);
                }
            }(_facet.id)
            );

    //positioning facet to the position of its slot
    var slot_object = slot_get_slot_by_id(_facet.slot_id);

    $(facet_dom_obj).css("position", "absolute");
    $(facet_dom_obj).css("left", $("#" + slot_object.dom_id).position().left);
    $(facet_dom_obj).css("top", $("#" + slot_object.dom_id).position().top);

    $(facet_dom_obj).css("width", _facet.width + "px");
    $(facet_dom_obj).css("height", _facet.height + "px");

    $(facet_dom_obj).css("margin", $("#" + slot_object.dom_id).css("margin"));

    _facet.top = $("#" + slot_object.dom_id).position().top;
    _facet.left = $("#" + slot_object.dom_id).position().left;

    //scrolling callback (dynamic loading of data)

  
    
    var title_span_obj = facet_dom_obj.find(".facet_title");
    var _title_object = this.create_title_object(_facet);
    $(title_span_obj).append(_title_object);
    var count_selection_obj = $("<span id=\"facet_span_" + _facet.id + "_facet_count_selection\" class=\"facet_count_selection\"></span>");
    $(title_span_obj).append(count_selection_obj);

    if (this.type_renderer.scroll_event_handler) {
        facet_dom_obj.find(".facet_content_container").bind("scroll",
                //function (e) {
                //    this.type_renderer.scroll_event_handler(e, _facet);
                //}
        
            function (t, f) {
                return function (e) {
                    t.scroll_event_handler(e, f);
                };
            }(this.type_renderer, _facet)
            
        );
    }   

    //set the facet to be draggable and resizable (if activated)
    //the order here is important - we need to set the facet to be draggable and resizable before we set its initial position and size

    $(facet_dom_obj).draggable({handle: "#facet_" + _facet.id + "_drag"});
    $(facet_dom_obj).draggable("option", "containment", "#facet_workspace");
    $(facet_dom_obj).draggable("option", "revert", false); // we handle revert ourselves, so disable it here
    $(facet_dom_obj).draggable("option", "zIndex", 100);
    $(facet_dom_obj).draggable("option", "snap", false);

    $(facet_dom_obj).bind('dragstart', function(event, ui) {
        //$(facet_dom_obj).addClass("facet_dragged");
    });


    $(facet_dom_obj).bind('dragstop', function(event, ui) {
        //$(facet_dom_obj).removeClass("facet_dragged");

        //burn in the last transaction to make it be the new permanent setup of the facets
        var layout_changed = layout_save();
        //move all facets to their new permanent slots
        layout_move_facets_to_layout();
        var action_type="selection_change";
        if (layout_changed) {
            
            action_type="layout_change";
            for (key in slot_objects) {
                if (slot_objects[key].facet_id == _facet.id) {
                    current_position = parseInt(slot_objects[key].chain_number);
                }
            }

            // reload all facets with a chain number equal to or higher than the chain number of the facet which was moved, and try to keep selections where possible
            // facet_request_data_for_chain(1, "selection_change");	 //Fredrik says: is this correct?

            facet_request_data_for_chain(current_position, action_type);	 //Fredriks version 2012-08-09

        }
    });

    //finally we add this facet to the workspace
    $("#facet_workspace").append(facet_dom_obj);
    $(facet_dom_obj).show();

}

function FacetRender_Discrete()
{
}

/*
* Function: render_content_container
* 
* Renders the content container of a discrete facet.
* 
* Parameters:
* facet_obj - A facet object.
* 
* Returns:
* The content container as a jQuery object.
* 
* See also:
* <facet_render_content_container_range>
* <facet_render_content_container_map>
*/
FacetRender_Discrete.prototype.render_content_container = function(_facet)
{
    trace_call_arguments(arguments);

    var html = "<div id=\"facet_"+_facet.id+"_content_container\" class=\"facet_content_container\">";
    html += "<table id=\"facet_"+_facet.id+"_item_list_table\" class=\"facet_content_container_table\" border=\"0\"><tbody class=\"facet_content_container_table\"></tbody></table>";
    html += "</div>";
    var content_obj = $(html);
    content_obj.css("height", (_facet.height-(facet_header_height+facet_footer_height))+"px");
    return content_obj;
}

FacetRender_Discrete.prototype.render_search_container = function(_facet, _top_middle_cell)
{
    var facet_search = _top_middle_cell.find(".facet_text_search_container");
    // change to "filter... by text
    var text_input_params="";
    if (filter_by_text=="")
    {
        facet_add_tooltip(t('Gå till position som börjar med...'), facet_search);
        
    }
    else
    {
        facet_add_tooltip(t('Visa endast (markering finns alltid kvar)'), facet_search);
        text_input_params=" value=\"%\"";
    }
    
    
    // % for filter by text
    facet_search.append("<input type=\"text\""+text_input_params+" class=\"facet_text_search_box\"></input>").bind("keyup", function(e) {
        if (e.target.value.length > 0 && e.keyCode=='13') {
            facet_text_search_callback(_facet.id, e.target.value);
        }
    });
}

FacetRender_Discrete.prototype.scroll_event_handler = function(e, _facet)
{
    if (result_object.disable_next_scroll_event) {
        result_object.disable_next_scroll_event = false;
        return;
    }

    //this is basically the size of the facet - the size which determines how many items can be seen at once
    var viewport_size = $("#"+_facet.dom_id).find(".facet_content_container").innerHeight();

    var items_per_viewport = Math.floor(viewport_size / facet_item_row_height);

    var start_row = $("#"+_facet.dom_id).find(".facet_content_container").scrollTop() / facet_item_row_height;
    var end_row = start_row + items_per_viewport;

    start_row = Math.round(start_row);
    end_row = Math.round(end_row) + 1;

    var load_data = false;

    if (start_row > _facet.view_port.start_row + facet_load_items_trigger_threshold) {
        load_data = true;
    }
    if (start_row < _facet.view_port.start_row - facet_load_items_trigger_threshold) {
        load_data = true;
    }

    if (load_data) {
        _facet.view_port.start_row = start_row;
        _facet.view_port.end_row = end_row;

        //add some buffer rows in each direction - just to give the user some ability to scroll without having to instantly start loading new rows
        start_row += -facet_load_items_num;
        end_row += facet_load_items_num;

        if (start_row < 0) {
            start_row = 0;
        }

        var load_obj = {
            "facet_requesting": _facet.id, //the id of the facet requesting this data
            "action_reason": "populate", //reason for update - "populate" with data or "selection_change"
            "facet_cause": _facet.id, //the facet which triggered the update - the facet in which something was selected in case of an selection
            "start_row": start_row,
            "rows_num": end_row - start_row,
            "request_id": ++global_request_id
        };

        facet_load_data(load_obj);
    }
}

function FacetRender_Geo()
{
}

// **********************************************************************************************************************************
/*
 * Function: render_content_container
 * 
 * Description:
 * Renders the content container of a map facet.
 * 
 * Parameters:
 * facet_obj - A facet object.
 * 
 * Returns:
 * content_obj - The content container as a jQuery object.
 * 
 * See also:
 * <facet_render_content_container_range>
 * <facet_render_content_container_discrete>
 */
FacetRender_Geo.prototype.render_content_container = function(_facet)
{
    trace_call_arguments(arguments);

    var map_width = _facet.width - (facet_left_width + facet_right_width);
    var map_height = _facet.height - (facet_header_height + facet_footer_height);

    var html = "<div id=\"facet_" + _facet.id + "_content_container\" class=\"facet_content_container facet_geo_content_container\">";

    html += "<div id=\"geo_canvas\" style=\"width:" + map_width + "px;height:" + map_height + "px;\"></div>";

    html += "</div>";

    var content_obj = $(html);
    var mapOptions = {
        center: new google.maps.LatLng(facet_geo_default_point.lat, facet_geo_default_point.lng),
        zoom: facet_geo_default_zoom,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: false,
        maxZoom: 12,
        streetViewControl: false
    };
    _facet.map = new google.maps.Map($(content_obj).find("#geo_canvas")[0], mapOptions);

    var geo_filter_control_div = document.createElement('div');

    _facet.map.facet_geo_filter_tool = new facet_geo_filter_controlV3(geo_filter_control_div, _facet.map);
    _facet.map.facet_geo_filter_tool.state = false;
    geo_filter_control_div.index = 1;

    _facet.map.controls[google.maps.ControlPosition.TOP_LEFT].push(geo_filter_control_div);

    // map object has a reference to the facet_obect id

    _facet.map.facet_id = _facet.id;

    google.maps.event.trigger(_facet.map, 'resize');
    return content_obj;
}

function FacetRender_Range()
{
}

/*
* Function: render_content_container
* 
* Renders the content container of a range facet.
* 
* Parameters:
* facet_obj - A facet object.
* 
* Returns:
* The content container as a jQuery object.
* 
* See also:
* <facet_render_content_container_discrete>
* <facet_render_content_container_map>
*/
FacetRender_Range.prototype.render_content_container = function(_facet)
{
    trace_call_arguments(arguments);

    var html = "<div id=\"facet_"+_facet.id+"_content_container\" class=\"facet_content_container\" style=\"overflow:hidden;margin:0px;\">";
    html += "<div id=\"facet_"+_facet.id+"_content_container_flash\" class=\"facet_content_container_flash\" style=\"height:"+(_facet.height-(facet_header_height+facet_footer_height+42))+"px; \"></div>"; // adjusted the height to remove 21px of the inner div,so the div has the same height as the flash
    html += "</div>";

    return $(html);
}

function FacetRender_Null()
{
   this.render_content_container = function(_facet)
   {
   }   
}