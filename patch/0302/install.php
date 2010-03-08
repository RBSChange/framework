<?php
class framework_patch_0302 extends patch_BasePatch
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
		$this->executeSQLQuery("DELETE FROM `m_website_urlrewriting_rules` WHERE `document_id` not in (SELECT `document_id` from f_document)");
		
		$stmt = $this->executeSQLSelect("select * from m_website_urlrewriting_rules");
		$documents = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
		{
			$id = $row["document_id"];
			$lang = $row["document_lang"];
			$document = DocumentHelper::getDocumentInstance($id);
			$document->setMeta("urlRewritingInfo_".$lang, $row["document_moved"].$row["document_url"]);
			$documents[$id] = $document;
		}
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		foreach ($documents as $document)
		{
			$document->applyMetas();
			$pp->updateDocument($document);
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
		return '0302';
	}

}