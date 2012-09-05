<?php
/**
 * @deprecated
 */
abstract class f_util_DOMUtils
{
	/**
	 * @param String $path
	 * @return f_util_DOMDocument
	 */
	static function fromPath($path)
	{
		$doc = self::newDocument();
		if (!$doc->load($path))
		{
			throw new Exception("Could not load $path as an XML file");
		}
		// we never know ... :-|
		$doc->formatOutput = true;
		return $doc;
	}

	/**
	 * @return f_util_DOMDocument
	 */
	static function fromString($xmlString)
	{
		$doc = self::newDocument();
		if ($doc->loadXML($xmlString) === false)
		{
			throw new Exception("Could not load '$xmlString' as XML");
		}
		// we never know ... :-|
		$doc->formatOutput = true;
		return $doc;
	}
	
	/**
	 * @param string $xhtml
	 * @return f_util_DOMDocument
	 */
	static function fromXhtmlFragmentString($xhtml)
	{
		$dtd = str_replace(array(DIRECTORY_SEPARATOR, ' '), array('/', '%20'), f_util_FileUtils::buildFrameworkPath('f_web', 'dtd', 'xhtml1-transitional.dtd'));
		if ($dtd[0] != '/') {$dtd = '/' . $dtd;}
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' .
			 '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "file://'. $dtd .'">' .
			 '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:change="http://www.rbs.fr/change/1.0/schema" xmlns:tal="http://xml.zope.org/namespaces/tal"><body>' . 
			 $xhtml .
			 '</body></html>';
		$doc = self::newDocument();
		$doc->substituteEntities = false;
		$doc->resolveExternals = true;
		$doc->loadXML($xml);	
		$doc->formatOutput = true;
		return $doc;
	}
	
	/**
	 * @return f_util_DOMDocument
	 */
	static function newDocument()
	{
		$doc = new f_util_DOMDocument();
		$doc->formatOutput = true;
		$doc->preserveWhiteSpace = false;
		return $doc;
	}

	/**
	 * @param DOMDocument $document
	 * @param String $path
	 */
	static function save($document, $path)
	{
		f_util_FileUtils::writeAndCreateContainer($path, $document->saveXML(), f_util_FileUtils::OVERRIDE);
	}
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) use fromPath
	 */
	static function getDocument($path)
	{
		return self::fromPath($path);
	}
}

/**
 * @deprecated
 */
class f_util_DOMDocument extends DOMDocument
{
	/**
	 * @var DOMXPath
	 */
	private $xpath;

	/**
	 * @return DOMXPath
	 */
	private function getXPath()
	{
		if ($this->xpath === null)
		{
			$this->xpath = new DOMXPath($this);
		}
		return $this->xpath;
	}

	/**
	 * @param String $prefix
	 * @param String $namespaceURI
	 * @return f_util_DOMDocument
	 */
	function registerNamespace($prefix, $namespaceURI)
	{
		if ($this->getXPath()->registerNamespace($prefix, $namespaceURI) === false)
		{
			throw new Exception(__METHOD__.": could not register name space $prefix/$namespaceURI");
		}
		return $this;
	}

	/**
	 * @param String $xPathExpression
	 * @param DOMNode $context
	 * @return Boolean
	 */
	function exists($xPathExpression, $context = null)
	{
		if ($context === null)
		{
			$nodes = $this->getXPath()->query($xPathExpression);
		}
		else
		{
			$nodes = $this->getXPath()->query($xPathExpression, $context);			
		}
		return $nodes->length > 0;
	}
	
	/**
	 * @param String $xPathExpression
	 * @param DOMNode $context
	 * @return DOMElement
	 */
	function createIfNotExists($xPathExpression, $context = null)
	{
		$element = $this->findUnique($xPathExpression, $context);
		if ($element !== null)
		{
			return $element;
		}
		$xPathInfo = explode("/", $xPathExpression);
		$xPathContext = $context;
		foreach ($xPathInfo as $xPathPart)
		{
			$subElement = $this->findUnique($xPathPart, $xPathContext);
			if ($subElement === null)
			{
				$subElement = $this->createElement(f_util_ArrayUtils::firstElement(explode('[', $xPathPart)));
				if ($xPathContext !== null)
				{
					$xPathContext->appendChild($subElement);
				}
				else
				{
					$this->appendChild($subElement);
				}	
			}
			$xPathContext = $subElement;
		}
		return $subElement;
	}

	/**
	 * @param String $xPathExpression
	 * @param DOMNode $context
	 * @return DOMNode
	 */
	function findUnique($xPathExpression, $context = null)
	{
		if ($context === null)
		{
			$nodes = $this->getXPath()->query($xPathExpression);
		}
		else
		{
			$nodes = $this->getXPath()->query($xPathExpression, $context);
		}
		if ($nodes->length == 0)
		{
			return null;
		}
		// TODO/FIXME: throw if length > 1 ?
		return $nodes->item(0);
	}

	/**
	 * @param String $xPathExpression
	 * @param DOMNode $context
	 * @return DOMNodeList
	 */
	function find($xPathExpression, $context = null)
	{
		if ($context === null)
		{
			return $this->getXPath()->query($xPathExpression);
		}
		return $this->getXPath()->query($xPathExpression, $context);
	}

	/**
	 * @param String $xPathExpression
	 * @param DOMNode $context
	 * @return Integer the number of deleted nodes
	 */
	function findAndRemove($xPathExpression, $context = null)
	{
		$nodes = $this->find($xPathExpression, $context);
		$length = $nodes->length;
		foreach ($nodes as $node)
		{
			$node->parentNode->removeChild($node);
		}
		return $length;
	}

	/**
	 * Release internal resources
	 */
	function release()
	{
		$this->xpath = null;
	}
}