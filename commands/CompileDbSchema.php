<?php
class commands_CompileDbSchema extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "";
	}
	
	function getAlias()
	{
		return "cds";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "compile DB schema: langs, relation ids and trees";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile DB schema ==");
		
		$this->loadFramework();
		
		$persistentProvider = \Change\Db\Provider::getInstance();
		$sm = $persistentProvider->getSchemaManager();
		
		$this->message('=== Update table f_document with supported languages ===');

		//Generate localized label in f_document
		foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang)
		{
			if ($sm->addLang($lang))
			{
				$this->message("Lang $lang added");
			}
			else
			{
				$this->message("Lang $lang already present");
			}
		}
		$this->okMessage("f_document table ok");

		$this->message('=== Compile document relation name ===');

		try
		{
			RelationService::getInstance()->compile();
			$this->okMessage("Relation name compiled");
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			$this->errorMessage('Unable to compile \'relation.php\'. ' . $e->getMessage());
			$this->errorMessage('See logs for more details.');
		}

		$this->message('=== Generate Tree tables ===');
		$ms = ModuleService::getInstance();
		foreach ($ms->getModulesObj() as $module)
		{
			/* @var $module c_Module */
			if ($module->isVisible())
			{
				$ms->getRootFolderId($module->getName());
			}
		}
		$this->okMessage("Tree tables generated");

		$this->quitOk("DB Schema compiled");
	}
}