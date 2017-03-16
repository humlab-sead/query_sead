<?php
/*
file: fb_server_image_funct.php
This file holds all functions to create icons and images
*/

/*
Function:  drawStar
This function computes the coordinates for a gd-function to make star-shape image
$x, $y -> Position in the image
$radius -> Radius of the star
$spikes -> Number of spikes

*/
function drawStar($x, $y, $radius, $spikes=5) {
    
    $coordinates = array();
    $angel = 360 / $spikes ;
    // Get the coordinates of the outer shape of the star
    $outer_shape = array();
    for($i=0; $i<$spikes; $i++){
        $outer_shape[$i]['x'] = $x + ($radius * cos(deg2rad(270 - $angel*$i)));
        $outer_shape[$i]['y'] = $y + ($radius * sin(deg2rad(270 - $angel*$i)));
    }
    
    // Get the coordinates of the inner shape of the star
    $inner_shape = array();
    for($i=0; $i<$spikes; $i++){
        $inner_shape[$i]['x'] = $x + (0.5*$radius * cos(deg2rad(270-180 - $angel*$i)));
        $inner_shape[$i]['y'] = $y + (0.5*$radius * sin(deg2rad(270-180 - $angel*$i)));
    }
    
    // Bring the coordinates in the right order
    foreach($inner_shape as $key => $value){
        if($key == (floor($spikes/2)+1))
        break;
    $inner_shape[] = $value;
    unset($inner_shape[$key]);
}

// Reset the keys
$i=0;
foreach($inner_shape as $value){
    $inner_shape[$i] = $value;
    $i++;
}

// "Merge" outer and inner shape
foreach($outer_shape as $key => $value){
    $coordinates[] = $outer_shape[$key]['x'];
    $coordinates[] = $outer_shape[$key]['y'];
    $coordinates[] = $inner_shape[$key]['x'];
    $coordinates[] = $inner_shape[$key]['y'];
}

// Return the coordinates
return $coordinates ;

/*

// Example
$spikes = 5;

$values = drawStar(250, 250, 200, $spikes);
$im = imagecreate(500,500);
imagecolorallocate($im,0,0,0);
$w = imagecolorallocate($im, 255, 255, 255);
imagefilledpolygon($im, $values, $spikes*2, $w);
imageGIF($im);
imagedestroy($im);
*/
}

/*
function: render_symbol
This function make symbols to be used in diagram legend
Inputs:
red in decimal
green
blue
alpha
symbol - circle, square,triangle, triangle_down, diamond

Returns:
file name png-image 13x13px
*/
function render_symbol ($symbol,$color_list,$bg_color_list,$alpha=0,$size,$suffix_symbol)
{
    
    $red=$color_list[0];
    $green=$color_list[1];
    $blue=$color_list[2];
    $multiplier=$size/13;
    $desiredwidth=ceil(13*$multiplier);
    $desiredheight=ceil(13*$multiplier);
    
    $image_out=imagecreatetruecolor($desiredwidth,$desiredheight);
    
    imagealphablending($image_out, true);
    imagesavealpha($image_out, true);
    $fillcolor = imagecolorallocatealpha($image_out, 255,255,255,127);
    $black = imagecolorallocatealpha($image_out, 0,0,0,0);
    $white = imagecolorallocatealpha($image_out, 255,255,255,0);
    
    $bg_color=imagecolorallocatealpha($image_out, $bg_color_list[0],$bg_color_list[1],$bg_color_list[2],0);
    
    imagefill($image_out,0,0,$fillcolor);
    $tmpcolor =  imagecolorallocatealpha($image_out, $red,$green,$blue,$alpha);
    
    switch ($symbol)
    {
        case "circle":
            imageSmoothArc ($image_out, ceil(6*$multiplier), ceil(6*$multiplier),ceil(10*$multiplier),  ceil(10*$multiplier),array($red,$green,$blue,$alpha), 0, 2*M_PI);
            break;
        case "halfcircle":
            //foreground
            imageSmoothArc ($image_out, ceil(6*$multiplier), ceil(6*$multiplier),ceil(11*$multiplier)-2,  ceil(11*$multiplier)-2,array($red,$green,$blue,$alpha), 0, 1*M_PI);
            //background
            imageSmoothArc ($image_out, ceil(6*$multiplier), ceil(6*$multiplier),ceil(11*$multiplier)-2,  ceil(11*$multiplier)-2,array($bg_color_list[0],$bg_color_list[1],$bg_color_list[2],0),1*M_PI, 2*M_PI);
            $thickness=1;//round(1*$multiplier);
            imagesetthickness($image_out,$thickness);
            break;
        case "star":
            $spikes = 5;
            $values = drawStar(floor($desiredwidth/2), floor($desiredwidth/2), floor($desiredwidth/2), $spikes);
            imagefilledpolygon($image_out, $values, $spikes*2, $tmpcolor);
            break;
        case "halfcircle_up":
            //foreground
            imageSmoothArc ($image_out, ceil(6*$multiplier), ceil(6*$multiplier),ceil(11*$multiplier)-2,  ceil(11*$multiplier)-2,array($bg_color_list[0],$bg_color_list[1],$bg_color_list[2],0),1*M_PI, 2*M_PI);
            //background
            imageSmoothArc ($image_out, ceil(6*$multiplier), ceil(6*$multiplier),ceil(11*$multiplier)-2,  ceil(11*$multiplier)-2,array($red,$green,$blue,$alpha), 0, 1*M_PI);
            break;
        
        case "square":
            imagefilledrectangle  ( $image_out  , 0 , 0 , $desiredwidth-1 , $desiredheight-1 ,	$tmpcolor );
            break;
        case "halfsquare":
            //foreground
            imagefilledrectangle  ( $image_out  , 0 , ceil($desiredheight)/2 , $desiredwidth-1  ,  $desiredheight-1 ,	$tmpcolor );
            // background
            imagefilledrectangle  ( $image_out  , 0 , 0 , $desiredwidth-1  , ceil($desiredheight)/2 ,	$bg_color );
            $thickness=1;//round(1*$multiplier);
            imagesetthickness($image_out,$thickness);
            //borders
            imagerectangle  (  $image_out  , $thickness-1 , $thickness -1, $desiredwidth-$thickness , $desiredheight-$thickness ,	$black );
            break;
        
        case "halfsquare_up":
            //foreground
            imagefilledrectangle  ( $image_out  , 0 , 0 , $desiredwidth-1  , ceil($desiredheight)/2 ,	$tmpcolor );
            // background:
            imagefilledrectangle  ( $image_out  , 0 , ceil($desiredheight)/2 , $desiredwidth-1  ,  $desiredheight-1 ,	$bg_color );
            $thickness=1;//round(1*$multiplier);
            imagesetthickness($image_out,$thickness);
            //borders
            imagerectangle  (  $image_out  , $thickness-1 , $thickness -1, $desiredwidth-$thickness , $desiredheight-$thickness ,	$black );
            
            break;
        case "diamond":
            $values = array(
            ceil(6*$multiplier), 0,  // Point 1 (x, y)
            0,ceil(6*$multiplier), // Point 2 (x, y)
            ceil(6*$multiplier),  ceil(12*$multiplier),  // Point 3 (x, y)
            ceil(12*$multiplier),  ceil(6*$multiplier),  // Point 4 (x, y)
            
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $tmpcolor );
            //imagefilledrectangle  ( $image_out  , 0 , 0 , $desiredwidth-1 , $desiredheight-1 ,	$tmpcolor );
            break;
        case "half_diamond":
            //foreground
            $values = array(
            0, ceil(6*$multiplier),  // Point 1 (x, y)
            ceil(6*$multiplier), ceil(12*$multiplier),  // Point 4 (x, y)
            ceil(12*$multiplier),ceil(6*$multiplier) // Point 2 (x, y)
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $tmpcolor );
            // background
            $values = array(
            0, ceil(6*$multiplier),  // Point 1 (x, y)
            ceil(6*$multiplier), 0,  // Point 2 (x, y)
            ceil(12*$multiplier),ceil(6*$multiplier) // Point3 (x, y)
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $bg_color );
            
            //borders
            $values = array(
            ceil(6*$multiplier), 0,  // Point 1 (x, y)
            0,ceil(6*$multiplier), // Point 2 (x, y)
            ceil(6*$multiplier),  ceil(12*$multiplier),  // Point 3 (x, y)
            ceil(12*$multiplier),  ceil(6*$multiplier),  // Point 4 (x, y)
            
            );
            imagepolygon($image_out, $values, count($values)/2, $black );
            
            break;
        case "half_diamond_up":
            //foreground
            $values = array(
            0, ceil(6*$multiplier),  // Point 1 (x, y)
            ceil(6*$multiplier), 0,  // Point 2 (x, y)
            ceil(12*$multiplier),ceil(6*$multiplier) // Point3 (x, y)
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $tmpcolor );
            //background
            $values = array(
            0, ceil(6*$multiplier),  // Point 1 (x, y)
            ceil(6*$multiplier), ceil(12*$multiplier),  // Point 4 (x, y)
            ceil(12*$multiplier),ceil(6*$multiplier) // Point 2 (x, y)
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $bg_color );
            //border
            $values = array(
            ceil(6*$multiplier), 0,  // Point 1 (x, y)
            0,ceil(6*$multiplier), // Point 2 (x, y)
            ceil(6*$multiplier),  ceil(12*$multiplier),  // Point 3 (x, y)
            ceil(12*$multiplier),  ceil(6*$multiplier),  // Point 4 (x, y)
            );
            imagepolygon($image_out, $values, count($values)/2, $black );
            break;
        
        case "triangle":
            $values = array(
            0, ceil(12*$multiplier),  // Point 1 (x, y)
            ceil(6*$multiplier), 0, // Point 2 (x, y)
            ceil(12*$multiplier), ceil(12*$multiplier),  // Point 3 (x, y)
            
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $tmpcolor );
            
            break;
        case "triangle-down":
            $values = array( 0, 0, 
                ceil(6*$multiplier), ceil(12*$multiplier), // Point 2 (x, y)
                ceil(12*$multiplier), 0,  // Point 3 (x, y)
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $tmpcolor );
            break;
    }


if ($suffix_symbol!='')
{
    $size=$size;
    $dest = imagecreatetruecolor($desiredwidth,$desiredheight+$size);;
    // Copy and merge
    imagealphablending($dest, true);
    imagesavealpha($dest, true);
    
    $fillcolor = imagecolorallocatealpha($dest, 255,255,255,127);
    $black = imagecolorallocatealpha($dest, 0,0,0,0);
    $white = imagecolorallocatealpha($dest, 255,255,255,0);
    $bg_color=imagecolorallocatealpha($dest, $bg_color_list[0],$bg_color_list[1],$bg_color_list[2],0);
    
    imagefill($dest,0,0,$fillcolor);
    $tmpcolor =  imagecolorallocatealpha($dest, $red,$green,$blue,$alpha);
    
    imagecopy($dest, $image_out, 0, $size, 0, 0, $desiredwidth, $desiredwidth);
    switch ($suffix_symbol)
    {
        case "cross":
            // draw suffix as cross
            $x1=ceil($size/4);
            $y1=ceil($size/4*3);
            $x2=ceil($size/4*3);
            $y2=ceil($size/4*3);
            imageline  ( $dest  , $x1  , $y1  ,  $x2  ,  $y2  , $black  ); //horizontal line
            $x1=ceil($size/2);
            $y1=ceil($size/2);
            $x2=ceil($size/2);
            $y2=ceil($size);
            imageline  ( $dest  , $x1  , $y1  ,  $x2  ,  $y2  , $black ); //vertical line
            break;
}
$image_out=$dest;
}

$filename="cache/icons/".$symbol."_".$suffix_symbol.$green.$red.$blue.$bg_color_list[0].$bg_color_list[1].$bg_color_list[2]."_".$desiredwidth."x".$desiredheight."px.png";
//imageantialias($image_out,true);
Imagepng($image_out,$filename);

return $filename;
}

function diagram_symbol($symbol,$color)
{
    
    $desiredwidth=13;
    $desiredheight=13;
    // symbol=diamond&color=#AA4643
    $color=$_REQUEST["color"];
    $symbol=$_REQUEST["symbol"];
    
    
    //	echo $color."<BR>";
    $red= hexdec ( substr($color, 0,2));
    $green= hexdec ( substr($color,2,2));
    $blue= hexdec ( substr($color, 4,2));
    //echo $red.$green.$blue ;
    $image_out=imagecreatetruecolor($desiredwidth,$desiredheight);
    $fillcolor = ImageColorAllocate($image_out,$red,$green,$blue);
    $white = imagecolorallocatealpha($image_out, 255,255,255,0);
    imagefill($image_out,0,0,$white);
    $tmpcolor =  imagecolorallocatealpha($image_out, $red,$green,$blue,0);
    
    //imagefill($image_out,0,0,$tmpcolor);
    
    switch ($symbol)
    {
        case "circle":
            imagefilledarc($image_out,  6,  6,  11,  11,  0, 360, $tmpcolor,IMG_ARC_PIE);
            break;
        case "square":
            imagefilledrectangle  ( $image_out  , 0 , 0 , $desiredwidth-1 , $desiredheight-1 ,	$tmpcolor );
            break;
        case "diamond":
            $values = array(
            6, 0,  // Point 1 (x, y)
            0,  6, // Point 2 (x, y)
            6,  12,  // Point 3 (x, y)
            12, 6,  // Point 4 (x, y)
            
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $tmpcolor );
            //imagefilledrectangle  ( $image_out  , 0 , 0 , $desiredwidth-1 , $desiredheight-1 ,	$tmpcolor );
            break;
        case "triangle":
            $values = array(
            0, 12,  // Point 1 (x, y)
            6, 0, // Point 2 (x, y)
            12, 12,  // Point 3 (x, y)
            
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $tmpcolor );
            
            break;
        case "triangle-down":
            $values = array(
            0, 0,  // Point 1 (x, y)
            6, 12, // Point 2 (x, y)
            12, 0,  // Point 3 (x, y)
            
            );
            imagefilledpolygon($image_out, $values, count($values)/2, $tmpcolor );
            
            break;
        
        
}


//	Imagepng($image_out,"cache/icons/$symbol_".$green.$red.$blue.".png");
return $image_out;

}


/*

function: get_map_legend_image
Renders a image legend based on the content of the map.

*/
function get_map_legend_image($conn,$result_item_text,$color_scheme,$database_table,$client_language=null)
{
    
    if (isset($client_language))
    {
        $null_value=t("Inget värde",$client_language);
    }
    else
        $null_value="NULL";
    
    
    $q="select  distinct COALESCE(lower::text||'-'||upper::text, '".$null_value."')::text as interval_text ,lower,upper,$color_scheme as color_code from  $database_table order by lower, upper" ;
    if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }
    $tot_records=pg_numrows($rs);
    
    $desiredheight=15*$tot_records+13+35;
    $max_length_label=strlen($result_item_text);
    $text_pos_x=0;
    $row_counter=0;
    while ($row = pg_fetch_assoc($rs) )
    {
        if (strlen($row["interval_text"])>$max_length_label)
            $max_length_label=strlen($row["interval_text"]);
        
        $interval_obj[$row_counter]["interval_text"]=$row["interval_text"];;
        $interval_obj[$row_counter]["color_code"]=$row["color_code"];;
        $interval_text[]=$row["interval_text"];
        $color_str[]=$row["color_code"];
        $row_counter++;
        
    }
    
    $desiredwidth=$max_length_label*13; // text + image
    
    $image_out=imagecreatetruecolor($desiredwidth,$desiredheight);
    $fillcolor = imagecolorallocatealpha($image_out, 111, 111,111, 50);
    imagefill($image_out,0,0,$fillcolor);
    $black = imagecolorallocatealpha($image_out, 0, 0,0,50);
    
    $font_file = '/usr/share/fonts/truetype/freefont/FreeSerifBold.ttf';
    imagefttext($image_out, 15, 0,5, 18,$black, $font_file, $result_item_text);
    $font_file = '/usr/share/fonts/truetype/freefont/FreeMono.ttf';
    $counter=0;
    if (isset($interval_obj))
    {
        foreach ($interval_obj as $interval_key =>$interval_item)
        {
            
            $fill_color_array=explode(" ", $interval_item["color_code"]);
            //print_r($fill_color_array);
            $fill_color=imagecolorallocatealpha($image_out,$fill_color_array[0], $fill_color_array[1],$fill_color_array[2], 0);
            
            imagefttext($image_out, 13, 0, 40, 15*$counter+45,$black, $font_file, $interval_item["interval_text"]);
            
            imagefilledrectangle($image_out  , 5 , 15*$counter+15+15 , 35 , 15*$counter+24+15+15 ,$fill_color );
            $border_color=imagecolorallocatealpha($image_out, 0, 0,0, 0);
            imagerectangle  ( $image_out  ,5 ,15*$counter+15+15 , 35 , 15*$counter+24+15+15,$border_color );
            $counter++;
        }
    }
    else
    {
        // no data in the interval array
        imagefttext($image_out, 13, 0, 40, 15*0+45,$black, $font_file, t("Inga data finns",$client_language));
        
    }
    return $image_out;
    
}


?>