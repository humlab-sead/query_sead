<?php
/*
file: save_view_state.php
Stores the text from the "view_state" argument into the view_state table.
Using a counter to to get new id for each view state being saved
*/
session_start();

$view_state=$_REQUEST["view_state"];
require('fb_server_funct.php');

if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error: pg_connect failed.\n"; exit; }

$q="BEGIN transaction;\n insert into $view_state_table (view_state, session_id) values ('".$view_state."', '".session_id()."')";
if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }

$q="select max(view_state_id) as max from $view_state_table;";

if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }
while ($row = pg_fetch_assoc($rs) )
{
    echo $row["max"];
}

$q=" commit transaction";
if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }
?>