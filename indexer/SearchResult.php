<?php
/**
 * @package framework.indexer
 */
class indexer_SearchResult
{
	private $fields = array();
	
	/**
	 * @var solrsearch_GaugeObject
	 */
	private $gaugeObject;
	
	/**
	 * @param String $name
	 * @param String $value
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
	 * Private getProperty
	 *
	 * @param String $name
	 * @return String
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
		return DocumentHelper::getDocumentInstance($id);
	}
	
	/**
	 * @return solrsearch_GaugeObject
	 */	
	public function getGauge()
	{
		if (null == $this->gaugeObject)
		{
			$this->gaugeObject = new solrsearch_GaugeObject($this->getNormalizedScore());			
		}
		return $this->gaugeObject;
	}
	
	/**
	 * Gets the "css" friendly type of the result eg: modules_news_news, modules_media_media_pdf, ....
	 * 
	 *  @return String
	 */
	public function getResultType()
	{
		$res = $this->getDocumentModel(); 
		if ($this->isMedia())
		{
			$res .= "_" . $this->getMediaType();
		}
		return str_replace('/', '_', $res);
	}

	/**
	 * Returns true if result is a media.
	 *
	 * @return unknown
	 */
	public function isMedia()
	{
		return $this->getDocumentModel() == 'modules_media/media';
	}
	
	/**
	 * Magic "getter" method 
	 * 
	 */
	public function __call($method, $args)
	{
		if( substr($method, 0, 3) != "get")
		{
			throw new Exception('Unimplemented Method: ' . $method);
		}
		
		if (f_util_StringUtils::endsWith($method, 'Date'))
		{
			return indexer_Field::solrDateToDate($this->getProperty(f_util_StringUtils::lowerCaseFirstLetter(substr($method, 3, -4))));
		}
		return $this->getProperty(f_util_StringUtils::lowerCaseFirstLetter(substr($method, 3)));
	}

	private function getHighlightedProperty($name)
	{
		if (!array_key_exists('highlighting', $this->fields))
		{
			$result = substr($this->getProperty($name),0,256);
		}

		if (!array_key_exists($name, $this->fields['highlighting']))
		{
			$result = substr($this->getProperty($name),0,256);
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