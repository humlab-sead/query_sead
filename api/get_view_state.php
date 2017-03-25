<?php

require_once(__DIR__ . '/../server/view_state.php');

$view_state_id = $_REQUEST["view_state_id"];
$view_state = ViewState::getViewState($view_state_id);

header("Content-type: text/xml");
echo $view_state;

?>