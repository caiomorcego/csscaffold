<?php

// Name of the system directory
$system_dir = "system";

// Control the order the plugins are loaded. Comment out plugins you don't want to load.
function pluginOrder($plugins)
{
	$plugins = array(
	
		'ServerImportPlugin'		=> $plugins['ServerImportPlugin'],
		'ConstantsPlugin' 		=> $plugins['ConstantsPlugin'],
		'Base64Plugin' 			=> $plugins['Base64Plugin'],
		'NestedSelectorsPlugin' => $plugins['NestedSelectorsPlugin'],
		'BasedOnPlugin' 			=> $plugins['BasedOnPlugin'],
		//'Math' 					=> $plugins['Math'],
		'Grid' 					=> $plugins['Grid'],
		'Classes' 					=> $plugins['Classes'],
		'ImageReplacement' 		=> $plugins['ImageReplacement'],
		'CondenserPlugin' 		=> $plugins['CondenserPlugin'],
		'Browsers'			 		=> $plugins['Browsers'],
		//'PrettyPlugin' 			=> $plugins['PrettyPlugin']
		
	);
	return $plugins;
}

?>