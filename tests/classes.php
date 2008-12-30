<?php

	$css = <<<ENDOFSTRING
	
		/* Scaffold CSS Framework (Project Name) -----------------------------------------

			Created by:

				Anthony Short (anthony@anthonyshort.com.au)

			Changelog:

				27th December 2008
					- Made some changes to the CSS.

			Supported Browsers:

				- IE6+  
				- Safari 3+ 
				- Firefox 2+

			Notes: 

				1. --------------------------

				This stylesheet is processed with CSS Cacheer. Any CSS
				files from /css that are linked to in the HTML, are parsed
				through a series of PHP scripts. You'll notice some syntax
				which isn't native to CSS - this is code which the scripts
				recognise and parse.  

				These include, but are not limited to:

					@server import
					@grid
					@constants
					columns:1;
					grid(x);
					const();

				For more information read this blog post
					http://www.shauninman.com/archive/2008/05/30/check_out_css_cacheer

				2. --------------------------

				For content styling (ie. Blog posts), use the styles inside content.css


			Grid: 

				966px wide
				23px columns
				18px gutters
				24 columns

		-----------------------------------------------------------------------------*/

		/* @group Imports */

			@server import url("snippets/reset.css");
			@server import url("snippets/development.css");
			@server import url("sections/forms.css");
			@server import url("sections/content.css");

		/* @end */

		/* @group Settings */

			@grid {
				column-width:23;
				gutter-width:18;
				column-count:24;
				keep-settings:no;
				format:newline;
				generate-path: '/css/sections/'; 
				baseline:18;
			}

			@constants {
				textColor: #222;
				fontSize: 13px;
				lineHeight: 18px;
			}

			@font-face {
			  font-family: "other";
			  src: url("otherfont.ttf");
			}

		/* @end */


		/* @group Typography *//* -------------------------------------

			Typography

		-------------------------------------------------------------*/


			body 
			{ 
			  font: const(fontSize)/const(lineHeight) Helvetica, Arial, sans-serif;
			  background: #fff; 
			  color: const(textColor);
			}


			/* @group Headings */
			/* Headings
			-------------------------------------------------------------- */

			h1,h2,h3,h4,h5,h6 { font-weight: normal; color: #111; }

			h1 
			{ 
				font: 3em/1 "Helvetica Neue", Helvetica, Arial, sans-serif;  
				margin-bottom: 0.5em; 

				class:image-replaced;
				/* 
				image-replacement:url(/images/image-replacements/h1.png); 
				*/ 
			}

			h2 
			{
			 	font: 2em/1; 
			 	margin-bottom: 0.75em; 
			}

			h3 
			{ 
				font: 1.5em/1; 
				margin-bottom: 1em; 
			}

			h4 
			{ 
				font: 1.2em/1.25; 
				margin-bottom: 1.25em; 
			}

			h5 
			{ 
				font: bold 1em/1;  
				margin-bottom: 1.5em; 
			}

			h6 
			{ 
				font: bold 1em/1; 
			}

			h1 img, h2 img, h3 img, 
			h4 img, h5 img, h6 img {
			  margin: 0;
			}

			/* @end */


			/* @group Text elements */
			/* Text elements
			-------------------------------------------------------------- */

			p				{ margin: 0; }
			p + p			{ margin: 1.5em 0 0 0;}
			.ie6 p			{ margin-bottom: 1.5em; }

			blockquote  	{ margin: 1.5em; font-style: italic; color: #666; }

			strong      	{ font-weight: bold; }
			em      	  	{ font-style: italic; }

			pre,code    	{ margin: 1.5em 0; white-space: pre; }
			pre,code,tt 	{ font: 1em 'andale mono', 'lucida console', monospace; line-height: 1.5; }

			dfn         	{ font-weight: bold; font-style: italic;  }
			del         	{ color:#666; }
			ins 			{ background: #fffde2;text-decoration:none;}
			sup, sub    	{ line-height: 0; }
			abbr, 
			acronym     	{ border-bottom: 1px dotted; border-color:#666; }
			address     	{ margin: 0 0 1.5em; font-style: italic; }

			hr 				{ clear: both; float: none; width: 100%; height: 1px; margin: 18px 0; border: none; background:#ddd; }


			/* @end */



			/* @group Links */
			/* Links
			-------------------------------------------------------------------- */

			a:focus, 
			a:hover     	{ color: #000; }
			a           	{ color: #009; text-decoration: underline; }

			/* @end */



			/* @group Figures */
			/* Figures
			-------------------------------------------------------------- */

			p img       	{ float: left; margin: 1.5em 1.5em 1.5em 0; padding: 0; }

			/* @end */



			/* @group Lists */
			/* Lists
			-------------------------------------------------------------- */

			li ul, 
			li ol       { margin:0 1.5em; }
			ul, ol      { margin: 0 1.5em 1.5em 1.5em; }

			ul          { list-style-type: disc; }
			ol          { list-style-type: decimal; }

			dl          { margin: 0 0 1.5em 0; }
			dl dt       { font-weight: bold; }
			dd          { margin-left: 1.5em;}
			/* @end */



			/* @group Tables */
			/* Tables
				Note: Tables still need 'cellspacing="0"' in the markup.
			-------------------------------------------------------------- */

			table       { margin-bottom: 1.4em; width:100%; }
			th          { font-weight: bold; background: #C3D9FF; }
			th,td       { padding: 4px 10px 4px 5px; }
			tr.alt td   { background: #E5ECF9; }
			tfoot       { font-style: italic; }
			caption     { background: #eee; }

			/* @end */



			/* @group Misc classes */
			/* Misc classes
			-------------------------------------------------------------- */

			.small      { font-size: .8em; margin-bottom: 1.875em; line-height: 1.875em; }
			.large      { font-size: 1.2em; line-height: 2.5em; margin-bottom: 1.25em; }
			.hide       { display: none; }

			.quiet      { color: #666; }
			.loud       { color: #000; }
			.highlight  { background:#ff0; }
			.added      { background:#060; color: #fff; }
			.removed    { background:#900; color: #fff; }

			.first      { margin-left:0; padding-left:0; }
			.last       { margin-right:0; padding-right:0; }
			.top        { margin-top:0; padding-top:0; }
			.bottom     { margin-bottom:0; padding-bottom:0; }

			/* @end */



			/* @group Image Replacement */
			/* Image Replacement
			-------------------------------------------------------------- */

			.image-replaced
			{
				display:block;
				text-indent:-9999px;
				background:no-repeat 0 0;
				overflow:hidden;
			}

			/* @end */


		/* @end */

		/* @group Navigation *//* -------------------------------------------

			Navigation

		--------------------------------------------------------------------*/

			#navigation			{ margin:0;padding:0; }
			#navigation li 		{ float:left;padding:0; }
			#navigation li a 		{ display:block; }

		/* @end */


		/* @group Layout *//* ----------------------------------------------

			Layout

		--------------------------------------------------------------------*/


			.container,
			#page		 	{ width: grid(24col); margin: 0 auto; position:relative; overflow: hidden;background: url('/images/backgrounds/grid.png'); }	

			#header		{ columns:24!; height: 108px; }
			#footer		{ columns:24!; height: 108px; margin: 0; }



			/* @group Layout Utilities */
			/* Layout Utilities
			---------------------------------------------------------------- */

			.clear 			{ overflow:hidden; }
			.clearfix:after 	{ content:"."; display:block; height:0; clear:both; visibility:hidden; }

			/* 
			Any div that is used for layout, should have a 'column' class.
			*/

			.column 		{ float:left; margin: 0 18px 18px 0; text-align: center; opacity:0.7; background: lightblue; }
			.ie6 .column 	{ overflow:hidden; display:inline; }

			.column:last-child,
			.column:only-child
			.last 			{ margin-right: 0; }	

			/* @end */	



			/* @group Layouts */
			/* Layouts
			---------------------------------------------------------------- */

			#primary-content 						{ }
			#secondary-content					{ }
			#tertiary-content						{ }

			.layout-1	#primary-content 			{ columns:18!; padding: 8px; border: 1px solid #000; height:180px;}
			.layout-1	#secondary-content		{ columns:6!; margin-right:0; height:198px;}
			.layout-1	#tertiary-content			{ columns:24!; }

			.layout-2	#primary-content 			{ }
			.layout-2	#secondary-content		{ }
			.layout-2	#tertiary-content			{ }


			/* @end */



			/* @group Page Specific */
			/* Page Specific
			---------------------------------------------------------------- */	

			#home					{}
			#about					{}	
			#services				{}
			#contact				{}
			#search-results		{}	

			/* @end */



		/* @end *//*------------------------------------------------------- */

		/* @group Common Elements *//* ----------------------------------------------

			Common Elements

		--------------------------------------------------------------------*/


		/* @end */
ENDOFSTRING;

	preg_match_all("/([\w#,.@\-\+\s:]+)\s*\{(.*?)\}/sx", $css, $selector);
	
	foreach ($selector[2] as $key => $properties)
	{		
		if(preg_match_all('/class:(.*?)\;/sx', $properties, $class))
		{	
			// $selector[1][$key] = h1
			// $class[1][0] = image-replaced
			$css = preg_replace('/.'.$class[1][0].'/sx', '.'.$class[1][0].','.$selector[1][$key], $css);
		}
	}

	

	echo $css;

?>