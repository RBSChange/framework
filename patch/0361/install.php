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
		
		foreach (generic_folderService::getInstance()->createQuery()->add(Restrictions::eq('model', 'modules_order/waitingresponseorderfolder'))->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.order.document.waitingresponseorderfolder.document-name');
			$folder->save();
		}
		
		foreach (generic_folderService::getInstance()->createQuery()->add(Restrictions::eq('model', 'modules_catalog/noshelfproductfolder'))->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.catalog.document.noshelfproductfolder.document-name');
			$folder->save();
		}
		
		foreach (generic_folderService::getInstance()->createQuery()->add(Restrictions::eq('model', 'modules_catalog/shopfolder'))->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.catalog.document.shopfolder.document-name');
			$folder->save();
		}
		
		foreach (generic_folderService::getInstance()->createQuery()->add(Restrictions::eq('model', 'modules_customer/tarifcustomergroupfolder'))->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.customer.document.tarifcustomergroupfolder.document-name');
			$folder->save();
		}
		
		foreach (generic_folderService::getInstance()->createQuery()->add(Restrictions::eq('model', 'modules_customer/voucherfolder'))->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.customer.document.voucherfolder.document-name');
			$folder->save();
		}
		
		foreach (generic_folderService::getInstance()->createQuery()->add(Restrictions::eq('model', 'modules_form/recipientGroupFolder'))->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.form.document.recipientgroupfolder.document-name');
			$folder->save();
		}
		
		foreach (generic_folderService::getInstance()->createQuery()->add(Restrictions::eq('model', 'modules_productreturns/paymentfolder'))->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.productreturns.document.paymentfolder.label-content');
			$folder->save();
		}
		
		foreach (generic_folderService::getInstance()->createQuery()->add(Restrictions::eq('model', 'modules_productreturns/reasonfolder'))->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.productreturns.document.reasonfolder.label-content');
			$folder->save();
		}
	
	}
}