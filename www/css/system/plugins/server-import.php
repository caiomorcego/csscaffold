<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'ServerImportPlugin';
class ServerImportPlugin extends CacheerPlugin
{
	function pre_process($css)
	{
		global $relative_file, $relative_dir;
		
		$imported	= array($relative_dir.$relative_file);
		$context	= $relative_dir;
		while (preg_match_all('#@server\s+import\s+url\(([^\)]+)+\);#i', $css, $matches))
		{
			foreach($matches[1] as $i => $include)
			{
				$include = preg_replace('#^("|\')|("|\')$#', '', $include);

				// import each file once, only import css
				if (!in_array($include, $imported) && substr($include, -3) == 'css')
				{
					$imported[] = $include;
					if (file_exists($include))
					{
						$include_css = file_get_contents($include);
						$css = str_replace($matches[0][$i], $include_css, $css);
					}
					else
					{
						$css .= "\r\nerror { -si-missing: url('{$include}'); }";
					}
				}
				$css = str_replace($matches[0][$i], '', $css);
			}
		}
		
		return $css;
	}
}