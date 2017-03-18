/*
* file: util.js
*/

var reported_phrases = [];
var reported_phrases_ticker = 0;


// **********************************************************************************************************************************
/*
   Function: util_render_generic_box

   Description:

   Parameters: 
   box_obj -

*/


function util_render_generic_box(box_obj) {
	
	box_obj =  {
		"id" : dom_id,
		"title" : title,
		"content" : html,
		"exclusive" : false
	};
	
}


function is_browser_ie8()
{
	var browser_info=Array();
	var counter=0;
	jQuery.each(jQuery.browser, function(i, val) {
		browser_info[counter]=i;
		counter++;
		browser_info[counter]=val;
		counter++;


        
    });
	if (browser_info[0]=='msie' && browser_info[3]=='8.0')
	{
		return true
	}
	else
	  return false;
}

// **********************************************************************************************************************************
/*
   Function: msg

   Description: Debugging function

   Parameters: 

   Returns:

   see also:
*/


function msg(text, clear, alt_out) {
	
	if(typeof(clear) == "undefined") { clear = false; }
	if(typeof(alt_out) == "undefined") { alt_out = false; }
	
	var outbox_id;
	if(alt_out) {
		outbox_id = "#msg2";
	}
	else {
		outbox_id = "#msg";
	}
	
	if(clear === true) {
		$(outbox_id).text("");
	}
	$(outbox_id).append(text+"<br />");
}

/*
* Function: trace_call_arguments
* 
* Parameters:
*
* Returns:
*
* See also:
*/
function trace_call_arguments(_arguments) {
    if(FUNC_LOG) {
        var _name = _arguments.callee.toString();
        _name = _name.substr('function '.length);
        _name = _name.substr(0, _name.indexOf('('));
        msg(util_elapsed_time()+" : "+_name);
    }
}

// **********************************************************************************************************************************
/*
   Function: soft_alert

   Description: Debugging function

   Parameters: 

   Returns:

   see also:
*/
function soft_alert(msg, duration) {
	
	if(typeof(duration) == "undefined") {
		duration = 3000;
	}
	else {
		duration = parseInt(duration);
	}
	
	var alert_box = $("<div id=\"soft_alert_box\" class=\"soft_alert_box\" style=\"position:fixed; left:"+(parseInt($(window).width()/2)-100)+"px; top:"+parseInt($(window).height()/3)+"px;\">"+msg+"</div>").animate({
		"opacity" : 0.0
	}, duration, "easeInQuad", function() { $("#soft_alert_box").remove(); });
	
	$("body").append(alert_box);
}

function clear_msg_box() {
	$("#msg").text("");
}
// **********************************************************************************************************************************
/*
   Function: print_selections

   Description: Debugging function

   Parameters: 

   Returns:

   see also:
*/
function print_selections() {
	var key;
	var selection_key;
	var out;
	
	//out += "<table><tbody><tr><td style=\"vertical-align:top;font-size:10px;\">";
	
	for(key in facet_presenter.facet_list.facets) {
		/*
		out += "FACET "+facet_presenter.facet_list.facets[key].id+"<br />";
		out += "--------------<br />";
		for(selection_key in facet_presenter.facet_list.facets[key].selections) {
			out += facet_presenter.facet_list.facets[key].selections[selection_key]+"<br />";
		}
		*/
		$.dump(facet_presenter.facet_list.facets[key].selections);
	}
	
	//out += "</td></tr></tbody></table>";
	
	//msg(out, false, false);
	
	
}
// **********************************************************************************************************************************
/*
   Function: print_slots_and_facets

   Description: Debugging function for ...

   Parameters: 
   serial -

   Returns:

   see also:
*/
function print_slots_and_facets(serial) {
	
	var out = "";
	var i = 0;
	var key;
	out += "DATA START "+serial+"<br />";
	out += "<table><tbody><tr><td style=\"vertical-align:top;font-size:10px;\">";
	for(key in facet_presenter.facet_list.facets) {
		var contents_length = 0;
		for(var ck in facet_presenter.facet_list.facets[key].contents) {
			contents_length++;
		}
		out += "FACET (key:"+key+")<br />";
		out += "--------------<br />";
		out += "id: "+facet_presenter.facet_list.facets[key].id+"<br />";
		out += "dom_id: "+facet_presenter.facet_list.facets[key].dom_id+"<br />";
		out += "title: "+facet_presenter.facet_list.facets[key].title+"<br />";
		out += "displayed_in_ui: "+facet_presenter.facet_list.facets[key].displayed_in_ui+"<br />";
		out += "slot_id: "+facet_presenter.facet_list.facets[key].slot_id+"<br />";
		out += "width: "+facet_presenter.facet_list.facets[key].width+"<br />";
		out += "height: "+facet_presenter.facet_list.facets[key].height+"<br />";
		out += "selections: "+facet_presenter.facet_list.facets[key].selections.length+"<br />";
		out += "contents: "+contents_length+"<br />";
		out += "type: "+facet_presenter.facet_list.facets[key].type+"<br />";

		//out += "selection: "+facet_presenter.facet_list.facets[key].selections+"<br />";
					

		if (typeof(facet_presenter.facet_list.facets[key].map)!="undefined")
		{
					out += "map: "+facet_presenter.facet_list.facets[key].map+"<br />";
					out += "facet id of map: "+facet_presenter.facet_list.facets[key].map.facet_id+"<br />";
		}

		out += "--------------<br />";
	}
	
	out += "</td><td style=\"vertical-align:top;font-size:10px;\">";
	
	for(key in slot_objects) {
		out += "SLOT (key:"+key+")<br />";
		out += "--------------<br />";
		out += "id: "+slot_objects[key].id+"<br />";
		out += "dom_id: "+slot_objects[key].dom_id+"<br />";
		out += "facet_id: "+slot_objects[key].facet_id+"<br />";
		out += "width: "+slot_objects[key].width+"<br />";
		out += "height: "+slot_objects[key].height+"<br />";
		//out += "id: "+facet_slots[i].id+"<br />";
		out += "<br />";
		out += "<br />";
		out += "<br />";
		out += "<br />";
		out += "<br />";
		out += "--------------<br />";
		
	}
	
	out += "</td><td style=\"vertical-align:top;font-size:10px;\">";
	for(key in facet_presenter.facet_list.facets_saved_state) {
		out += "FACET TMP<br />";
		out += "--------------<br />";
		out += "id: "+facet_presenter.facet_list.facets_saved_state[key].id+"<br />";
		out += "dom_id: "+facet_presenter.facet_list.facets_saved_state[key].dom_id+"<br />";
		out += "title: "+facet_presenter.facet_list.facets_saved_state[key].title+"<br />";
		out += "displayed_in_ui: "+facet_presenter.facet_list.facets_saved_state[key].displayed_in_ui+"<br />";
		out += "slot_id: "+facet_presenter.facet_list.facets_saved_state[key].slot_id+"<br />";
		out += "width: "+facet_presenter.facet_list.facets_saved_state[key].width+"<br />";
		out += "height: "+facet_presenter.facet_list.facets_saved_state[key].height+"<br />";
		out += "selections: "+facet_presenter.facet_list.facets_saved_state[key].selections.length+"<br />";
		out += "contents: "+facet_presenter.facet_list.facets_saved_state[key].contents.length+"<br />";
		out += "type: "+facet_presenter.facet_list.facets_saved_state[key].type+"<br />";
		if (typeof(facet_presenter.facet_list.facets_saved_state[key].map)!="undefined")
		{
					out += "map: "+facet_presenter.facet_list.facets_saved_state[key].map+"<br />";
					out += "facet id of map: "+facet_presenter.facet_list.facets_saved_state[key].map.facet_id+"<br />";
		}
		out += "--------------<br />";
	}
	
	out += "</td><td style=\"vertical-align:top;font-size:10px;\">";
	
	for(key in slot_objects_tmp) {
		out += "SLOT TMP<br />";
		out += "--------------<br />";
		out += "id: "+slot_objects_tmp[key].id+"<br />";
		out += "dom_id: "+slot_objects_tmp[key].dom_id+"<br />";
		out += "facet_id: "+slot_objects_tmp[key].facet_id+"<br />";
		out += "width: "+slot_objects_tmp[key].width+"<br />";
		out += "height: "+slot_objects_tmp[key].height+"<br />";
		//out += "id: "+facet_slots[i].id+"<br />";
		out += "<br />";
		out += "<br />";
		out += "<br />";
		out += "<br />";
		out += "<br />";
		out += "--------------<br />";
		
	}
	
	out += "</td></tr></tbody></table>";
	out += "DATA END "+serial+"<br />";
	msg(out, false);
}
// **********************************************************************************************************************************
/*
   Function: util_elapsed_time

   Description:

   Parameters: 

   Returns:

   see also:
*/
function util_elapsed_time() {
	var date_obj = new Date();
	return (date_obj.getTime() - run_start_time);
}
// **********************************************************************************************************************************
/*
   Function: util_toggle_debug

   Description:

   Parameters: 

   Returns:

   see also:
*/
function util_toggle_debug() {
	if(FUNC_LOG) {
		FUNC_LOG = false;
	}
	else {
		FUNC_LOG = true;
	}
}
// **********************************************************************************************************************************
/*
   Function: testing

   Description:

   Parameters: 

   Returns:

   see also:
*/
function testing() {
	var state = {
		start: 1750,
		end: 1860,
		current: 1840
	};
	//result_object.time_bar.broadcast('timebar.setState', state);
	result_object.time_bar.broadcast('timebar.clearBarChart');
}
// **********************************************************************************************************************************
/*
   Function: t

   Description: Translates text, labels and so on i the gui depending on the selected language.

   Parameters: 
   text - Example: "This post is called !title and has !num_hits views"
   variables - Example: array('!num_hits' => 3, '!title' => 'Hello')

   Returns:
   translated - 
   text - 

   see also:
*/
function t(text, variables) {

	var translated = text;
	//console.log(arguments.callee.caller.name+" "+text);

	if(client_language == "sv_SE") {
		for(var varkey in variables) {
			translated = text.replace(varkey, variables[varkey]);
		}
		return translated ;
	}
	

	
	//client_language is defined in client_ui_definitions.js
	
	var found_text = false;
	
	for(var key in languages[client_language]) {

		if(key == text) {
			//alert("FOUND TEXT:"+text);
			found_text = true;
			
			if(typeof(variables) != "undefined") {
				
				for(var varkey in variables) {
					
					translated = languages[client_language][key].replace(varkey, variables[varkey]);
					
					//alert(varkey+" "+variables[variables]);
					//translated = languages[lang_key][key];
				}
			}
			else {
				translated = languages[client_language][key];
			}
			
			//if the translation is empty, just return the original string instead
			if(languages[client_language][key] == "") {
				translated = text;
			}
		}
	}
	
	if(found_text == false) {
		report_new_phrase(text);
	}
	
	return translated;
}

// **********************************************************************************************************************************
/*
   Function: report_new_phrase

   Description: Reports an untranslated phrase to the server, so it can be inserted into the translation system and later translated.

   Parameters: 
   text - The phrase
   
   Returns:
   none
   
   see also:
*/
function report_new_phrase(text) {
	
	var pretend = true; // disable lopop
	if(pretend) {
		return;
	}
	
	//var text_xml = escape(text);
	var text_xml = text.replace("&", "__amp__");
	
	//keep track of reported phrases to avoid multiple reports
	
	for(var key in reported_phrases) {
		if(reported_phrases[key] == text_xml) {
			return;
		}
	}
	
	reported_phrases[reported_phrases_ticker++] = text_xml;
	
	var xml_doc = "<phrase><language>"+client_language+"</language><original_phrase><![CDATA["+text_xml+"]]></original_phrase></phrase>";
	
	//report this new phrase to the server
	request = $.ajax({
		type: "POST",
		url: "http://" + application_address + application_prefix_path + "server/language/add_phrase.php?f=language_new_phrase",
		cache: false,
		dataType: "text/html",
		processData: false,
		data: "xml="+xml_doc+"&application_name="+application_name,
		global: false,
		success: function(xml){
			//msg("Reported new phrase to server: "+text);
			// add the phrase to the language_phrases
			// languages["en_GB"]["sv_phrase"]=""; It will be empty so no need to assigned it.
		}
	});
}

// **********************************************************************************************************************************
/*
   Function: array_unique

   Description:

   Parameters: 
   a -

   Returns:
   r - 

   see also:
*/
function array_unique(a)
{
   var r = new Array();
   o:for(var i = 0, n = a.length; i < n; i++)
   {
      for(var x = 0, y = r.length; x < y; x++)
      {
         if(r[x]==a[i]) continue o;
      }
      r[r.length] = a[i];
   }
   return r;
}
// **********************************************************************************************************************************

var Base64 = {
 
	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
// **********************************************************************************************************************************
 /*
   Function: decode

   Description: public method for encoding

   Parameters: 
   input -

   Returns:
   output - 

   see also:
   <utf8_encode>
*/ 

	encode : function (input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = Base64._utf8_encode(input);
 
		while (i < input.length) {
 
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);
 
			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;
 
			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}
 
			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
 
		}
 
		return output;
	},
// **********************************************************************************************************************************
 /*
   Function: decode

   Description: public method for decoding

   Parameters: 
   input -

   Returns:
   output - 

   see also:
   <utf8_decode>
*/
	decode : function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
 
		while (i < input.length) {
 
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));
 
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;
 
			output = output + String.fromCharCode(chr1);
 
			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}
 
		}
 
		output = Base64._utf8_decode(output);
 
		return output;
 
	},
 // **********************************************************************************************************************************
/*
   Function: _utf8_encode

   Description: private method for UTF-8 encoding

   Parameters: 
   string- 

   Returns:
   utftext -
*/
	_utf8_encode : function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";
 
		for (var n = 0; n < string.length; n++) {
 
			var c = string.charCodeAt(n);
 
			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
 		}
 
		return utftext;
	},
 // **********************************************************************************************************************************
/*
   Function: _utf8_decode

   Description: private method for UTF-8 decoding

   Parameters: 
   utftext -

   Returns:
   string -
*/
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;
 
		while ( i < utftext.length ) {
 
			c = utftext.charCodeAt(i);
 
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			} 
		}
 
		return string;
	}
 
}

function cut_string_to_length(string, length) {
	
	var cutting_done = false;
	while(string.length > length) {
		string = string.substr(0, string.length-1);
		cutting_done = true;
	}
	
	if(cutting_done) {
		string += "...";
	}
	
	return string;
}

function is_numeric(input) {
	return (input - 0) == input && input.length > 0;
}


