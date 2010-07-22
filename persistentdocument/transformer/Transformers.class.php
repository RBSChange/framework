<?php
/**
 * @package framework.persistentdocument.transformer
 */
class Transformers
{
    /**
     * @param f_persistentdocument_PersistentDocumentModel $srcModel
     * @param f_persistentdocument_PersistentDocumentModel $destModel
     * @return f_persistentdocument_transformer_Transformer
     */
    public static final function getInstance($srcModel, $destModel)
    {
        $srcClass = ucfirst($srcModel->getModuleName()).ucfirst($srcModel->getDocumentName());
        $destClass = ucfirst($destModel->getModuleName()).ucfirst($destModel->getDocumentName());
               
        $className = 'transformer_'.$srcClass.'To'.$destClass;
        if (f_util_ClassUtils::classExists($className))
        {
            $transformer =  new $className();
        } 
        elseif ($srcModel->getTableName() == $destModel->getTableName())
        {
            $transformer = new f_persistentdocument_transformer_DefaultTransformer($srcModel, $destModel);   
        }
        else 
        {
            throw new IllegalOperationException('Unable to transform ' . $srcModel->getName() . ' to ' . $destModel->getName(), 'transformer_error');
        }
 
        return $transformer;
    }
    
    
    /**
     * @param f_persistentdocument_PersistentDocumentModel $srcModel
     * @param f_persistentdocument_PersistentDocumentModel $destModel
     * @return array<String>
     */
    public static function buildCommonPropertiesArray($srcModel, $destModel)
    {
        $propertiesName = array();
        foreach ($destModel->getPropertiesInfos() as $propertyInfo) 
        {
            $propertyName = $propertyInfo->getName();
        	if($srcModel->getProperty($propertyName))
        	{
        	    $propertiesName[] = $propertyName;
        	}
        }
        return $propertiesName;
    }
    
    /**
     * @param f_persistentdocument_PersistentDocumentModel $srcModel
     * @param f_persistentdocument_PersistentDocumentModel $destModel
     * @return array<String>
     */
    public static function buildCommonI18NPropertiesArray($srcModel, $destModel)
    {
        $propertiesName = array();
        if ($destModel->isLocalized())
        {
            foreach ($destModel->getPropertiesInfos() as $propertyInfo) 
            {
                $propertyName = $propertyInfo->getName();
            	if($propertyInfo->isLocalized() && $srcModel->getProperty($propertyName))
            	{
            	    $propertiesName[] = $propertyName;
            	}
            }
        }
        return $propertiesName;
    }
      
    /**
     * @param f_persistentdocument_PersistentDocument $sourceDocument
     * @param f_persistentdocument_PersistentDocument $destDocument
     * @param array<String> $propertiesName
     * @param array<String> $i18NpropertiesName
     */
    public static function copyProperties($sourceDocument, $destDocument, $propertiesName, $i18NpropertiesName)
    {
        $rc = RequestContext::getInstance();          
        $vo = $sourceDocument->getLang();	
		
		//Update VO
		try
		{
			$rc->beginI18nWork($vo);
			$sourceDocument->copyPropertiesListTo($destDocument, $propertiesName, true);
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}
		
		if (f_util_ArrayUtils::isEmpty($i18NpropertiesName))
		{
		    return;
		}

		//Update localized
		foreach ($rc->getSupportedLanguages() as $lang)
		{
			if ($lang == $vo) { continue;}
			try
			{
				$rc->beginI18nWork($lang);
				if ($sourceDocument->isContextLangAvailable())
				{
					$sourceDocument->copyPropertiesListTo($destDocument, $i18NpropertiesName, false);
				}
				$rc->endI18nWork();
			}
			catch (Exception $e)
			{
				$rc->endI18nWork($e);
			}
		}
    }
}