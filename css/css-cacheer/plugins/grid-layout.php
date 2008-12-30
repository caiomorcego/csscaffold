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
		
		// Send the parsed css back to cacheer
		return $css;
	}
	
	function process($css)
	{
		// Create a new GridCSS object and put the css into it
		$grid = new GridCSS($css);
		
		// Build the grid using the css
		$css = $grid -> buildGrid($css);
		
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