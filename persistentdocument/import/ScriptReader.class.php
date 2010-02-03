<?php

class import_ScriptReader extends BaseService
{
	/**
	 * @var import_ScriptReader
	 */
	private static $instance;
	
	private $elements = array();
	
	private $regiteredElementsClass = array();
	
	protected function __construct()
	{
	}
	
	private function initialize()
	{
		$this->regiteredElementsClass = array();
		$this->registerElementClass('script', 'import_ScriptScriptElement');
		$this->registerElementClass('binding', 'import_ScriptBindingElement');
		$this->registerElementClass('tag', 'import_ScriptTagElement');
		$this->registerElementClass('documentRef', 'import_ScriptDocumentRefElement');
	}
	
	/**
	 * @return import_ScriptReader
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	public function registerElementClass($name, $className)
	{
		$this->regiteredElementsClass[$name] = $className;
	}
	
	/**
	 * @param String $moduleName ex : website
	 * @param String $scriptName ex : defaultsite.xml
	 */
	public function executeModuleScript($moduleName, $scriptName)
	{
		$path = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->setDirectory('setup')->getPath($scriptName);
		if ($path === null)
		{
			throw new Exception("Could not find any script named $scriptName for module $moduleName");
		}
		$this->execute($path);
	}
	
	/**
	 * @param String $fileName
	 */
	public function execute($fileName)
	{
		$this->initialize();
		$this->executeInternal($fileName);
	}
	
	/**
	 * @param String $fileName
	 */
	public function executeInternal($fileName)
	{
		$reader = new XMLReader();
		if (! $reader->open($fileName))
		{
			throw new Exception('Could not open ' . $fileName . ' for reading');
		}
		
		$this->parse($reader);
		$reader->close();
	}	
	
	/**
	 * @param XMLReader $reader
	 * @param import_ScriptBaseElement $currentElement;
	 */
	private function parse($reader, $currentElement = null)
	{		
		while ($reader->read())
		{
			//If this is a text node then test for attributes.
			if ($reader->nodeType == XMLReader::ELEMENT)
			{
				$name = $reader->name;
				$element = $this->createElement($name, $currentElement);
				$isEmpty = $reader->isEmptyElement;
				$id = null;
				
				if ($reader->hasAttributes)
				{
					while ($reader->moveToNextAttribute())
					{
						$attributeName = $reader->name;
						if ($attributeName == 'id')
						{
							$id = $reader->value;
						}
						else
						{
							$element->setAttribute($attributeName, $reader->value);
						}
					}
				}
				
				$this->processElement($element, $id);
				
				if (! $isEmpty)
				{
					$currentElement = $element;
				}
				else
				{
					$this->endProcessElement($element);
				}
			
			}
			elseif ($currentElement !== null)
			{
				if ($reader->nodeType == XMLReader::END_ELEMENT)
				{
					$this->endProcessElement($currentElement);
					$currentElement = $currentElement->getParent();
				}
				elseif ($reader->nodeType == XMLReader::TEXT || $reader->nodeType == XMLReader::CDATA)
				{
					if ($value = trim($reader->value))
					{
						$currentElement->addContent($value);
					}
				}
			}
		}
	}
	
	/**
	 * @param string $name
	 * @param import_ScriptBaseElement $parentElement
	 * @return import_ScriptBaseElement
	 */
	private function createElement($name, $parentElement)
	{
		if (isset($this->regiteredElementsClass[$name]))
		{
			return new $this->regiteredElementsClass[$name]($this, $parentElement, $name);
		}
		else
		{
			$this->addWarning("Element $name is not registered.");
		}
		return new import_ScriptBaseElement($this, $parentElement, $name);
	}
	
	/**
	 * @param import_ScriptBaseElement $element
	 */
	public function getChildren($element)
	{
		$result = array();
		
		foreach ($this->elements as $child)
		{
			if ($element === $child->getParent())
			{
				$result[] = $child;
			}
		}
		return $result;
	}
	
	/**
	 * @param Integer $id
	 * @return Boolean
	 */
	public final function hasElementById($id)
	{
		return isset($this->elements[$id]);
	}
	
	/**
	 * @param string $id
	 * @return import_ScriptBaseElement
	 * @throws Exception
	 */
	public function getElementById($id, $assertedClass = null)
	{
		if (isset($this->elements[$id]))
		{
			$element = $this->elements[$id];
			if ($assertedClass !== null && !is_a($element, $assertedClass))
			{
				throw new Exception("element[@id = ".$id."] is not a ".$assertedClass);
			}
			return $element;
		}
		throw new Exception('Identifiant ' . $id . ' introuvable');
	}
	
	/**
	 * @param string $id
	 * @return import_ScriptDocumentElement
	 * @throws Exception
	 */
	public function getDocumentElementById($id)
	{
		return $this->getElementById($id, "import_ScriptDocumentElement");
	}	
	
	/**
	 * @param string $message
	 */
	public function addWarning($message)
	{
		echo $message . "\n";
	}
	
	private function processElement($element, $id)
	{
		if ($id)
		{
			$this->elements[$id] = $element;
		}
		else
		{
			$this->elements[] = $element;
		}
		
		$element->process();
	}
	
	private function endProcessElement($element)
	{
		$element->endProcess();
	}

}