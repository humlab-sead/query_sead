<?php

require_once(__DIR__ . '/../server/view_state.php');

session_start();
$session_id = session_id();
$view_state = $_REQUEST["view_state"];

echo ViewState::saveViewState($session_id, $view_state);

?>