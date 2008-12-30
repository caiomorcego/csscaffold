<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'CondenserPlugin';
class CondenserPlugin extends CacheerPlugin
{
	function post_process($css)
	{
		$css = trim(preg_replace('#/\*[^*]*\*+([^/*][^*]*\*+)*/#', '', $css)); // comments
		$css = preg_replace('#\s+(\{|\})#', "$1", $css); // before
		$css = preg_replace('#(\{|\}|:|,|;)\s+#', "$1", $css); // after
		return $css;
	}
}