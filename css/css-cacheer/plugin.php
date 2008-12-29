<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

/******************************************************************************
 CacheerPlugin

 Cacheer plugins have 3 methods. Each method is passed a string containing the 
 requested css file. Each MUST return that string after processing. All plugins
 should `extend CacheerPlugin`. If the plugin does not make use of one of the
 methods below, don't implement it and let inheritance do its thing.

 Plugins that that do something based on a condition should either require an
 argument in the requested CSS files query string or set their own flag in the
 $_GET superglobal so that the outcome of different conditions can be cached
 individually. See the base64 plugin for an example of this idea.

 Plugins are loaded in alphabetical order according to the server file system.
 Processing order can be loosely controlled using the appropriate method below.
 More granular control will be provided as required.

 ******************************************************************************/
class CacheerPlugin
{
	var $flags = array();
	
	// each overridden method MUST return the original or processed css
	function pre_process($css)	{ return $css; } 	// for importers and simple replacements
	function process($css) 		{ return $css; }	// for the heavy lifting
	function post_process($css)	{ return $css; }	// for formatters
}