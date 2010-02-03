<?php
/**
 * @package framework.persistentdocument
 */
class DocumentHelper
{


    /**
     * Key accessor for allowed component names.
     *
     */
    const ALLOWED_ACCESSOR = 'allowed';


    /**
     * Key accessor for disabled component names.
     *
     */
    const DISABLED_ACCESSOR = 'disabled';


    /**
     * Key accessor for suggested component names and values.
     *
     */
    const SUGGESTED_ACCESSOR = 'suggested';


    /**
     * Key accessor for translation option.
     *
     */
    const TRANSLATION_ACCESSOR = 'translation';


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
	 * Checks if $a equals $b.
	 *
	 * @param f_persistentdocument_PersistentDocument $a
	 * @param f_persistentdocument_PersistentDocument $b
	 * @return boolean
	 *
	 * @deprecated Use equals() instead.
	 */
	public static function isEquals($a, $b)
	{
		return self::equals($a, $b);
	}

	/**
	 * Returns the document instance with ID $id.
	 * 
	 * If you are in the context of a DocumentService, please use
	 * $this->getDocumentInstance().
	 * 
	 * If you expect a given model name, give the modelName parameter :
	 * you will save queries, except if you only load model or the label
	 * of the document (directly stored in f_document table)
	 * @param integer $id
	 * @param String $modelName
	 * @return f_persistentdocument_PersistentDocument
	 */
	public static function getDocumentInstance($id, $modelName = null)
	{
		return f_persistentdocument_DocumentService::getInstance()->getDocumentInstance($id, $modelName);
	}



	/**
	 * Returns an array of IDs from an array of PersistentDocuments.
	 *
	 * @param array<f_persistentdocument_PersistentDocument> $documents
	 *
	 * @return array<integer>
	 */
	public static function getIdArrayFromDocumentArray($documents)
	{
		if ($documents instanceof f_persistentdocument_PersistentDocumentArray)
		{
			$documents = $documents->getArrayCopy();
		}
		if ( is_array($documents) )
		{
			return array_map(array('DocumentHelper', 'getId'), $documents);
		}
		return array();
	}
	
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
		return f_persistentdocument_PersistentTreeNode::getInstanceByDocument($document);
	}
	
	
	/**
	 * @return String
	 */
	public static function getPublicationstatusLocaleKey($document)
	{
		return  '&framework.persistentdocument.status.'. ucfirst(strtolower($document->getPublicationstatus())) .";";
	}
	
	/**
	 * Return the orignal document for the $correctionId if exist. 
	 * If has no correction return the document instance of $correctionId
	 *
	 * @param Integer $correctionId
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
	 * @param Integer $documentId
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
	 * @param Request $request The Request where to get parameters value.
	 * @param f_persistentdocument_PersistentDocument $document The document to set.
	 */
	public static function setPropertiesFromRequestTo(Request $request, f_persistentdocument_PersistentDocument $document)
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
	 * Returns the XML content that is used to populate backoffice forms.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document to serialize in XML.
	 * @param array $allowedComponentNames array of allowed component names (null if all components are allowed).
	 * @param array $disabledComponentNames array of disabled component names (null if all components are enabled).
	 * @param array $suggestedComponents associative array of suggested values for component names.
	 * @return string XML
	 */
	public static function toXml($document, $allowedComponentNames = null, $disabledComponentNames = null, $suggestedComponents = null)
	{
	    $model = $document->getPersistentModel();

	    if ($model->isLocalized() && ($document->getLang() != RequestContext::getInstance()->getLang()))
	    {
	        if (is_null($disabledComponentNames))
	        {
	            $disabledComponentNames = array();
	        }

	        $componentNames = $model->getPropertiesNames();

    	    foreach ($componentNames as $componentName)
    		{
    			$property = $model->getProperty($componentName);

    			if (!$property->isLocalized())
    			{
    			    $disabledComponentNames[] = $componentName;
    			}
    		}

        	$disabledComponentNames = array_unique($disabledComponentNames);
	    }

	    $options = array(
	        self::ALLOWED_ACCESSOR => $allowedComponentNames,
	        self::DISABLED_ACCESSOR => $disabledComponentNames,
	        self::SUGGESTED_ACCESSOR => $suggestedComponents,
	        self::TRANSLATION_ACCESSOR => false
	    );

	    return self::_toXml($document, 1, null, null, $options);
	}


	/**
	 * Returns the XML content that is used to populate backoffice forms FOR TRANSLATION USE.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document to serialize in XML.
	 * @param boolean $localizedOnly indicates whether to retrieve localized components only or all components.
	 * @param array $allowedComponentNames array of allowed component names (null if all components are allowed).
	 * @param array $disabledComponentNames array of disabled component names (null if all components are enabled).
	 * @param array $suggestedComponents associative array of suggested values for component names.
	 * @return string XML
	 */
	public static function toXmlForTranslation($document, $localizedOnly = false, $allowedComponentNames = null, $disabledComponentNames = null, $suggestedComponents = null)
	{
	    $model = $document->getPersistentModel();

   	    $componentNames = $model->getPropertiesNames();

	    if ($localizedOnly)
	    {
	        if (is_null($allowedComponentNames))
    	    {
    	        $allowedComponentNames = array();
    	    }

    	    foreach ($componentNames as $componentName)
    		{
    			$property = $model->getProperty($componentName);

    			if ($property->isLocalized())
    			{
    			    $allowedComponentNames[] = $componentName;
    			}
    		}

    		$allowedComponentNames = array_unique($allowedComponentNames);
	    }

	    if (is_null($disabledComponentNames))
	    {
	        $disabledComponentNames = array();
	    }

	    foreach ($componentNames as $componentName)
		{
			$property = $model->getProperty($componentName);

			if (!$property->isLocalized())
			{
			    $disabledComponentNames[] = $componentName;
			}
		}

		$disabledComponentNames = array_unique($disabledComponentNames);

	    $options = array(
	        self::ALLOWED_ACCESSOR => $allowedComponentNames,
	        self::DISABLED_ACCESSOR => $disabledComponentNames,
	        self::SUGGESTED_ACCESSOR => $suggestedComponents,
	        self::TRANSLATION_ACCESSOR => true
	    );

	    return self::_toXml($document, 1, null, null, $options);
	}


	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param integer $level
	 * @param string $lang
	 * @param integer $forceId
	 * @param array $options array of process options.
	 * @return string
	 */
	private static function _toXml($document, $level = 1, $lang = null, $forceId = null, $options)
	{
	    if ($level < 0)
		{
			return '';
		}

		if (isset($options[self::ALLOWED_ACCESSOR]) && !is_null($options[self::ALLOWED_ACCESSOR]))
		{
		    $allowedComponentNames = $options[self::ALLOWED_ACCESSOR];
		}
		else
		{
		    $allowedComponentNames = null;
		}

		if (isset($options[self::DISABLED_ACCESSOR]) && !is_null($options[self::DISABLED_ACCESSOR]))
		{
		    $disabledComponentNames = $options[self::DISABLED_ACCESSOR];
		}
		else
		{
		    $disabledComponentNames = null;
		}

		if (isset($options[self::SUGGESTED_ACCESSOR]) && !is_null($options[self::SUGGESTED_ACCESSOR]))
		{
		    $suggestedComponents = $options[self::SUGGESTED_ACCESSOR];
		}
		else
		{
		    $suggestedComponents = null;
		}

		if (isset($options[self::TRANSLATION_ACCESSOR]) && $options[self::TRANSLATION_ACCESSOR])
		{
		    $supportedLanguages = array(RequestContext::getInstance()->getLang());

		    // $supportedLanguages = RequestContext::getInstance()->getSupportedLanguages();

		    $forTranslation = true;
		}
		else
		{
		    $forTranslation = false;
		}

		$model = $document->getPersistentModel();

		$componentNames = $model->getPropertiesNames();

		$xml = array('<document>');

		if (!is_numeric($forceId) )
		{
			$forceId = $document->getId();
		}

		if (!$forTranslation && (is_null($allowedComponentNames) || in_array('id', $allowedComponentNames)))
		{
		    $xml[] = '<component name="id">' . $forceId . '</component>';
		}

		if (!$forTranslation && (is_null($allowedComponentNames) || in_array('lang', $allowedComponentNames)))
		{
		    $xml[] = '<component name="lang">' . $document->getLang() . '</component>';
		}

		 $xml[] = '<component name="documentversion">' . $document->getDocumentversion() . '</component>';


		if ( is_null($lang) )
		{
			$lang = $document->getLang();
		}

		foreach ($componentNames as $componentName)
		{
			$formProperty = $model->getFormProperty($componentName);
			$property = $model->getProperty($componentName);

			// TODO intbonjf 2007-04-12:
			// Label is always visible, since it may be displayed in an element picker.
			// THIS IS A WORKAROUND: may be the 'hidden' information is not suffisant...
			if ((!$formProperty->isHidden() || $componentName == 'label')
			&& (is_null($allowedComponentNames) || in_array($componentName, $allowedComponentNames)
			|| (!is_null($suggestedComponents) && isset($suggestedComponents[$componentName])) ))
			{
			    if ($forTranslation && $property->isLocalized()
			    && (is_null($disabledComponentNames) || !in_array($componentName, $disabledComponentNames)))
			    {
			        foreach ($supportedLanguages as $language)
			        {
			            if ($document->isLangAvailable($language))
                        {
                            $value = $document->{'get'.ucfirst($componentName).'ForLang'}($language);

                            if ($value)
                            {
                                $xml[] = '<component name="' . $componentName . '" suggested="true" label="' . f_Locale::translateUI('&modules.uixul.bo.languages.' . ucfirst($language) . 'Label;') . '">';
                                $xml[] = '<![CDATA[' . $document->{'get'.ucfirst($componentName).'ForLang'}($language). ']]>';
                    		    $xml[] = '</component>';
                            }
                        }
			        }
			    }
			    else if (is_null($allowedComponentNames) || in_array($componentName, $allowedComponentNames))
			    {
			        if (!is_null($disabledComponentNames) && in_array($componentName, $disabledComponentNames))
            		{
            		    $xml[] = '<component name="' . $componentName . '" disabled="true">';
            		}
            		else
            		{
            		    $xml[] = '<component name="' . $componentName . '">';
            		}

            		if ($property->isDocument())
    				{
    					if ($property->isArray())
    					{
    						$components = $document->{'get'.ucfirst($componentName).'Array'}();

    						foreach ($components as $component)
    						{
    							$xml[] = self::_toXml($component, $level-1, $lang, null, $options);
    						}
    					}
    					else
    					{
    						$component = $document->{'get'.ucfirst($componentName)}();

    						if ($component instanceof f_persistentdocument_PersistentDocument)
    						{
    							$xml[] = self::_toXml($component, $level-1, $lang, null, $options);
    						}
    					}
    				}
    				else
    				{
    					$value = $document->{"get".ucfirst($componentName)}();
    					if (is_bool($value))
    					{
    						$value = f_util_Convert::toString($value);
    					}
    					$xml[] = '<![CDATA['.$value.']]>';
    				}

    				$xml[] = '</component>';
			    }

			    if (!is_null($suggestedComponents) && isset($suggestedComponents[$componentName]))
    		    {
    		        $first = true;

    		        if (!is_array($suggestedComponents[$componentName]))
    		        {
    		            $suggestedComponents[$componentName] = array($suggestedComponents[$componentName]);
    		        }

        		    foreach ($suggestedComponents[$componentName] as $suggestion)
        		    {
        		        if ($first)
        		        {
        		            $first = false;
        		            $xml[] = '<component name="' . $componentName . '" suggested="true" label="' . f_Locale::translateUI('&modules.uixul.bo.general.suggestion.OtherLabel;') .'">';
        		        }
        		        else
        		        {
        		            $xml[] = '<component name="' . $componentName . '" suggested="true">';
        		        }
        		        $xml[] = '<![CDATA[' . $suggestion . ']]>';
        		        $xml[] = '</component>';
        		    }
    		    }
			}
		}

		$xml[] = '</document>';

		return join(K::CRLF, $xml);
	}
	
	/**
	 * Returns the XML content that is used to populate backoffice forms.
	 * @param f_persistentdocument_PersistentDocument $document The document to serialize in XML.
	 * @return string XML
	 */
	public static function toXmlForm($document)
	{
	    $model = $document->getPersistentModel();
	    $allowedProperties = array('id', 'lang', 'documentversion');
	    foreach ($model->getFormPropertiesInfos() as $formProperty) 
	    {
	    	if (!$formProperty->isHidden() && !in_array($formProperty->getName(), $allowedProperties))
	    	{
	    	    $allowedProperties[] = $formProperty->getName();
	    	}
	    }
	    
	    if ($model->isLocalized() && $document->getLang() != RequestContext::getInstance()->getLang())
	    {
	       $disabledProperties = self::getNoneLocalizedProperties($model);
	    }
	    else
	    {
	       $disabledProperties = array(); 
	    }
	    
	    $suggestedPropertiesValue = array();
	   
	    $xmlDocument  = new DOMDocument('1.0', 'utf-8');
	    self::xmlExport($xmlDocument, null, $document, $allowedProperties, $disabledProperties, $suggestedPropertiesValue);
	    return $xmlDocument->saveXML($xmlDocument->documentElement);
	}
	
	/**
	 * Returns the XML content that is used to populate backoffice forms.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document to serialize in XML.
	 * @return string XML
	 */
	public static function toXmlFormForTranslation($document)
	{
	    $model = $document->getPersistentModel();
	    $allowedProperties = array('documentversion');
	    foreach ($model->getFormPropertiesInfos() as $formProperty) 
	    {
	    	if (!$formProperty->isHidden() && !in_array($formProperty->getName(), $allowedProperties))
	    	{
	    	    $allowedProperties[] = $formProperty->getName();
	    	}
	    }
	    
	    if ($model->isLocalized())
	    {
	       $disabledProperties = self::getNoneLocalizedProperties($model);
	    }
	    else
	    {
	       $disabledProperties = array(); 
	    }
	    
	    $suggestedPropertiesValue = array();	    
	    if ($document->isContextLangAvailable())
	    {
	        $label = f_Locale::translateUI('&modules.uixul.bo.languages.' .RequestContext::getInstance()->getLang() . 'Label;');
	        foreach ($model->getPropertiesInfos() as $name => $infos)
		    {
			    if ($infos->isLocalized())
			    {
			        if ($infos->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN)
			        {
			            $value = $document->{'get'.ucfirst($name)}() ? 'true' : 'false';
			        } else if ($infos->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME)
                    {
                        $value = $document->{"getUI".ucfirst($name)}();	
                    } else 
                    {
                        $value = $document->{'get'.ucfirst($name)}();		        
                    }
                    
			        if ($value)
			        {
			            $suggestedPropertiesValue[$name] = array($label, $value);
			        }
			    }
		    }	        
	    }
 
	    $xmlDocument  = new DOMDocument('1.0', 'utf-8');
	    self::xmlExport($xmlDocument, null, $document, $allowedProperties, $disabledProperties, $suggestedPropertiesValue);
	    return $xmlDocument->saveXML($xmlDocument->documentElement);
	}
	
	
		/**
	 * Returns the XML content that is used to populate backoffice forms.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document to serialize in XML.
	 * @param array $disabledProperties array of disabled component names
	 * @param array $suggestedPropertiesValue associative array of suggested values for component names.
	 * @return string XML
	 */
	public static function toXmlCustomForm($document, $allowedProperties = array(), $disabledProperties = array(), $suggestedPropertiesValue = array())
	{
	    if (!in_array('documentversion', $allowedProperties))
        {
            $allowedProperties[] = 'documentversion';
        }
        
        foreach ($disabledProperties as $disabledProperty) 
        {
        	if (!in_array($disabledProperty, $allowedProperties))
        	{
        	    $allowedProperties[] = $disabledProperty;
        	}
        }
    	foreach ($suggestedPropertiesValue as $suggestedProperty => $data) 
        {
        	if (!in_array($suggestedProperty, $allowedProperties))
        	{
        	    $allowedProperties[] = $suggestedProperty;
        	}
        }	          
        
	    $xmlDocument  = new DOMDocument('1.0', 'utf-8');
	    self::xmlExport($xmlDocument, null, $document, $allowedProperties, $disabledProperties, $suggestedPropertiesValue);
	    return $xmlDocument->saveXML($xmlDocument->documentElement);
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
	 * @param DOMDocument $xmlDocument
	 * @param DOMElement $parentElement
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private static function xmlExport($xmlDocument, $parentElement, $document, $allowedProperties = array(), $disabledProperties  = array(), $suggestedPropertiesValue = array())
	{
	    $xmlElement = $xmlDocument->createElement('document');
	    if ($parentElement === null)
	    {
	        $xmlDocument->appendChild($xmlElement);
	    } else 
	    {
	        $parentElement->appendChild($xmlElement);
	    }	    
	    foreach ($document->getPersistentModel()->getPropertiesInfos() as $name => $infos) 
	    {
	    	if (!in_array($name, $allowedProperties))
	    	{
	    	    continue;
	    	}
	    	self::xmlExportProperty($xmlDocument, $xmlElement, $document, $infos, 
	    	    in_array($name, $disabledProperties), 
	    	    isset($suggestedPropertiesValue[$name]) ? $suggestedPropertiesValue[$name] : null, $parentElement !== null);
	    }	    
	}
	
	/**
	 * @param DOMDocument $xmlDocument
	 * @param DOMElement $parentElement
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param PropertyInfo $propertyInfo
	 * @param Boolean $disabled
	 * @param array $suggested
	 * @param Boolean $recurcive
	 */
	private static function xmlExportProperty($xmlDocument, $parentElement, $document, $propertyInfo, $disabled, $suggeste = null, $isSubProperty = false)
	{
	    $xmlElement = $xmlDocument->createElement('component');
	    $parentElement->appendChild($xmlElement);
	    $xmlElement->setAttribute('name', $propertyInfo->getName());
	    if ($disabled)
	    {
	        $xmlElement->setAttribute('disabled', 'true');
	    }
	    if ($suggeste !== null)
	    {
	        $xmlElement->setAttribute('suggested', 'true');
            if (is_array($suggeste))
            {
    	        $xmlElement->setAttribute('label', $suggeste[0]);
    	        $xmlElement->appendChild($xmlDocument->createCDATASection($suggeste[1])); 
            }
	        else
	        {
    	        $xmlElement->setAttribute('label', f_Locale::translateUI('&modules.uixul.bo.general.suggestion.OtherLabel;'));
    	        $xmlElement->appendChild($xmlDocument->createCDATASection($suggeste)); 
	        } 	        
	    }
	    else
	    {
            if ($propertyInfo->isDocument())
            {
                if ($propertyInfo->isArray())
                {
                    $documents = $document->{'get'.ucfirst($propertyInfo->getName()).'Array'}();
                    foreach ($documents as $subdoc)
					{
						self::xmlExport($xmlDocument, $xmlElement, $subdoc, array('id', 'label', 'lang', 'documentversion'));
					}                   
                }
                else
                {
                    $subdoc = $document->{'get'.ucfirst($propertyInfo->getName())}();
                    if ($subdoc instanceof f_persistentdocument_PersistentDocument)
					{
						self::xmlExport($xmlDocument, $xmlElement, $subdoc, array('id', 'label'));
					}
                }
            }
            else if ($propertyInfo->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN)
            {
                $value = $document->{"get".ucfirst($propertyInfo->getName())}();
            	$xmlElement->appendChild($xmlDocument->createTextNode($value ? 'true' : 'false'));
            } 
            else if ($propertyInfo->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME)
            {
                $value = $document->{"getUI".ucfirst($propertyInfo->getName())}();
            	$xmlElement->appendChild($xmlDocument->createTextNode($value));                
            }
	        else if ($propertyInfo->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE)
            {
                $value = $document->{"get".ucfirst($propertyInfo->getName())}();
                $txtValue = f_util_Convert::toUIDouble($value);
            	$xmlElement->appendChild($xmlDocument->createTextNode($txtValue));                
            }
            else
            {
            	if ($isSubProperty && "label" == $propertyInfo->getName())
            	{
            		$value = $document->getTreeNodeLabel();
            	}
            	else
            	{
                	$value = $document->{"get".ucfirst($propertyInfo->getName())}();
            	}
            	$xmlElement->appendChild($xmlDocument->createCDATASection($value));
            }  
	    }
	}
	/**
	 * Check if type property is document
	 * @param String $type
	 * @return Boolean
	 */
	public static function isDocumentProperty($type)
	{
		return (strpos($type, 'modules_') === 0);
	}

	/**
	 * Check if property type is a long field
	 * @param String $type
	 * @return Boolean
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
	 * @param String $lang
	 */
	public static function getLabelForDisplay($document, $lang)
	{
    	return $document->isLangAvailable($lang) ? $document->getLabel() : $document->getVoLabel();
	}

}
