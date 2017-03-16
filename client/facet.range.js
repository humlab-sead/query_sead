/*
* File: facet.range.js
* 
* Contains functions specific of the range type of facet.
* 
*/


var FACET_RANGE_FUNC_LOG = false;


/*
* Function: facet_range_render_data
* 
* Renders the data for a range facet.
* 
* Parameters:
* facet_obj - The range facet object to do the rendering in.
* xml - The data received from the server which we're going to render.
* 
* See also:
* <facet_discrete_render_data>
* <facet_geo_render_data>
*/
function facet_range_render_data(facet_obj, xml) {
    
        trace_call_arguments(arguments);
	
	var interval;
	var inter_lower;
	var inter_upper;
	var label_point;
	var inter_direct_counts;
	
	var value_type;
	
	
	facet_obj.contents = [];
	
	var series = [];
	
	series = {};
	series.data = [];
	
	var max_inter_count_value=Number.MIN_VALUE;
	
	var span_size = parseFloat($(xml).find("range_interval").text());
	
	var range_limits = facet_range_get_current_values(facet_obj.id);
	
	if(range_limits === false) {
		range_limits = [];
		range_limits['lower'] = parseInt(facet_obj.facet_range_min_value);
		range_limits['upper'] = parseInt(facet_obj.facet_range_max_value);
	}
	
	//msg("Draw span: "+range_limits['lower']+" - "+range_limits['upper']);
	
	var spans_to_draw = Math.ceil((range_limits['upper'] - range_limits['lower']) / span_size);
	
	if(spans_to_draw < 0) {
		spans_to_draw *= -1;
	}
	if(spans_to_draw == 0) {
		spans_to_draw = 1;
	}

	
	//msg("Number of spans to draw (span size: "+span_size+"): "+spans_to_draw);
	
	var spans = [];
	
	var range_lower = range_limits['lower'];
	var range_upper = range_limits['lower'] + span_size;
	
	for(spans_to_draw; spans_to_draw > 0; spans_to_draw--) {
		//msg("Spans to draw: "+spans_to_draw);
		//msg("Now drawing "+range_lower+" - "+range_upper);
		spans.push({
			"lower": range_lower,
			"upper": range_upper,
			"value": null
		});
		
		
		range_lower += span_size;
		range_upper += span_size;
	}
	
	// get the max counts for the ranges
	$(xml).find("row").each(function() {
	interval = $(this);
	inter_direct_counts = interval.find("direct_counts").text();
		inter_direct_counts = parseInt(inter_direct_counts);
		if (inter_direct_counts>max_inter_count_value)
		{
			max_inter_count_value=inter_direct_counts;
		}
	});

	
	$(xml).find("row").each(function() {
		interval = $(this);
		
		interval.find("value_item").each(function() {
			value_type = $(this).find("value_type").text();
			if(value_type == "lower") {
				inter_lower = $(this).find("value").text();
				inter_lower = parseInt(inter_lower);
			}
			else if(value_type == "upper") {
				inter_upper = $(this).find("value").text();
				inter_upper = parseInt(inter_upper);
			}
		});
		
		inter_direct_counts = interval.find("direct_counts").text();
		inter_direct_counts = parseInt(inter_direct_counts);
		
		label_point=interval.find("name").text();
		
		
		//msg("Looking for inter-lower: "+inter_lower);
		for(var key in spans) {
			if(spans[key]['lower'] == inter_lower) {
				//msg(inter_direct_counts);
				//msg("HIT");
				spans[key]['value_original'] = inter_direct_counts;
				var percent=(inter_direct_counts/max_inter_count_value)*100
				if (percent<2 &&   inter_direct_counts>0)
				{
					inter_direct_counts=Math.ceil(0.02*max_inter_count_value);
				}
				spans[key]['value'] = inter_direct_counts;
				spans[key]['label_point'] = label_point;
			}
		}
	});
	
	for(var key in spans) {
		
		var point_color = "";
		if(spans[key]['value'] == null) {
			point_color = "#FF0000";
		}
		else {
			point_color = "#0000FF";
		}
		
		var dataset = {
			name: spans[key]['label_point'],
			xLabel: t("Startvärde"),
			yLabel: t("Antal observationer"),
			count_with_values: spans[key]['value_original'],
			x: spans[key]['lower'] + (spans[key]['upper'] - spans[key]['lower']) / 2,
			y: spans[key]['value'],
			marker: {
				enabled: true,
				radius: 4,
				lineWidth: 0,
				symbol: "circle",
				fillColor: point_color
			}
		}
		
		series.data.push(dataset);
		
	}
	
	series.borderRadius = 0;
	series.borderWidth = 0;
	series.lineWidth = 0;
	if (is_browser_ie8)
	{
			
			series.animation = false;
	}
	series.shadow = false;

	series.groupPadding = 0.0;
	series.pointPadding = 0.0;
	series.pointWidth = Math.ceil(200 / series.data.length);
	if (series.pointWidth==1) 
                series.pointWidth=2;
     //
     //            console.log(series.pointWidth);
	
	
	
	
	if(!facet_obj.value_bar_obj) {
		facet_range_render_contents(facet_obj, range_limits['upper'], range_limits['lower']);
	}
	
	facet_obj.system_induced_slider_update = true;
	//facet_range_set_values(facet_obj.id, parseInt(highest_value), parseInt(lowest_value), true);
	
	
	while(facet_obj.chart_obj.series.length > 0) {
		facet_obj.chart_obj.series[0].remove(false);
	}
	
	facet_obj.chart_obj.addSeries(series, false);
	
	
	facet_obj.chart_obj.redraw();
	facet_obj.chart_obj.hideLoading();
	
	facet_obj.render_complete = true;
	
}

/*
* Function: facet_range_render_contents
* 
* 
* See also:
*
*/
function facet_range_render_contents(facet_obj, highest_value, lowest_value) {
	if(FUNC_LOG || FACET_RANGE_FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	
	var value_bar = facet_render_value_bar(facet_obj, highest_value, lowest_value);
	facet_obj.value_bar_obj = value_bar;
	
	var html_table = "<table style=\"width:100%;\"><tr><td colspan=\"3\"><div id=\""+facet_obj.id+"_slider_container\" style=\"width:100%;align:center\"></div></td></tr><tr><td style=\"width:25%;font-size:10px;color:#666;\"><input type=\"text\" id=\"facet_"+facet_obj.id+"_content_container_lower_text_box\" name=\"lower\" class=\"facet_range_limits_box\" value=\""+lowest_value+"\">:min</td>";
	
	html_table += "<td></td><td style=\"width:25%;text-align:right;font-size:10px;color:#666;\">max:<input type=\"text\"  id=\"facet_"+facet_obj.id+"_content_container_upper_text_box\" name=\"upper\" class=\"facet_range_limits_box\" value=\""+highest_value+"\"></td> </tr></table>";
	
	var html = "<div id=\"facet_"+facet_obj.id+"_content_container_textboxes\" style =\"height: 42px;width:100%;\">"+html_table+"</div>"; // 2011-03-14 adjusted heigh to 21px instead of 16px due to the two divs that should co-exist
	
	var contents = $(html);
	
	var middle_value=(lowest_value+ highest_value)/2;
	result_object.middle_handle_value=middle_value;
//	console.log(middle_value);
	$("#"+facet_obj.id+"_slider_container", contents).slider({

		
		values : [lowest_value,middle_value,highest_value],
		max : highest_value,
		min : lowest_value,
		slide : function(event, ui) {

			 var delta_value=parseInt(ui.values[1]-result_object.middle_handle_value); // how much has the middle handle moved
			result_object.middle_handle_value=parseInt(ui.values[1]); // store the new as a global to get the previous value before stop.
		    $( "#" + facet_obj.id +  "_slider_container" ).slider( "values" , 0 , ui.values[0]+delta_value);
		   $( "#" + facet_obj.id +  "_slider_container" ).slider( "values" , 2 , ui.values[2]+delta_value);


			   if (parseInt(ui.values[0])>=parseInt(ui.values[1]))
			{
			  $( "#" + facet_obj.id +  "_slider_container" ).slider( "values" , 0 , ui.values[1]-1);
			}
		
		if (parseInt(ui.values[2])<=parseInt(ui.values[1]))
			{
			   $( "#" + facet_obj.id +  "_slider_container" ).slider( "values" , 2 , ui.values[1]+1);
			}
		if (parseInt(ui.values[1])<1)
			{
			   $( "#" + facet_obj.id +  "_slider_container" ).slider( "values" , 1 , 1);
			}
			$("#facet_"+facet_obj.id+"_content_container_lower_text_box").attr("value", ui.values[0]);
			$("#facet_"+facet_obj.id+"_content_container_upper_text_box").attr("value", ui.values[2]);

				facet_range_update_slider_background(facet_obj.id);
		//	facet_range_update_slider_background(facet_obj.id)
		},
		stop: function(event, ui) {  
	

		   },
		change : function(event, ui) {


			facet_range_update_value_bar_from_textboxes(facet_obj.id);
			facet_range_update_slider_background(facet_obj.id);
			var middle_value=Math.round((ui.values[0]+ ui.values[2])/2);
			//console.log(ui.values[1]+ ' sss'  + middle_value);
			if (ui.values[1]!=middle_value)
			{
				
			//	ui.values[1]=middle_value;
			 $( "#" + facet_obj.id +  "_slider_container" ).slider( "values" , 1 , middle_value);

			}
			

		}
	});
	
	//$("#"+facet_obj.dom_id).find(".facet_content_container").html("");
	$("#facet_"+facet_obj.id+"_content_container_textboxes").remove();
	$("#"+facet_obj.dom_id).find(".facet_content_container").append(contents);
	
	
	$("#facet_"+facet_obj.id+"_content_container_upper_text_box").bind("change", function() {
		$("#"+facet_obj.id+"_slider_container").slider("values", 2, $(this).val());
	});
	
	$("#facet_"+facet_obj.id+"_content_container_lower_text_box").bind("change", function() {
		$("#"+facet_obj.id+"_slider_container").slider("values", 0, $(this).val());
	});

    var handle = $("#" + facet_obj.id +  "_slider_container"+"  A.ui-slider-handle");        
    handle.eq(0).addClass('start-handle');        
   handle.eq(1).addClass('middle-handle');
   handle.eq(2).addClass('end-handle');

//	facet_range_update_slider_background(facet_obj.id);

//	var middle= ($("#duration_slider_container").slider("values", 0)+ $("#duration_slider_container").slider("values", 2))/2;
//	$("#duration_slider_container").slider("values", 1, middle);

	return;
}

function facet_range_update_slider_background(facet_id)
{
$('#' + facet_id+'_slider_container .slide-back').remove();
        $($('#' + facet_id+'_slider_container a').get().reverse()).each(function(i) {
            var bg = '#fff';
            if(i == 0) {
                bg = '#003399';
            } else if(i == 1) {
                bg = '#003399';
            } else if(i == 2) {
                bg = '#fff';
            } 
            //console.log(i+$(this).offset().left - 30);
            
            $('#' + facet_id + '_slider_container').append($('<div></div>').addClass('slide-back').width($(this).offset().left-30 ).css('background', bg));
        });


}
/*
* Function: facet_range_populate
* 
* Helper function for facet_render_data_range which inserts the values/samples of a range facet. This was lifted out from the main function because we need to make sure that the flash object is properly drawn and ready before we send data to it.
* 
* See also:
* <facet_render_data_range>
*/
function facet_range_populate(facet_id) {
	if(FUNC_LOG || FACET_RANGE_FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	//check if this facet still exists since it may have been closed during the timeout, if it doesn't exist then facet_presenter.facet_list.facet_get_facet_by_id should return false
	if(facet_obj == false) {
		msg("No facet obj from id "+facet_id);
		return;
	}
	
	if(facet_obj.value_bar_initialized == false) {
		msg("Waiting for value bar to init...");
		setTimeout("facet_range_populate('"+facet_id+"');", 100);
		return;
	}
	

	
	facet_reload_facet_objects_tmp();

}

/*
* Function: facet_range_update_value_bar_from_textboxes
* 
* 
* See also:
*
*/
function facet_range_update_value_bar_from_textboxes(facet_id) {
	if(FUNC_LOG || FACET_RANGE_FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	
	
	var text_area_lower_box="facet_"+facet_id+"_content_container_lower_text_box";
	var text_area_upper_box="facet_"+facet_id+"_content_container_upper_text_box";
	
	var lower_value = parseFloat($("#"+ text_area_lower_box).val(),10);
	var upper_value = parseFloat($("#"+ text_area_upper_box).val(),10);
	
	var 	current_value	=(lower_value+upper_value)/2;

	if (isNaN(lower_value))
	{
		alert(t("Värdet måste vara ett heltal"));
		return false;
	}
	
	if (isNaN(upper_value))
	{
		alert(t("Värdet måste vara ett heltal"));
		return false;
	}
	
	if (lower_value>upper_value)
	{
		alert(facet_id+ "  "+t("Det lägre värdet måste vara lägre än över värdet"));
		//alert("Det nedre gränsen ska vara lägre än den övre gränsen");
		return false;
		
	}
	
	var max_value = 999999;
	var min_value = -999999;
	
	if (lower_value > max_value || lower_value < min_value) {
		alert(t("Värdet är för litet."));
		return false;
	}
	if (upper_value > max_value || upper_value < min_value) {
		alert(t("Värdet är för stort."));
		return false;
	}
	
	var state = {
		start: lower_value,
		end: upper_value,
		current: current_value
	};
	//value_bar.broadcast('timebar.setState', state);
	facet_range_changed_callback(facet_id, state);

}


/*
* Function: facet_render_value_bar
* 
* Renders the value bar of a range facet. This function needs to be called AFTER the range facet has been rendered and attached to the document, since it will look for a certain DOM element to attach itself to.
* 
* Parameters:
* facet_obj - The facet object to render the value bar in.
* 
* Returns:
* The value bar object.
* 
* See also:
*/
function facet_render_value_bar(facet_obj, highest_value, lowest_value) {
	if(FUNC_LOG || FACET_RANGE_FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	//msg("facet_render_value_bar "+facet_obj.id);
	
	var loaded_data_exists = false;
	
	var settings = { };
	
	
	
	var default_series_type = "column";
	if(loaded_data_exists) {
		default_series_type = $(result_object.result_xml).find("diagram_type").text();
	}
	
	settings.chart = {
		renderTo: "facet_"+facet_obj.id+"_content_container_flash",
   //     zoomType: 'x',
		defaultSeriesType: default_series_type
	};
	
	
	
	var settings_title_text = "";
	if(loaded_data_exists) {
		settings_title_text = $(result_object.result_xml).find("title").text();
	}
	
	settings.title = {
		text: ""
	};
	
	
	var settings_x_axis_title_text = "";
	if(loaded_data_exists) {
		settings_x_axis_title_text = $(result_object.result_xml).find("serie_x_label").first().text();
	}
	settings.xAxis = {
		title: {
			text: settings_x_axis_title_text
		},
		//min: 1749,
		//max: 1860,
		minPadding: 0.0,
		maxPadding: 0.0,
		//maxZoom: 60,
		
		labels: { style: {margin: "0px", padding: "0px", fontSize: "8px"} },
		labels : {
			rotation : 0,
			formatter : function() {
					return Math.round(this.value);
				}
		}
		//tickInterval: 10
	};
	
	settings.yAxis = {
// 		min : 0,
		title: {
			text: ""
		},
		plotLines: [{
			value: 0,
			width: 1,
			color: '#808080'
			//			color: '#808080'
		}]
		//min: 0
	};
	
	
	
	settings.tooltip = {

		formatter: function() {
			return '<b>'+ this.point.name +'</b><br/>'+
				//this.point.xLabel + ': ' + this.x+'<br/>' +
//				this.point.yLabel + ': ' + this.y+'<br/>' +
				t('Antal observationer')+' : '+this.point.count_with_values;
		},
	style:{
			position:'absolute',
			fontSize: '9pt',
			padding: '5px',
			margin:'10px',

	}
	
	};
	
	settings.credits = {
		enabled : false
	};
	
	settings.legend = {
		enabled : false,
		layout: "vertical",
// 		align: "right",
		lineHeight: 16,
		backgroundColor: "#fff",
		x: 0,
		y: -75
	};
	
	//settings.chart.spacingRight = 0;
	//settings.chart.spacingLeft = 0;
	settings.chart.marginLeft = 0;
	settings.chart.marginRight = 0;
	settings.chart.showAxes = true;
	//settings.chart.plotBackgroundColor = "#FF0000";
	//settings.chart.backgroundColor = "#FF0000";
 	settings.colors = ['#003399', '#003399', '#003399', '#003399', '#003399', '#003399', '#003399', '#003399', '#003399'];	

//	settings.colors = ['#FF0000', '#FF0000', '#FF0000', '#FF0000', '#FF0000', '#FF0000', '#FF0000', '#FF0000', '#FF0000'];
	
	settings.exporting = {
		"enabled" : false
	}
	
	facet_obj.chart_obj = new Highcharts.Chart(settings);
	
	
	facet_obj.value_bar_initialized = true;
	
	return facet_obj.chart_obj;
}

/*
* Function: facet_range_changed_callback
* 
* Callback for when the selected range in a range facet is changed.
* 
* Parameters:
* facet_id - The system ID of the facet which has been manipulated.
* state - An object containing the state of the value bar component.
* 
*/
function facet_range_changed_callback(facet_id, state) {
	if(FUNC_LOG || FACET_RANGE_FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	var slot_obj = slot_get_slot_by_id(facet_obj.slot_id);
	
	var lowest_value = state.start;
	var highest_value = state.end;
	
	
	facet_obj.selections.start = parseFloat(state.start);
	facet_obj.selections.end = parseFloat(state.end);
	
	
	facet_obj.selections_ready = true;
	
	facet_request_data_for_chain(slot_obj.chain_number, "selection_change");
}

function facet_range_get_current_values(facet_id) {
	if(FUNC_LOG || FACET_RANGE_FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var text_area_lower_box = "#facet_"+facet_id+"_content_container_lower_text_box";
	var text_area_upper_box = "#facet_"+facet_id+"_content_container_upper_text_box";
	var lower = parseInt($(text_area_lower_box).val());
	var upper = parseInt($(text_area_upper_box).val());
	
	if(isNaN(lower) || isNaN(upper)) {
		return false;
	}
	
	var values = [];
	values["upper"] = upper;
	values["lower"] = lower;
	
	return values;
}


function facet_range_set_values(facet_id, upper, lower, update_slider) {
	if(FUNC_LOG || FACET_RANGE_FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var text_area_lower_box = "#facet_"+facet_id+"_content_container_lower_text_box";
	var text_area_upper_box = "#facet_"+facet_id+"_content_container_upper_text_box";
	
	$(text_area_lower_box).val(lower);
	$(text_area_upper_box).val(upper);
}

function facet_range_build_xml_for_request(facet_id) {
	if(FUNC_LOG || FACET_RANGE_FUNC_LOG) {
		var ownName = arguments.callee.toString();
		ownName = ownName.substr('function '.length);
		ownName = ownName.substr(0, ownName.indexOf('('));
		msg(util_elapsed_time()+" : "+ownName);
	}
	
	var facet_obj = facet_presenter.facet_list.facet_get_facet_by_id(facet_id);
	
	var xml = "";
	if(facet_obj.selections.end != -1) {
		xml += "<selection_group>";
		xml += "<selection>";
		xml += "<selection_type>lower</selection_type>";
		xml += "<selection_value>"+facet_obj.selections.start+"</selection_value>";
		xml += "</selection>";
		xml += "<selection>";
		xml += "<selection_type>upper</selection_type>";
		xml += "<selection_value>"+facet_obj.selections.end+"</selection_value>";
		xml += "</selection>";
		xml += "</selection_group>";
	}
	
	//msg("requesting range: "+facet_obj.selections.start+" - "+facet_obj.selections.end);
	
	return xml;
}

