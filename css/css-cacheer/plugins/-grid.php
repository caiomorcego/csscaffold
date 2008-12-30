<?php

class GridCSS
{
	function __construct($css)
	{	
		global $settings;

		// Get the parameters from @grid
		$settings['format']			= 	$this -> getParam('format', $css);			/* newline or inline */
		$settings['columncount'] 	= 	$this -> getParam('column-count', $css);
		$settings['columnwidth']	= 	$this -> getParam('column-width', $css);
		$settings['gutterwidth']	= 	$this -> getParam('gutter-width', $css);
		$settings['baseline']		=	$this -> getParam('baseline', $css);
		$settings['keep-settings']	=	$this -> getParam('keep-settings', $css);	/* yes or no */
		$settings['generate-path']	=	$this -> getParam('generate-path', $css);
		
		// Check whether we should use the column width or calculate it from the grid width
		if ($settings['columnwidth'] == "") 
		{
			$settings['gridwidth']	= $this -> getParam('grid-width', $css);
			$settings['columnwidth'] = $this -> getColumnWidth();
		}
		else 
		{
			$settings['columnwidth'] = $settings['columnwidth'] + $settings['gutterwidth'];
			$settings['gridwidth'] = ($settings['columnwidth'] * $settings['columncount']) - $settings['gutterwidth'];
		}	
		
		// If theres no format specified, go with 'newline'
		if ($settings['format'] == "") 
		{
			$settings['format'] = "newline";
		}	
	}
	
	public function generateGrid($css)
	{
		global $settings;
		
		// Make the .columns classes
		for ($i=1; $i < $settings['columncount'] + 1; $i++) { 
			$w = $settings['columnwidth'] * $i - $settings['gutterwidth'];\
			$s .= "  .columns-$i \t{ width:".$w."px; }\n";
		}
		
		// Add an extra line to clean it up
		$s .= "\n";
		
		// Make the .push classes
		for ($i=1; $i < $settings['columncount']; $i++) { 
			$w = $settings['columnwidth'] * $i;
			$s .= "  .push-$i \t{ margin: 0 -".$w."px 0 ".$w."px; }\n";
			$pushselectors .= ".push-$i,";
		}
		$s .= $pushselectors . "{ float:right; position:relative; }\n\n";
		
		// Add an extra line to clean it up
		$s .= "\n";
		
		// Make the .pull classes
		for ($i=1; $i < $settings['columncount']; $i++) { 
			$w = $settings['columnwidth'] * $i;
			$s .= "  .pull-$i \t{ margin-left:-".$w."px; }\n";
			$pullselectors .= ".pull-$i,";
		}
		$s .= $pullselectors . "{ float:left; position:relative; }\n\n";
		
		// Open the file relative to /css/
		$file = fopen("sections/grid.css", "w") or die("Can't open the file");
		
		// Write the string to the file
		fwrite($file, $s);
		fclose($file);
	}
	
	public function generateGridImage($css)
	{
		global $settings;
		
		$image = ImageCreate($settings['columnwidth'], $settings['baseline']);
		
		$colorWhite		= ImageColorAllocate($image, 255, 255, 255);
		$colorGrey		= ImageColorAllocate($image, 200, 200, 200);
		$colorBlue		= ImageColorAllocate($image, 240, 240, 255);
		
		Imagefilledrectangle($image, 0, 0, ($settings['columnwidth'] - $settings['gutterwidth']), ($settings['baseline'] - 1), $colorBlue);
		Imagefilledrectangle($image, ($settings['columnwidth'] - $settings['gutterwidth'] + 1), 0, $settings['columnwidth'], ($settings['baseline'] - 1), $colorWhite);
	
		imageline($image, 0, ($settings['baseline'] - 1 ), $settings['columnwidth'], ($settings['baseline'] - 1 ), $colorGrey);
		
	    ImagePNG($image,"../images/backgrounds/grid.png");
	    ImageDestroy($image);
	}
	
	public function buildGrid($css) 
	{	
		global $settings;
		
		$css = $this -> replaceGutters($css);
		$css = $this -> replaceColumnWidth($css);
		$css = $this -> replaceColumns($css);

		return $css;
	}
	 
	public function freezeGrid()
	{
		// Remove the @grid settings 
		$css = preg_replace('/\/\*.+\@grid.+?\*\//s','',$css);

		//Remove all the grid(col) variable stores
		while (preg_match_all('/\/\*.?grid\(\d+col\).?\*\//', $css, $match))
		{
			foreach ($match[0] as $key => $properties)
			{
				$css = str_replace($match[0][0], '', $css);
			}
		}

		//Remove all the grid(gutter) variable stores
		$css = preg_replace('/\/\*.?grid\(gut\).?\*\//', '', $css);

		// Removes all the /*grid(cols:x;)*/ 
		while (preg_match_all('/\/\*.?grid.?\(.?cols.?\:.?\d+.?\;.?\).?\*\//', $css, $match))
		{
			foreach ($match[0] as $key => $properties)
			{
				$css = str_replace($match[0][0], '', $css);
			}
		}

		while (preg_match_all('/\/\*.?grid.?\(.?end.?\).?\*\//', $css, $match))
		{
			foreach ($match[0] as $key => $properties)
			{
				$css = str_replace($match[0][0], '', $css);
			}
		}

		return $css;
	}
	
	public function restoreGrid()
	{
		global $settings;
		
		//Replace grid(xcol)
		while (preg_match_all('/\d*px\/\*grid\(\d+\)\*\//', $css, $match))
		{
			foreach ($match[0] as $key => $properties)
			{
				$s = $match[0][0];  // 23px/*grid(1)*/
				
				// Get the original variable
				preg_match('/grid.+\)/', $s, $var); 
				$original = $var[0]; // grid(1)
				$original = str_replace(')', 'col)',$original); 
				
				// Get rid of the variable
				// $s = preg_replace('/\/\*.+\*\//','',$s);
				
				$css = str_replace($match[0][0], $original, $css);

			}
			
		}
		
		//Restore grid(gutter)
		$css = preg_replace('/'.$settings['gutterwidth'].'px.?\/\*.?grid\(gut\).?\*\//', 'grid(gutter)', $css);
		
		// Restore columns:x;
		while (preg_match_all('/\/\*grid\(cols\:\d+\;\)\*\/.+?\/\*grid\(end\)\*\//s', $css, $match))
		{
			foreach ($match[0] as $key => $properties)
			{
				$s = $match[0][0];
				$s = str_replace("\n","",$s);
				
				// Get the original variable
				preg_match('/cols:\d+\;/', $s, $var);
				$original = $var[0];
				$original = str_replace('cols','columns',$original);// 'columns' was causing issues for some reason
				
				$css = str_replace($match[0][0], $original, $css);
			}
			
		}
		
		return $css;
	}

	private function replaceColumns($css)
	{
		global $settings;
		
		// We'll loop through each of the columns properties by looking for each columns:x; property.
		// This means we'll only loop through $columnscount number of times which could be better
		// or worse depending on how many columns properties there are in your css
		
		for ($i=1; $i <= $settings['columncount']; $i++) { 
		
			// Matches all selectors (just the properties) which have a columns property
			while (preg_match_all('/\{([^\}]*(columns\:\s*('.$i.'!?)\s*\;).*?)\}/sx', $css, $match)) {
			
				// For each of the selectors with columns properties...
				foreach ($match[0] as $key => $properties)
				{
					$properties 		= $match[1][0]; // First match is all the properties				
					$columnsproperty 	= $match[2][0]; // Second match is just the columns property
					$numberofcolumns	= $match[3][0]; // Third match is just number of columns
					
					
					// If there is an ! after the column number, we don't want the properties included.
					if (substr($numberofcolumns, -1) == "!") {
						$showproperties = false;
					}
					else {
						$showproperties = true;
					}
			
					// Send the properties through the functions to get the padding and border from them  
					$padding 	= $this -> getPadding($properties);
					$border 	= $this -> getBorder($properties);
			
					// Add it all together to get extra width
					$extrawidth = $padding + $border;
			
					// Calculate the width of the column with adjustments for padding and border
					$width = (($settings['columnwidth']*$i)-$settings['gutterwidth'])-$extrawidth;
					
					// Create the properties
					$styles = "width:" . $width . "px;";
					
					if ($showproperties) 
					{
						$styles .= "display:inline;float:left;overflow:hidden;";
						
						if ($numberofcolumns <= $settings['columncount'])
						{
							$styles .= "margin-right:" . $settings['gutterwidth'] . "px;";
						}
					}

					// Apply some formatting and add variable comments
					if ($settings['keep-settings'] == "yes") {
						$styles = "/*grid(cols:".$numberofcolumns.";)*/". $styles . "/*grid(end)*/";
					}

					if ($settings['format'] == "newline")
					{
						$styles = str_replace("*/", "*/\n\t", $styles);
						$styles = str_replace(";", ";\n\t", $styles);
					}
					
					// Insert into property string
					$newproperties = str_replace($columnsproperty, $styles, $properties);

					// Insert this new string into CSS string
					$css = str_replace($properties, $newproperties, $css);
				
				}
			}
		}
		return $css;
	}
	
	private function getParam($name, $css)
	{		
		// Make sure there are settings, if so, grab them
		if (preg_match_all('/@grid.*?\}/sx', $css, $match)) {
			$settings = $match;
		} 
		else {
			echo "There are no grid settings";
			exit;
		}
		
		// Make the settings regex-friendly
		$name = str_replace('-','\-', $name);
		
		if (preg_match_all('/'.$name.'\:.+?\;/x', $css, $matches))
		{
			// Strip the name and leave the value so the value can be anything
			$result = preg_replace('/'.$name.'|\:|\;| /', '', $matches[0][0]);
			return $result;
		}
	}
	
	private function replaceGutters($css)
	{
		global $settings;
		
		if ($settings['keep-settings'] == "yes") {
			$gutter = $settings['gutterwidth'].'px/*grid(gut)*/';
		}
		else {
			$gutter = $settings['gutterwidth'].'px';
		}
		
		$css = str_replace('grid(gutter)', $gutter, $css);
		
		return $css;
	}
	
	private function replaceColumnWidth($css) 
	{	
		global $settings;
		
		if (preg_match_all('/grid\(\d+col\)/', $css, $matches))
		{
			foreach ($matches[0] as $key => $value)
			{
				$number = str_replace(' ', '', $value);
				$number = str_replace('grid(', '', $number);
				$number = str_replace('col)', '', $number);
				$colw = ($number * $settings['columnwidth']) - $settings['gutterwidth'];
				$colw   = $colw.'px/*grid('.$number.')*/';
				$css = str_replace($value,$colw,$css);
			}
		}
		return $css;
	}
	
	
	private function getColumnWidth() 
	{
		global $settings;
		
		$grossgridwidth		= $settings['gridwidth'] - ($settings['gutterwidth'] * ($settings['columncount']-1)); /* Width without gutters */
		$singlecolumnwidth 	= $grossgridwidth/$settings['columncount'];
		$columnwidth 		= $singlecolumnwidth + $settings['gutterwidth'];

		return $columnwidth;
	}
	
	private function getPadding($properties)
	{
		$padding = $paddingleft = $paddingright = 0;
		
		// Get the padding (in its many different forms)
		// This gets it in shorthand
		if (preg_match_all('/padding\:.+?\;/x', $properties, $matches))
		{

			$padding = str_replace(';','',$matches[0][0]);
			$padding = str_replace('padding:','',$padding);
			$padding = str_replace('px','',$padding);
			$padding = preg_split('/\s/', $padding);
			if (sizeof($padding) == 1)
			{
				$paddingright = $padding[0];
				$paddingleft = $padding[0];
			} 
			elseif (sizeof($padding) == 2 || sizeof($padding) == 3)
			{
				$paddingleft = $padding[1];
				$paddingright = $padding[1];
			}
			elseif (sizeof($padding) == 4)
			{
				$paddingright = $padding[1];
				$paddingleft = $padding[3];
			}
		}
		if (preg_match_all('/padding\-left\:.+?\;/x', $properties, $paddingl))
		{
			$paddingleft =  $paddingl[0][0];
			$paddingleft = str_replace(' ', '', $paddingleft);
			$paddingleft = str_replace('padding-left:', '', $paddingleft);
			$paddingleft = str_replace('px', '', $paddingleft);
			$paddingleft = str_replace(';', '', $paddingleft);

		}
		if (preg_match_all('/padding\-right\:.+?\;/x', $properties, $paddingr))
		{
			$paddingright =  $paddingr[0][0];
			$paddingright = str_replace(' ', '', $paddingright);
			$paddingright = str_replace('padding-right:', '', $paddingright);
			$paddingright = str_replace('px', '', $paddingright);
			$paddingright = str_replace(';', '', $paddingright);
		}

		$padding = $paddingleft + $paddingright;
		return $padding;
		
	}
	
	private function getBorder($properties)
	{		
	
		$border = 0;
		$borderleft = 0;
		$borderright = 0;
				
		if (preg_match_all('/border\:.+?\;/x', $properties, $matches))
		{
			if (preg_match_all('/\d.?px/', $matches[0][0], $match))
			{
				$borderw = $match[0][0];
				$borderw = str_replace('px','',$borderw);
				
				$borderleft = $borderw;
				$borderright = $borderw;
			}
		}	
		if (preg_match_all('/border\-left\:.+?\;/x', $properties, $matches))
		{
			if (preg_match_all('/\d.?px/', $matches[0][0], $match))
			{
				$borderleft = $match[0][0];
				$borderleft = str_replace('px','',$borderleft);
			}
		}
		
		if (preg_match_all('/border\-right\:.+?\;/x', $properties, $matches))
		{
			if (preg_match_all('/\d.?px/', $matches[0][0], $match))
			{
				$borderright = $match[0][0];
				$borderright = str_replace('px','',$borderright);
			}
		}
			
		$border = $borderleft + $borderright;
		return $border;
		
	}

}

?>