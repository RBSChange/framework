<?php
class import_ScriptDocumentElement extends import_ScriptObjectElement
{
	/**
	 * @var f_persistentdocument_PersistentDocument
	 */
	private $persistentDocument;

	/**
	 * @var String
	 */
	private $tag;

	/**
	 * (non-PHPdoc)
	 * @see persistentdocument/import/import_ScriptObjectElement#getObject()
	 */
	public function getObject()
	{
		return $this->getPersistentDocument();
	}

	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getPersistentDocument()
	{
		if ($this->persistentDocument === null)
		{
			$document = null;
			foreach ($this->attributes as $key => $value)
			{
				$matches = null;
				if (preg_match('/^by([A-Z]\w+)-attr$/', $key, $matches))
				{
					$attrName = (isset($this->attributes[$key]) && $this->attributes[$key]) ? $this->attributes[$key] : 'by'.$matches[1];
					$attributeValue = $this->getAncestorAttribute($attrName);
					if ($attributeValue !== null)
					{
						$this->attributes['by'.$matches[1]] = $this->getAncestorAttribute($attrName);
					}
					unset($this->attributes[$key]);
				}
			}
			
			if (isset($this->attributes['byRefid']))
			{
				$docElem = $this->script->getDocumentElementById($this->attributes['byRefid']);
				$document = $docElem->getPersistentDocument();
				$model = $document->getPersistentModel();
				unset($this->attributes['byRefid']);
			}
			else if (isset($this->attributes['byDocumentId']))
			{
				$document = DocumentHelper::getDocumentInstance($this->attributes['byDocumentId']);
				$model = $document->getPersistentModel();
				unset($this->attributes['byDocumentId']);
			}
			else if (isset($this->attributes['byTag']))
			{
				$tag = $this->attributes['byTag'];
				$model = $this->getDocumentModel();
				if ($model !== null)
				{
					$document = $this->getDocumentByTag($tag);
					if ($document === null)
					{
						$this->tag = $tag;
					}
				}
				unset($this->attributes['byTag']);
			}
			else
			{
				$model = $this->getDocumentModel();
				if ($model !== null)
				{
					foreach ($this->attributes as $key => $value)
					{
						if ($model->hasProperty($key))
						{
							continue;
						}
						$matches = null;
						if (preg_match('/^by([A-Z]\w+)$/', $key, $matches))
						{
							$propName = strtolower($matches[1][0]).substr($matches[1], 1);
							$document = $this->getChildDocumentByProperty($propName, $value, $model->getName());
							if (!isset($this->attributes[$propName]))
							{
								$this->attributes[$propName] = $value;
							}
							unset($this->attributes[$key]);
						}
					}
				}
			}

			// Check that a document is found and that it has de good type.
			if ($document !== null)
			{
				if ($document->getPersistentModel()->isModelCompatible($model->getName()))
				{
					$this->persistentDocument = $document;
				}
				else
				{
					throw new Exception('Invalid model: '.$document->getPersistentModel()->getName().' is not compatible with '.$model->getName());
				}
			}
		}

		if ($this->persistentDocument === null)
		{
			$this->persistentDocument = $this->initPersistentDocument();
		}
		return $this->persistentDocument;
	}

	/**
	 * @return void
	 */
	public function process()
	{
		$this->setDocumentProperties();
		$this->saveDocument();
		$this->addTag($this->tag);
	}

	/**
	 * @param String $tag
	 */
	public function addTag($tag)
	{
		if (f_util_StringUtils::isNotEmpty($tag))
		{
			TagService::getInstance()->addTag($this->getPersistentDocument(), $tag);
		}
	}

	// Protected methods.

	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function initPersistentDocument()
	{
		throw new Exception(__METHOD__);
	}

	/**
	 * @return import_ScriptDocumentElement
	 */
	protected function getParentDocument()
	{
		return $this->getAncestorByClassName('import_ScriptDocumentElement');
	}

	/**
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		return $this->attributes;
	}

	/**
	 * @return void
	 */
	private function setDocumentProperties()
	{
		// This must be the first line as it manipulates attributes (especially byXxx attributes).
		$document = $this->getPersistentDocument();
		$properties = $this->computeAttributes($this->getDocumentProperties());
		$this->affectDocumentProperties($document, $properties);
	}

	/**
	 * @return void
	 */
	protected function saveDocument()
	{
		$document = $this->getPersistentDocument();
		$document->save($this->getParentNodeId());
	}

	/**
	 * @see DocumentService::save($parentId)
	 * @return Integer id of the document has to be the parent document
	 */
	protected function getParentNodeId()
	{
		$parent = $this->getParentDocument();
		return ($parent !== null) ? $parent->getPersistentDocument()->getId() : null;
	}

	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		$type = $this->getDocumentType();
		if ($type === null)
		{
			throw new Exception("Could not determine document type ! (".$this->attributes['type'].", ".get_class($this).")");
		}
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($type);
	}
	
	protected function getDocumentType()
	{
		$type = null;
		$matches = array();
		if (isset($this->attributes['type']) && preg_match('#modules_[a-z0-9]+/[a-z0-9]+#', $this->attributes['type']) > 0)
		{
			$type = $this->attributes['type'];
		}
		else if (preg_match('#([a-z0-9]+)_([A-Z][a-z0-9]+)ScriptDocumentElement#', get_class($this), $matches))
		{
			$type = 'modules_'.$matches[1].'/'.f_util_StringUtils::toLower($matches[2]);
		}
		return $type;
	}

	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function getParentInTree()
	{
		$parentDoc = $this->getParentDocument();
		if ($parentDoc !== null && $parentDoc->getPersistentDocument()->getTreeId())
		{
			return $parentDoc->getPersistentDocument();
		}
		return null;
	}
	
	/**
	 * @param String $label
	 * @param String $type
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function getChildDocumentByProperty($propName, $propValue, $type)
	{
		$persistentProvider = f_persistentdocument_PersistentProvider::getInstance();
		$query = $persistentProvider->createQuery($type)->add(Restrictions::eq($propName, $propValue));
		
		$parentScript = $this->getParentDocument();
		$parentInTree = $this->getParentInTree(); // Some documents should redefine getParentInTree, so use it here.
		if ($parentScript)
		{
			$parent = $parentScript->getPersistentDocument();
			$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($type);
			$candidateComponentNames = $model->findTreePropertiesNamesByType($parent->getDocumentModelName());
			if (count($candidateComponentNames) == 1)
			{
				$query->add(Restrictions::eq($candidateComponentNames[0], $parent));
			}
			elseif ($parentInTree)
			{
				$query->add(Restrictions::childOf($parentInTree->getId()));
			}
		}
		elseif ($parentInTree)
		{
			$query->add(Restrictions::childOf($parentInTree->getId()));
		}

		$documents = $query->find();
		if (count($documents) > 0)
		{
			// FIXME: what if multiple documents ? Shouldn't we throw something ?
			return $documents[0];
		}

		return null;
	}
	
	// Private methods.

	/**
	 * @param String $tag
	 * @return f_persistentdocument_PersistentDocument
	 */
	private function getDocumentByTag($tag)
	{
		$tagService = TagService::getInstance();
		try
		{
			// Exclusive tags.
			if ($tagService->isExclusiveTag($tag))
			{
				return TagService::getInstance()->getDocumentByExclusiveTag($tag);
			}
			// Contextual tags.
			else if ($tagService->isContextualTag($tag))
			{
				$website = $this->getAncestorDocumentByType('website_persistentdocument_website');
				if ($website === null)
				{
					if ($this->getParentDocument() instanceof import_ScriptDocumentElement)
					{
						$parent = $this->getParentDocument()->getPersistentDocument();
						$website = website_WebsiteService::getInstance()->getByDescendentId($parent->getId());
					}
				}
				return TagService::getInstance()->getDocumentByContextualTag($tag, $website);
			}
			// Functional tags.
			else if ($tagService->isFunctionalTag($tag))
			{
				$persistentProvider = f_persistentdocument_PersistentProvider::getInstance();
				$parent = $this->getParentDocument()->getPersistentDocument();
				$documents = $persistentProvider->createQuery()->add(Restrictions::childOf($parent->getId()))->add(Restrictions::hasTag($tag))->find();
				if (count($documents) > 0)
				{
					return $documents[0];
				}
			}
			// Other tags.
			else
			{
				$documents = $tagService->getDocumentsByTag($tag);
				if (count($documents) > 0)
				{
					return $documents[0];
				}
			}
		}
		catch (Exception $e)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::exception($e);
			}
		}
		return null;
	}

	/**
	 * @param String $type
	 * @return f_persistentdocument_PersistentDocument
	 */
	private function getAncestorDocumentByType($type)
	{
		$parent = $this->getParentDocument();
		while ($parent !== null)
		{
			if (is_a($parent->getPersistentDocument(), $type))
			{
				return $parent->getPersistentDocument();
			}
			$parent = $parent->getParentDocument();
		}
		return null;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array $properties
	 */
	private function affectDocumentProperties($document, $properties)
	{
		$model = $document->getPersistentModel();
		$langs = RequestContext::getInstance()->getSupportedLanguages();

		foreach ($properties as $propertyName => $propertyValue)
		{
			if ($propertyValue === '')
			{
				$propertyValue = null;
			}

			//Check lang
			$data = explode('-', $propertyName);
			if (isset($data[1]))
			{
				$propertyName = $data[0];

				$lang = $data[1];
				if (!in_array($lang, $langs))
				{
					continue;
				}
			}
			else
			{
				$lang = null;
			}

			$property = $model->getProperty($propertyName);

			if ($property === null)
			{
				$methodName = 'set' . ucfirst($propertyName);
				if (f_util_ClassUtils::methodExists($document, $methodName))
				{
					$document->{$methodName}($propertyValue);
				}
				else
				{
					$this->script->addWarning('Unable to affect ' . get_class($document) . '->' . $methodName . '(' . $propertyValue . ')');
				}
			}
			else
			{
				if ($property->isArray())
				{
					if (is_array($propertyValue))
					{
						foreach ($propertyValue as $value)
						{
							$this->addValueToDocumentProperty($document, $propertyName, $value);
						}
					}
					else
					{
						$this->addValueToDocumentProperty($document, $propertyName, $propertyValue);
					}
				}
				elseif ($property->isDocument())
				{
					if ($propertyValue === null || $propertyValue === '==')
					{
						$document->{'set' . ucfirst($propertyName)}(null);
					}
					elseif ($propertyValue instanceof f_persistentdocument_PersistentDocument)
					{
						$document->{'set' . ucfirst($propertyName)}($propertyValue);
					}
					elseif (strval(intval($propertyValue)) === $propertyValue)
					{
						$document->{'set' . ucfirst($propertyName)}(DocumentHelper::getDocumentInstance(intval($propertyValue)));
					}
					else
					{
						$this->script->addWarning('Unable to affect ' . get_class($document) . '->set' . ucfirst($propertyName) . '(' . $propertyValue . ')');
					}
				}
				else
				{
					if ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN)
					{
						$propertyValue = $this->parseBoolean($propertyValue);
					}
					else if ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT)
					{
						$propertyValue = $this->parseXHTMLFragment($propertyValue);
					}
					else if (($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER ||
						$property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTID)
						&& ($propertyValue instanceof f_persistentdocument_PersistentDocument))
					{
						$propertyValue = $propertyValue->getId();
					}
										
					if ($lang !== null && $property->isLocalized())
					{
						RequestContext::getInstance()->beginI18nWork($lang);
						$document->{'set' . ucfirst($propertyName)}($propertyValue);
						RequestContext::getInstance()->endI18nWork();
					}
					else
					{
						$document->{'set' . ucfirst($propertyName)}($propertyValue);
					}
				}
			}
		}
	}
	
	/**
	 * @param string $value
	 * @return string
	 */
	protected function parseXHTMLFragment($value)
	{
		if (!empty($value))
		{
			$value = $this->replaceRefIdInString($value);
			$value = $this->replaceAttrInString($value);
			$value = website_XHTMLCleanerHelper::clean($value);
		}
		return $value;
	}
	
	/**
	 * @param string $value
	 * @return string
	 */
	protected function replaceRefIdInString($value)
	{
		return preg_replace_callback('#\{ref-id:([^\}]+)\}#', array($this, 'getDocumentIdCallback'), $value);
	}
	
	/**
	 * @param array $matches
	 * @return integer
	 */
	public function getDocumentIdCallback($matches)
	{
		$documentElement = $this->script->getDocumentElementById($matches[1]);
		if ($documentElement === null)
		{
			throw new Exception('Reference not found: '.$matches[1]);	
		}
		$document = $documentElement->getPersistentDocument();
		if ($document->isNew())
		{
			throw new Exception('Reference '.$matches[1].' is not persisted.');
		}
		return $document->getId();
	}
	
	/**
	 * @param string $value
	 * @return string
	 */
	protected function replaceAttrInString($value)
	{
		return preg_replace_callback('#\{attr:([^\}]+)\}#', array($this, 'getAttributeCallback'), $value);
	}
	
	/**
	 * @param array $matches
	 * @return string
	 */
	public function getAttributeCallback($matches)
	{
		return $this->getAncestorAttribute($matches[1]);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $propertyName
	 * @param Mixed $propertyValue
	 */
	private function addValueToDocumentProperty($document, $propertyName, $propertyValue)
	{
		if ($propertyValue === '==')
		{
			$document->{'removeAll' . ucfirst($propertyName)}();
		}
		elseif ($propertyValue instanceof f_persistentdocument_PersistentDocument)
		{
			$document->{'add' . ucfirst($propertyName)}($propertyValue);
		}
		elseif (strval(intval($propertyValue)) === $propertyValue)
		{
			$document->{'add' . ucfirst($propertyName)}(DocumentHelper::getDocumentInstance(intval($propertyValue)));
		}
		else
		{
			$this->script->addWarning('Unable to affect ' . get_class($document) . '->add' . ucfirst($propertyName) . '(' . $propertyValue . ')');
		}
	}
	
	/**
	 * May be used inside an xml script with the "execute" element to set the 'byDocumentId' attribute by default.
	 * 
	 * @param import_ScriptExecuteElement $scriptExecute
	 * @param array $attr Si le tableau contient une clé 'attribute-name' cette valeur sera utilisée comme nom d'attribut
	 */
	public function setDocumentIdAttribute($scriptExecute, $attr = null)
	{
		$attributeName = (is_array($attr) && isset($attr['attribute-name'])) ? $attr['attribute-name'] : 'byDocumentId';
		$this->script->setAttribute($attributeName, $this->getPersistentDocument()->getId());
	}
	
	/**
	 * May be used inside an xml script with the "execute" element to set the 'type' attribute by default.
	 * @param import_ScriptExecuteElement $scriptExecute
	 * @param array $attr Si le tableau contient une clé 'attribute-name' cette valeur sera utilisée comme nom d'attribut
	 */
	public function setDocumentTypeAttribute($scriptExecute, $attr = null)
	{
		$attributeName = (is_array($attr) && isset($attr['attribute-name'])) ? $attr['attribute-name'] : 'type';
		$this->script->setAttribute($attributeName, $this->getPersistentDocument()->getDocumentModelName());
	}
}