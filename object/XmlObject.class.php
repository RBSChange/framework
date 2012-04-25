<?php
/**
 * @deprecated
 */
function xmlObjectHandleXslt($errno, $errstr, $errfile, $errline)
{
	if (Framework::isDebugEnabled())
	{
		Framework::debug('XmlObject : ' . $errno . ' : '.$errstr);
	}
}


/**
 * @deprecated
 */
class f_object_XmlObject
{
	public $xmlData = null;
	public $filepath = null;
	public $readonly = null;
	// static $_cache_xmlObject = array();

	protected function __construct($xmlData,$filepath=null,$readonly=true)
	{
		$this->readonly = $readonly;
		$this->xmlData=$xmlData;
		$this->filepath=$filepath;
	}

	public function setFilePath($filepath)
	{
		$this->filepath=$filepath;
	}

	public static function getInstanceFromFile($filePath,$readonly=true)
	{
		//inactivate libxml standard error handler (so that error can be caught and sent to our specific handler)
		libxml_use_internal_errors(true);

		if (is_readable($filePath))
		{
			if ($readonly)
			{
				// SebC - terrible fix for SimpleXml lack of good namespaces handling :
				// $sxmltxt = file_get_contents($filePath);
				// $sxmltxt = str_replace("xmlns=", "a=", $sxmltxt);
				// $xmlData = @simplexml_load_string($sxmltxt);
				$xmlData = @simplexml_load_file($filePath);
				// $sxmltxt =null;
			}
			else
			{
				//$xmlData = new DOMDocument();
				$xmlData = DOMDocument::load($filePath);
			}

			if ($xmlData===false)
			{
				$e = new ClassException("xml-parsing-problem");

				$mess="";
				//get errors from XML file
				$errors = libxml_get_errors();
				foreach($errors as $error) {
					$mess.="(Line: ".$error->line.", message: ".$error->message.")";
				};
				$e->setAttribute("message",$mess);
				$e->setAttribute('file', $filePath);
				throw $e;
			}
			return new f_object_XmlObject($xmlData,$filePath,$readonly);
		}
		else
		{
			$e = new FileNotFoundException($filePath);
			throw $e;
		}
	}

	public static function getInstanceFromString($xmlString,$readonly=true)
	{

		if (is_string($xmlString))
		{
			if ($readonly)
			{
				$xmlData = @simplexml_load_string($xmlString);
			}
			else
			{
				set_error_handler('xmlObjectHandleXslt');
				$xmlData = new DOMDocument();
				$xmlData->loadXML($xmlString);
				restore_error_handler();

			}
			return new f_object_XmlObject($xmlData,null,$readonly);
		}
		else
		{
			$error = sprintf($xmlString.' IS NOT A STRING');
			throw new Exception($error);
		}
	}

	public function getRootElement()
	{
		if ($this->readonly)
		{
			return $this->xmlData;
		}
		else
		{
			$xpath = new DOMXPath($this->xmlData);
			// We starts from the root element
			$query = '/';
			$items = $xpath->query($query);
			return $items->item(0);
		}
	}

	/*
	public function save()
	{
	if ($this->filepath==null) throw new Exception("XmlObject not link to a filepath");
	if (file_exists($this->filepath)) unlink($this->filepath);
	file_put_contents($this->filepath,$this->xmlData);
	}
	*/

	function __sleep()
	{
		if ($this->readonly)
		{
			$this->xmlData = $this->xmlData->asXML();
		}
		else
		{
			$this->xmlData = $this->xmlData->saveXML();
		}
		//  return( array_keys( get_object_vars( &$this ) ) );
		return( array_keys( get_object_vars( $this ) ) );
	}

	function __wakeup()
	{
		if ($this->readonly)
		{
			$this->xmlData = simplexml_load_string($this->xmlData);
		}
		else
		{
			$this->xmlData = DOMDocument::loadXML($this->xmlData);
		}
	}

	public function xpath($xmlObject,$xpath,$arrayInsteadOfDomNodeList=true)
	{
		if ($xmlObject instanceof SimpleXMLElement)
		{
			$result = $xmlObject->xpath($xpath);
			if ($result===false || empty($result))
			{
				$e = new ClassException("xpath-failed");
				$e->setAttribute("xpath",$xpath);
				$e->setAttribute('filepath',$this->filepath);
				throw $e;
			}
		}
		elseif ($xmlObject instanceof DOMNode)
		{
			$xpathObject = new DOMXPath($this->xmlData);
			$domNodes = $xpathObject->query($xpath,$xmlObject);
			if ($domNodes->length===0)
			{
				$e = new ClassException("xpath-failed");
				$e->setAttribute("xpath",$xpath);
				$e->setAttribute('filepath',$this->filepath);
				throw $e;
			}
			if ($arrayInsteadOfDomNodeList)
			{
				$result=array();
				foreach ($domNodes as $domNode)
				{
					$result[]=$domNode;
				}
			}
			else
			{
				$result=$domNodes;
			}
		}
		else
		{
			$e = new FrameworkException("xmlObject_is_not_valid ");
			$e->setAttribute("class", get_class($xmlObject));
			throw $e;
		}
		return $result;
	}

	public function save()
	{
		if ($this->readonly)
		{
			throw new FrameworkException("cannot save readonly XML ");
		}
		$this->xmlData->save($this->filepath);
	}

	public static function translateToXmlValue($lvalue)
	{
		if (is_bool($lvalue))
		{
			if ($lvalue) return true;
			return false;
		}
		return strval($lvalue);
	}

	public static function translateXmlValue($lvalue)
	{
		if (is_float($lvalue))
		{
			return floatval($lvalue);
		}
		elseif(is_int($lvalue))
		{
			return intval($lvalue);
		}
		elseif ($lvalue == 'on' || $lvalue == 'yes' || $lvalue == 'true')
		{
			// replace values 'on' and 'yes' with a boolean true value
			return true;
		}
		else if ($lvalue == 'off' || $lvalue == 'no' || $lvalue == 'false')
		{
			return false;
		}

		return $lvalue;
	}

	public static function transform($xml,$xslfile)
	{
		$xsl = new domDocument();
		if ($xsl -> load($xslfile)===false) $this->throwXmlException();
		$xslt = new XSLTProcessor();
		if ($xslt -> importStylesheet($xsl)===false) $this->throwXmlException();
		return  $xslt -> transformToXml($xml);
	}

	private static function throwXmlException()
	{
		$mess="";
		//get errors from XML file
		$errors = libxml_get_errors();
		foreach($errors as $e) {
			$mess.="Ligne ".$e->line." => ".$e->message.$eol;
		};
		//build notification message and throw exception
		$e = new ClassException('xml-file-error');
		$e->setAttribute("eol",$eol);
		$e->setAttribute("mess",$mess);
		$e->setAttribute("file",$xmlFile);
		throw $e;

	}
}
?>