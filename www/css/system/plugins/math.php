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
		
		if(preg_match_all('/math\([\"|\']?(.*?)[\"|\']?\)/', $css, $matches))
		{
			foreach($matches[1] as $key => $match)
			{	
				$match = str_replace('px', '', $match);	
				eval("\$result = ".$match.";");
				$css = str_replace($matches[0][$key], $result, $css);
			}
		}
		
		return $css;
	}
}

?>