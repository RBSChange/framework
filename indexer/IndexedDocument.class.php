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
	 * @param string $name
	 * @param string $value
	 * @param boolean $multi
	 */
	public function setStringField($name, $value, $multi = false)
	{
		if ($value !== null)
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
		elseif (!$multi)
		{
			unset($this->fields[$name . indexer_Field::STRING]);	
		}
	}
	
	/**
	 * Set the Field named $name to $value and treat it as a (non stored) simple string
	 *
	 * @param string $name
	 * @param string $value
	 * @param boolean $multi
	 */
	public function setVolatileStringField($name, $value, $multi = false)
	{
		if ($value !== null)
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
		elseif (!$multi)
		{
			unset($this->fields[$name . indexer_Field::STRING_VOLATILE]);	
		}
	}
	
	/**
	 * Set Localized field named $name to $value and treat it as a stemmable string 
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setLocalizedStringField($name, $value)
	{
		$lang = RequestContext::getInstance()->getLang();
		if ($value !== null)
		{
			$this->fields[$name . '_' . $lang] = array('value' => $value, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
		else
		{
			unset($this->fields[$name . '_' . $lang]);	
		}
	}
	
	/**
	 * @param string $name
	 * @param string $date
	 */
	public function setDateField($name, $date)
	{
		if ($date !== null)
		{
			$suffix = indexer_Field::DATE;
			$this->fields[$name . $suffix] = array('value' => $date, 'type' => indexer_Field::INDEXED | indexer_Field::STORED);
		}
		else
		{
			unset($this->fields[$name . indexer_Field::DATE]);	
		}
	}
	
	/**
	 * Set the Field named $name to $date and treat it as a Date (non stored)
	 *
	 * @param string $name
	 * @param string $date
	 */
	public function setVolatileDateField($name, $date)
	{
		if ($date !== null)
		{
			$suffix = indexer_Field::DATE_VOLATILE;
			$this->fields[$name . $suffix] = array('value' => $date, 'type' => indexer_Field::INDEXED);
		}
		else
		{
			unset($this->fields[$name . indexer_Field::DATE_VOLATILE]);	
		}
	}
	
	/**
	 * Set the field name $name to value $int and treat it as a multivalued integer
	 *
	 * @param string $name
	 * @param mixed $int
	 * @param boolean $multivalued
	 */
	public function setIntegerField($name, $int, $multivalued = false)
	{
		if ($int !== null)
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
		elseif (!$multivalued)
		{
			unset($this->fields[$name .  indexer_Field::INTEGER]);	
		}
	}
	
	/**
	 * Set the field name $name to value $int and treat it as a multivalued integer (non stored)
	 *
	 * @param string $name
	 * @param integer|Integer[] $int
	 * @param boolean $multivalued
	 */
	public function setVolatileIntegerField($name, $int, $multivalued = false)
	{
		if ($int !== null)
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
		elseif (!$multivalued)
		{
			unset($this->fields[$name . indexer_Field::INTEGER_VOLATILE]);	
		}
	}
	
	/**
	 * Set the field name $name to value $float and treat it as a decimal
	 *
	 * @param string $name
	 * @param Float $float
	 */
	public function setFloatField($name, $float)
	{
		if ($float !== null)
		{
			$this->fields[$name . indexer_Field::FLOAT] = array('value' => $float, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
		else
		{
			unset($this->fields[$name . indexer_Field::FLOAT]);	
		}
	}
	
	/**
	 * Set the field name $name to value $float and treat it as a decimal (non stored)
	 *
	 * @param string $name
	 * @param Float $float
	 */
	public function setVolatileFloatField($name, $float)
	{
		if ($float !== null)
		{
			$suffix = indexer_Field::FLOAT_VOLATILE;
			$this->fields[$name . $suffix] = array('value' => $float, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
		else
		{
			unset($this->fields[$name . indexer_Field::FLOAT_VOLATILE]);	
		}
	}
	
	/**
	 * Label setter
	 *
	 * @param string $value
	 */
	public function setLabel($value)
	{
		if ($value !== null)
		{
			$this->fields['label'] = array('value' => $value, 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
		}
	}
	
	/**
	 * text setter
	 *
	 * @param string $value
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
	 * @param string $value
	 */
	public function setId($value)
	{
		if (!is_null($value))
		{
			$this->fields['changeId'] = array('value' => $value, 'type' => indexer_Field::IGNORED);
			unset($this->fields['id']);
		}
	}
	
	/**
	 * lang setter
	 *
	 * @param string $value
	 */
	public function setLang($value)
	{
		if (!is_null($value))
		{
			$this->fields['lang'] = array('value' => $value, 'type' => indexer_Field::INDEXED | indexer_Field::STORED);
			unset($this->fields['id']);
		}
	}
	
	/**
	 * document_model setter
	 *
	 * @param string $value
	 */
	public function setDocumentModel($value)
	{
		$this->fields['documentModel'] = array('value' => $value, 'type' => indexer_Field::INDEXED | indexer_Field::STORED);
	}
	
	/**
	 * id getter
	 *
	 * @return string
	 */
	public final function getId()
	{
		return $this->fields['changeId']['value'];
	}
	
	/**
	 * lang getter
	 *
	 * @return string
	 */
	public final function getLang()
	{
		return $this->fields['lang']['value'];
	}
	
	/**
	 * document_model getter
	 *
	 * @return string
	 */
	public final function getDocumentModel()
	{
		return $this->fields['documentModel']['value'];
	}
	
	/**
	 * label getter
	 *
	 * @return string
	 */
	public final function getLabel()
	{
		return $this->fields['label']['value'];
	}
	
	/**
	 * text getter
	 *
	 * @return string
	 */
	public final function getText()
	{
		return $this->fields['text']['value'];
	}
	
	/**
	 * Unique key getter (concatenation of document id, "/" and lang).
	 *
	 * @return string
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
	 * @param boolean $indexable
	 * @return boolean
	 */
	public function boIndexable($indexable = null)
	{
		$name = 'SEARCHBO' . indexer_Field::INTEGER;
		$result = isset($this->fields[$name]) && $this->fields[$name]['value'] == 1;
		if ($indexable !== null)
		{
			$this->setIntegerField('SEARCHBO', $indexable ? 1 : null);
		}
		return $result;
	}
	
	/**
	 * @param boolean $indexable
	 * @return boolean
	 */
	public function foIndexable($indexable = null)
	{
		$name = 'SEARCHFO' . indexer_Field::INTEGER;
		$result = isset($this->fields[$name]) && $this->fields[$name]['value'] == 1;
		if ($indexable !== null)
		{
			$this->setIntegerField('SEARCHFO', $indexable ? 1 : null);
		}
		return $result;
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
	 * @return boolean
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
	 * @param integer $id
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
	 * @return boolean
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
	 * @param string $name
	 * @param array $arguments
	 */
	final function __call($name, $arguments)
	{
		switch ($name)
		{
			case 'hasParentWebsiteId': 
				Framework::error('Call to deleted ' . get_class($this) . '->'.$name.' method');
				return isset($this->fields['__solrsearch_parentwebsite_id' . indexer_Field::INTEGER]);
			case 'hasParentTopicId': 
				Framework::error('Call to deleted ' . get_class($this) . '->'.$name.' method');
				return isset($this->fields['parentTopicId' . indexer_Field::INTEGER]);
			case 'setParentWebsiteId': 
				Framework::error('Call to deleted ' . get_class($this) . '->'.$name.' method');
				if (!is_null($arguments[0]))
				{
					$this->fields['__solrsearch_parentwebsite_id' . indexer_Field::INTEGER] = array('value' => $arguments[0], 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
				}
				return;
			case 'setParentTopicId': 
				Framework::error('Call to deleted ' . get_class($this) . '->'.$name.' method');
				if (!is_null($arguments[0]))
				{
					$this->fields['parentTopicId' . indexer_Field::INTEGER] = array('value' => $arguments[0], 'type' => indexer_Field::INDEXED | indexer_Field::STORED | indexer_Field::TOKENIZED);
				}
				return;
			default: 
				throw new BadMethodCallException('No method ' . get_class($this) . '->' . $name);
		}
	}
}