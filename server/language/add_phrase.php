<?php
//echo "test";
//exit;

require_once(__DIR__ . "/language.php");

language_receive_new_phrase($_POST['xml']);

echo "Phrase  added";

?>