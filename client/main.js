/*
file: main.js
Handles the initiation of js-modules in the gui.

   see also:
 <user_view_state_init>

 <facet_control_init>

 <layout_spawn_default_facets>

 <slot_init_droppable_all>

 <result_init>

 <info_area_init>

 <user_render_user_area>
*/


FUNC_LOG = false;
DEBUG_FACET_MOVE = false;
TRANSACTION_DEBUG = false;
event_queue = Array();
preview_moving = false;
transaction_id = 0;
var date_obj = new Date();
var run_start_time = date_obj.getTime();
var global_request_id = 0;
var last_chain_final_request_id = 0;
var global_result_request_id = 0;
var map = null;
var g_markers= new Array();


/*
function: main_init
This function is runned when starting the application with a timeout of 300ms (to get IE8 to work)
*/
function main_init()
{

    	facet_view = new FacetView();
        facet_presenter = new FacetPresenter(facet_view);
        
        if (use_web_socket=="yes")
        {    
            facet_notifier= NotifyServiceLocator.locateFacetService();
        }
        else
        {
             facet_notifier= NotifyServiceLocator.locateLocalFacetService();
        }
        //facet_notifier.local_notify_service=new NotifyService();
        result_notifier=NotifyServiceLocator.locateResultService();
        result_object.global_facet_xml_id="";
        result_notifier.listenTo("facet-change",
         function(o, data) {
           result_object.global_facet_xml = data;
           result_load_data();
         }
        );
        
        user_view_state_init();
	
	//init facet control
	facet_control_init();
	//create default facets
	layout_spawn_default_facets();
	//setup droppable functionality on slots with facets
	slot_init_droppable_all();

	
	//init result controls for switching views and selecting variables etc
	result_init(); // moved to user.js so the correct language is used when rendering control
	//init links/buttons in info area
	info_area_init();
	
	user_get_view_state(current_view_state_id);

	user_render_user_area(currentUser);
	

}


$(document).ready(function() {
	setTimeout("main_init();", 500)
	
});

