<?php
class framework_patch_0304 extends patch_BasePatch
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
		// Remove the following line and implement the patch here.
		parent::execute();
		$this->executeSQLQuery('ALTER TABLE `f_availabletags` DROP `target`, DROP `required`');
		
		foreach (ModuleService::getInstance()->getModules() as $packageName) 
		{
			$tagPath = FileResolver::getInstance()
				->setPackageName($packageName)
				->setDirectory('config')
				->getPath('tags.xml');
			if ($tagPath != null)
			{
				$doc = new DOMDocument();
				$doc->load($tagPath);
				if ($doc->documentElement)
				{
					$tagsList = $doc->getElementsByTagName('tag');
					if ($tagsList->length > 0)
					{
						$this->logError('Fichier tag à migrer :' . $tagPath);	
					}
					else
					{
						if (is_writable($tagPath))
						{
							unlink($tagPath);
						}
						else
						{
							$this->log('Fichier tag à supprimer :' . $tagPath);	
						}
						
					}
				}
				else 
				{
					$this->log('Fichier tag invalid à supprimer :' . $tagPath);	
				}	
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
		return '0304';
	}

}