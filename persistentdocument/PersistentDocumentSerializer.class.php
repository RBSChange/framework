<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument
 */
class SerialisationContext
{
	const DIRECTION_IN = 'IN';
	const DIRECTION_OUT = 'OUT';

	/**
	 * @var DOMDocument
	 */
	private $document;
	
	
	private $direction = 'OUT';
	
	public function __construct($direction)
	{
		$this->direction = $direction;
	}
	
	/**
	 * @return String DIRECTION_IN, DIRECTION_OUT
	 */
	public function getDirection()
	{
		return $this->direction;
	}
	
	public function setDocument($document)
	{
		$this->document = $document;
	}
	
	/**
	 * @return DOMDocument
	 */
	public function getDocument()
	{
		if (is_null($this->document))
		{
			$this->document = new DOMDocument('1.0', 'UTF-8');
		}
		return $this->document;
	}
}



class f_persistentdocument_PersistentDocumentSerializer
{
	
	/**
	 * @var DOMDocument
	 */
	private $document;
	
	/**
	 * DOMElement
	 */
	private $element;
	
	
	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @param SerialisationContext $context
	 * @return String
	 */
	public function serialize($persistentDocument, $context)
	{
		$this->document = $context->getDocument();
		$this->element = $this->createProperty('document');
		$lang = RequestContext::getInstance()->getLang();
		
		$properties = $persistentDocument->getPersistentModel()->getPropertiesInfo();
		$this->setAttribute($this->element, 'id', $persistentDocument->getId());
		$this->setAttribute($this->element, 'model', $persistentDocument->getDocumentModelName());
		$this->setAttribute($this->element, 'lang', $lang);
		
		foreach ($properties as $propertyName => $propertyInfo)
		{
			if ($propertyName == 'id' || $propertyName == 'model' || $propertyName == 'lang')
			{
				continue;
			}
			
			$propertyName = ucfirst($propertyName);
			if (!$propertyInfo->isDocument())
			{
				if (DocumentHelper::isLobProperty($propertyInfo->getType()))
				{
					$element = $this->addProperty($this->createLobProperty($propertyInfo->name, $this->{'get'.$propertyName}()));
				}
				else
				{
					$element = $this->addProperty($this->createProperty($propertyInfo->name, $this->{'get'.$propertyName}()));
				}
				
				
			}
			else
			{
				if (!$propertyInfo->isArray())
				{
					$value = $this->{'get'.$propertyName}();
					if (!is_null($value))
					{
						$this->createDocumentProperty($propertyInfo->name, $value);
					}
				}
				else
				{
					$array = $this->{'get'.$propertyName.'Array'}();
					$this->createDocumentPropertyArray($propertyInfo->name, $array);	
				}
			}
		}			
		
		return $doc->saveXML();
	}
	
	/**
	 * @param DOMElement $element
	 * @param string $name
	 * @param string $value
	 * @return DOMElement
	 */
	private function setAttribute($element, $name, $value)
	{
		$element->setAttribute($name, $value);
		return $element;
	}
	
	private function addProperty($property)
	{
		$this->element->appendChild($property);
		return $property;
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 * @return DOMElement
	 */
	private function createProperty($name, $value)
	{
		$element = $this->document->createElement('component', $value);
		return $this->setAttribute($element, 'name', $name);
	}
	
	private function createLobProperty($name, $value)
	{
		$element = $this->createProperty($name, null);
		$element->appendChild($this->document->createCDATASection($value));
	}
	
	private function createDocumentProperty($name, $value)
	{
		$element = $this->createProperty($name, null);
		if (!is_null($value))
		{
			$doc = $this->createProperty('document', $value->getLabel());
			$this->setAttribute($doc, 'id', $value->getId());
			$this->setAttribute($doc, 'model', $value->getDocumentModelName());
			$element->appendChild($doc);
		}
		return $element;
	}
	
	private function createDocumentPropertyArray($name, $array)
	{
		$element = $this->createProperty($name, null);
		foreach ($array as $value)
		{
			$doc = $this->createProperty('document', $value->getLabel());
			$this->setAttribute($doc, 'id', $value->getId());
			$this->setAttribute($doc, 'model', $value->getDocumentModelName());
			$element->appendChild($doc);			
		}
		return $element;
	}	
}
