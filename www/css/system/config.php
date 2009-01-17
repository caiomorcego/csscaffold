<?php

// Name of the system directory relative to your css folder
$system_dir = "system";

// Path of generated css files to your css folder
$generated_dir = "snippets";

// Generate the header at the top of the CSS - useful for debugging plugins
$show_header = true;

// CSSTidy Options
$tidy_options = array(
	
		'preserve_css' 			=> false,
		'sort_selectors' 			=> false,
		'sort_properties' 		=> true,
		'merge_selectors' 		=> 2,
		'optimise_shorthands'	=> 1,
		'compress_colors' 		=> true,
		'compress_font-weight' 	=> false,
		'lowercase_s' 			=> true,
		'case_properties' 		=> 1,
		'remove_bslash' 			=> false,
		'remove_last_;' 			=> true,
		'discard_invalid_properties' => false,
		'css_level' 				=> 'CSS2.1',
		'timestamp' 				=> false,
		'template'					=> 'highest_compression'
	
);

// Control the order the plugins are loaded. Comment out plugins you don't want to load.
$plugin_order = array(
	
		// 'Benchmark' doesn't need to be included 
		'ServerImportPlugin',
		'NestedSelectorsPlugin',
		'ConstantsPlugin',
		'BasedOnPlugin',
		'Math',
		'Grid',
		'Classes',
		//'Fonts',
		'ImageReplacement',
		'Append',
		'DesignOverlay',
		'BorderRadiusPlugin',
		'Base64Plugin',
		'CSSTidyPlugin',
		'CondenserPlugin',
		'Browsers'	,
		//'PrettyPlugin'
		
);

?>