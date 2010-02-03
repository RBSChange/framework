<?php
/**
 * framework_patch_0308
 * @package modules.framework
 */
class framework_patch_0308 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		
		$this->executeSQLQuery("CREATE TABLE IF NOT EXISTS `f_url_rules` (
  `rule_id` int(11) NOT NULL auto_increment,
  `document_id` int(11) NOT NULL,
  `document_lang` varchar(2) NOT NULL DEFAULT 'fr',
  `website_id` int(11) NOT NULL DEFAULT '0',
  `from_url` varchar(255) NOT NULL,
  `to_url` varchar(255) DEFAULT NULL,
  `redirect_type` int(11) NOT NULL DEFAULT '200',
  PRIMARY KEY  (`rule_id`),
  UNIQUE (`website_id`, `from_url`)
) TYPE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin");
		
		$stmt = $this->executeSQLSelect('SELECT document_id, website_id, document_url, document_lang, document_moved FROM m_website_urlrewriting_rules');
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$rc = RequestContext::getInstance();
		$urs = website_UrlRewritingService::getInstance();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		$pp = $tm->getPersistentProvider();
		
		foreach ($result as $row) 
		{
			$lang = $row['document_lang'];
			$documentId = $row['document_id'];
			$url  = $row['document_url'];
			$websiteId  = $row['website_id'];
			if ($websiteId === null) {$websiteId = 0;}
			$moved = $row['document_moved'] == 1;
			
			
			try 
			{
				$tm->beginTransaction();
				try 
				{
					$rc->beginI18nWork($lang);
					$document = DocumentHelper::getDocumentInstance($documentId);
					if (!$document->isContextLangAvailable())
					{
						throw new Exception($documentId . ' not exist in langue ' + $lang);
					}
					
					$urs->beginOnlyUseRulesTemplates();
					$generated = LinkHelper::getDocumentUrl($document, $lang);
					$urs->endOnlyUseRulesTemplates();
				
					$matches = array();
					preg_match('/^https?:\/\/([^\/]*)(\/'.$lang.')?(\/.*)$/', $generated, $matches);
					$currentURL = $matches[3];		

					if ($moved)
					{
						$pp->setUrlRewriting($documentId, $lang, $websiteId, $url, $currentURL, 301);
						$document->getDocumentService()->setUrlRewriting($document, $lang, null);
					}
					else
					{
						$pp->setUrlRewriting($documentId, $lang, $websiteId, $url, null, 200);
						$document->getDocumentService()->setUrlRewriting($document, $lang, $url);
					}
					
					$urs->beginOnlyUseRulesTemplates();
					$generated = LinkHelper::getDocumentUrl($document, $lang);
					$urs->endOnlyUseRulesTemplates();
				
					$rc->endI18nWork();
				}
				catch (Exception $ee)
				{
					$rc->endI18nWork($ee);
				}
				$tm->commit();
			}
			catch (Exception $e)
			{
				$tm->rollBack($e);
				$this->logError($e->getMessage());
			}
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
		return '0308';
	}
}