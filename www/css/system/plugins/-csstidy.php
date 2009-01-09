<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

@(include('-tidy.php')) OR die ("Can't open the CSSTidy files");

echo "hi";exit;

$plugin_class = 'CSSTidy';

// This plugin isn't ready yet

class CSSTidy extends CacheerPlugin
{
	
	function post_process($css)
	{
	
		echo "hi";exit;
		$csstidy = new csstidy();
		
		
//		$csstidy->set_cfg('preserve_css',false);
//		$csstidy->set_cfg('sort_selectors',false);
//		$csstidy->set_cfg('sort_properties',true);
//		$csstidy->set_cfg('merge_selectors',2);
//		$csstidy->set_cfg('optimise_shorthands',1);
//		$csstidy->set_cfg('compress_colors',true);
//		$csstidy->set_cfg('compress_font-weight',false);
//		$csstidy->set_cfg('lowercase_s',true);
//		$csstidy->set_cfg('case_properties',1);
//		$csstidy->set_cfg('remove_bslash',false);
//		$csstidy->set_cfg('remove_last_;',true);
//		$csstidy->set_cfg('discard_invalid_properties',false);
//		$csstidy->set_cfg('css_level','CSS2.1');
//		$csstidy->set_cfg('timestamp',false);
		
		$csstidy->load_template('highest_compression');
		
		
		
		$result = $csstidy->parse($css_string);
		
		echo "hi";exit;
		
		$css_string = $csstidy->print->plain();

		return $css;
	}
}

?>