<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

include '-grid.php';

$plugin_class = 'Fonts';

class Fonts extends CacheerPlugin
{

	function process($css)
	{
		
		
		return $css;
	}
	
}

?>