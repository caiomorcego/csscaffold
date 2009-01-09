<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'Browsers';

class Browsers extends CacheerPlugin
{
	function separateCSS($css, $class)
	{
		global $css_dir;
		
		if(preg_match_all("/(\.".$class.".*?)(\{.*?\})/sx",$css,$matches))
		{
			foreach($matches[1] as $key => $match)
			{
				$ie6_selectors = array();
				
				$selectors = explode(",", $match);
				
				// Remove the selectors that don't contain .ie6 from the string
				foreach($selectors as $k => $selector)
				{
					if(stristr($selector, ".".$class) == TRUE) {
    					array_push($ie6_selectors, $selector);
    					unset($selectors[$k]);
  					}
				}
								
				$ie6_selectors 	= implode(",", $ie6_selectors);
				$selectors 		= implode(",", $selectors);
				
				$properties 		= $matches[2][$key];
				
				$ie6_string 		.= $ie6_selectors.$properties;
				$old_string 		= $matches[0][$key];
				$new_string 		= $selectors.$properties;
				
				$css = str_replace($old_string,$new_string,$css);
		
			}
			
			$ie6_string = str_replace(".".$class." ",'',$ie6_string);	

		}
		
		$fp = $_SERVER['DOCUMENT_ROOT'].$css_dir."/browser-specific/".$class.".css";
			
		$file = fopen($fp, "w") or die("Can't open the file");
	
		// Write the string to the file
		chmod($file, 777);
		fwrite($file, $ie6_string);
		fclose($file);
	}
	
	function post_process($css)
	{
		$this -> separateCSS($css, 'ie6');
		$this -> separateCSS($css, 'ie7');
			
		return $css;
	}
}

?>