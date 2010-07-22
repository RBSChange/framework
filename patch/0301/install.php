<?php
class framework_patch_0301 extends patch_BasePatch
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
		$stmt = $this->executeSQLSelect("select * from f_tags");
		$documents = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
		{
			//echo $row["id"]." => ".$row["tag"]."\n";
			$id = $row["id"];
			$document = DocumentHelper::getDocumentInstance($id);
			if (!isset($documents[$id]))
			{
				// if re-apply, be sure you do not duplicate the value
				$document->setMeta("f_tags", null);
			}
			$document->addMetaValue("f_tags", $row["tag"]);
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
		return '0301';
	}

}