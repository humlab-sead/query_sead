/*
file: map_module.js (SEAD)
This file holds the application specific functions for the map component (SEAD)

Client map_xml post:
(see ../../../applications/ships/natural_doc_img/map_element_data_post.jpg)

server xml-response:
(see ../../../applications/ships/natural_doc_img/result_xml_response.jpg)


*/

var g_polygons= Array();
var g_markers= Array();
var map_table_name ;
var	 join_table_name;

var FUNC_LOG_MAP = false;

var infowindow = new google.maps.InfoWindow(
  { 
    size: new google.maps.Size(250,250)
  });


/*
function: result_map_info
get the id and title and position

*/
function result_map_info() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	return {
		"id" : "map",
		"title" : t("Karta"),
		"sort_order_weight" : 1
	};
}

/* 
function: result_map_init
runs the following function unless result_view is not the map, then the map is hidden.
<result_map_create_workspace_containers>
<result_map_create_controls>
<result_map_create_timebar>
<result_map_create_map>
*/
function result_map_init() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	result_map_create_workspace_containers();
	result_map_create_controls();
//	result_map_create_timebar();
	result_map_create_map();
        
        $("#result_control").find("input[name=\"result_variable_aggregation_type\"]").each(function() {
           facet_add_tooltip($(this).next().text(), this); 

        });
        $("#result_control").find(".result_variable_aggregation_type_text").each(function() {
            facet_add_tooltip($(this).text(), this); 


        });

        
	if(result_object.view != "map") {
		result_map_hide();
	}
        
}

function result_map_user_view_state_activate()
{

	result_object.map_obj.setCenter(new  google.maps.LatLng(view_state.result_map_center_lat, view_state.result_map_center_lng));


	result_object.map_obj.setZoom(view_state.result_map_zoom_level);

}
function result_map_user_view_state_unpack()
{

	
	view_state.result_map_bbox=$("map_bbox", view_state_xml).text();
	view_state.result_map_center_lat= parseFloat($("map_center_lat", view_state_xml).text());
	view_state.result_map_center_lng= parseFloat($("map_center_lng", view_state_xml).text());
	view_state.result_map_zoom_level= parseInt($("map_zoom_level", view_state_xml).text());

	return "";
	
	
}


/* 
function: result_map_show
show map view by showing the div and also setting the state of the result_time_bar
*/

function result_map_show() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	$("#result_map_workspace_container").show();
	

}

/*
function: result_map_hide
hides the map by hiding the div
*/
//hide/sleep (when switching to another result module view)
function result_map_hide() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	$("#result_map_workspace_container").hide();
}

/*
function result_map_create_workspace_containers
creates the container for the time_bar, map, and area map_controls
Add function for clicking backward and forward in time and setting the year by using a textbox below the time-bar
*/
function result_map_create_workspace_containers() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	
	var html = "<table style=\"width:100%;height:100%;\"><tbody>";
//	html += "<tr><td colspan=\"2\" style=\"height:100px;\"><div id=\"map_timebar_container\"><div id=\"map_timebar\"></div><div id=\"map_timebar_controls\"></div></div></td></tr>";
	html += "<tr>";
	html += "<td style=\"vertical-align:top\"><div id=\"map_display\" style=\"background-color:white;\"></div></td>";
	html += "<td style=\"vertical-align:top;width:40px;\"><div id=\"map_controls\"></div></td>";
	html += "</tr>";
	html += "</tbody></table>";
	
	$("#result_workspace_content_container").append("<div id=\"result_map_workspace_container\" style=\"width:100%;height:100%;\"></div>");
	$("#result_map_workspace_container").append($(html));
        

	
	
	
	var dim = result_map_get_map_standard_dimensions();
	
	$("#map_display").css("width", dim.width+"px");
	$("#map_display").css("height", dim.height+"px");
}



/*
function result_map_get_map_standard_dimensions
get the height and width of the max size of the parent of DIV "map_display" DIV - 10 px
*/
function result_map_get_map_standard_dimensions() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var map_display_height = parseInt($("#map_display").parent().css("height"))-10;
	var map_display_width = parseInt($("#map_display").parent().css("width"))-10;
	
	return {
		"height" : map_display_height,
		"width" : map_display_width
	};
}

function createMarker(point, letter, title_text,info) {


    var marker = new google.maps.Marker({
              position: point,
              map: result_object.map_obj,
    			anchorPoint: new google.maps.Point(0,-18),
              icon: {url:"http://www.fredrikpalm.eu/mjcb_icons/Temp1.png",anchor:new google.maps.Point(11,18)},
               title: title_text
            });

 google.maps.event.addListener(marker, 'click', function() {
			result_map_set_infowindow_content( '<B>'+title_text+'</B><BR>'+info);
			infowindow.setPosition(marker.getPosition());
	          infowindow.open(result_object.map_obj);
        });

		 return marker;
        }    


/*
function: plot_points_on_map

*/
function plot_points_on_map(map_data_xml){


for (var key in g_markers)
{
	g_markers[key].setVisible(false) ; // set to invisible
}

var sites = $(map_data_xml).find("points");

	var i = 0;
	sites.children().each(
		function() {
			

			var name = $(this).find("name").text();
			var latitude = $(this).find("latitude").text();
			var longitude = $(this).find("longitude").text();
			var filtered_count = $(this).find("filtered_count").text();
			var un_filtered_count = $(this).find("un_filtered_count").text();
			var id = $(this).find("id").text();// .text()+filtered_count+un_filtered_count;
                        var latlng = new google.maps.LatLng(latitude,longitude);
			
			if (g_markers[id]==undefined )
			{	
				info="latitude: "+latitude+"<BR>";
                                info+="longitude: "+longitude+"<BR>";
                                info+="<a href=\"applications/sead/show_site_details.php?application_name=sead&site_id="+$(this).find("id").text()+"\" target=\"_new\"> "+name+"</A>";//name+"";//<BR>Filter count of samples "+filtered_count +"<BR> Total samples:"+un_filtered_count;
				letter="X";
				g_markers[id]=createMarker(latlng, letter,name , info);;


			}
			else // exist, so set to visible
			{
				if (!g_markers[id].getVisible())
				{
					g_markers[id].setVisible(true);
				}
			
		
			}
	
		}
	);
	
}


  function getTileUrl(tile, zoom) {
// Natural earth

        
   var urlTemplate="http://geoserver.humlab.umu.se:8080/geoserver/gwc/service/gmaps?layers=sead:ne_4326&zoom="+zoom+"&x="+tile.x+"&y="+tile.y+"&format=image/png"
        
return urlTemplate;
  } 
//http://geoserver.humlab.umu.se:8080/geoserver/gwc/demo/sead:ne_4326?gridSet=EPSG:4326&format=image/png

  function getTileUrl2(coord, zoom) {
// Natural earth

    var proj = result_object.map_obj.getProjection();
    var zfactor = Math.pow(2, zoom);
    // get Long Lat coordinates
    var top = proj.fromPointToLatLng(new google.maps.Point(coord.x * 256 / zfactor, coord.y * 256 / zfactor));
    var bot = proj.fromPointToLatLng(new google.maps.Point((coord.x + 1) * 256 / zfactor, (coord.y + 1) * 256 / zfactor));

    //corrections for the slight shift of the SLP (mapserver)
    var deltaX =0;//; 0.0013;
    var deltaY =0;//0.00058;

    //create the Bounding box string
    var bbox =     (top.lng() + deltaX) + "," +
                   (bot.lat() + deltaY) + "," +
                   (bot.lng() + deltaX) + "," +
                   (top.lat() + deltaY);

    //base WMS URL
    var url = "http://geoserver.humlab.umu.se:8080/geoserver/sead/wms?";
    url += "&REQUEST=GetMap"; //WMS operation 
    url += "&SERVICE=WMS";    //WMS service 
    url += "&VERSION=1.1.1";  //WMS version   
    url += "&LAYERS=" + "sead:ne_4326"; //WMS layers
    url += "&FORMAT=image/png" ; //WMS format
    url += "&BGCOLOR=0xFFFFFF";  
    url += "&TRANSPARENT=TRUE";
    url += "&SRS=EPSG:4326";     //set WGS84 
    url += "&BBOX=" + bbox;      // set bounding box
    url += "&WIDTH=256";         //tile size in google
    url += "&HEIGHT=256";

   return url;                 // return UR

  }

/*
function: result_map_update
update view with new data
using functions 
- <result_map_update_controls>

*/

function result_map_update() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	

 plot_points_on_map(result_object.result_xml);
 

}


/*
function result_map_create_controls
creates html for the form for controlling the map
ie selection of result variables, colors, classficiation type, number of classes, opacity controls

*/
function result_map_create_controls() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	

	
}

/*
function: result_map_update_controls
populate the map control DIV with content from the result-XML from server.
- variables to be selected
- links to download maps
- classification_mode
*/
function result_map_update_controls() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	
}


function result_map_set_infowindow_content(content)
{		
		if (content.length>25)  // approx the amount of data before one needs a scrollable infowindow
		{
					var max_height=100;
		}

		infowindow.setContent( '<Div style=\"overflow:auto;height:'+max_height+'px; width:250px\">'+content+'</DIV>');
}




/*
function result_map_create_map
Creates the google map and the tile overlay with demographic statistical visualization
Get the argument for the tile-request from the server xml-request from the load_result.php
ie map-table, join_table and map_file_path.


*/
function result_map_create_map() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	  var wmsOptions = {
            alt: "MapServer Layer",
            getTileUrl: getTileUrl,
            isPng: false,
            maxZoom: 17,
            minZoom: 1,
            name: "MapServer Layer",
            tileSize: new google.maps.Size(256, 256)
        };
 
 
        //Creating the object to create the ImageMapType that will call the WMS Layer Options. 
        
       wmsMapType = new google.maps.ImageMapType(wmsOptions);

	 var mapOptions = {
          center: new google.maps.LatLng(45.397, 15.644),
          zoom: 4,
          mapTypeId: 'natural_earth',
			  mapTypeControl:false,
			  maxZoom:10,
			  streetViewControl:false
        };
        var map = new google.maps.Map(document.getElementById("map_display"),
            mapOptions);

	 map.mapTypes.set('natural_earth', wmsMapType);


	result_object.map_obj = map;
	
}


/*
function: result_map_data_loading_params
contructs the map_xml-document for the load_result operation
getting the parameters from the map_controls form (indirectly)

*/
function result_map_data_loading_params() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	//<map_classification_type></map_classification_type> == "equal_intervals" OR "percentiles"
	
	var map_xml = "<data_post><map_year>"+result_object.time_bar_current+"</map_year><map_result_item>"+result_object.map_selected_variable+"</map_result_item><map_number_of_intervals>"+result_object.map_number_of_intervals+"</map_number_of_intervals><map_classification_type>"+result_object.map_classification_mode+"</map_classification_type><map_color_scheme>"+result_object.map_color_scheme+"</map_color_scheme></data_post>";
	
	var result = Array();
	result["map_xml"] = map_xml;
	
	return result;
}


/* function  result_map_post_maximize
Maximize the map area by setting the css-parameter height and width to be as large as possible
*/
function result_map_post_maximize() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	if(result_object.view == "map") {
		
		var maximized_map_width = $("#map_display").parent().width();
		var maximized_map_height = $("#map_display").parent().height();
		
		$("#map_display").css("width", maximized_map_width);
		$("#map_display").css("height", maximized_map_height);
		
		google.maps.event.trigger(result_object.map_obj , 'resize');
	}
}
/*
function result_map_post_minimize
Restore the map area to normal size
*/
function result_map_post_minimize() {
	if(FUNC_LOG || FUNC_LOG_MAP) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	if(result_object.view == "map") {
		
		//this is not a mistake - we first set size to 100% and then to pixel-dims, FF needs this
		$("#map_display").css("width", "100%");
		$("#map_display").css("height", "100%");
		
		var dim = result_map_get_map_standard_dimensions();
		$("#map_display").css("width", dim.width+"px");
		$("#map_display").css("height", dim.height+"px");
			google.maps.event.trigger(result_object.map_obj , 'resize');
	}
}


/* 
function: result_map_post_switch_view
// check resize and  remove all unneeded map-types
// fix of half map sometimes
*/
function result_map_post_switch_view()
{
		google.maps.event.trigger(result_object.map_obj , 'resize');
      
}




