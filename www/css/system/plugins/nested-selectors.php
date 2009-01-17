<?php
/******************************************************************************
 Prevent direct access
 ******************************************************************************/
if (!defined('CSS_CACHEER')) { header('Location:/'); }

$plugin_class = 'NestedSelectorsPlugin';
class NestedSelectorsPlugin extends CacheerPlugin
{
	var $DOM;
	function process($css)
	{
		/******************************************************************************
		 Process nested selectors
		 ******************************************************************************/
		// Transform the CSS into XML
		// does not like the data: protocol
		
		$xml = trim($css);
		$xml = preg_replace('#(/\*[^*]*\*+([^/*][^*]*\*+)*/)#', '', $xml); // Strip comments to prevent parsing errors
		$xml = str_replace('"', '#SI-CSSC-QUOTE#', $xml);
		$xml = preg_replace('/([-a-z]+)\s*:\s*([^;}{]+)(?:;)/ie', "'<property name=\"'.trim('$1').'\" value=\"'.trim('$2').'\" />'", $xml); // Transform properties		
		$xml = preg_replace('/^(\s*)([\+>#*@:.a-z][^{]+)\{/me', "'$1<rule selector=\"'.preg_replace('/\s+/', ' ', trim(str_replace('>','&gt;','$2'))).'\">'", $xml); // Transform selectors
		$xml = str_replace('}', '</rule>', $xml); // Close rules
		$xml = preg_replace('/\n/', "\r\t", $xml); // Indent everything one tab
		$xml = '<?xml version="1.0" ?'.">\r<css>\r\t$xml\r</css>\r"; // Tie it all up with a bow

		//header('Content-type: text/text');
		//echo $xml;
		//exit();
		
		/******************************************************************************
		 Parse the XML into a crawlable DOM
		 ******************************************************************************/
		$this->DOM = new SI_Dom($xml);
		$rule_nodes =& $this->DOM->css->getNodesByNodeName('rule');
		
		/******************************************************************************
		 Rebuild parsed CSS
		 ******************************************************************************/
		$css = '';
		$standard_nest = '';
		foreach ($rule_nodes as $node)
		{	
			if (preg_match('#^@media#', $node->selector))
			{
				$standard_nest = $node->selector;
				$css .= $node->selector.' {';
			}
			
			$properties = $node->getChildNodesByNodeName('property');
			if (!empty($properties))
			{
				$selector = str_replace('&gt;', '>', $this->parseAncestorSelectors($this->getAncestorSelectors($node)));
				
				if (!empty($standard_nest))
				{
					if (substr_count($selector, $standard_nest))
					{
						$selector = trim(str_replace($standard_nest, '', $selector));
					}
					else
					{
						$css .= '}';
						$standard_nest = '';
					}
				}
				
				$css .= $selector.' {';
				foreach($properties as $property)
				{	
					$css .= $property->name.': '.str_replace('#SI-CSSC-QUOTE#', '"', $property->value).';';
				}
				$css .= '}';
			}	
		}
		
		if (!empty($standard_nest))
		{	
			$css .= '}';
			$standard_nest = '';
		}
		
		return $css;
	}
	
	function getAncestorSelectors($node)
	{
		$selectors = array();

		if (!empty($node->selector))
		{
			$selectors[] = $node->selector;
		}
		if (!empty($node->parentNodeId))
		{
			$parentNode = $this->DOM->nodeLookUp[$node->parentNodeId];
			if (isset($parentNode->selector))
			{
				$recursiveSelectors = $this->getAncestorSelectors($parentNode);
				$selectors = array_merge($selectors, $recursiveSelectors);
			}
		}
		return $selectors;
	}

	function parseAncestorSelectors($ancestors = array())
	{
		$growth = array();
		foreach($ancestors as $selector)
		{
			$these = preg_split('/,\s*/', $selector);
			if (empty($growth))
			{
				$growth = $these;
				continue;
			}

			$fresh = array();

			foreach($these as $tSelector)
			{
				foreach($growth as $gSelector)
				{
					$fresh[] = $tSelector.(($gSelector{0} != ':')?' ':'').$gSelector;
				}
			}
			$growth = $fresh;
		}
		return implode(',', $growth);
	}
}

class SI_DomNode
{
	var $dom;
	var $nodeName	= '';
	var $cdata		= '';
	var $nodeId;
	var $parentNodeId;
	var $childNodes = array();
	
	function SI_DomNode($nodeId, $nodeName = '', $attrs = array())
	{
		$this->nodeId			= $nodeId;
		$this->nodeName			= $nodeName;
		if (!empty($attrs))
		{
			foreach ($attrs as $attr => $value)
			{
				$attr = strtolower($attr);
				$this->$attr = $value;
			}
		}
	}
	
	function &getNodesByNodeName($nodeNames, $childrenOnly = false)
	{
		$nodeNamesArray = explode('|', strtolower($nodeNames));
		$nodes = array();
		
		foreach($this->childNodes as $node)
		{
			if (in_array(strtolower($node->nodeName), $nodeNamesArray))
			{
				array_push($nodes, $node);
			}
			if (!$childrenOnly)
			{
				$nestedNodes = $node->getNodesByNodeName($nodeNames);
				$nodes = array_merge($nodes, $nestedNodes);
			}
		}
		return $nodes;
	}
	
	function &getChildNodesByNodeName($nodeNames)
	{
		return $this->getNodesByNodeName($nodeNames, true);
	}
}

class SI_Dom extends SI_DomNode
{
    var $xmlObj;
    var $nodeLookUp = array();

    function SI_Dom($xml = '') 
    {
    	$this->name = 'DOM';
        $this->xmlObj = xml_parser_create();
        xml_set_object($this->xmlObj, $this);
        xml_set_element_handler($this->xmlObj, 'tagOpen', 'tagClose');
        xml_set_character_data_handler($this->xmlObj, "cdata");
        
        if (!empty($xml))
        {
        	$this->nodeId = count($this->nodeLookUp);
        	$this->nodeLookUp[] =& $this;
        	$this->parse($xml);
        }
    }

    function parse($data) 
    {
        if (!xml_parse($this->xmlObj, $data, true))
        {
        	printf("XML error: %s at line %d", xml_error_string(xml_get_error_code($this->xmlObj)), xml_get_current_line_number($this->xmlObj));
        }
    }

    function tagOpen($parser, $nodeName, $attrs)
    {
    	$node =& new SI_DomNode(count($this->nodeLookUp), $nodeName, $attrs);
    	$this->nodeLookUp[] = $node;
    	array_push($this->childNodes, $node);
    }

    function cdata($parser, $cdata) 
    {
    	$parentId = count($this->childNodes) - 1;
		$this->childNodes[$parentId]->cdata = $cdata;
    }

    function tagClose($parser, $nodeName) 
    {
    	$totalNodes = count($this->childNodes);
    	if ($totalNodes == 1)
    	{
    		$node =& $this->childNodes[0];
    		$node->parentNodeId = 0;
    		$container = strtolower($node->nodeName);
    		$this->$container =& $node;
    	}
		else if($totalNodes > 1)
		{
			$node		= array_pop($this->childNodes);
			$parentId	= count($this->childNodes) - 1;
			$node->parentNodeId = $this->childNodes[$parentId]->nodeId;
			$this->childNodes[$parentId]->childNodes[] =& $node;
			$this->nodeLookUp[$node->nodeId] =& $node;
		}
    }
}
