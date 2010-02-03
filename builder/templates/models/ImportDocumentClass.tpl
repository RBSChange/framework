<?php
/**
 * <{$model->getImportScriptDocumentClassName()}>
 * @package modules.<{$model->getModuleName()}>.persistentdocument.import
 */
class <{$model->getImportScriptDocumentClassName()}> extends import_ScriptDocumentElement
{
    /**
     * @return <{$model->getDocumentClassName()}>
     */
    protected function initPersistentDocument()
    {
    	return <{$model->getServiceClassName()}>::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('<{$model->getName()}>');
	}
}