<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'ImageReplacement';

class ImageReplacement extends CacheerPlugin
{
	function process($css)
	{		
		// Find all the image-replace:; properties
		preg_match_all('/image\-replace\:\s*url\([\'\"]*(.*?)[\'\"]*\)\s*\;/',$css, $match);
		
		// Loop through each of them, put their unique url in the properties string, and replace it. 
		foreach ($match[0] as $key => $value) {
		
			// The basic image replacement properties
			$properties = "display:block;text-indent:-9999px;overflow:hidden;";
		
			// Pull out the url
			$url = $match[1][$key]; 
			
			// Get the image size
			$size = GetImageSize($_SERVER['DOCUMENT_ROOT'].'/'.$url);
			$width = $size[0];
			$height = $size[1];
			
			// Make sure theres a value so it doesn't break the css
			if(!$width && !$height)
			{
				$width = $height = 0;
			}
			
			// Substitute in the dynamic values
			$properties .= "background:url($url) no-repeat 0 0;height:".$height."px;width:".$width."px;";
			$css = str_replace($value, $properties, $css);
			
		}
		
		return $css;
	}

}

?>