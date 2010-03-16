<?php
/**
 * framework_patch_0310
 * @package modules.framework
 */
class framework_patch_0310 extends patch_BasePatch
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
		echo "\n";
		$computedDeps = unserialize(file_get_contents(".change/autoload/.computedChangeComponents.ser"));
		foreach (glob("modules/*/persistentdocument/*.xml") as $docXML)
		{
			preg_match('#.*modules/([^/]*)/persistentdocument/(.*)\.xml$#', $docXML, $matches);
			$moduleName = $matches[1];
			$docName = $matches[2];
			
			$doc = f_util_DOMUtils::fromPath($docXML);
			$doc->registerNamespace("c", "http://www.rbs.fr/schema/change-document/1.0");
			if ($doc->exists("c:properties/c:add[@localized = 'true' and @name != 'publicationstatus']"))
			{
				$publicationStatusLocalized = false;
				$currentDoc = $doc;
				while (!$publicationStatusLocalized && $currentDoc !== null)
				{
					$publicationStatusLocalized = $currentDoc->exists("c:properties/c:add[@localized = 'true' and @name = 'publicationstatus']");
					if ($currentDoc->documentElement->hasAttribute("extend"))
					{
						if (preg_match("#^modules_(.*)/(.*)$#", $currentDoc->documentElement->getAttribute("extend"), $matches))
						{
							$parentModule = $matches[1];
							$parentDocName = $matches[2];
							$currentDoc = f_util_DOMUtils::fromPath("modules/".$parentModule."/persistentdocument/".$parentDocName.".xml");
							$currentDoc->registerNamespace("c", "http://www.rbs.fr/schema/change-document/1.0");
						}
						else
						{
							throw new Exception("invalid extend attribute ".$currentDoc->documentElement->getAttribute("extend"));
						}
					}
					else
					{
						$currentDoc = null;
					}
				}

				$model = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $docName);
				if (!$publicationStatusLocalized)
				{
					if (isset($computedDeps["module"][$moduleName]))
					{
						// Ignore standard modules: already examined
					}
					else
					{
						if ($model->useCorrection())
						{
							echo "Model $moduleName/$docName is localized, use correction and did not declared publicationstatus property localized: you can encounter problems. Please check.\n";
						}
						elseif ($model->publishOnDayChange())
						{
							echo "Model $moduleName/$docName is localized, use 'publish on day change' and did not declared publicationstatus property localized: you can encounter problems. Please check.\n";
						}
					}
				}

				$tableName = $model->getTableName();
				$i18nTableName = $model->getTableName()."_i18n";
				try
				{
					$this->executeSQLQuery("alter table $i18nTableName add `document_publicationstatus_i18n` ENUM('DRAFT', 'CORRECTION', 'ACTIVE', 'PUBLICATED', 'DEACTIVATED', 'FILED', 'DEPRECATED', 'TRASH', 'WORKFLOW') NULL DEFAULT NULL");
					$this->executeSQLQuery("update $i18nTableName set document_publicationstatus_i18n = (select document_publicationstatus from $tableName where  $i18nTableName.document_id = $tableName.document_id)");
					echo "publicationstatus localized field added to $tableName\n";
				}
				catch (BaseException $e)
				{
					if ($e->getAttribute("errorcode") != "1060")
					{
						throw $e;
					}
				}
			}
		}
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'framework';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0310';
	}
}