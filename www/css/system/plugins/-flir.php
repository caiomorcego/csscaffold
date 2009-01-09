<?php
/*
Generate
*/

define('ENABLE_FONTSIZE_BUG', false);

define('FLIR_VERSION', '1.2');
define('IS_WINDOWS', (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'));

define('FONT_DISCOVERY', false);

define('ALLOWED_DOMAIN', false); // ex: 'example.com', 'subdomain.domain.com', '.allsubdomains.com', false disabled

define('UNKNOWN_FONT_SIZE', 16); // in pixels

define('CACHE_CLEANUP_FREQ', -1); // -1 disable, 1 everytime, 10 would be about 1 in 10 times
define('CACHE_KEEP_TIME', 604800); // 604800: 7 days

define('CACHE_DIR', '../../images/image-replacement');
define('FONTS_DIR', '../../fonts');

define('PLUGIN_DIR',	 'plugins');

define('HBOUNDS_TEXT', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz[]{}()_'); 

define('IM_EXEC_PATH', ''); // Path to ImageMagick (with trailing slash).  ImageMagick is needed by some plugins, but not necessary.


// Each font you want to use should have an entry in the fonts array.
$fonts = array();

// The font will default to the following (put your most common font here).
//$fonts['default'] = $fonts['okolaks'];

/*

$fonts['illuminating'] 	= 'ArtOfIlluminating.ttf';

$fonts['your_font'][] 	= array(
										'file' 				=> 'test/font_bolditalic.ttf',
										'font-stretch'		=> '',
										'font-style'			=> 'italic',
										'font-variant'		=> '',
										'font-weight'			=> 'bold',
										'text-decoration'		=> ''
									);

*/


// Set default replacements for "web fonts" here

$fonts['arial'] = $fonts['helvetica'] = $fonts['sans-serif'];

$fonts['times new roman'] = $fonts['times'] = $fonts['serif'];

$fonts['courier new'] = $fonts['courier'] = $fonts['monospace'];






/*
inc-flir.php
*/


/***
 *
 * Can be deleted if magic quotes is disabled.  Magic quotes, what a plague it is/was.
 *
*/
if (get_magic_quotes_gpc()) {
    function stripslashes_deep($value)
    {
        $value = is_array($value) ?
                    array_map('stripslashes_deep', $value) :
                    stripslashes($value); 

        return $value;
    }

    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

$ERROR_MSGS = array(
     'COULD_NOT_CREATE'  => 'Invalid: Could not create image.'
    ,'DISALLOWED_DOMAIN' => 'Bad Domain: Domain is not allowed to generate images.'

    ,'PHP_TOO_OLD'  => 'The version of PHP you are using is too old.  Facelift requires at least PHP v4.3.0.'
    ,'PHP_UNSUPPORTED' => 'The version of PHP you are using is not supported.'

    ,'GD_NOT_INSTALLED' => 'Facelift requires the GD extension for PHP.'
    ,'GD_TOO_OLD' => 'The version of GD you are using is too old.  Facelift requires at least GD v2.'
    ,'GD_NO_IMAGES' => 'Facelift needs to be able to create images in PNG, GIF, or JPG.  You have none of these supported by your version of GD.  Please enable the ability to create PNG, GIF, or JPG images in your GD installation.'
    ,'GD_NO_FREETYPE' => 'The version of GD you are using does not have support for FreeType.  Facelift requires GD with FreeType support to work.'

    ,'FONT_DOESNT_EXIST'            => 'Cannot find font.  Be sure you have specified a valid default font file.'
    ,'FONT_PS_COULDNT_LOAD'        => 'Unable to load the PostScript font.'
    ,'FONT_PS_UNSUPPORTED'        => 'PostScript fonts are unsupported at this time.  That goes double for Windows servers.<br /><br />If you want, you can comment out "err(\'FONT_PS_UNSUPPORTED\');" and give it a shot.'

    ,'CACHE_DOESNT_EXIST'        => 'Cache directory does not exist.'
    ,'CACHE_UNABLE_CREATE'        => 'Unable to create the cache directory.  Verify that permissions are properly set.'
);


// functions

function get_cache_fn($md5, $ext='png') {
    if(!file_exists(CACHE_DIR))
        err('CACHE_DOESNT_EXIST');
        
    $tier1 = CACHE_DIR.'/'.$md5[0].$md5[1];
    $tier2 = $tier1.'/'.$md5[2].$md5[3];
    
    if(!file_exists($tier1))
        @mkdir($tier1);
    if(!file_exists($tier2))
        @mkdir($tier2);
        
    if(!file_exists($tier2))
        err('CACHE_UNABLE_CREATE');
        
    return $tier2.'/'.$md5.'.'.$ext;
}

function cleanup_cache() {
    $d1 = dir(CACHE_DIR);
    while(false !== ($tier1 = $d1->read())) {
        if($tier1 == '.' || $tier1 == '..') continue; 
        
        $d2 = dir(CACHE_DIR.'/'.$tier1);
        while(false !== ($tier2 = $d2->read())) {
            if($tier2 == '.' || $tier2 == '..') continue; 
            
            $path = CACHE_DIR.'/'.$tier1.'/'.$tier2;
            $d3 = dir($path);
            while(false !== ($entry = $d3->read())) {
                if($entry == '.' || $entry == '..') continue; 
                
                if((time() - filectime($path.'/'.$entry)) > CACHE_KEEP_TIME) {
//                    echo $path.'/'.$entry.' removed<BR>';
                    unlink($path.'/'.$entry);
                }
            }
            $d3->close();            
        }
        $d2->close();
    }
    $d1->close();
}

function imagettftextbox($size_pts, $angle, $left, $top, $color, $font, $raw_text, $max_width, $align='left', $lineheight=1.0) {
    global $FLIR;
    
    $raw_textlines = explode("\n", $raw_text);
    
    $formatted_lines = $formatted_widths = array();
    $max_values = bounding_box(HBOUNDS_TEXT);
    $previous_bounds = array('width' => 0);
    
    $spaces = ' '.str_repeat(' ', (defined('SPACING_GAP')?SPACING_GAP:0));
        
    foreach($raw_textlines as $text) {        
        $bounds = bounding_box($text);
        if($bounds['height'] > $max_lineheight)
            $max_lineheight = $bounds['height'];
        if($bounds['belowBasepoint'] > $max_baseheight)
            $max_baseheight = $bounds['belowBasepoint'];
        if($bounds['xOffset'] > $max_leftoffset)
            $max_leftoffset = $bounds['xOffset'];
        if($bounds['yOffset'] > $max_rightoffset)
            $max_rightoffset = $bounds['yOffset'];

        if($bounds['width'] < $max_width) { // text doesn't require wrapping
            $formatted_lines[] = $text;
            $formatted_widths[$text] = $bounds['width'];
        }else { // text requires wrapping
            $words = explode($spaces, trim($text));
            
            $test_line = '';
            for($i=0; $i < count($words); $i++) { // test words one-by-one to see if they put the width over
                $prepend = $i==0 ? '' : $test_line.$spaces; // add space if not the first word
                $working_line = $prepend.$words[$i];
                
                $bounds = bounding_box($working_line);
                
                if($bounds['width'] > $max_width) { // if working line is too big previous line isn't, use that 
                    $formatted_lines[] = $test_line;
                    $formatted_widths[$test_line] = $previous_bounds['width'];
                    $test_line = $words[$i];
                    
                    $bounds = bounding_box($test_line);
                }else { // keep adding
                    $test_line = $working_line;
                }
                
                $previous_bounds = $bounds;
            }
            
            if($test_line!='') { // if words are finished and there is something left in the buffer add it
                $bounds = bounding_box($test_line);

                $formatted_lines[] = $test_line;
                $formatted_widths[$test_line] = $bounds['width'];
            }
        }
    }
    
    $max_lineheight = ($max_values['height']*$lineheight);
    $image = imagecreatetruecolor($max_width, (($max_lineheight*(count($formatted_lines)-1))+$max_values['yOffset'])+$max_values['belowBasepoint']);
    
    gd_alpha($image);
    imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), gd_bkg($image));    
    
    for($i=0; $i < count($formatted_lines); $i++) {
        if($i==0)
            $offset_top = $max_values['yOffset'];
        else
            $offset_top = ($max_lineheight*$i)+$max_values['yOffset'];

        switch(strtolower($align)) {
            default:
            case 'left':
                $offset_left = 0;
                break;
            case 'center':
                $offset_left = ($max_width-$formatted_widths[$formatted_lines[$i]])/2;
                break;
            case 'right':
                $offset_left = ($max_width-$formatted_widths[$formatted_lines[$i]])-1;
                break;
        }
        
//        imagettftext($image, $size_pts, $angle, $offset_left, $offset_top, gd_color($image), $font, $formatted_lines[$i]);
        $bounds = array('xOffset' => $offset_left, 'yOffset' => $offset_top);
        render_text($bounds, $formatted_lines[$i], $image, $bounds);
    }

    return $image;
}

function css2hex($css_str, $default_color='000000') {
    $css_color = array();
    
    $css_color['aliceblue']       = 'f0f8ff';
    $css_color['antiquewhite']    = 'faebd7';
    $css_color['aqua']            = '00ffff';
    $css_color['aquamarine']      = '7fffd4';
    $css_color['azure']            = 'f0ffff';
    $css_color['beige']            = 'f5f5dc';
    $css_color['bisque']            = 'ffe4c4';
    $css_color['black']            = '000000';
    $css_color['blanchedalmond']            = 'ffebcd';
    $css_color['blue']            = '0000ff';
    $css_color['blueviolet']            = '8a2be2';
    $css_color['brown']            = 'a52a2a';
    $css_color['burlywood']            = 'deb887';
    $css_color['cadetblue']            = '5f9ea0';
    $css_color['chartreuse']            = '7fff00';
    $css_color['chocolate']            = 'd2691e';
    $css_color['coral']            = 'ff7f50';
    $css_color['cornflowerblue']            = '6495ed';
    $css_color['cornsilk']            = 'fff8dc';
    $css_color['crimson']            = 'dc143c';
    $css_color['cyan']            = '00ffff';
    $css_color['darkblue']            = '00008b';
    $css_color['darkcyan']            = '008b8b';
    $css_color['darkgoldenrod']            = 'b8860b';
    $css_color['darkgray']            = 'a9a9a9';
    $css_color['darkgrey']            = 'a9a9a9';
    $css_color['darkgreen']            = '006400';
    $css_color['darkkhaki']            = 'bdb76b';
    $css_color['darkmagenta']            = '8b008b';
    $css_color['darkolivegreen']            = '556b2f';
    $css_color['darkorange']            = 'ff8c00';
    $css_color['darkorchid']            = '9932cc';
    $css_color['darkred']            = '8b0000';
    $css_color['darksalmon']            = 'e9967a';
    $css_color['darkseagreen']            = '8fbc8f';
    $css_color['darkslateblue']            = '483d8b';
    $css_color['darkslategray']            = '2f4f4f';
    $css_color['darkslategrey']            = '2f4f4f';
    $css_color['darkturquoise']            = '00ced1';
    $css_color['darkviolet']            = '9400d3';
    $css_color['deeppink']            = 'ff1493';
    $css_color['deepskyblue']            = '00bfff';
    $css_color['dimgray']            = '696969';
    $css_color['dimgrey']            = '696969';
    $css_color['dodgerblue']            = '1e90ff';
    $css_color['firebrick']            = 'b22222';
    $css_color['floralwhite']            = 'fffaf0';
    $css_color['forestgreen']            = '228b22';
    $css_color['fuchsia']            = 'ff00ff';
    $css_color['gainsboro']            = 'dcdcdc';
    $css_color['ghostwhite']            = 'f8f8ff';
    $css_color['gold']            = 'ffd700';
    $css_color['goldenrod']            = 'daa520';
    $css_color['gray']            = '808080';
    $css_color['grey']            = '808080';
    $css_color['green']            = '008000';
    $css_color['greenyellow']            = 'adff2f';
    $css_color['honeydew']            = 'f0fff0';
    $css_color['hotpink']            = 'ff69b4';
    $css_color['indianred']            = 'cd5c5c';
    $css_color['indigo']            = '4b0082';
    $css_color['ivory']            = 'fffff0';
    $css_color['khaki']            = 'f0e68c';
    $css_color['lavender']            = 'e6e6fa';
    $css_color['lavenderblush']            = 'fff0f5';
    $css_color['lawngreen']            = '7cfc00';
    $css_color['lemonchiffon']            = 'fffacd';
    $css_color['lightblue']            = 'add8e6';
    $css_color['lightcoral']            = 'f08080';
    $css_color['lightcyan']            = 'e0ffff';
    $css_color['lightgoldenrodyellow']            = 'fafad2';
    $css_color['lightgray']            = 'd3d3d3';
    $css_color['lightgrey']            = 'd3d3d3';
    $css_color['lightgreen']            = '90ee90';
    $css_color['lightpink']            = 'ffb6c1';
    $css_color['lightsalmon']            = 'ffa07a';
    $css_color['lightseagreen']            = '20b2aa';
    $css_color['lightskyblue']            = '87cefa';
    $css_color['lightslategray']            = '778899';
    $css_color['lightslategrey']            = '778899';
    $css_color['lightsteelblue']            = 'b0c4de';
    $css_color['lightyellow']            = 'ffffe0';
    $css_color['lime']            = '00ff00';
    $css_color['limegreen']            = '32cd32';
    $css_color['linen']            = 'faf0e6';
    $css_color['magenta']            = 'ff00ff';
    $css_color['maroon']            = '800000';
    $css_color['mediumaquamarine']            = '66cdaa';
    $css_color['mediumblue']            = '0000cd';
    $css_color['mediumorchid']            = 'ba55d3';
    $css_color['mediumpurple']            = '9370d8';
    $css_color['mediumseagreen']            = '3cb371';
    $css_color['mediumslateblue']            = '7b68ee';
    $css_color['mediumspringgreen']            = '00fa9a';
    $css_color['mediumturquoise']            = '48d1cc';
    $css_color['mediumvioletred']            = 'c71585';
    $css_color['midnightblue']            = '191970';
    $css_color['mintcream']            = 'f5fffa';
    $css_color['mistyrose']            = 'ffe4e1';
    $css_color['moccasin']            = 'ffe4b5';
    $css_color['navajowhite']            = 'ffdead';
    $css_color['navy']            = '000080';
    $css_color['oldlace']            = 'fdf5e6';
    $css_color['olive']            = '808000';
    $css_color['olivedrab']            = '6b8e23';
    $css_color['orange']            = 'ffa500';
    $css_color['orangered']            = 'ff4500';
    $css_color['orchid']            = 'da70d6';
    $css_color['palegoldenrod']            = 'eee8aa';
    $css_color['palegreen']            = '98fb98';
    $css_color['paleturquoise']            = 'afeeee';
    $css_color['palevioletred']            = 'd87093';
    $css_color['papayawhip']            = 'ffefd5';
    $css_color['peachpuff']            = 'ffdab9';
    $css_color['peru']            = 'cd853f';
    $css_color['pink']            = 'ffc0cb';
    $css_color['plum']            = 'dda0dd';
    $css_color['powderblue']            = 'b0e0e6';
    $css_color['purple']            = '800080';
    $css_color['red']            = 'ff0000';
    $css_color['rosybrown']            = 'bc8f8f';
    $css_color['royalblue']            = '4169e1';
    $css_color['saddlebrown']            = '8b4513';
    $css_color['salmon']            = 'fa8072';
    $css_color['sandybrown']            = 'f4a460';
    $css_color['seagreen']            = '2e8b57';
    $css_color['seashell']            = 'fff5ee';
    $css_color['sienna']            = 'a0522d';
    $css_color['silver']            = 'c0c0c0';
    $css_color['skyblue']            = '87ceeb';
    $css_color['slateblue']            = '6a5acd';
    $css_color['slategray']            = '708090';
    $css_color['slategrey']            = '708090';
    $css_color['snow']            = 'fffafa';
    $css_color['springgreen']            = '00ff7f';
    $css_color['steelblue']            = '4682b4';
    $css_color['tan']            = 'd2b48c';
    $css_color['teal']            = '008080';
    $css_color['thistle']            = 'd8bfd8';
    $css_color['tomato']            = 'ff6347';
    $css_color['turquoise']            = '40e0d0';
    $css_color['violet']            = 'ee82ee';
    $css_color['wheat']            = 'f5deb3';
    $css_color['white']            = 'ffffff';
    $css_color['whitesmoke']            = 'f5f5f5';
    $css_color['yellow']            = 'ffff00';
    $css_color['yellowgreen']            = '9acd32';

    $color = isset($css_color[$css_str])?$css_color[$css_str]:$default_color;
    $colors     = explode(',',substr(chunk_split($color, 2, ','), 0, -1));
    $acolor = array();
    $acolor['red']     = hexdec($colors[0]);
    $acolor['green']     = hexdec($colors[1]);
    $acolor['blue']     = hexdec($colors[2]);
    
    return $acolor;
}

function dec2hex($r, $g, $b) {
    $hxr = dechex($r);
    $hxg = dechex($g);
    $hxb = dechex($b);
    
    return strtoupper((strlen($hxr)==1?'0'.$hxr:$hxr).(strlen($hxg)==1?'0'.$hxg:$hxg).(strlen($hxb)==1?'0'.$hxb:$hxb));
}

function output_file($cache_file) {
    $ts = filemtime($cache_file);

    $ifmodsince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:false;
    if ($ifmodsince && strtotime($ifmodsince) >= $ts) {
        header('HTTP/1.0 304 Not Modified', true, 304);
        return;
    }
    
    $etag = isset($_SERVER['HTTP_IF_NONE-MATCH'])?$_SERVER['HTTP_IF_NONE-MATCH']:false;
    if($etag && $etag == md5($ts)) {
        header('HTTP/1.0 304 Not Modified', true, 304);
        return;
    }
    
    header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', $ts));
    header('ETag: "'.md5($ts).'"');

    switch(exif_imagetype($cache_file)) {
        case IMAGETYPE_PNG:
            header('Content-Type: image/png');
            break;
        case IMAGETYPE_GIF:
            header('Content-Type: image/gif');
            break;
        case IMAGETYPE_JPEG:
            header('Content-Type: image/jpeg');
            break;
    }
    readfile($cache_file);
//    exit;
}


function bounding_box($text, $font=NULL, $size=NULL) {
    global $FLIR;

    if(is_null($font))
        $font = $FLIR['postscript'] ? $FLIR['ps']['font'] : $FLIR['font'];
        
    if(is_null($size))
        $size = $FLIR['postscript'] ? $FLIR['size'] : $FLIR['size_pts'];
    elseif($FLIR['postscript'])
        $size = get_points($FLIR['dpi'], $size); // convert to points

    if($FLIR['postscript'])
        return convertPSBoundingBox(imagepsbbox($text, $font, $size, $FLIR['ps']['space'], $FLIR['ps']['kerning'], 0));
    else
        return convertBoundingBox(imagettfbbox($size, 0, $font, $text));
}

/*
0  lower left corner, X position            -3
1     lower left corner, Y position            10
2     lower right corner, X position        735
3     lower right corner, Y position        10
4     upper right corner, X position        735
5     upper right corner, Y position        -44
6     upper left corner, X position            -3
7     upper left corner, Y position            -44

$width = abs($bounds[2]) + abs($bounds[0]);
$height = abs($bounds[7]) + abs($bounds[1]);
*/    
function convertBoundingBox ($bbox) {
    if ($bbox[0] >= -1)
        $xOffset = -abs($bbox[0] + 1);
    else
        $xOffset = abs($bbox[0] + 2);
    $width = abs($bbox[2] - $bbox[0]);
    if ($bbox[0] < -1) $width = abs($bbox[2]) + abs($bbox[0]) - 1;
    $yOffset = abs($bbox[5] + 1);
    if ($bbox[5] >= -1) $yOffset = -$yOffset; // Fixed characters below the baseline.
    $height = abs($bbox[7]) - abs($bbox[1]);
    if ($bbox[3] > 0) $height = abs($bbox[7] - $bbox[1]) - 1;
    return array(
        'width' => $width,
        'height' => $height,
        'xOffset' => $xOffset, // Using xCoord + xOffset with imagettftext puts the left most pixel of the text at xCoord.
        'yOffset' => $yOffset, // Using yCoord + yOffset with imagettftext puts the top most pixel of the text at yCoord.
        'belowBasepoint' => max(0, $bbox[1])
    );
}

function convertPSBoundingBox ($bbox) {
//echo 'here';
//print_r($bbox);
    if ($bbox[0] >= -1)
        $xOffset = -abs($bbox[0] + 1);
    else
        $xOffset = abs($bbox[0] + 2);
    
    $yOffset = abs($bbox[1] + 1);
    
    $width = abs($bb[2] - $bb[0]);
    $height = abs($bbox[1]) - abs($bbox[3]);

    return array(
      'width' => $width,
      'height' => $height,
      'xOffset' => $xOffset, // Using xCoord + xOffset with imagettftext puts the left most pixel of the text at xCoord.
      'yOffset' => $yOffset, // Using yCoord + yOffset with imagettftext puts the top most pixel of the text at yCoord.
      'belowBasepoint' => max(0, $bbox[1])
    );
}

/*
imagettftext( resource $image , float $size , float $angle , int $x , int $y , int $color , string $fontfile , string $text )
imagepstext( resource $image , string $text , resource $font_index 
                , int $size , int $foreground , int $background 
                , int $x  , int $y  
                [, int $space  [, int $tightness  [, float $angle  [, int $antialias_steps  ]]]] )
*/
function render_text($bounds, $text=NULL, $img=NULL, $realheight=NULL) {
    global $FLIR;
    
    if(!is_null($realheight))
        $REAL_HEIGHT_BOUNDS = $realheight;
    else
        global $REAL_HEIGHT_BOUNDS;
    
    if(is_null($img))
        global $image;
    else
        $image = $img;
        
    if(is_null($text))
        $text = $FLIR['postscript'] ? $FLIR['original_text'] : $FLIR['text'];
    
    if($FLIR['postscript']) {
        imagepstext($image, $text, $FLIR['ps']['font'], $FLIR['size'], gd_color($image)
                        , imagecolorallocatealpha($image        , $FLIR['bkgcolor']['red']
                                                                        , $FLIR['bkgcolor']['green']
                                                                        , $FLIR['bkgcolor']['blue'], 127)
                        , $bounds['xOffset'], $REAL_HEIGHT_BOUNDS['yOffset']
                        , $FLIR['ps']['space'], $FLIR['ps']['kerning'], 0, ($FLIR['size'] < 20 ? 16 : 4 ));
    }else {
        imagettftext($image, $FLIR['size_pts'], 0, $bounds['xOffset']
                        , $REAL_HEIGHT_BOUNDS['yOffset'], gd_color($image), $FLIR['font'], $text);
    }
}

function is_number($str, $bAllowDecimals=false, $bAllowZero=false, $bAllowNeg=false) {
    if($bAllowDecimals)
        $regex = $bAllowZero?'[0-9]+(\.[0-9]+)?': '(^([0-9]*\.[0-9]*[1-9]+[0-9]*)$)|(^([0-9]*[1-9]+[0-9]*\.[0-9]+)$)|(^([1-9]+[0-9]*)$)';
    else
        $regex = $bAllowZero?'[0-9]+': '[1-9]+[0-9]*';
        
    return preg_match('#^'.($bAllowNeg?'\-?':'').$regex.'$#', $str);
}

function is_hexcolor($str) {
    return preg_match('#^[a-f0-9]{6}$#i', $str);
}

function convert_color($color, $bHex=false, $default_color='000000') {
    $rgb = array();
    if(preg_match('#(\(([0-9]{1,3}), ?([0-9]{1,3}), ?([0-9]{1,3})(, ?([0-9]{1,3}))?\))#i', $color, $m)) {
        $rgb['red']     = $m[2];
        $rgb['green']     = $m[3];
        $rgb['blue']    = $m[4];
    }elseif(preg_match('#[a-f0-9]{3}|[a-f0-9]{6}#i', $color)) {
            if(strlen($color) == 3)
                $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
                
            $colors     = explode(',',substr(chunk_split($color, 2, ','), 0, -1));
            $rgb['red']     = hexdec($colors[0]);
            $rgb['green']     = hexdec($colors[1]);
            $rgb['blue']    = hexdec($colors[2]);
    }else {
        $rgb = css2hex($color, $default_color);
    }

    return $bHex ? dec2hex($rgb['red'],$rgb['green'],$rgb['blue']) : $rgb;
}

function is_transparent($str) {
    if(trim($str) == '' || $str == 'transparent' || $str == 'none')
        return true;
        
    if(false !== strpos($str, 'rgba') && preg_match('#\([0-9]{1,3}, ?[0-9]{1,3}, ?[0-9]{1,3}, 0\)#i', $str, $m))
        return true;
        
    return false;
}

function get_points($dpi, $pxsize) {
    return round(((72/$dpi)*$pxsize), 3);
}

function discover_font($default, $passed) {
    $passed_fn = strtolower(get_filename($passed));
    $ret = $default;
    $fdir = str_replace('\\', '/', (getcwd().'/'.FONTS_DIR));
    $d = dir($fdir);
    while(false !== ($entry = $d->read())) {
        if($passed_fn == strtolower(get_filename($entry))) {
            $ret = $entry;
        }
    }
    $d->close();
    
    $rp = realpath(($fdir.'/'.$ret));
    
    return (!$rp || false === strpos(str_replace('\\', '/', $rp), $fdir)) ? $default : $ret;
}

function match_font_style($font) {
    global $FStyle;

    $best_match = array();
    $best_match_value = -1.0;
    foreach($font as $k => $v) {
        $stretch     = $FStyle['cStretch']=='normal'         || $FStyle['cStretch']==''         ? '' : $FStyle['cStretch'];
        $style         = $FStyle['cFontStyle']=='normal'     || $FStyle['cFontStyle']==''         ? '' : $FStyle['cFontStyle'];
        $variant     = $FStyle['cVariant']=='normal'         || $FStyle['cVariant']==''         ? '' : $FStyle['cVariant'];
        $weight         = $FStyle['cWeight']=='normal'         || $FStyle['cWeight']==''             ? '' : $FStyle['cWeight'];
        $decoration = $FStyle['cDecoration']=='none'     || $FStyle['cDecoration']==''     ? '' : $FStyle['cDecoration'];
        
        $total = (
                            ($v['font-stretch']        == $stretch     ? 1 : 0) 
                        +    ($v['font-style']            == $style        ? 1 : 0)
                        +    ($v['font-variant']        == $variant     ? 1 : 0)
                        +    ($v['font-weight']        == $weight         ? 1 : 0)
                        +    ($v['text-decoration']    == $decoration ? 1 : 0)
                    );
        if($total>0)
            $total /= 5;
        
        if($total > $best_match_value) {
            $best_match_value = $total;
            $best_match = $v['file'];
        }
    }
    
    return $best_match;
}

function space_out($text, $spaces) {
    $ret = '';
    for($i=0; $i<strlen($text); $i++) {
        $ret .= $text[$i].str_repeat(' ', $spaces);
    }
    
    return rtrim($ret);
}

function verify_gd() {
    global $ERROR_MSGS;
    
    if(!extension_loaded('gd'))
        err('GD_NOT_INSTALLED');
    
    if(function_exists('gd_info')) {
        $gdinfo = gd_info();
        
        $errors = array();
        preg_match('/\d/', $gdinfo['GD Version'], $m);
        if($m[0]!='2')
            $errors[] = $ERROR_MSGS['GD_TOO_OLD'];            

        if(!$gdinfo['FreeType Support'])
            $errors[] = $ERROR_MSGS['GD_NO_FREETYPE'];            

        if(!$gdinfo['PNG Support'] && !$gdinfo['GIF Create Support'] && !$gdinfo['JPG Support'])
            $errors[] = $ERROR_MSGS['GD_NO_IMAGES'];
            
        if(!empty($errors)) {
            echo implode('<br>', $errors);
            exit;
        }
    }
}

function gd_bkg($img=NULL) {
    global $FLIR;
    
    if(is_null($img))
        global $image;
    else
        $image = $img;
    
    switch($FLIR['output']) {
        case 'png':
            return imagecolorallocatealpha($image, $FLIR['bkgcolor']['red'], $FLIR['bkgcolor']['green'], $FLIR['bkgcolor']['blue'], 127);
        case 'gif':
        case 'jpg':
            return imagecolorallocate($image, $FLIR['bkgcolor']['red'], $FLIR['bkgcolor']['green'], $FLIR['bkgcolor']['blue']);
    }
}

function gd_color($img=NULL) {
    global $FLIR;
    
    if(is_null($img))
        global $image;
    else
        $image = $img;
    
    $color = '';
    if($opacity != 100)
        $color = imagecolorallocatealpha($image, $FLIR['color']['red'], $FLIR['color']['green'], $FLIR['color']['blue'], round(127-(($FLIR['opacity']/100)*127)));
    else 
        $color = imagecolorallocate($image, $FLIR['color']['red'], $FLIR['color']['green'], $FLIR['color']['blue']);

    return $color;
}

function gd_alpha($img=NULL) {
    global $FLIR;
    
    if(is_null($img))
        global $image;
    else
        $image = $img;

    if($FLIR['output'] == 'png') {
        imagesavealpha($image, true);
        imagealphablending($image, false);
    }
}

function fix_path($str) {
    return IS_WINDOWS ? str_replace('/', '\\', $str) : str_replace('\\', '/', $str);
}

function consttrue($const) {
    return !defined($const) ? false : constant($const);
}

function err($k) {
    global $ERROR_MSGS;
    
    die(isset($ERROR_MSGS[$k]) ? $ERROR_MSGS[$k] : 'Unknown Error');
}


/*
 manfred at werkzeugH dot at
27-May-2008 04:35
here is my version for strings with utf8-characters represented as numerical entities  (e.g. &#1234;)
*/

function utf8_entities_strrev($str, $preserve_numbers = true)
{
  //split string into string-portions (1 byte characters, numerical entitiesor numbers)

  $parts=Array();
  while ($str)
  {
    if ($preserve_numbers && preg_match('/^([0-9]+)(.*)$/',$str,$m))
    {
      // number-flow
      $parts[]=$m[1];
      $str=$m[2];
    }
    elseif (preg_match('/^(\&#[0-9]+;)(.*)$/',$str,$m))
    {
      // numerical entity
      $parts[]=$m[1];
      $str=$m[2];
    }
    else
    {
      $parts[]=substr($str,0,1);
      $str=substr($str,1);
    }
  }

  $str=implode(array_reverse($parts),"");

  return $str;
}





// PHP Compat stuff
if(!function_exists('json_decode')) {
    // very plain json_decode
    function json_decode($str, $ignore=true) {
        $str = trim($str);
        if(!preg_match('#^\{(("[\w]+":"[^"]*",?)*)\}$#i', $str, $m)) return array();
        $data = explode('","', substr($m[1], 1, -1));
        $ret = array();
        for($i=0; $i<count($data);$i++) {
            list($k,$v) = explode(':', $data[$i], 2);
            $ret[substr($k, 0, -1)] = substr($v, 1);
        }
        
        return $ret;
    }
}

if(!function_exists('exif_imagetype')) {
// http://us3.php.net/manual/en/function.exif-imagetype.php#80383
// orig author: tom dot ghyselinck at telenet dot be
// modified a bit by me
    function exif_imagetype ( $filename ) {
        if ( ( list(,,$type,) = getimagesize( $filename ) ) !== false ) {
            return $type;
        }
        return IMAGETYPE_PNG; // meh
    }
}

if(version_compare(PHP_VERSION, '5.2.0', '<')) {    
    function get_filename($path) {
        $pathinf = pathinfo($path);
        return substr($pathinf['basename'], 0, 0-strlen('.'.$pathinf['extension']) );
    }
}else {
    function get_filename($path) {
        return pathinfo($path, PATHINFO_FILENAME);
    }
}

if(version_compare(PHP_VERSION, '5.1.2', '<')) {
    function get_hostname($url) {
        $urlinf = parse_url($path);
        return $urlinf['host'];
    }
}else {
    function get_hostname($url) {
        return parse_url($url, PHP_URL_HOST);
    }
}

if(version_compare(PHP_VERSION, '5.0.0', '<')) {
    /***
     * The following has all been taken from the http://php.net/html_entity_decode comments.
     */
    function html_entity_decode_utf8($string)
    {
         static $trans_tbl;

//        echo 'starting with: '.$string."<BR>";

         // replace numeric entities
        $string = preg_replace('~&#x0*([0-9a-f]+);~ei', 'code2utf(hexdec("\\1"))', $string);
        $string = preg_replace('~&#0*([0-9]+);~e', 'code2utf(\\1)', $string);
        
         // replace literal entities
         if (!isset($trans_tbl))
         {
              $trans_tbl = array();
             
              foreach (get_html_translation_table(HTML_ENTITIES) as $val=>$key)
                    $trans_tbl[$key] = utf8_encode($val);
         }
        
         return strtr($string, $trans_tbl);
    }
        
    function code2utf($number)
    {
        if ($number < 0)
            return FALSE;
       
        if ($number < 128)
            return chr($number);
       
        // Removing / Replacing Windows Illegals Characters
        if ($number < 160)
        {
                if ($number==128) $number=8364;
            elseif ($number==129) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==130) $number=8218;
            elseif ($number==131) $number=402;
            elseif ($number==132) $number=8222;
            elseif ($number==133) $number=8230;
            elseif ($number==134) $number=8224;
            elseif ($number==135) $number=8225;
            elseif ($number==136) $number=710;
            elseif ($number==137) $number=8240;
            elseif ($number==138) $number=352;
            elseif ($number==139) $number=8249;
            elseif ($number==140) $number=338;
            elseif ($number==141) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==142) $number=381;
            elseif ($number==143) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==144) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==145) $number=8216;
            elseif ($number==146) $number=8217;
            elseif ($number==147) $number=8220;
            elseif ($number==148) $number=8221;
            elseif ($number==149) $number=8226;
            elseif ($number==150) $number=8211;
            elseif ($number==151) $number=8212;
            elseif ($number==152) $number=732;
            elseif ($number==153) $number=8482;
            elseif ($number==154) $number=353;
            elseif ($number==155) $number=8250;
            elseif ($number==156) $number=339;
            elseif ($number==157) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==158) $number=382;
            elseif ($number==159) $number=376;
        } //if
       
        if ($number < 2048)
            return unichr(($number >> 6) + 192) . unichr(($number & 63) + 128);
        if ($number < 65536)
            return unichr(($number >> 12) + 224) . unichr((($number >> 6) & 63) + 128) . unichr(($number & 63) + 128);
        if ($number < 2097152)
            return unichr(($number >> 18) + 240) . unichr((($number >> 12) & 63) + 128) . unichr((($number >> 6) & 63) + 128) . chr(($number & 63) + 128);
       
       
        return FALSE;
    } //code2utf()    
    function unichr($c) {
//            echo 'unichr: '.$c."<BR>";

         if ($c <= 0x7F) {
              return chr($c);
         } else if ($c <= 0x7FF) {
              return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
         } else if ($c <= 0xFFFF) {
              return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F)
                                                    . chr(0x80 | $c & 0x3F);
         } else if ($c <= 0x10FFFF) {
              return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F)
                                                    . chr(0x80 | $c >> 6 & 0x3F)
                                                    . chr(0x80 | $c & 0x3F);
         } else {
              return false;
         }
    }
}else {
    function html_entity_decode_utf8($text) {
        return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }
}

if(version_compare(PHP_VERSION, '4.3.0', '<'))
    err('PHP_TOO_OLD');
if(version_compare(PHP_VERSION, '6.0.0', '>='))
    err('PHP_UNSUPPORTED');
    
if(false !== ALLOWED_DOMAIN && $_SERVER['HTTP_REFERER'] != '') {
    $refhost = get_hostname($_SERVER['HTTP_REFERER']);
    if(substr(ALLOWED_DOMAIN, 0, 1) == '.') {
        if(false === strpos($refhost, substr(ALLOWED_DOMAIN, 1)))
            err('DISALLOWED_DOMAIN');
    }else {
        if($refhost != ALLOWED_DOMAIN) 
            err('DISALLOWED_DOMAIN');
    }
}












$fonts_dir = str_replace('\\', '/', realpath(FONTS_DIR.'/'));

if(substr($fonts_dir, -1) != '/')
    $fonts_dir .= '/';

$FLIR = array();
$FStyle = preg_match('#^\{("[\w]+":"[^"]*",?)*\}$#i', $_GET['fstyle'])?json_decode($_GET['fstyle'], true):array();

$FLIR['mode']    = isset($FStyle['mode']) ? $FStyle['mode'] : '';
$FLIR['output']  = isset($FStyle['output']) ? ($FStyle['output']=='jpeg'?'jpg':$FStyle['output']) : 'auto';

$FLIR['bkg_transparent'] = is_transparent($FStyle['cBackground']);

if($FLIR['output'] == 'auto')
    $FLIR['output'] = $FLIR['bkg_transparent'] ? 'png' : 'gif';
    
// format not supported, fall back to png
if(($FLIR['output'] == 'gif' && !function_exists('imagegif')) || ($FLIR['output'] == 'jpg' && !function_exists('imagejpeg')))
    $FLIR['output'] = 'png';

$FLIR['dpi'] = preg_match('#^[0-9]+$#', $FStyle['dpi']) ? $FStyle['dpi'] : 96;
$FLIR['size']     = is_number($FStyle['cSize'], true) ? $FStyle['cSize'] : UNKNOWN_FONT_SIZE; // pixels
$FLIR['size_pts'] = ENABLE_FONTSIZE_BUG ? $FLIR['size'] : get_points($FLIR['dpi'], $FLIR['size']);
$FLIR['maxheight']= is_number($_GET['h']) ? $_GET['h'] : UNKNOWN_FONT_SIZE; // pixels
$FLIR['maxwidth']= is_number($_GET['w']) ? $_GET['w'] : 800; // pixels

$font_file = '';
$FStyle['cFont'] = strtolower($FStyle['cFont']);
$FONT_PARENT = false;
if(isset($fonts[$FStyle['cFont']])) {
    $font_file = $fonts[$FStyle['cFont']];
    
    if(is_array($font_file)) {
        $FONT_PARENT = reset($font_file);
        $font_file = match_font_style($font_file);
        $FONT_PARENT = $fonts_dir.(isset($FONT_PARENT['file']) ? $FONT_PARENT['file'] : $font_file);
    }
}elseif(FONT_DISCOVERY) {
    $font_file = discover_font($fonts['default'], $FStyle['cFont']);
}else {
    $font_file = $fonts['default'];
}
$FLIR['font']     = $fonts_dir.$font_file;

//die($FStyle['cFont']);

if(!is_file($FLIR['font']))
    err('FONT_DOESNT_EXIST');
    
if(in_array(strtolower(pathinfo($FLIR['font'], PATHINFO_EXTENSION)), array('pfb','pfm'))) { // pfm doesn't work
    // You can try uncommenting this line to see what kind of mileage you get.
    err('FONT_PS_UNSUPPORTED'); // PostScript will work as long as you don't set any kind of spacing... unless you are using Windows (PHP bug?).
    
    $FLIR['postscript'] = true;
    $FLIR['ps'] = array('kerning' => 0, 'space' => 0);
    if(false === (@$FLIR['ps']['font'] = imagepsloadfont($FLIR['font']))) 
        err('FONT_PS_COULDNT_LOAD');
}
    
$FLIR['color']         = convert_color($FStyle['cColor']);

if($FLIR['bkg_transparent']) {
    $FLIR['bkgcolor'] = array('red'         => abs($FLIR['color']['red']-100)
                                    , 'green'     => abs($FLIR['color']['green']-100)
                                    , 'blue'     => abs($FLIR['color']['blue']-100));
}else {
    $FLIR['bkgcolor'] = convert_color($FStyle['cBackground'], false, 'FFFFFF');
}

$FLIR['opacity'] = is_number($FStyle['cOpacity'], true) ? $FStyle['cOpacity']*100 : 100;
if($FLIR['opacity'] > 100 || $FLIR['opacity'] < 0) 
    $FLIR['opacity'] = 100;    

$FLIR['text']     = $_GET['text']!=''?str_replace(array('{amp}nbsp;', '{amp}', '{plus}'), array(' ','&','+'), trim($_GET['text'], "\t\n\r")):'null';

$FLIR['cache']     = get_cache_fn(md5(($FLIR['mode']=='wrap'?$FLIR['maxwidth']:'').$FLIR['font'].(print_r($FStyle,true).$FLIR['text'])), $FLIR['output']);

$FLIR['text_encoded'] = $FLIR['text'];
$FLIR['text'] = $FLIR['original_text'] = strip_tags(html_entity_decode_utf8($FLIR['text']));

$SPACE_BOUNDS = false;
if(is_number($FStyle['cSpacing'], true, false, true)) {
    $SPACE_BOUNDS = bounding_box(' ');
    $spaces = ceil(($FStyle['cSpacing']/$SPACE_BOUNDS['width']));
    if($spaces>0) {
        $FLIR['text'] = space_out($FLIR['text'], $spaces);
        define('SPACING_GAP', $spaces);
    }
    
    if($FLIR['postscript']) {
        $FLIR['ps']['kerning'] = ($FStyle['cSpacing']/$FLIR['size'])*1000;
    }
}

if($FLIR['postscript'] && isset($FStyle['space_width'])) {
    $FLIR['ps']['space'] = ($FStyle['space_width']/$FLIR['size'])*1000;
}

if(($SPACES_COUNT = substr_count($FLIR['text'], ' ')) == strlen($FLIR['text'])) {
    if(false === $SPACE_BOUNDS)
        $SPACE_BOUNDS = bounding_box(' '); 
        
    $FLIR['cache'] = get_cache_fn(md5($FLIR['font'].$FLIR['size'].$SPACES_COUNT));
    $FLIR['mode'] = 'spacer';
}

if(file_exists($FLIR['cache']) && !DEBUG) {
    output_file($FLIR['cache']);
}else {    
    verify_gd();
    
    $REAL_HEIGHT_BOUNDS = $FStyle['realFontHeight']=='true' ? bounding_box(HBOUNDS_TEXT, (false !== $FONT_PARENT ? $FONT_PARENT : $FLIR['font'])): false;
    
    switch($FLIR['mode']) {
        default:
            $dir = dir(PLUGIN_DIR);
            $php_mode = strtolower($FLIR['mode'].'.php');
            while(false !== ($entry = $dir->read())) {
                $p = PLUGIN_DIR.'/'.$entry;
                if(is_dir($p) || $entry == '.' || $entry == '..') continue;
                
                if($php_mode == strtolower($entry)) {
                    $dir->close();
                    $PLUGIN_ERROR = false;                    
                    
                    include($p);
                                        
                    if(false !== $PLUGIN_ERROR)
                        break;
                    else
                        break(2);
                }
            }
            $dir->close();

            $bounds = bounding_box($FLIR['text']);
            if($FStyle['realFontHeight']!='true') 
                $REAL_HEIGHT_BOUNDS = $bounds;

            if(false === (@$image = imagecreatetruecolor($bounds['width'], $REAL_HEIGHT_BOUNDS['height'])))
                err('COULD_NOT_CREATE');
                
            gd_alpha();
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), gd_bkg());
            render_text($bounds);
            break;
        case 'wrap':
            if(!is_number($FStyle['cLine'], true))
                $FStyle['cLine'] = 1.0;

            $bounds = bounding_box($FLIR['text']);
            if($FStyle['realFontHeight']!='true') 
                $REAL_HEIGHT_BOUNDS = $bounds;
    
            // if mode is wrap, check to see if text needs to be wrapped, otherwise let continue to progressive
            if($bounds['width'] > $FLIR['maxwidth']) {
                $image = imagettftextbox($FLIR['size_pts'], 0, 0, 0, $FLIR['color'], $FLIR['font'], $FLIR['text'], $FLIR['maxwidth'], strtolower($FStyle['cAlign']), $FStyle['cLine']);
                break;
            }else {
                if(false === (@$image = imagecreatetruecolor($bounds['width'], $REAL_HEIGHT_BOUNDS['height'])))
                    err('COULD_NOT_CREATE');

                gd_alpha();
                imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), gd_bkg());
                render_text($bounds);
            }
            break;
        case 'progressive':
            $bounds = bounding_box($FLIR['text']);
            if($FStyle['realFontHeight']!='true') 
                $REAL_HEIGHT_BOUNDS = $bounds;
            
            $offset_left = 0;
            
            $nsize=$FLIR['size_pts'];
            while(($REAL_HEIGHT_BOUNDS['height'] > $FLIR['maxheight'] || $bounds['width'] > $FLIR['maxwidth']) && $nsize > 2) {
                $nsize-=0.5;
                $bounds = bounding_box($FLIR['text'], NULL, $nsize);
                $REAL_HEIGHT_BOUNDS = $FStyle['realFontHeight']=='true' ? bounding_box(HBOUNDS_TEXT, NULL, $nsize) : $bounds;
            }
            $FLIR['size_pts'] = $nsize;
    
            if(false === (@$image = imagecreatetruecolor($bounds['width'], $REAL_HEIGHT_BOUNDS['height'])))
                err('COULD_NOT_CREATE');

            gd_alpha();
            imagefilledrectangle($image, $offset_left, 0, imagesx($image), imagesy($image), gd_bkg());
            
            imagettftext($image, $FLIR['size_pts'], 0, $bounds['xOffset'], $REAL_HEIGHT_BOUNDS['yOffset'], gd_color(), $FLIR['font'], $FLIR['text']);
            render_text($bounds);
            break;
            
        case 'spacer':
            if(false === (@$image = imagecreatetruecolor(($SPACE_BOUNDS['width']*$SPACES_COUNT), 1)))
                err('COULD_NOT_CREATE');

            imagesavealpha($image, true);
            imagealphablending($image, false);
    
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), gd_bkg());
            break;
    }

    if($FLIR['postscript'])
        imagepsfreefont($FLIR['ps']['font']);

    if(false !== $image) {
        switch($FLIR['output']) {
            default:
            case 'png':
                imagepng($image, $FLIR['cache']);
                break;
            case 'gif':
                imagegif($image, $FLIR['cache']);
                break;
            case 'jpg':
                $qual = is_number($FStyle['quality']) ? $FStyle['quality'] : 90;
                imagejpeg($image, $FLIR['cache'], $qual);
                break;
        }
        imagedestroy($image);
    }

    output_file($FLIR['cache']);    
} // if(file_exists($FLIR['cache'])) {

flush();

if(CACHE_CLEANUP_FREQ != -1 && rand(1, CACHE_CLEANUP_FREQ) == 1)
    @cleanup_cache();
?>