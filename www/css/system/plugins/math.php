<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'Math';

class Math extends CacheerPlugin
{	
	function process($css)
	{
		global $settings;
		
		if(preg_match_all('/math\([\"|\']?(.*?)[\"|\']?\)/', $css, $matches))
		{
			foreach($matches[1] as $key => $match)
			{	
				$match = str_replace('px', '', $match);	
				eval("\$result = ".$match.";");
				$css = str_replace($matches[0][$key], $result, $css);
			}
		}
		
		if(preg_match_all('/round\((\d+)\)/', $css, $matches))
		{
			foreach($matches[1] as $key => $match)
			{
				$num = $this->round_nearest($match,$settings['baseline']);
				$css = str_replace($matches[0][$key],$num."px",$css);
			}
		}
		
		return $css;
	}
	
	// Round a number to the nearest multiple
	function round_nearest($number,$multiple) 
	{ 
    	return round($number/$multiple)*$multiple; 
	}
	
	
}

?>