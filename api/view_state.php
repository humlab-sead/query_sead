<?php

require_once(__DIR__ . '/../server/view_state_repository.php');
require_once(__DIR__ . '/serializers/view_state_serializer.php');

session_start();
$session_id = session_id();

$index = ViewStateRepository::getSessionIndex($session_id);

header("Content-type: text/xml");
echo ViewStateSerializer::toXML($index);

//header('Content-Type: application/json');
//echo json_encode(array("view_states" => $index));

?>