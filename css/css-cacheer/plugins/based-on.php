<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'BasedOnPlugin';
class BasedOnPlugin extends CacheerPlugin
{
	function process($css)
	{
		$bases = array();
		if (preg_match_all('#@base\(([^\s\{]+)\)\s*\{(\s*[^\}]+)\s*\}\s*#i', $css, $matches))
		{
			// For each declaration
			foreach ($matches[0] as $key => $base)
			{
				// Remove the @base declaration
				$css = str_replace($base, '', $css);

				// Add declaration to our array indexed by base name
				$bases[$matches[1][$key]] = $matches[2][$key];
			}

			// Parse nested based-on properties, stopping at circular references
			foreach ($bases as $base_name => $properties)
			{
				$bases[$base_name] = $this->replace_bases($bases, $properties, $base_name);
			}
			
			// Now apply replaced based-on properties in our CSS
			$css = $this->replace_bases($bases, $css);
		}
		
		return $css;
	}
	
	function replace_bases($bases, $css, $current_base_name = false)
	{
		// As long as there's based-on properties in the CSS string
		// Get all instances
		while (preg_match_all('#\s*based-on:\s*base\(([^;]+)\);#i', $css, $matches))
		{
			// Loop through based-on instances
			foreach ($matches[0] as $key => $based_on)
			{
				$styles = '';
				$base_names = array();
				// Determine bases
				$base_names = preg_split('/[\s,]+/', $matches[1][$key]);
				// Loop through bases
				foreach ($base_names as $base_name)
				{
					// Looks like a circular reference, skip to next base
					if ($current_base_name && $base_name == $current_base_name)
					{
						$styles .= '/* RECURSION */';
						continue;
					}

					$styles .= $bases[$base_name];
				}

				// Insert styles this is based on
				$css = str_replace($based_on, $styles, $css);
			}
		}
		return $css;
	}
}