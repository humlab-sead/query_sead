<?php
session_start();
 
require('fb_server_funct.php');

if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error: pg_connect failed.\n"; exit; }

$q="select * from $view_state_table  where session_id='".session_id()."' order by view_state_id desc limit 5";
if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }

header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<view_states>\n";
while ($row = pg_fetch_assoc($rs) )
{
	//print_r($row);
	echo "<view_state>\n";
	echo "<id>".$row["view_state_id"]."</id>\n";
	echo "<created>".$row["creatation_date"]."</created>\n";
	echo "</view_state>\n";
}
echo "</view_states>";

?>