<?php
/**
 * @deprecated
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
			return ($a->getId() === $b->getId() && $a->getId() > 0);
		}
		return false;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument[] $a
	 * @param f_persistentdocument_PersistentDocument[] $b
	 */
	public static function documentArrayEquals($a, $b)
	{
		if ($a === $b)
		{
			return true;
		}
		if ($a instanceof ArrayObject)
		{
			$a = $a->getArrayCopy();
		}
		elseif (!is_array($a))
		{
			return false;
		}
		
		if ($b instanceof ArrayObject)
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
				if (!self::equals($a[i], $b[i]))
				{
					return false;
				}
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
	 * @throws Exception
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
		return f_persistentdocument_PersistentProvider::getInstance()->getDocumentInstanceIfExist($id);
	}
	
	/**
	 * Returns an array of IDs from an array of PersistentDocuments.
	 *
	 * @param f_persistentdocument_PersistentDocument[] $documents
	 * @return integer[]
	 */
	public static function getIdArrayFromDocumentArray($documents)
	{
		if (is_array($documents))
		{
			return array_map(function ($document)
			{
				if ($document instanceof f_persistentdocument_PersistentDocument)
				{
					return $document->getId();
				}
				throw new InvalidArgumentException('document not a "f_persistentdocument_PersistentDocument" ');
			}, $documents);
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
		if (is_array($documentIds))
		{
			return array_map(function ($id)
			{
				return f_persistentdocument_PersistentProvider::getInstance()->getDocumentInstance($id);
			}, $documentIds);
		}
		return array();
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
			if ($document->isContextLangAvailable() && $document->getPersistentModel()->useCorrection() && $document->getCorrectionid() > 0)
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
		$propertiesInfo = $document->getPersistentModel()->getEditablePropertiesInfos();
		foreach ($propertiesInfo as $propertyName => $propertyInfo)
		{
			/* @var $propertyInfo PropertyInfo */
			if (!$propertyInfo->isDocument() && $propertyName != 'id' && $propertyName != 'model')
			{
				$getter = 'get' . ucfirst($propertyName);
				if (!is_null($lang) && $propertyInfo->isLocalized())
				{
					$properties[$propertyName] = $document->{$getter . 'ForLang'}($lang);
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
	 * @param string[] $propertiesNames
	 * @return array<propertyName => propertyValue>
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
		$provider = f_persistentdocument_PersistentProvider::getInstance();
		$model = $document->getPersistentModel();
		
		foreach ($properties as $propertyName => $propertyValue)
		{
			if (($propertyName == 'lang') && ($propertyValue != $document->getI18nInfo()->getVo()))
			{
				continue;
			}
			
			// If the value is REALLY empty, set it to null (and not empty array, empty string...)
			if ($propertyValue === '')
			{
				$propertyValue = null;
			}
			$property = $model->getEditableProperty($propertyName);
			
			if ($property === null)
			{
				$propertySet = false;
				$methodName = 'set' . ucfirst($propertyName);
				if (method_exists($document, $methodName))
				{
					call_user_func(array($document, $methodName), $propertyValue);
				}
			}
			else
			{
				// Call the "setter".
				if ($property->isArray())
				{
					$methodName = 'removeAll' . ucfirst($propertyName);
					call_user_func(array($document, $methodName));
					
					if (is_array($propertyValue))
					{
						$methodName = 'add' . ucfirst($propertyName);
						foreach ($propertyValue as $value)
						{
							if (is_numeric($value))
							{
								$value = self::getDocumentInstanceIfExists($value);
							}
							if ($value instanceof f_persistentdocument_PersistentDocument)
							{
								call_user_func(array($document, $methodName), $value);
							}
							else
							{
								Framework::warn(__METHOD__ . ' Invalid value for ' . get_class($document) . '->' . $methodName);
							}
						}
					}
				}
				elseif ($property->isDocument())
				{
					$methodName = 'set' . ucfirst($propertyName);
					if (is_numeric($propertyValue))
					{
						$propertyValue = self::getDocumentInstanceIfExists($propertyValue);
					}
					
					if ($propertyValue instanceof f_persistentdocument_PersistentDocument)
					{
						call_user_func(array($document, $methodName), $propertyValue);
					}
					else
					{
						Framework::warn(__METHOD__ . ' Invalid value for ' . get_class($document) . '->' . $methodName);
					}
				}
				else
				{
					$methodName = 'set' . ucfirst($propertyName);
					if ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN)
					{
						$propertyValue = f_util_Convert::toBoolean($propertyValue);
					}
					elseif ($uiValues)
					{
						if ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME)
						{
							$methodName = 'setUI' . ucfirst($propertyName);
						}
						elseif ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE)
						{
							$propertyValue = f_util_Convert::parseUIDouble($propertyValue);
						}
						elseif ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT)
						{
							$propertyValue = website_XHTMLCleanerHelper::clean($propertyValue);
						}
					}
					call_user_func(array($document, $methodName), $propertyValue);
				}
			}
		}
	}
	
	/**
	 * @return string[] properties that are handled by system and normally not edited by user
	 */
	public static function getSystemPropertyNames()
	{
		return array('id', 'model', 'author', 'authorid', 'creationdate', 'modificationdate', 'publicationstatus', 'lang', 'metastring', 
			'modelversion', 'documentversion', 'si18n');
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
					Framework::exception($e);
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
	
	/**
	 * @deprecated
	 */
	public static function getId($document)
	{
		return $document->getId();
	}
	
	/**
	 * @deprecated
	 */
	public static function getTreeNodeOf($document)
	{
		return TreeService::getInstance()->getInstanceByDocument($document);
	}
	
	/**
	 * @deprecated
	 */
	public static function getStatusLocaleKey($document)
	{
		$status = ($document->getPublicationstatus() === null) ? 'null' : strtolower($document->getPublicationstatus());
		return 'f.persistentdocument.status.' . $status;
	}
	
	/**
	 * @deprecated
	 */
	public static function setPropertiesFromRequestTo(change_Request $request, f_persistentdocument_PersistentDocument $document)
	{
		$documentModel = $document->getPersistentModel();
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
	 * @deprecated
	 */
	public static function isDocumentProperty($type)
	{
		return (strpos($type, 'modules_') === 0);
	}
	
	/**
	 * @deprecated
	 */
	public static function isLobProperty($type)
	{
		return ($type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB || $type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_LONGSTRING || $type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT);
	}
	
	/**
	 * @deprecated
	 */
	public static function getLabelForDisplay($document, $lang)
	{
		return $document->isLangAvailable($lang) ? $document->getLabel() : $document->getVoLabel();
	}
	
	/**
	 * @deprecated
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
				$info = explode('_', str_replace(array('[', ']'), '', $type));
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
	
	// BASE: label if specific + icon if specific + all values used in js actions
	/**
	 * @deprecated
	 */
	const MODE_ITEM = 1; // + hasPreviewImage
	

	/**
	 * @deprecated
	 */
	const MODE_RESOURCE = 2; // + block + htmllink
	

	/**
	 * @deprecated
	 */
	const MODE_CUSTOM = 4; // + thumbnailsrc + custom columns
	

	/**
	 * @deprecated
	 */
	const MODE_ICON = 8; // + icon always set
	

	/**
	 * @deprecated
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