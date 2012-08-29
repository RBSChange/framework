<?php
/**
 * @package framework.indexer
 */
class indexer_SearchResult
{
	private $fields = array();
		
	/**
	 * @param string $name
	 * @param string $value
	 */
	public function setProperty($name, $value)
	{
		$this->fields[$name] = $value;
	}
	
	/**
	 * @return array
	 */
	public final function getFields()
	{
		return $this->fields;
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	function hasProperty($name)
	{
		return array_key_exists($name, $this->fields);
	}

	/**
	 * Private getProperty
	 *
	 * @param string $name
	 * @return string
	 */
	private function getProperty($name)
	{
		if (!array_key_exists($name, $this->fields))
		{
			throw new Exception("indexer_SearchResult object has no property named $name");
		}
		return $this->fields[$name];
	}

	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getDocument()
	{
		list($id, $lang) = explode('/', $this->getId());
		$documentModelName = $this->getDocumentModel();
		return DocumentHelper::getDocumentInstance($id, $documentModelName);
	}
	
	/**
	 * @return integer
	 */
	public function getDocumentId()
	{
		list($id, ) = explode('/', $this->getId());
		return intval($id);
	}
		
	/**
	 * Magic "getter" method
	 */
	public function __call($method, $args)
	{
		if ($method === 'getResultType')
		{
			Framework::error('Call to deleted ' . get_class($this) . '->' .$method. ' method');
			$res = $this->getDocumentModel(); 
			if ($this->isMedia())
			{
				$res .= "_" . $this->getMediaType();
			}
			return str_replace('/', '_', $res);
		}
		elseif ($method === 'isMedia')
		{
			Framework::error('Call to deleted ' . get_class($this) . '->' .$method. ' method');
			return $this->getDocumentModel() == 'modules_media/media';
		} 
		elseif (f_util_StringUtils::beginsWith($method, "getHighlighted"))
		{
			$propName = f_util_StringUtils::lcfirst(substr($method, 14));
			return $this->getHighlightedProperty($propName);
		}
		elseif (f_util_StringUtils::beginsWith($method, "get"))
		{
			if (f_util_StringUtils::endsWith($method, 'Date'))
			{
				return indexer_Field::solrDateToDate($this->getProperty(f_util_StringUtils::lcfirst(substr($method, 3, -4))));
			}
			return $this->getProperty(f_util_StringUtils::lcfirst(substr($method, 3)));
		}
		elseif (f_util_StringUtils::beginsWith($method, "has"))
		{
			$propName = f_util_StringUtils::lcfirst(substr($method, 3));
			return $this->hasProperty($propName);
		}
		throw new BadMethodCallException('No method ' . get_class($this) . '->' . $method);
	}

	private function getHighlightedProperty($name)
	{
		if (!array_key_exists('highlighting', $this->fields))
		{
			$result = f_util_StringUtils::shortenString($this->getProperty($name));
		}
		elseif (!array_key_exists($name, $this->fields['highlighting']))
		{
			$result = f_util_StringUtils::shortenString($this->getProperty($name));
		}
		else 
		{
			$result = $this->fields['highlighting'][$name];
		}
		return trim($result);
	}
	
	public function getHighlightedText()
	{
		return $this->getHighlightedProperty('text');
	}
		
	public function getHighlightedLabel()
	{
		return $this->getHighlightedProperty('label');
	}
}