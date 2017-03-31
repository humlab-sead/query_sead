/*
file: list_module.js (SEAD)
This file holds the application(SEAD) specific functions for the list module

Client list_xml post:
(see ../../../applications/ships/natural_doc_img/list_element_data_post.jpg)

server list-response:
(see ../../../applications/ships/natural_doc_img/list_element_xml.jpg)

Test


*/


var FUNC_LOG_LIST = false;

/*
function: result_list_info
get the id, title and postition of the result_view
*/
function result_list_info() {
	if(FUNC_LOG || FUNC_LOG_LIST) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	return {
		"id" : "list",
		"title" : t("Tabell"),
		"sort_order_weight" : 3
	};
}

/*
function: result_list_init 
- creates div container using <result_list_create_workspace_containers>
- creates the basic html-table of the list using <result_list_create_list>

*/
function result_list_init() {
	if(FUNC_LOG || FUNC_LOG_LIST) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	result_list_create_workspace_containers();
	result_list_create_list();
        $("#result_controller_outer").show();
	
	if(result_object.view != "list") {
		result_list_hide();
	}
}

function download_data(cache_id,application_name)
{
	// make a ajax request
	// open the file that is being returned.
	result_loading_indicator_set_state("on");
	$.ajax({
		type: "POST",
		url: "/api/report/get_data_table.php?cache_id="+cache_id+"&application_name="+application_name+"&link_only=1",
		cache: false,
		dataType: "text",
		processData: false,
		data: "",
		global: false,
		success: function(data_link){
			

//					
			//console.log("http://" + application_address + application_prefix_path + "server/"+data_link);

			window.location.href ="server/"+data_link;
//			window.open("server/"+data_link);
			$("#download_link").attr("href", "server/"+data_link);
			result_loading_indicator_set_state("off");
		}
	});

//
}
/*
function: result_list_create_workspace_containers
creates the div for the result_list

*/
function result_list_create_workspace_containers() {
	$("#result_workspace_content_container").append("<div id=\"result_list_workspace_container\"></div>");
}
/*
function: result_list_create_list
basic html-table of the list using and append that to the DIV-container
*/
function result_list_create_list() {
	
	var list_html = "";
	list_html += "<table><thead>";
	list_html += "<tr>";
	list_html += "";
	list_html += "</tr>";
	list_html += "</thead>";
	list_html += "<tbody>";
	list_html += "</tbody></table>";
	
	$("#result_list_workspace_container").append(list_html);
}

/*
function: result_list_update
update list with new data by putting the resonse into the DIV as html-text
*/
function result_list_update() {
	if(FUNC_LOG || FUNC_LOG_LIST) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	$("#result_list_workspace_container").html($("response", result_object.result_xml).text());
}


/*
function: result_list_hide
hides the result_list by hiding the div but keeping the content
*/

function result_list_hide() {
	if(FUNC_LOG || FUNC_LOG_LIST) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	$("#result_list_workspace_container").hide();
}

/*
function: result_list_show
shows the result_list
*/
function result_list_show() {
	if(FUNC_LOG || FUNC_LOG_LIST) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	$("#result_list_workspace_container").show();
}
/*
function result_list_load_data
Special function that does not do anything.
*/ 
function result_list_load_data() {
	if(FUNC_LOG || FUNC_LOG_LIST) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
}

// register
result_modules.push('list');
