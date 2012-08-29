<?php
/**
 * @package framework.persistentdocument
 */
class DocumentHelper
{
	/**
	 * Checks if $a equals $b.
	 *
	 * @param f_persistentdocument_PersistentDocument $a
	 * @param f_persistentdocument_PersistentDocument $b
	 * @return boolean
	 */
	public static function equals($a, $b)
	{
		if ($a === $b)
		{
			return true;
		}

		if ($a instanceof f_persistentdocument_PersistentDocument && $b instanceof f_persistentdocument_PersistentDocument)
		{
			return ($a->getId() === $b->getId() && $a->getId() != 0);
		}

		return false;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument[] $a
	 * @param f_persistentdocument_PersistentDocument[] $b
	 */
	public static function documentArrayEquals($a, $b)
	{
		if ($a === $b) {return true;}
		if ($a instanceof f_persistentdocument_PersistentDocumentArray)
		{
			$a = $a->getArrayCopy();
		}
		elseif (!is_array($a)) 
		{
			return false;
		}
		
		if ($b instanceof f_persistentdocument_PersistentDocumentArray)
		{
			$b = $b->getArrayCopy();
		}		
		elseif (!is_array($b)) 
		{
			return false;
		}
		
		if (count($a) === count($b))
		{
			for ($i = 0; $i < count($a); $i++) 
			{
				if (!self::equals($a[i], $b[i])) {return false;}
			}
			return true;
		}
		return false;
	}

	/**
	 * Returns the document instance or throws an exception.
	 * If you are in the context of a DocumentService, please use $this->getDocumentInstance().
	 * If you expect a given model name, please use static getInstanceById() on the final document class.
	 * @param integer $id
	 * @param string $modelName
	 * @return f_persistentdocument_PersistentDocument
	 * @throws BaseException
	 * @throws IllegalArgumentException
	 */
	public static function getDocumentInstance($id, $modelName = null)
	{
		return f_persistentdocument_PersistentProvider::getInstance()->getDocumentInstance($id, $modelName);
	}

	/**
	 * Returns the document instance or null if the document does not exist.
	 * Please check the retuned value with instanceof.
	 * @param integer $id
	 * @return f_persistentdocument_PersistentDocument|null
	 */
	public static function getDocumentInstanceIfExists($id)
	{
		if (!is_numeric($id) || $id <= 0)
		{
			return null;
		}
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$realModelName = $pp->getDocumentModelName($id);
		if ($realModelName === false)
		{
			return null;
		}
		return $pp->getDocumentInstance($id, $realModelName);
	}
	
	/**
	 * Returns an array of IDs from an array of PersistentDocuments.
	 *
	 * @param f_persistentdocument_PersistentDocument[] $documents
	 * @return integer[]
	 */
	public static function getIdArrayFromDocumentArray($documents)
	{
		if ($documents instanceof f_persistentdocument_PersistentDocumentArray)
		{
			$documents = $documents->getArrayCopy();
		}
		if (is_array($documents))
		{
			return array_map(array('DocumentHelper', 'getId'), $documents);
		}
		return array();
	}
	
	/**
	 * Returns an array of PersistentDocuments from an array of IDs.
	 *
	 * @param integer[] $documentIds
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public static function getDocumentArrayFromIdArray($documentIds)
	{
		if (f_util_ArrayUtils::isNotEmpty($documentIds))
		{
			return array_map(array('DocumentHelper', 'getDocumentInstance'), $documentIds);
		}
		return array();
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return integer
	 */
	public static function getId($document)
	{
		return $document->getId();
	}

	/**
	 * Returns the TreeNode object associated to the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public static function getTreeNodeOf($document)
	{
		return TreeService::getInstance()->getInstanceByDocument($document);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public static function getStatusLocaleKey($document)
	{
		return  'f.persistentdocument.status.'. strtolower($document->getPublicationstatus());
	}
	
	/**
	 * Return the orignal document for the $correctionId if exist. 
	 * If has no correction return the document instance of $correctionId
	 *
	 * @param integer $correctionId
	 * @return f_persistentdocument_PersistentDocument
	 */
	public static function getByCorrectionId($correctionId)
	{
		return self::getByCorrection(self::getDocumentInstance($correctionId));
	}

	/**
	 * Return the orignal document for the $correction if exist. 
	 * If has no correction return the $correction document 
	 * 
	 * @param f_persistentdocument_PersistentDocument $correction
	 * @return f_persistentdocument_PersistentDocument
	 */
	public static function getByCorrection($correction)
	{
		if ($correction instanceof f_persistentdocument_PersistentDocument) 
		{
			$model = $correction->getPersistentModel();
			if ($model->useCorrection())
			{
				if ($correction->getCorrectionofid() > 0)
				{
					return self::getDocumentInstance($correction->getCorrectionofid());
				}
			}  
		}
		return $correction;
	}

	/**
	 * Return the correction document for the $documentId if exist. 
	 * If has no correction return the originadocument document 
	 * 
	 * @param integer $documentId
	 * @return f_persistentdocument_PersistentDocument
	 */
	public static function getCorrectionById($documentId)
	{
		return self::getCorrection(self::getDocumentInstance($documentId));
	}
	
	/**
	 * Return the correction document for the $document if exist. 
	 * If has no correction return the $document document 
	 * 
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument
	 */
	public static function getCorrection($document)
	{
		if ($document instanceof f_persistentdocument_PersistentDocument)
		{
			if ($document->isContextLangAvailable() 
				&& $document->getPersistentModel()->useCorrection()
				&& $document->getCorrectionid() > 0)
			{
				return self::getDocumentInstance($document->getCorrectionid());
			}
		}
		return $document;
	}
	
	
	/**
	 * Returns the properties values of the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $lang
	 * @return array<propertyName => propertyValue>
	 */
	public static function getPropertiesOf($document, $lang = null)
	{
		$properties = array();
		$propertiesInfo = $document->getPersistentModel()->getPropertiesInfos();
		foreach ($propertiesInfo as $propertyName => $propertyInfo)
		{
			if (!$propertyInfo->isDocument() && $propertyName != 'id' && $propertyName != 'model')
			{
				$getter = 'get'.ucfirst($propertyName);
				if ( ! is_null($lang) && $propertyInfo->isLocalized() )
				{
					$properties[$propertyName] = $document->{$getter.'ForLang'}($lang);
				}
				else
				{
					$properties[$propertyName] = $document->{$getter}();
				}
			}
		}
		return $properties;
	}


	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param unknown_type $propertiesNames
	 * @return unknown
	 */
	public static function getPropertiesListOf($document, $propertiesNames = null)
	{
		$allProperties = self::getPropertiesOf($document);
		if (count($propertiesNames) == 0)
		{
			return $allProperties;
		}
		$result = array();
		foreach ($propertiesNames as $propertyName)
		{
			if (array_key_exists($propertyName, $allProperties))
			{
				$result[$propertyName] = $allProperties[$propertyName];
			}
		}
		return $result;
	}


	/**
	 * Sets $properties to the $document.
	 *
	 * @param array<string, mixed> $properties
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param boolean $uivalues
	 */
	public static function setPropertiesTo($properties, $document, $uiValues = false)
	{
		$persistentProvider = f_persistentdocument_PersistentProvider::getInstance();
		$model = $document->getPersistentModel();

		foreach ($properties as $propertyName => $propertyValue)
		{
			if (($propertyName == 'lang') && ($propertyValue != $document->getI18nInfo()->getVo()) )
			{
				continue;
			}

			// If the value is REALLY empty, set it to null (and not empty array, empty string...)
			if ( empty($propertyValue) && $propertyValue !== "0" )
			{
				$propertyValue = null;
			}

			$isPropertyDocument = $model->isDocumentProperty($propertyName);

			if (!$model->isProperty($propertyName))
			{
				$propertySet = false;
				$methodName = 'set'.ucfirst($propertyName);
				try
				{
					$reflectionMethod = new ReflectionMethod(get_class($document), $methodName);
					// intbonjf 2007-02-20: isPublic <=> isCallable?
					if ($reflectionMethod->isPublic())
					{
						$document->{$methodName}($propertyValue);
						$propertySet = true;
					}
				}
				catch (ReflectionException $e)
				{
					//Framework::exception($e); // FIXME intsimoa : this exception should not be ignored, or ?
				}

				if (!$propertySet && Framework::isDebugEnabled())
				{
					Framework::debug('DocumentHelper::setPropertiesTo(): tried to '.$methodName.'() on a '.get_class($document));
				}
			}
			else
			{
				// Call the "setter".
				if ($model->isArrayProperty($propertyName))
				{
					// TODO intbonjf : call addAll when available

					$document->{'removeAll' . ucfirst($propertyName)}();
					if (is_array($propertyValue))
					{
						foreach ($propertyValue as $value)
						{
							if ($isPropertyDocument)
							{
								// If $value is a string representation of an integer (ID),
								// we must turn it into a REAL integer value.
								// First test if this value is a string because calling
								// intval() on a document causes a warning.
								if (is_string($value) && strval(intval($value)) === $value)
								{
									$value = intval($value);
								}
								if (is_int($value) && $value > 0)
								{
									$document->{'add' . ucfirst($propertyName)}($persistentProvider->getDocumentInstance($value));
								}
								elseif ($value instanceof f_persistentdocument_PersistentDocument)
								{
									$document->{'add' . ucfirst($propertyName)}($value);
								}
								else
								{
									Framework::warn('DocumentHelper::setPropertiesTo(): invalid $value in line '.__LINE__.'.');
								}
							}
							else
							{
								$document->{'add' . ucfirst($propertyName)}($value);
							}
						}
					}
				}
				else
				{
					if ($isPropertyDocument)
					{
						// If $propertyValue is a string representation of an integer (ID),
						// we must turn it into a REAL integer value.
						// First test if this value is a string because calling
						// intval() on a document causes a warning.
						if (is_string($propertyValue) && strval(intval($propertyValue)) === $propertyValue)
						{
							$propertyValue = intval($propertyValue);
						}
						if (is_int($propertyValue) && $propertyValue > 0)
						{
							$document->{'set' . ucfirst($propertyName)}($persistentProvider->getDocumentInstance($propertyValue));
						}
						elseif ($propertyValue instanceof f_persistentdocument_PersistentDocument || is_null($propertyValue))
						{
							$document->{'set' . ucfirst($propertyName)}($propertyValue);
						}
						else
						{
							Framework::warn('DocumentHelper::setPropertiesTo(): invalid $propertyValue in line '.__LINE__.'.');
						}
					}
					else
					{
						$property = $model->getProperty($propertyName);
						if ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN)
						{
							$propertyValue = f_util_Convert::toBoolean($propertyValue);
						}
						
						if ($uiValues && $property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME)
						{
							 $document->{'setUI' . ucfirst($propertyName)}($propertyValue);  
						} 
						else if ($uiValues && $property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE)
						{
							 $document->{'set' . ucfirst($propertyName)}(f_util_Convert::parseUIDouble($propertyValue));  
						} 
						else if ($uiValues && $property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT)
						{
							$xHTML = website_XHTMLCleanerHelper::clean($propertyValue);
							$document->{'set' . ucfirst($propertyName)}($xHTML);
						}
						else
						{
							$document->{'set' . ucfirst($propertyName)}($propertyValue);
						}
					}
				}
			}
		}
	}


	/**
	 * Sets the properties of the $document from the $request parameters.
	 *
	 * @param change_Request $request The Request where to get parameters value.
	 * @param f_persistentdocument_PersistentDocument $document The document to set.
	 */
	public static function setPropertiesFromRequestTo(change_Request $request, f_persistentdocument_PersistentDocument $document)
	{
		$documentModel = $document->getPersistentModel();
		//$properties = $documentModel->getComponentNames();
		$values = array();
		foreach ($request->getParameters() as $name => $value)
		{
			// If the value we have is an array and if the attribute is NOT multi-valued
			// we must only keep the first value of the array.
			if (!$documentModel->isArrayProperty($name) && is_array($value))
			{
				$value = $value[0];
			}

			if (is_array($value))
			{
				$trimValue = trim(implode('', $value));
			}
			else
			{
				$trimValue = trim($value);
				// Trim the value. Trimming is not needed in array case, because
				// multi-valued properties are only document ones, not text ones.
				$value = $trimValue;
			}

			// If the value is empty, turn it into a REAL NULL value.
			if ($trimValue === '')
			{
				$value = null;
			}

			$values[$name] = $value;
		}
		self::setPropertiesTo($values, $document, true);
	}
	

	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */	
	private static function getNoneLocalizedProperties($model)
	{	  
		$properties = array();  
		foreach ($model->getPropertiesInfos() as $name => $infos)
		{
			if (!$infos->isLocalized())
			{
				$properties[] = $name;
			}
		}
		return $properties;
	}
	
	/**
	 * @return string[] properties that are handled by system and normally not edited by user
	 */
	public static function getSystemPropertyNames()
	{
		return array("author", "authorid", "creationdate", "modificationdate", "modelversion", "documentversion", "metastring", "publicationstatus", "si18n");
	}

	/**
	 * Check if type property is document
	 * @param string $type
	 * @return boolean
	 */
	public static function isDocumentProperty($type)
	{
		return (strpos($type, 'modules_') === 0);
	}

	/**
	 * Check if property type is a long field
	 * @param string $type
	 * @return boolean
	 */
	public static function isLobProperty($type)
	{
		return ($type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB
			|| $type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_LONGSTRING
			|| $type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT);
	}

	/**
	 * Return the vo label if the $document has not the translation for the $lang
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $lang
	 */
	public static function getLabelForDisplay($document, $lang)
	{
		return $document->isLangAvailable($lang) ? $document->getLabel() : $document->getVoLabel();
	}
	
	/**
	 * For example: "[modules_generic_folder],!modules_generic_rootfolder"
	 * 				"modules_generic_folder,modules_generic_systemfolder"
	 * @param string $allow
	 * @return string
	 */
	public static function expandAllowAttribute($allow)
	{
		$models = array();	
		foreach (explode(',', $allow) as $type)
		{
			$type = trim($type);
			if ($type === 'hasUrl')
			{
				$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('allowedDocumentInfos.ser');
				if (!file_exists($compiledFilePath))
				{
					throw new Exception("File not found : $compiledFilePath. compile-documents needed");
				}
				$allowedInfos = unserialize(file_get_contents($compiledFilePath));
				foreach ($allowedInfos['hasUrl'] as $modelName)
				{
					$models[str_replace('/', '_', $modelName)] = true;
				}
			}
			else if (strlen($type) > 0 && $type[0] == '[')
			{
				$info =  explode('_', str_replace(array('[', ']'), '', $type));
				if (count($info) === 3)
				{
					try 
					{
						$model = f_persistentdocument_PersistentDocumentModel::getInstance($info[1], $info[2]);
						$models[str_replace('/', '_', $model->getName())] = true;
						$children = $model->getChildrenNames();
						if (is_array($children))
						{
							foreach ($children as $childModelName)
							{
								$models[str_replace('/', '_', $childModelName)] = true;
							}
						}
						continue;
					}
					catch (Exception $e)
					{
						Framework::fatal($e->getMessage());
					}
				}
			}
			else if (strlen($type) > 0 && $type[0] == '!')
			{
				$unsetType = substr($type, 1);
				if (isset($models[$unsetType]))
				{
					unset($models[$unsetType]);
				}
				continue;
			} 
			else if (strlen($type) > 0)
			{
				$models[$type] = true;
			}
		}
		
		return implode(',', array_keys($models));
	}
	
	/**
	 * For example: "[modules_generic/folder],!modules_generic/rootfolder"
	 * 				"modules_generic/folder,modules_generic/systemfolder"
	 * @param string $modelList
	 * @return string[]
	 */
	public static function expandModelList($modelList)
	{
		$models = array();
		$modelsChildren = f_persistentdocument_PersistentDocumentModel::getModelChildrenNames(); 
		foreach (explode(',', $modelList) as $modelItem)
		{
			$modelItem = trim($modelItem);
			if (strlen($modelItem) > 0 && $modelItem[0] == '[')
			{
				$modelName = str_replace(array('[', ']'), '', $modelItem);
				try 
				{
					$models[$modelName] = true;
					if (isset($modelsChildren[$modelName]))
					{
						foreach ($modelsChildren[$modelName] as $childModelName)
						{
							$models[$childModelName] = true;
						}
					}
					
					continue;
				}
				catch (Exception $e)
				{
					Framework::fatal($e->getMessage());
				}
			}
			else if (strlen($modelItem) > 0 && $modelItem[0] == '!')
			{
				$unsetType = substr($modelItem, 1);
				if (isset($models[$unsetType]))
				{
					unset($models[$unsetType]);
				}
				continue;
			} 
			else if (strlen($modelItem) > 0)
			{
				$models[$modelItem] = true;
			}
		}
		return array_keys($models);
	}
	
	// BASE: label if specific + icon if specific + all values used in js actions
	const MODE_ITEM = 1; // + hasPreviewImage
	const MODE_RESOURCE = 2; // + block + htmllink
	const MODE_CUSTOM = 4; // + thumbnailsrc + custom columns
	const MODE_ICON = 8; // + icon always set
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array<string, string> $attributes
	 * @param integer $mode
	 * @param string $moduleName
	 */
	public static function completeBOAttributes($document, &$attributes, $mode = 0, $moduleName = null)
	{
		$document->getDocumentService()->completeBOAttributes($document, $attributes, $mode, $moduleName);
		
		$moduleService = ModuleBaseService::getInstanceByModuleName($moduleName);
		if ($moduleService && f_util_ClassUtils::methodExists($moduleService, 'completeBOAttributes'))
		{
			$moduleService->completeBOAttributes($document, $attributes, $mode);
		}

		if (!isset($attributes['label']))
		{
			$attributes['label'] = $document->getTreeNodeLabel();
		}
		
		if (($mode & self::MODE_ICON) && !isset($attributes['icon']))
		{
			$attributes['icon'] = $document->getPersistentModel()->getIcon();
		}
		
		if ($mode & self::MODE_RESOURCE)
		{
			if (!isset($attributes['block']))
			{
				$modelName = $document->getDocumentModelName();
				$models = block_BlockService::getInstance()->getBlocksDocumentModelToInsert();
				if (isset($models[$modelName]))
				{
					$attributes['block'] = f_util_ArrayUtils::firstElement($models[$modelName]);
				}
			}
			if (!isset($attributes['htmllink']) && $document->isContextLangAvailable() && $document->getPersistentModel()->hasURL())
			{
				$lang = RequestContext::getInstance()->getLang();
				$attributes['htmllink'] = '<a class="link" href="#" rel="cmpref:' . $document->getId() . '" lang="' . $lang . '">' . f_util_HtmlUtils::textToHtml($attributes['label']) . '</a>';
			}
		}
	}
}