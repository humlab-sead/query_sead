<?php
/* file: get_view_state.php
Retrieves the view state xml data from server given a "view_state_id" value
*/

$view_state_id=$_REQUEST["view_state_id"];

require('fb_server_funct.php');

if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error: pg_connect failed.\n"; exit; }

$q="select * from $view_state_table where view_state_id=".$view_state_id;
if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }

header("Content-type: text/xml");
/*echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"; */
while ($row = pg_fetch_assoc($rs) )
{
	echo $row["view_state"];
}

?>