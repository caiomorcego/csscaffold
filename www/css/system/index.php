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
foreach($plugins as $plugin_class => $plugin)
{
	$css = $plugin->post_process($css);
	$filesize[$plugin_class] = strlen($css);
}


/******************************************************************************/
if ($show_header)
{
	$header  = '/* Processed and cached by Shaun Inman\'s CSS Cacheer';
	$header .= ' (with '.str_replace('Plugin', '', preg_replace('#,([^,]+)$#', " &$1", join(', ', array_keys($plugins)))).' enabled)';
	$header .= ' on '.gmdate('r').' <http://shauninman.com/search/?q=cacheer> */'."\r\n";
	$css = $header.$css;
}

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


/****************************************************************************
 Create the size report
 ****************************************************************************/
 
 // Output the benchmark text file
foreach($filesize as $plugin_class => $css_size)
{
	// Make the report line
	$s .= "Filesize after ".$plugin_class." => ".$css_size."\n";
}

// Create the ratio in the string
$size_ratio = end($filesize) / reset($filesize) * 100 ."%";

$s .= "\n\n Compression Ratio = ". $size_ratio;
$s .= "\n Final CSS Size (as file before Gzip) = ". fileSize($cached_file) ." bytes (". fileSize($cached_file) / 1024 . " kB)";

// Open the file relative to /css/
$benchmark_file = fopen("docs/css_report.txt", "w") or die("Can't open the report.txt file");
// Write the string to the file
chmod($benchmark_file, 777);
fwrite($benchmark_file, $s);
fclose($benchmark_file);