<?php
//echo "test";
//exit;

require_once("applications/applicationSpecification.php");
require_once("applications/sead/fb_def.php");
require_once("server/lib/t.php");
require_once("server/lib/language.php");

language_receive_new_phrase($_POST['xml']);

echo "Phrase  added";

?>