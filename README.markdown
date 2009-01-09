#CSScaffold

**Not ready for use just yet :)**

A dynamic CSS framework built on top of Shaun Inman's CSS Cacheer. Favouring convention over configuration, it aims to speed up development time by reducing the number of times you need to repeat yourself. It extends into the markup to make sure everything is consistent. By standardizing the markup, it makes it extremely easy to create templates and frameworks for common items.

- Constants
- Base a selector on another selector
- Assign classes to selectors within your CSS (In development)
- Easy grid layout system (no more floats or positioning - we use columns instead)
- Generated and included utility classes
- Easy image replacement (using image-replace:url('url');)
- Embed images in your CSS using Base64 to save http requests
- Tidy and Compress your CSS on the fly
- Cached and Gzipped for speedy download
- Nested Selectors
- Form Framework for building forms quickly
- Global reset
- Development styles for debugging and testing
- Module-based css, broken up into common areas.

##Installation

To install Scaffold, all you need to do is placed everything from the www directory into your servers www directory or root directory. You also need to make sure these files are set to CHMOD 777 or Read and Write:

-css/images/*
-css/fonts/
-css/browser-specific/
-css/sections/
-css/snippets/
-css/system/cache
-css/

## Usage

Once you've 'installed' Scaffold, any css file inside the root level of css/ will be processed by CSS Cacheer. So link to the screen and print stylesheets like so in your HTML:

	<link href="css/screen.css?recache" media="screen" rel="stylesheet" type="text/css" />
	<link href="css/print.css?recache" media="print" rel="stylesheet" type="text/css" />
	
?recache is used at the end of the file name to tell the script to recache it everytime. When you are putting your site live, remove this otherwise you won't actually be benefitting from the cached files. 

##Notes:

- Paths are always relative to screen.css, even in deeper folders, if they are imported.

##New:

- grid(maxcol) will output the width of the maximum amount of columns. Useful when the column count can change.