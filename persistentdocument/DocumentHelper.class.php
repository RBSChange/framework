<?php
/**
 * @deprecated
 */
class DocumentHelper extends \Change\Documents\DocumentHelper
{

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
		return  'f.persistentdocument.status.'. $status;
	}
	
	/**
	 * @deprecated
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
		return ($type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB
			|| $type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_LONGSTRING
			|| $type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT);
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