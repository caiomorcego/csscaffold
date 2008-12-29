<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'PrettyPlugin';
class PrettyPlugin extends CacheerPlugin
{
	function post_process($css)
	{
		$css = preg_replace('#(/\*[^*]*\*+([^/*][^*]*\*+)*/|url\(data:[^\)]+\))#e', "'esc('.base64_encode('$1').')'", $css); // escape comments, data protocol to prevent processing
		
		$css = str_replace(';', ";\r\r", $css); // line break after semi-colons (for @import)
		$css = preg_replace('#([-a-z]+):\s*([^;}{]+);\s*#i', "$1: $2;\r\t", $css); // normalize property name/value space
		$css = preg_replace('#\s*\{\s*#', "\r{\r\t", $css); // normalize space around opening brackets
		$css = preg_replace('#\s*\}\s*#', "\r}\r\r", $css); // normalize space around closing brackets
		$css = preg_replace('#,\s*#', ",\r", $css); // new line for each selector in a compound selector
		// remove returns after commas in property values
		if (preg_match_all('#:[^;]+,[^;]+;#', $css, $m))
		{
			foreach($m[0] as $oops)
			{
				$css = str_replace($oops, preg_replace('#,\r#', ', ', $oops), $css);
			}
		}
		$css = preg_replace('#esc\(([^\)]+)\)#e', "base64_decode('$1')", $css); // unescape escaped blocks
		
		// indent nested @media rules
		if (preg_match('#@media[^\{]*\{(.*\}\s*)\}#', $css, $m))
		{
			$css = str_replace($m[0], str_replace($m[1], "\r\t".preg_replace("#\r#", "\r\t", trim($m[1]))."\r", $m[0]), $css);
		}
		
		return $css;
	}
}