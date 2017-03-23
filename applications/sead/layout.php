<?php
/*
file: layout.php (SEAD2)
this file defines the html-structure of tables and divs in the interface
This files is included when running the index.php

Nested structure of Divs and tables of the interface.


*/

?>


<div id="title_bar_container">
	<table style="border-collapse:collapse;">
		<tr>
			<td id="title_bar_left_container">
			<div id="title_bar_left"></div>
			</td>
			<td id="title_bar_middle_container">
				<div id="title_bar_middle">
					<span style="color:#ffffff;font-weight:bold;font-size:12px;font-style:italic;">SEAD</span>
					<span style="color:#ffffff;font-size:10px;position:relative;top:-1px;"> - The Strategic Environmental Archaeology Database</span>
					<span id="aux_buttons_container">
						<table style="border-collapse:collapse;position:relative;top:-1px;left:6px;margin:0px;padding:0px;">
						<tbody>
							<tr>
								<td onclick="info_area_open_about('<?=t("http://www.sead.se",$client_language);?>')">
                                                                   
									<span id="about_sead_button"> <?=interface_render_title_button("About SEAD");?></span>
								</td>
								<td onclick="info_area_open_about('<?=t("http://www.sead.se/help",$client_language);?>')">
									<span id="help_button"><?=interface_render_title_button("Help", true);?></span>
								</td>
							</tr>
						</tbody>
						</table>
					</span>
					<span id="view_state_buttons_container">
						<table style="border-collapse:collapse;position:relative;top:-1px;">
							<tbody>
								<tr>
									<td>
										<span id="save_view_state_button"><?=interface_render_title_button("Save view");?></span>
									</td>
									<td>
										<span id="load_view_state_button"><?=interface_render_title_button("Load view");?></span>
									</td>
								</tr>
							</tbody>
						</table>
					</span>
					<span id="language_selection_button_container">
						<table style="border-collapse:collapse;position:relative;top:-1px;">
							<tbody>
								<tr>
									<td>
										
									</td>
								</tr>
							</tbody>
						</table>
					</span>
				</div>
			</td>
			<td id="title_bar_right_container">
				<div id="title_bar_right"></div>
			</td>
		</tr>
	</table>
</DIV>

<div id="vertical_control_container">
	<table style="border-collapse:collapse;">
		<tr>
			<td id="vertical_control_middle_container">
				
							<div id="facet_controller_outer">
							<table class="generic_table">
								<tbody>
									<tr>
										<td class="generic_table_top_left"></td>
										<td class="generic_table_top_middle"></td>
										<td class="generic_table_top_right"></td>
									</tr>
									<tr>
										<td class="generic_table_middle_left"></td>
										<td class="generic_table_middle_middle content_container"><?=$facet_control?></td>
										<td class="generic_table_middle_right"></td>
									</tr>
									<tr>
										<td class="generic_table_bottom_left"></td>
										<td class="generic_table_bottom_middle"></td>
										<td class="generic_table_bottom_right"></td>
									</tr>
								</tbody>
							</table>
						</div>
					
				
			</td>
			
		</tr>
	</table>
</DIV>


<img id="umu_logo" src="applications/sead/theme/images/umu.png" />
<!-- <div id="header"><img id="header_img" src="applications/ships/theme/images/header.png"></img></div> -->
<table>
	<tr>
		<td style="vertical-align:top;">
			<div id="facet_workspace"></div>
		</td>
	
		<td style="vertical-align:top;">
			<div id="info_area"><?=interface_render_info_area2();?></div>
			<div id="status_area_outer">
				<table class="generic_table">
					<tbody>
						<tr>
							<td class="generic_table_top_left"></td>
							<td class="generic_table_top_middle"></td>
							<td class="generic_table_top_right"></td>
						</tr>
						<tr>
							<td class="generic_table_middle_left"></td>
							<td class="generic_table_middle_middle content_container">
								<?=$status_area?></td><td class="generic_table_middle_right">
							</td>
						</tr>
						<tr>
							<td class="generic_table_bottom_left"></td>
							<td class="generic_table_bottom_middle"></td>
							<td class="generic_table_bottom_right"></td>
						</tr>
					</tbody>
				</table>
			</div>
			<div id="result_workspace"><?=$result_workspace?></div>
		</td>
        	<td style="vertical-align:top;">
			
			<div id="result_controller_outer" >
				<table class="generic_table">
					<tbody>
						<tr>
							<td class="generic_table_top_left"></td>
							<td class="generic_table_top_middle"></td>
							<td class="generic_table_top_right"></td>
						</tr>
						<tr>
							<td class="generic_table_middle_left"></td>
							<td class="generic_table_middle_middle content_container"><?=$result_control?></td>
							<td class="generic_table_middle_right"></td>
						</tr>
						<tr>
							<td class="generic_table_bottom_left"></td>
							<td class="generic_table_bottom_middle"></td>
							<td class="generic_table_bottom_right"></td>
						</tr>
<tr>
							<td class="generic_table_top_left"></td>
							<td class="generic_table_top_middle"></td>
							<td class="generic_table_top_right"></td>
						</tr>
						<tr>
							<td class="generic_table_middle_left"></td>
						<td class="generic_table_middle_middle content_container">
						
						<SPAN ID="LOGO_AREA">

						<IMG SRC="applications/sead/theme/images/SeadLogo.jpg" ALT="LOGO_sead">
						<BR>
						An international standard database for environmental archaeology data is under development at the Environmental Archaeology Lab (MAL), in collaboration with HUMlab, at Umeå University, Sweden.
<BR><BR>
					SEAD is financed by The Swedish Research Council and Umeå University Faculty of Humanities and Department of Historical, Philosophical and Religious Studies.
                                         <BR><BR>
                                     
        
                                        <BR>
        <BR>
        <a href src="http://www.idesam.umu.se/english/mal/?languageId=1"><img src="applications/sead/theme/images/logo_mal_x116_notext.jpg"></a >
        
        <BR>
                                        <BR>
        <BR>
        <a href src="http://www.humlab.umu.se"><img src="applications/sead/theme/images/logo_humlab_x116.gif"></a >
        
        <BR>
        
                                        <BR>
        <BR>
        <a href src="http://www.umu.se"><img src="applications/sead/theme/images/logo_umu_x116.gif"></a >
        
        <BR>
        
        
        <BR>
        <BR>
        <a href src="http://www.lunduniversity.lu.se/"><img src="applications/sead/theme/images/450.gif" width="116"></a >
        
        <BR>
        
                                        

						<BR>
                                                		

						</span>

						<td class="generic_table_middle_right"></td>

                                                <tr>
							<td class="generic_table_bottom_left"></td>
							<td class="generic_table_bottom_middle"></td>
							<td class="generic_table_bottom_right"></td>
						</tr>
					</tbody>
				</table>
			</div>
		</td>
	</tr>
</table>

<!--
<div id="msg_ctrl" style="background-color:#ddd;color:#000;position:absolute;left:530px;top:0px;">
<a href="javascript:print_slots_and_facets('manual');">Dump facet layout</a><br />
<a href="javascript:print_selections();">Dump selections</a><br />
<a href="javascript:util_toggle_debug();">Debug on/off</a><br />
<a href="javascript:clear_msg_box();">Clear messages</a><br />
<a href="javascript:testing();">Testing</a><br />
</div>
-->
<div id="msg" style="background-color:#eee;color:#000;position:absolute;left:1230px;top:0px;">
<!--<a href="#" onclick='$("#msg").html("");'>Clear</a><br />-->
</div>

<div id="msg2" style="float:left;background-color:#ffe;color:#000;font-size:10px;"></div>
<div id="msg3" style="float:left;background-color:#ffe;color:#000;font-size:10px;"></div>