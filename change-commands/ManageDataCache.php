<?php
class commands_ManageDataCache extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<enable|disable>";
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "manage data cache (disable manage)";
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 1;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$subCommand = $params[0];
		switch ($subCommand)
		{
			case "enable":
				$doc = new DOMDocument();
				$path = "config/project.".$this->getProfile().".xml";
				if (!$doc->load($path))
				{
					return $this->quitError("Could not load $path");
				}
				$xpath = new DOMXPath($doc);
				$definesElem = $xpath->query("defines")->item(0);
				$elems = $xpath->query("define[@name = 'DISABLE_DATACACHE']", $definesElem);
				
				$enabled = false;
				if ($elems->length > 0)
				{
					foreach ($elems as $elem)
					{
						if ($elem->textContent != "false")
						{
							$definesElem->removeChild($elem);
						}
						else
						{
							$enabled = true;
						}
					}
				}
				if (!$enabled)
				{
					$newDefine = $doc->createElement("define");
					$newDefine->setAttribute("name", "DISABLE_DATACACHE");
					$newDefine->appendChild($doc->createTextNode("false"));
					$definesElem->appendChild($newDefine);
					if (!file_put_contents($path, $doc->saveXML()))
					{
						return $this->quitError("Could not update config");
					}
				}
				$this->executeCommand("compile-config");
				
				$this->loadFramework();
				f_DataCacheService::getInstance()->clearCommand();
				
				$this->quitOk("Data cache enabled");
				break;
			case "disable":
				$doc = new DOMDocument();
				$path = "config/project.".$this->getProfile().".xml";
				if (!$doc->load($path))
				{
					return $this->quitError("Could not load $path");
				}
				$xpath = new DOMXPath($doc);
				$definesElem = $xpath->query("defines")->item(0);
				$elems = $xpath->query("define[@name = 'DISABLE_DATACACHE']", $definesElem);
				
				$disabled = false;
				if ($elems->length > 0)
				{
					foreach ($elems as $elem)
					{
						if ($elem->textContent != "true")
						{
							$definesElem->removeChild($elem);
						}
						else
						{
							$disabled = true;
						}
					}
				}
				if (!$disabled)
				{
					$newDefine = $doc->createElement("define");
					$newDefine->setAttribute("name", "DISABLE_DATACACHE");
					$newDefine->appendChild($doc->createTextNode("true"));
					$definesElem->appendChild($newDefine);
					if (!file_put_contents($path, $doc->saveXML()))
					{
						return $this->quitError("Could not update config");
					}
				}
				$this->executeCommand("compile-config");
				$this->quitOk("Data cache disabled");
				break;
		}
	}
}