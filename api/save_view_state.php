<?php

require_once(__DIR__ . '/../server/view_state_repository.php');

session_start();
$session_id = session_id();
$view_state = $_REQUEST["view_state"];

echo ViewStateRepository::save($session_id, $view_state);

?>