<?php
/**
 * framework_patch_0361
 * @package modules.framework
 */
class framework_patch_0361 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		foreach (generic_RootfolderService::getInstance()->createQuery()->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_rootfolder */
			$folder->setLabel('m.generic.document.rootfolder.document-name');
			$folder->save();
		}
		
		$ls = LocaleService::getInstance();
		foreach (generic_SystemfolderService::getInstance()->createQuery()->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_systemfolder */
			$label = $ls->cleanOldKey($folder->getLabel());
			if ($label !== false)
			{
				$folder->setLabel($label);
				$folder->save();
			}
		}
	}
}