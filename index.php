<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php

    error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

    @ini_set('log_errors','On');
    @ini_set('display_errors','Off');
    @ini_set('error_log', __DIR__ . '/errors.log');

    session_start();
    $sid = session_id();

    require_once("server/language/language.php");

    $view_state_id = empty($_GET["view_state"]) ? get_default_view_state() : $_GET["view_state"];
    $client_language = get_view_state_language($view_state_id);

    require_once("server/config/environment.php");
    include_once("interface.php");

?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Query SEAD</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="client/theme/style.css" />

    <script type="text/javascript" src="//code.jquery.com/jquery-3.2.0.min.js"></script>
    <script type="text/javascript" src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.9/highcharts.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.9/js/modules/exporting.js"></script>
    <script type="text/javascript" src="//maps.google.com/maps/api/js?key=AIzaSyDEnaCiVoQ54k1MFbUECGJttDU1Vj7pPOw&sensor=false"></script>

    <script type="text/javascript" src="client/client_definitions.js"></script>
    <script type="text/javascript">
    var client_language = "<?=$client_language?>";
    var current_view_state_id = "<?=$view_state_id?>";
    </script>

    <script type="text/javascript" src="client/user.js"></script>
    <script type="text/javascript">
        var application_address = "<?php echo ConfigRegistry::getServerName() ?>";
        var application_prefix_path = "<?php echo ConfigRegistry::getServerPrefixPath() ?>";
        var application_name = "sead";
        var filter_by_text = true;
        var currentUser = new User();
        var use_web_socket = false; 
        currentUser.sessionKey = "<?php echo ConfigRegistry::generateSessionKey() ?>";
    </script>

    <script type="text/javascript">
        <?php require_once("api/get_facet_definitions.php"); ?>
        <?php require_once("api/get_result_definitions.php"); ?>
        var result_modules = [];
    </script>
    <?php
        foreach (ConfigRegistry::getClientResultModules() as $module_file) {
            echo "<script type=\"text/javascript\" src=\"$module_file\"></script>";
        }
    ?>
<?php
    require_once("server/language/language.php");
    // if(isset($_GET['f']) && $_GET['f'] == "language_update")
        // echo language_perform_update();
    echo language_create_javascript_languages_definition_array();
    echo language_create_javascript_translation_arrays();

?>
    <script type="text/javascript" src="client/client_ui_definitions.js"></script>
    <script type="text/javascript" src="client/util.js"></script>
    <script type="text/javascript" src="client/slot.js"></script>
    <script type="text/javascript" src="client/facet_list.js"></script>
    <script type="text/javascript" src="client/facet.range.js"></script>
    <script type="text/javascript" src="client/facet.discrete.js"></script>
    <script type="text/javascript" src="client/facet.geo.js"></script>
    <script type="text/javascript" src="client/facet_view_render.js"></script>
    <script type="text/javascript" src="client/facet_view.js"></script>
    <script type="text/javascript" src="client/facet_presenter.js"></script>
    <script type="text/javascript" src="client/facet.js"></script>
    <script type="text/javascript" src="client/nodejs-client.js"></script>
    <script type="text/javascript" src="client/notifyservice.js"></script>
    <script type="text/javascript" src="client/layout.js"></script>
    <script type="text/javascript" src="client/control_bar.js"></script>
    <script type="text/javascript" src="client/result.js"></script>
    <script type="text/javascript" src="client/info_area.js"></script>

<?php
    if(!empty($_GET['view_state'])) {
        echo "<script type=\"text/javascript\"> current_view_state_id = ".$_GET['view_state']."; </script>";
    }
?>
<script type="text/javascript" src="client/main.js"></script>
</head>
<body>

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
                                <td onclick="info_area_open_about('<?=t("http://www.sead.se", $client_language);?>')">
                                                                   
                                    <span id="about_sead_button"> <?=interface_render_title_button("About SEAD");?></span>
                                </td>
                                <td onclick="info_area_open_about('<?=t("http://www.sead.se/help", $client_language);?>')">
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
                            <td class="generic_table_middle_left"></td>
                            <td class="generic_table_middle_middle content_container">
                            <div id="facet_control"></div>
                            </td>
                            <td class="generic_table_middle_right">
                            
                            <div id="result_control"></div>
                            
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </td>
        </tr>
    </table>
</DIV>
<img id="umu_logo" src="client/theme/images/umu.png" />
<table>
    <tr>
        <td style="vertical-align:top;">
            <div id="facet_workspace"></div>
        </td>
        <td style="vertical-align:top;">
            <div id="info_area"></div>
            <div id="status_area_outer">
                <table class="generic_table">
                    <tbody>
                        <tr>
                            <td class="generic_table_middle_left"></td>
                            <td class="generic_table_middle_middle content_container">
                                <?=$status_area?></td><td class="generic_table_middle_right">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="result_workspace"><?=$result_workspace?></div>
        </td>
     </tr>
</table>

<div id="msg" style="background-color:#eee;color:#000;position:absolute;left:1230px;top:0px;">
</div>
<div id="msg2" style="float:left;background-color:#ffe;color:#000;font-size:10px;"></div>
<div id="msg3" style="float:left;background-color:#ffe;color:#000;font-size:10px;"></div>

</body>
</html>