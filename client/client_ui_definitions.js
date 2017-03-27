/*
File: client_ui_definition.js (SEAD)
Hold globals for the client, both for content and also for sizes in facet, resultarea etc.

Major ones below result_object,view_state


result_object:
result_modules - list of modules in use
result_xml - xml-document from the server
maximized - view maximized for resultarea???
map_view_maximize - flag for handling maximized view of the map

list_view_maximized - flag for handling maximized view of the list
view -  default result view

result_variables - definining which resultvariables should be check from the beginning //have bef_tot active as default
result_control_presentation_format - type of input, radio or checkbox, default is "checkbox"

css_top - parameter for the CSS???
css_left - parameter for the CSS???
map_selected_variable_text - text representation of the selected variable in the map
map_zoom_level - zoom level in google map default is 0,

result_control_header1 - headings for the result variable area default is "<H3> "+"Summeringsnivå"+" </H3>",
result_control_header2 -  headings for the result variable area default is <H3> "+"Resultatvariabler"+" </H3>"




view_state: 
result_maximized - default is false,
result_view -  which view is to be active default is "map",
result_map_zoom_level - zoom level in google map default is 0,
result_map_color_scheme -  default is "color_red" other values are color_green, color_blue
result_map_bbox - bounding box of the map????
facets - populated in <user_view_state_init> in <user.js>
	

*/

//this is the maximum number of characters a facet title may contain. If a title is larger than this, it will be cut down to this value -3 (to make room for 3 dots) and a tooltip will be assigned which will show the full title on hover
var max_title_size = 17;

//var current_view_state_id = 27;

var result_object = {
	"result_modules" : Array(),
	"result_xml" : null,
	"maximized" : false,
	"map_view_maximized" : false,
	"diagram_view_maximized" : false,
	"list_view_maximized" : false,
	"view" : "map", //default result view
	"diagram_x": "year",
	"diagram_y": ["tot_bef"],
	"diagram_y_mode": 1, //Y-axel mode in diagram view - one variable / all geo units (1), or one geo unit / all variables (2)
	//"aggregation_mode": "parish", //parish, county or year
	"result_variables": ["bef_tot"], //have bef_tot active as default
	"map_selected_variable" : "",
	"result_control_presentation_format": "checkbox",
	"time_bar_current" : 1795,
	"time_bar_init_done" : false,
	"disable_next_scroll_event" : false,
	"css_top" : "",
	"css_left" : "",
	"map_selected_variable_text":"",
	"map_zoom_level" : 0,
	"map_color_scheme" : "color_red",
	"map_number_of_intervals" : 7,
	"map_bbox" : null,
	"result_control_header1" :"<H3> "+"Visa"+" </H3>",
	"result_control_header2" :"<H3> "+"Mer info"+" </H3>",
	"double_load" : false,
	"map_classification_mode" : "percentiles",
	"facet_control_orientation":"vertical"
};



//how many series to draw simultaneously at the most on the result diagram
var result_diagram_series_max_draw = 30;


var view_state_xml;
//default view state of the system

var view_state = {
	
	//"facet_default_facets" : ["has_polygon", "geo", "parish"],
	"facets" : [], //populated in user_view_state_init in user.js
	
};


var last_saved_view_state = {
	"id" : -1,
};

//this is the max number of chars an item in a facet list may contain. if the title of an item is larger than this, it will be truncated to this length -3 (to make room for 3 dots) and a tooltip will be assigned
var max_facet_list_item_size = 25;

//max and min sizes of facets
var facet_min_width = 140;
var facet_max_width = 500;

var facet_min_height = 164;
var facet_max_height = 500;

var facet_default_width = 350;
var facet_default_height = 300;

var facet_discrete_default_height = facet_default_height;
var facet_range_default_height = facet_default_height;
var facet_geo_default_height = facet_default_height;

var facet_discrete_default_width = facet_default_width;
var facet_range_default_width = facet_default_width;
var facet_geo_default_width = facet_default_width;

var facet_header_height = 40;
var facet_footer_height = 25;
var facet_left_width = 25;
var facet_right_width = 25;

//can be "vertical" or "horizontal"
var facet_layout_order = "vertical";
var facet_slot_margin_top = "0px";
var facet_slot_margin_bottom = "0px";
var facet_slot_margin_left = "10px";
var facet_slot_margin_right = "0px";

//default center of map in geo facet
var facet_geo_default_point = {
	lat : 63.441270,
	lng : 16.890800
}

var result_map_default_point = {
	lat : 43.441270,
	lng : 3.890800
}

var result_map_default_zoom = 2;
var result_map_default_size = {
	width: 550,
	height: 700
}
//jokk
//start_row: 1134
//rows_num: 84
//total_number_of_rows: 


//total_number_of_rows: 3162


//var result_map_types =  Array (G_SATELLITE_MAP,G_PHYSICAL_MAP);

//default zoom level of geo facet map
var facet_geo_default_zoom = 3;

//this beautifully named variable controls at which distance a selection in the geo facet gets destroyed when one of the markers in that selection is dragged within proximity of the other (this is that proximity)
var geo_facet_marker_destruction_limits_threshold = 14;

//how many rows to load over and under the viewport - this is extra buffered rows which are not shown in the facet until the user starts scrolling up or down. It's just to give the user a more seamless experience by not having to wait for the loading of data as soon as a small scroll occurs. Setting it to a low value will cause the user having to wait for loading of new rows as soon as a small scroll occurs. Setting it to a high value means using up a lot of extra CPU and memory which might cause slowdowns in the browser and cause longer loading times
var facet_load_items_num = 150;

//the number of rows left in the buffer before we start loading new rows
//you'll probably have to experiment with this to get a feel for what it does, but basically: higher number = loading bigger chunks with longer intervals while scrolling, lower number = smaller chunks shorter intervals
//you'll also have to adjust facet_load_items_num according to match this setting, so experiment with both at the same time
var facet_load_items_trigger_threshold = 50;

//the graphical height (in pixels) of each row in a discrete facet
var facet_item_row_height = 18;


var result_diagram_workspace_container_min_width = 800;
var result_diagram_workspace_container_min_height = 20;
var result_diagram_workspace_container_max_width = 800;
var result_diagram_workspace_container_max_height = 10000;

//when result view is diagram, highcharts requires a container of fixed size to render into, and the result_digram_workspace_container is normally not of a fixed size, so this is the fixed size that this container will be given when the diagram is shown
var result_diagram_workspace_container_fixed_width = 800;
var result_diagram_workspace_container_fixed_height = 800;

var button_maximize_url = "client/theme/images/button_max.png";
var button_minimize_url = "client/theme/images/button_minimize.png";
