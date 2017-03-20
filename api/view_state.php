<?php

require_once(__DIR__ . '/../server/view_state.php');

session_start();
$session_id = session_id();

$index = ViewState::getIndex($session_id);

header("Content-type: text/xml");
echo ViewState::toXML($index);

//header('Content-Type: application/json');
//echo json_encode(array("view_states" => $index));

?>