<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'Fonts';

class Fonts extends CacheerPlugin
{
	function Fonts()
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

	function process($css)
	{
		$this -> whichBrowser();
		
		return $css;
	}
	
}

?>