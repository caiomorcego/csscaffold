<?php

/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

/******************************************************************************
 Grab the modified CSS file
 ******************************************************************************/
$css = file_get_contents($relative_dir.$relative_file);

// Pre-process for importers
foreach($plugins as $plugin)
{
	$css = $plugin->pre_process($css);
}

// Process for heavy lifting
foreach($plugins as $plugin)
{
	$css = $plugin->process($css);
}

// Post-process for formatters
foreach($plugins as $plugin)
{
	$css = $plugin->post_process($css);
}

/******************************************************************************/
$header  = '/* Processed and cached by Shaun Inman\'s CSS Cacheer';
$header .= ' (with '.str_replace('Plugin', '', preg_replace('#,([^,]+)$#', " &$1", join(', ', array_keys($plugins)))).' enabled)';
$header .= ' on '.gmdate('r').' <http://shauninman.com/search/?q=cacheer> */'."\r\n";
$css = $header.$css;

/******************************************************************************
 Make sure the target directory exists
 ******************************************************************************/
if ($cached_file != $cached_dir && !is_dir($cached_dir))
{
	$path = $cssc_cache_dir;
	$dirs = explode('/', $relative_dir);
	foreach ($dirs as $dir)
	{
		$path .= '/'.$dir;
		mkdir($path, 0777);
	}
}

/******************************************************************************
 Cache parsed CSS
 ******************************************************************************/
$css_handle = fopen($cached_file, 'w');
fwrite($css_handle, $css);
fclose($css_handle);
chmod($cached_file, 0777);
touch($cached_file, $requested_mod_time);