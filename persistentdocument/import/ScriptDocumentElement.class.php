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
			if (isset($this->attributes['byTag']))
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
						if (preg_match("/^by([A-Z]\w+)$/", $key, $matches))
						{
							$propName = strtolower($matches[1][0]).substr($matches[1], 1);
							$document = $this->getChildDocumentByProperty($propName, $value, $model->getName());
							if ($document === null && !isset($this->attributes[$propName]))
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
	 * @param String $className
	 * @return import_ScriptBaseElement
	 * @deprecated use getAncestorByClassName
	 */
	protected final function getParentByClassName($className)
	{
		return $this->getAncestorByClassName($className);
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
		if (isset($this->attributes['type']) && preg_match('#modules_[a-z]+/[a-z]+#', $this->attributes['type']) > 0)
		{
			$type = $this->attributes['type'];
		}
		else if (preg_match('#([a-z]+)_([A-Z][a-z]+)ScriptDocumentElement#', get_class($this), $matches))
		{
			$type = 'modules_'.$matches[1].'/'.f_util_StringUtils::strtolower($matches[2]);
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
	
	// Private methods.

	/**
	 * @param String $label
	 * @param String $type
	 * @return f_persistentdocument_PersistentDocument
	 */
	private function getChildDocumentByProperty($propName, $propValue, $type)
	{
		$persistentProvider = f_persistentdocument_PersistentProvider::getInstance();
		$query = $persistentProvider->createQuery($type)->add(Restrictions::eq($propName, $propValue));
		
		$parentDoc = $this->getParentInTree();
		if ($parentDoc !== null)
		{
			$query->add(Restrictions::childOf($parentDoc->getId()));
		}

		$documents = $query->find();
		if (count($documents) > 0)
		{
			// FIXME: what if multiple documents ? Shouldn't we throw something ?
			return $documents[0];
		}

		return null;
	}

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
					if ($propertyValue === NULL)
					{
						$document->{'set' . ucfirst($propertyName)}($propertyValue);
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
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $propertyName
	 * @param Mixed $propertyValue
	 */
	private function addValueToDocumentProperty($document, $propertyName, $propertyValue)
	{
		if ($propertyValue instanceof f_persistentdocument_PersistentDocument)
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
}