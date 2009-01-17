<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

include '-grid.php';

$plugin_class = 'Grid';

class Grid extends CacheerPlugin
{
	function pre_process($css)
	{	
		// Create a new GridCSS object and put the css into it
		$grid = new GridCSS($css);
		
		// Generate the grid.css
		$grid -> generateGrid($css);
		
		// Generate the grid.png
		$grid -> generateGridImage($css);
		
		// Replace the grid() variables
		$css = $grid -> replaceGridVariables($css);
		
		// Send the parsed css back to cacheer
		return $css;
	}
	
	function process($css)
	{
		// Create a new GridCSS object and put the css into it
		$grid = new GridCSS($css);
		
		// Create the layouts xml for use with the tests
		$grid -> generateLayoutXML($css);
		
		//$css = $this -> math($css);
		$css = $grid -> replaceColumns($css);
		
		return $css;
	}
	
	function post_process($css)
	{
		// Create a new GridCSS object and put the css into it
		$grid = new GridCSS($css);
		
		// Remove the settings
		$css = $grid -> removeSettings($css);
		
		return $css;
	}
}

?>