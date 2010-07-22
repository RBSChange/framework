<?php
/**
 * framework_patch_0307
 * @package modules.framework
 */
class framework_patch_0307 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		foreach (ModuleService::getInstance()->getModulesObj() as $cmodule) 
		{
			$this->checkModule($cmodule);
		}
	}
	
	
	/**
	 * @param c_Module $cmodule
	 */
	private function checkModule($cmodule)
	{
		foreach (ModuleService::getInstance()->getDefinedDocumentModels($cmodule->getName()) as $model) 
		{
			$this->checkModel($model);
		}
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	private function checkModel($model)
	{
		if ($model->useCorrection())
		{
			$this->log($model->getName() . ' Use correction');
			$ts = TreeService::getInstance();
			$ts->setTreeNodeCache(false)->setTreeNodeCache(true);
			$this->beginTransaction();
			
			$documents = $model->getDocumentService()->createQuery()
				->add(Restrictions::eq('model', $model->getName()))
				->add(Restrictions::gt('correctionofid', 0))->find();
			
			foreach ($documents as $document) 
			{
				if ($document->getTreeId())
				{
					echo 'Remove ' . $document->__toString() . ' from TreeId ' . $document->getTreeId() . "\n";
					$node = $ts->getInstanceByDocument($document);
					$ts->deleteNode($node);	
				}
			}
			
			$this->commit();
		}
	}
	
	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'framework';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0307';
	}
}