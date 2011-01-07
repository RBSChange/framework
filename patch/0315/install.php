<?php
/**
 * framework_patch_0315
 * @package modules.framework
 */
class framework_patch_0315 extends patch_BasePatch
{
	/**
	 * Returns true if the patch modify code that is versionned.
	 * If your patch modify code that is versionned AND database structure or content,
	 * you must split it into two different patches.
	 * @return Boolean true if the patch modify code that is versionned.
	 */
	public function isCodePatch()
	{
		return true;
	}
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$doc = new DOMDocument();
		$doc->load(WEBEDIT_HOME."/change.xml");
		foreach ($doc->getElementsByTagName("framework") as $fElem)
		{
			$matches = null;
			if (preg_match('/^(.*)-([0-9]*)$/', $fElem->textContent, $matches))
			{
				$fElem->setAttribute("hotfixes", $matches[2]);
				foreach ($fElem->childNodes as $childNode)
				{
					$fElem->removeChild($childNode);
				}
				$fElem->appendChild($doc->createTextNode($matches[1]));
			}
		}

		foreach ($doc->getElementsByTagName("module") as $mElem)
		{
			$matches = null;
			if (substr($mElem->textContent, 0, 5) == "media")
			{
				$mElem->parentNode->removeChild($mElem);
			}
			else if (preg_match('/^(.*)-([0-9]*)$/', $mElem->textContent, $matches))
			{
				$mElem->setAttribute("hotfixes", $matches[2]);
				foreach ($mElem->childNodes as $childNode)
				{
					$mElem->removeChild($childNode);
				}
				$mElem->appendChild($doc->createTextNode($matches[1]));
			}
		}

		// re-insert media-3.0.4-56
		$mediaElem = $doc->createElement("module");
		$mediaElem->setAttribute("hotfixes", "56");
		$mediaElem->appendChild($doc->createTextNode("media-3.0.4"));
		$doc->getElementsByTagName("modules")->item(0)->appendChild($mediaElem);

		file_put_contents(WEBEDIT_HOME."/change.xml", $doc->saveXML());
		unlink(WEBEDIT_HOME."/.change/autoload/.computedChangeComponents.ser");
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
		return '0315';
	}
}
