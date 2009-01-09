<?php

error_reporting(0);
/******************************************************************************
 Used to prevent direct access to CSS Cacheer files
 ******************************************************************************/
define('CSS_CACHEER', true);

// Anthony: Changed the directory back one, so we can use it
chdir('../');

//Anthony: Manually control the settings
include 'config.php';

/******************************************************************************
 Received request from mod_rewrite
 ******************************************************************************/
// absolute path to requested file, eg. /css/nested/sample.css
$requested_file	= isset($_GET['cssc_request']) ? $_GET['cssc_request'] : '';
// absolute path to directory containing requested file, eg. /css/nested
$requested_dir	= preg_replace('#/[^/]*$#', '', $requested_file);
// absolute path to css directory, eg. /css
$css_dir = preg_replace('#/[^/]*$#', '', (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_URL']);


// Anthony: Changed this for a quick fix. Removes the system directory
$css_dir = str_replace("/".$system_dir, '', $css_dir);


/******************************************************************************
 Limit processing to existing css files within this and nested directories
 ******************************************************************************/
if 
(
	substr($requested_file, -4) != '.css' ||
	substr($requested_file, 0, strlen($css_dir)) != $css_dir ||
	!file_exists(substr($requested_file, strlen($css_dir) + 1))
)
{
	echo '/* Invalid Request */';
	exit();
}

/******************************************************************************
 Load plugins
 ******************************************************************************/

include($system_dir."/plugin.php");
$flags = array();
$plugins = array();
$plugin_path = $system_dir."/plugins";

if (is_dir($plugin_path))
{
	if ($dir_handle = opendir($plugin_path)) 
	{
		while (($plugin_file = readdir($dir_handle)) !== false) 
		{
			if (substr($plugin_file, 0, 1) == '.' || substr($plugin_file, 0, 1) == '-')
			{ 
				continue; 
			}
			include($plugin_path.'/'.$plugin_file);
			if (isset($plugin_class) && class_exists($plugin_class))
			{
				$plugins[$plugin_class] = new $plugin_class($flags);
				$flags = array_merge($flags, $plugins[$plugin_class]->flags);
			}
		}
		closedir($dir_handle);
	}
}

// Anthony: Added this to control the plugin order.
$plugins = pluginOrder($plugins);

//print_r($plugins);exit;

/******************************************************************************
 Create hash of query string to allow variables to be cached
 ******************************************************************************/
$recache = isset($_GET['recache']);
$args = $flags;
ksort($args);
$checksum = md5(serialize($args));

/******************************************************************************
 Determine relative and cache paths
 ******************************************************************************/
$cssc_cache_dir = $system_dir."/cache/";

// path to requested file, relative to css directory, eg. nested/sample.css
$relative_file = substr($requested_file, strlen($css_dir) + 1);

// path to directory containing requested file, relative to css directory, eg. nested
$relative_dir = (strpos($relative_file, '/') === false) ? '' : preg_replace("/\/[^\/]*$/", '', $relative_file);
//$relative_dir .= "../";

// path to cache of requested file, relative to css directory, eg. css-cacheer/cache/nested/sample.css
$cached_file = $cssc_cache_dir.preg_replace('#(.+)(\.css)$#i', "$1-{$checksum}$2", $relative_file);

// path to directory containing cache of requested CSS file, relative from the directory containing cache.php, eg. cache/nested
$cached_dir = $cssc_cache_dir;

//echo "Requested File: " . $requested_file . "\n";
//echo "Requested Dir: " . $requested_dir . "\n";
//echo "Relative File: " . $relative_file . "\n ";
//echo "Relative Dir: " . $relative_dir . "\n";
//echo "Cached File: " . $cached_file . "\n";
//echo "Cached Dir: " . $cached_dir . "\n";
//exit;

/******************************************************************************
 Delete file cache
 ******************************************************************************/
if ($recache && file_exists($cached_file))
{
	unlink($cached_file);
}

/******************************************************************************
 Get modified time for requested file and if available, its cache
 ******************************************************************************/
$requested_mod_time	= filemtime($relative_dir.$relative_file);
$cached_mod_time	= (int) @filemtime($cached_file);
// cache may not exist, silence error with @


/******************************************************************************
 Recreate the cache if stale or nonexistent
 ******************************************************************************/
if ($cached_mod_time < $requested_mod_time)
{	
	include_once($system_dir."/index.php");	
}
/******************************************************************************
 Or send 304 header if appropriate
 ******************************************************************************/
else if 
(
	isset($_SERVER['HTTP_IF_MODIFIED_SINCE'], $_SERVER['SERVER_PROTOCOL']) && 
	$requested_mod_time <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])
)
{
	header("{$_SERVER['SERVER_PROTOCOL']} 304 Not Modified");
	exit();
}

/******************************************************************************
 Send cached file to browser
 ******************************************************************************/
header('Content-Type: text/css');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', $requested_mod_time).' GMT');
@include($cached_file);