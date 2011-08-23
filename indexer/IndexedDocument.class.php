<?php
class indexer_IndexedDocument
{
	
	private $fields = array();
	
	/**
	 * @param indexer_IndexedDocument $otherIndexDocument
	 * @return indexer_IndexedDocument
	 */
	public function merge($otherIndexDocument)
	{
		foreach ($otherIndexDocument->fields as $fieldName => $fieldData) 
		{
			if (!isset($this->fields[$fieldName]))
			{
				$this->fields[$fieldName] = $fieldData;
			}
			elseif (is_array($this->fields[$fieldName]['value']) && is_array($fieldData['value']))
			{
				$this->fields[$fieldName]['value'] = array_unique(array_merge($this->fields[$fieldName]['value'], $fieldData['value']));
			}
		}
		return $this;
	}
	
	/**
	 * Set the Field named $name to $value and treat it as a simple string 
	 *
	 * @param String $name
	 * @param String $value
	 */
	public function setStringField($name, $value, $multi = false)
	{
		if (!is_null($value))
		{
			if ($multi)
			{
				$type = indexer_Field::INDEXED | indexer_Field::MULTIVALUED | indexer_Field::STORED;
				$suffix = indexer_Field::STRING_MULTI;
			}
			else 
			{
				$type = indexer_Field::INDEXED | indexer_Field::STORED;
				$suffix = indexer_Field::STRING;
			}
			$this->fields[$name . $suffix] = array('value' => $value, 'type' => $type);
		}
	}
	
	/**
	 * Set the Field named $name to $value and treat it as a (non stored) simple string
	 *
	 * @param String $name
	 * @param String $value
	 */
	public function setVolatileStringField($name, $value, $multi = false)
	{
		if (!is_null($value))
		{
			if($multi)
			{
				$type = indexer_Field::INDEXED | indexer_Field::MULTIVALUED | indexer_Field::STORED;
				$suffix = indexer_Field::STRING_MULTI_VOLATILE;
			}
			else 
			{
				$type = indexer_Field::INDEXED | indexer_Field::STORED;
				$suffix = indexer_Field::STRING_VOLATILE;
			}
			$this->fields[$name . $suffix] = array('value' => $value, 'type' => $type);
		}
	}
	
	/**
	 * Set Localized field named $name to $value and treat it as a stemmable string 
	 *
	 * @param String $name
	 * @param String $value
	 */
	public function setLocalizedStringField($name, $value)
	{
		if (!is_null($value))
		{
			$lang = RequestContext::getInstance()->getLang();
			$this->fields[$name . "_$lang"] = array('value' => $value, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
	}
	
	
	
	/**
	 * Set the Field named $name to $date and treat it as a Date 
	 *
	 * @param String $name
	 * @param date_Calendar $date
	 */
	public function setDateField($name, $date)
	{
		if (!is_null($date))
		{
			$this->fields[$name . indexer_Field::DATE] = array('value' => 
				date_Formatter::format($date ,indexer_Field::SOLR_DATE_FORMAT), 
				'type' => indexer_Field::INDEXED | indexer_Field::STORED);
		}
	}
	
	/**
	 * Set the Field named $name to $date and treat it as a Date (non stored)
	 *
	 * @param String $name
	 * @param date_Calendar $date
	 */
	public function setVolatileDateField($name, $date)
	{
		if (!is_null($date))
		{
			$suffix = indexer_Field::DATE_VOLATILE;
			$this->fields[$name . $suffix] = array('value' => date_Formatter::format($date, indexer_Field::SOLR_DATE_FORMAT), 'type' => indexer_Field::INDEXED);
		}
	}
	
	/**
	 * Set the field name $name to value $int and treat it as a multivalued integer
	 *
	 * @param String $name
	 * @param mixed $int
	 * @param Boolean $multivalued
	 */
	public function setIntegerField($name, $int, $multivalued = false)
	{
		if (!is_null($int))
		{
			if($multivalued)
			{
				$type = indexer_Field::INDEXED | indexer_Field::MULTIVALUED | indexer_Field::STORED;
				$suffix = indexer_Field::INTEGER_MULTI;
			}
			else
			{
				$type = indexer_Field::INDEXED | indexer_Field::STORED;
				$suffix = indexer_Field::INTEGER;
			}
			$this->fields[$name . $suffix] = array('value' => $int, 'type' => $type);
		}
	}
	
	/**
	 * Set the field name $name to value $int and treat it as a multivalued integer (non stored)
	 *
	 * @param String $name
	 * @param Integer|Integer[] $int
	 * @param Boolean $multivalued
	 */
	public function setVolatileIntegerField($name, $int, $multivalued = false)
	{
		if (!is_null($int))
		{
			if ($multivalued)
			{
				$type = indexer_Field::INDEXED | indexer_Field::MULTIVALUED;
				$suffix = indexer_Field::INTEGER_MULTI_VOLATILE;
			}
			else
			{
				$type = indexer_Field::INDEXED;
				$suffix = indexer_Field::INTEGER_VOLATILE;
			}
			$this->fields[$name . $suffix] = array('value' => $int, 'type' => $type);
		}
	}
	
	/**
	 * Set the field name $name to value $float and treat it as a decimal
	 *
	 * @param String $name
	 * @param Float $float
	 */
	public function setFloatField($name, $float)
	{
		if (!is_null($float))
		{
			$this->fields[$name . indexer_Field::FLOAT] = array('value' => $float, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
	}
	
	/**
	 * Set the field name $name to value $float and treat it as a decimal (non stored)
	 *
	 * @param String $name
	 * @param Float $float
	 */
	public function setVolatileFloatField($name, $float)
	{
		if (!is_null($float))
		{
			$suffix = indexer_Field::FLOAT_VOLATILE;
			$this->fields[$name . $suffix] = array('value' => $float, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
	}
	
	/**
	 * Label setter
	 *
	 * @param String $value
	 */
	public function setLabel($value)
	{
		if (!is_null($value))
		{
			$this->fields['label'] = array('value' => $value, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
	}
	
	/**
	 * text setter
	 *
	 * @param String $value
	 */
	public function setText($value)
	{
		$this->fields['text'] = array('value' => $value, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
	}
	
	/**
	 * 
	 * @param string[] $values
	 * @param string $lang
	 */
	public function setAggregateTexts($values, $lang = null)
	{
		$fn = empty($lang) ? 'aggregateText' : $lang .'_aggregateText';
		if (is_array($values) && count($values))
		{
			$this->fields[$fn] = array('value' => $values, 'type' => indexer_Field::INDEXED);
		}
		else
		{
			unset($this->fields[$fn]);
		}
	}
	
	/**
	 * @param string $value
	 * @param string $lang
	 */
	public function addAggregateText($value, $lang = null)
	{
		if (!empty($value))
		{
			$fn = empty($lang) ? 'aggregateText' : $lang .'_aggregateText';
			if (isset($this->fields[$fn]))
			{
				$this->fields[$fn]['value'][] = $value;
			}
			else
			{
				$this->fields[$fn] = array('value' => array($value), 'type' => indexer_Field::INDEXED);
			}
		}
	}	
	
	
	/**
	 * change Id setter
	 *
	 * @param String $value
	 */
	public function setId($value)
	{
		if (!is_null($value))
		{
			$this->fields['changeId'] = array('value' => $value, 'type' => indexer_Field::IGNORED);
		}
	}
	
	/**
	 * lang setter
	 *
	 * @param String $value
	 */
	public function setLang($value)
	{
		if (!is_null($value))
		{
			$this->fields['lang'] = array('value' => $value, 'type' => indexer_Field::INDEXED | indexer_Field::STORED);
		}
	}
	
	/**
	 * document_model setter
	 *
	 * @param String $value
	 */
	public function setDocumentModel($value)
	{
		$this->fields['documentModel'] = array('value' => $value, 'type' => indexer_Field::INDEXED | indexer_Field::STORED);
	}
	
	/**
	 * id getter
	 *
	 * @return String
	 */
	public final function getId()
	{
		return $this->fields['changeId']['value'];
	}
	
	/**
	 * lang getter
	 *
	 * @return String
	 */
	public final function getLang()
	{
		return $this->fields['lang']['value'];
	}
	
	/**
	 * document_model getter
	 *
	 * @return String
	 */
	public final function getDocumentModel()
	{
		return $this->fields['documentModel']['value'];
	}
	
	/**
	 * label getter
	 *
	 * @return String
	 */
	public final function getLabel()
	{
		return $this->fields['label']['value'];
	}
	
	/**
	 * text getter
	 *
	 * @return String
	 */
	public final function getText()
	{
		return $this->fields['text']['value'];
	}
	
	/**
	 * Unique key getter (concatenation of document id, "/" and lang).
	 *
	 * @return String
	 */
	public final function getUniqueKey()
	{
		if (!array_key_exists('id', $this->fields))
		{
			if (array_key_exists('lang', $this->fields) && array_key_exists('changeId', $this->fields))
			{
				$this->fields['id']['value'] = $this->getId() . "/" . $this->getLang();
				$this->fields['id']['type'] = indexer_Field::INDEXED | indexer_Field::STORED;
			}
			else
			{
				return null;
			}
		}
		return $this->fields['id']['value'];
	}
	
	/**
	 * Get all the field as an associative array of the type:
	 * 		Array(name => array('value' => value, 'type' => type)
	 *
	 * @return Array<String, Array<mixed, Integer>>
	 */
	public function getFields()
	{
		if (!array_key_exists('changeId', $this->fields) || !array_key_exists('documentModel', $this->fields) || !array_key_exists('lang', $this->fields) || !array_key_exists('label', $this->fields) || !array_key_exists('text', $this->fields))
		{
			
			throw new Exception("IndexedDocument does not provide the required fields " . var_export($this->fields, true));
		}
		$this->getUniqueKey();
		return $this->fields;
	}
	
	/**
	 * Set the multivalued document accessor field given an array of "accessors" Id's.
	 *
	 * @param Array<Integer> $idArray
	 */
	public function setDocumentAccessors($idArray)
	{
		$this->fields['document_accessor']['value'] = $idArray;
		$this->fields['document_accessor']['type'] = indexer_Field::INDEXED | indexer_Field::MULTIVALUED;
	}
	
	/**
	 * Predicate on the existence of document accessors
	 *
	 * @return Boolean
	 */
	public function hasDocumentAccessors()
	{
		return isset($this->fields['document_accessor']);
	}
	
	/**
	 * Set the multivalued document ancestor field given an array of "ancestors" Id's.
	 *
	 * @param Array<Integer> $idArray
	 */
	public function setDocumentAncestors($idArray)
	{
		unset($this->fields['document_ancestor']);
		foreach ($idArray as $id)
		{
			$this->addDocumentAncestor($id);
		}
	}
	
	/**
	 * add a document ancestor 
	 *
	 * @param Integer $id
	 */
	public function addDocumentAncestor($id)
	{
		if (!$this->hasDocumentAncestors())
		{
			$this->fields['document_ancestor'] = array('value' => array(), 'type' => indexer_Field::INDEXED | indexer_Field::MULTIVALUED);
		}
		$this->fields['document_ancestor']['value'][] = $id;
	}
	
	
	/**
	 * Predicate on the existence of document ancestors
	 *
	 * @return Boolean
	 */
	public function hasDocumentAncestors()
	{
		return isset($this->fields['document_ancestor']);
	}
	
	/**
	 * @param integer[] $websiteIds
	 */
	public function setWebsiteIds($websiteIds)
	{
		if ($websiteIds === null) {$websiteIds = array(0);}
		$fieldName = indexer_Field::getVolatileIntegerMultiFieldName('websiteIds');
		$this->fields[$fieldName]['value'] = $websiteIds;
		$this->fields[$fieldName]['type'] = indexer_Field::INDEXED;
	}
	
	/**
	 * @deprecated
	 * @return Boolean
	 */
	public function hasParentWebsiteId()
	{
		return isset($this->fields[indexer_Field::PARENT_WEBSITE . indexer_Field::INTEGER]);
	}
	
	/**
	 * @deprecated
	 * @return Boolean
	 */
	public function hasParentTopicId()
	{
		return isset($this->fields[indexer_Field::PARENT_TOPIC . indexer_Field::INTEGER]);
	}
	
	/**
	 * @deprecated
	 * @param String $name
	 * @param Integer $int
	 */
	public function setParentWebsiteId($int)
	{
		if (!is_null($int))
		{
			$this->fields[indexer_Field::PARENT_WEBSITE . indexer_Field::INTEGER] = array('value' => $int, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
	}
	
	/**
	 * @deprecated
	 * @param String $name
	 * @param Integer $int
	 */
	public function setParentTopicId($int)
	{
		if ($int !== null)
		{
			$this->fields[indexer_Field::PARENT_TOPIC . indexer_Field::INTEGER] = array('value' => $int, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
	}
}