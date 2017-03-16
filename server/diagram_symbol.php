<?php
/*
file: diagram_symbol.php
This files make symbols to be used in diagram legend
Inputs:
color - in hexcode
symbol - circle, square,triangle, triangle_down, diamond

Returns:
png-image 13x13px

see http://dev.humlab.umu.se/frepalm/multibrowser_p3/server/diagram_symbol?symbol=diamond&color=AA4643

*/

require('fb_server_funct.php');

Header( "Content-type: image/png");

$color=$_REQUEST["color"];	
$symbol=$_REQUEST["symbol"];	

Imagepng(diagram_symbol($symbol,$color));


?>