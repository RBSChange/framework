<?php
/**
 * @method import_ScriptReader getInstance()
 */
class import_ScriptReader extends change_BaseService
{
	private $elements = array();
	
	private $attributes = array();

	private $regiteredElementsClass = array();

	private function initialize()
	{
		$this->elements = array();
		$this->attributes = array();
		$this->regiteredElementsClass = array();
		$this->registerElementClass('script', 'import_ScriptScriptElement');
		$this->registerElementClass('binding', 'import_ScriptBindingElement');
		$this->registerElementClass('tag', 'import_ScriptTagElement');
		$this->registerElementClass('documentRef', 'import_ScriptDocumentRefElement');
		$this->registerElementClass('execute', 'import_ScriptExecuteElement');
		$this->registerElementClass('attribute', 'import_ScriptAttributeElement');
		$this->registerElementClass('i18n', 'import_ScriptI18nElement');
		$this->registerElementClass('debug', 'import_ScriptDebugElement');
	}

	public function registerElementClass($name, $className)
	{
		$this->regiteredElementsClass[$name] = $className;
	}

	/**
	 * @param string $moduleName ex : website
	 * @param string $scriptName ex : defaultsite.xml
	 * @param array $attributes
	 */
	public function executeModuleScript($moduleName, $scriptName, $attributes = array())
	{
		$path = change_FileResolver::getNewInstance()->getPath('modules', $moduleName, 'setup', $scriptName);
		if ($path === null)
		{
			throw new Exception("Could not find any script named $scriptName for module $moduleName");
		}
		$this->execute($path, $attributes);
	}
	
	/**
	 * @param string $themeName ex : developer
	 * @param string $scriptName ex : init.xml
	 * @param array $attributes
	 */
	public function executeThemeScript($themeName, $scriptName, $attributes = array())
	{
		$path = change_FileResolver::getNewInstance()->getPath('themes', $themeName, 'setup', $scriptName);
		if ($path === null)
		{
			throw new Exception("Could not find any script named $scriptName for theme $themeName");
		}	
		$this->execute($path, $attributes);		
	}
	/**
	 * @param string $fileName
	 * @param attay $attributes
	 */
	public function execute($fileName, $attributes = array())
	{		
		if (!is_array($attributes)) {$attributes = array();}
		
		// Set default values of tempalte and templateHome attributes.
		if (!isset($attributes['template']))
		{
			$attributes['template'] = Framework::getConfigurationValue('modules/website/sample/defaultPageTemplate');
		}
		if (!isset($attributes['templateNosidebar']))
		{
			$attributes['templateNosidebar'] = Framework::getConfigurationValue('modules/website/sample/defaultNosidebarTemplate');
		}
		if (!isset($attributes['templateHome']))
		{
			$attributes['templateHome'] = Framework::getConfigurationValue('modules/website/sample/defaultHomeTemplate');
		}
		if (!isset($attributes['templatePopin']))
		{
			$attributes['templatePopin'] = Framework::getConfigurationValue('modules/website/sample/defaultPopinTemplate');
		}		
		
		$scriptInstance = new self();
		$scriptInstance->initialize();
		$scriptInstance->attributes = $attributes;
		$scriptInstance->executeInternal($fileName);		
	}

	/**
	 * @param string $fileName
	 */
	public function executeInternal($fileName)
	{
		error_reporting(E_ERROR | E_WARNING | E_PARSE);

		set_error_handler(array($this, "errorReport"));
		$reader = new XMLReader();
		if (!$reader->open($fileName))
		{
			throw new Exception('Could not open ' . $fileName . ' for reading');
		}
		$this->parse($reader);
		$reader->close();
		restore_error_handler();
		if ($this->errors !== null)
		{
			$message = join("\n", $this->errors);
			$this->errors = null;
			Framework::error(__METHOD__ . "Error while processing $fileName:\n$message");
			throw new Exception($message);
		}
	}

	private $errors;

	function errorReport($errno, $errstr, $errfile, $errline)
	{
		switch ($errno)
		{
			case E_USER_ERROR:
			case E_USER_WARNING:
			case E_STRICT:
				break;
			default:
				if ($this->errors === null)
				{
					$this->errors = array();
				}
				$this->errors[] = $errstr;
				break;
		}
		return Framework::getZendLog()->errorHandler($errno, $errstr, $errfile, $errline, null);
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
	 * @param integer $id
	 * @return boolean
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
		throw new Exception('Identifiant "' . $id . '" introuvable');
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
	 * @param string $name
	 * @return string 
	 */
	public function getAttribute($name)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 * @param boolean $default
	 */
	public function setAttribute($name, $value, $default = false)
	{
		if (!isset($this->attributes[$name]) || !$default)
		{
			$this->attributes[$name] = $value;
		}
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
