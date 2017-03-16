
var facet_view = null;
var facet_presenter = null;

var slot_objects = Array();
var slot_objects_tmp = Array();

var call_view_state = false;

var DEBUG = true;

var facet_last_range_data = [];

var default_selected_result_variable_x_axis = "AR";
var default_selected_result_variable_y_axis = "BEF_TOT";

var highest_year_value = 1860;
var lowest_year_value = 1749;

//something to do with the result map overlay
var WMS_URL_GAL="server/getimage_bet_fig.php?";

var system = {
	"do_not_initiate_result_load_data" : true
}
