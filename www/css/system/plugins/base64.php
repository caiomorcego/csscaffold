<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'Base64Plugin';
class Base64Plugin extends CacheerPlugin
{
	function Base64Plugin()
	{
		if (isset($_SERVER['HTTP_USER_AGENT']))
		{
			$ua = $this->parse_user_agent($_SERVER['HTTP_USER_AGENT']);
			// Safari (WebKit), Firefox & Opera are known to support data: urls so embed base64-encoded images
			if
			(
				($ua['browser'] == 'applewebkit' && $ua['version'] >= 125) || // Safari and ilk
				($ua['browser'] == 'firefox') || // Firefox et al
				($ua['browser'] == 'opera' && $ua['version'] >= 7.2) // quell vociferous Opera evangelists
			)
			{
				$this->flags['Base64'] = true;
			}
		}
	}
	
	function post_process($css)
	{
		if (isset($this->flags['Base64']))
		{
			global $requested_dir;
			
			$root_dir = $_SERVER['DOCUMENT_ROOT'];

			$images = array();
			if (preg_match_all('#url\(([^\)]+)\)#i', $css, $matches))
			{
				foreach($matches[1] as $relative_img)
				{
					if (!preg_match('#\.(gif|jpg|png)#', $relative_img, $ext))
					{
						continue;
					}

					$images[$relative_img] = $ext[1];
				}

				foreach($images as $relative_img => $img_ext)
				{
					$up = substr_count($relative_img, '../');
					$relative_img_loc = preg_replace('/[\'|\"]/', "",$relative_img);
					$absolute_img = $root_dir.preg_replace('#([^/]+/){'.$up.'}(\.\./){'.$up.'}#', '', $requested_dir.'/'.$relative_img_loc);

					if (file_exists($absolute_img))
					{
						$img_raw = file_get_contents($absolute_img);
						$img_data = 'data:image/'.$img_ext.';base64,'.base64_encode($img_raw);
						$css = str_replace("url({$relative_img})", "url({$img_data})", $css);
					}
				}
			}
		}

		return $css;
	}
	
	// really simple (read: imperfect) rendering engine detection
	function parse_user_agent($user_agent)
	{
		$ua['browser']	= '';
		$ua['version']	= 0;

		if (preg_match('/(firefox|opera|applewebkit)(?: \(|\/|[^\/]*\/| )v?([0-9.]*)/i', $user_agent, $m))
		{
			$ua['browser']	= strtolower($m[1]);
			$ua['version']	= $m[2];
		}
		else if (preg_match('/MSIE ?([0-9.]*)/i', $user_agent, $v) && !preg_match('/(bot|(?<!mytotal)search|seeker)/i', $user_agent))
		{
			$ua['browser']	= 'ie';
			$ua['version']	= $v[1];
		}
		return $ua;
	}
}