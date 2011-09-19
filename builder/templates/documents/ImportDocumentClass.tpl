<?php
/**
 * <{$importClassName}>
 * @package modules.<{$moduleName}>.persistentdocument.import
 */
class <{$importClassName}> extends import_ScriptDocumentElement
{
    /**
     * @return <{$className}>
     */
    protected function initPersistentDocument()
    {
    	return <{$serviceClassName}>::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return <{$className}>model
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_<{$moduleName}>/<{$documentName}>');
	}
}