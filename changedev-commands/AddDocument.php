<?php
class commands_AddDocument extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName> <name>";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "add a document to the project.";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 2;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return $components;
		}
		if ($completeParamCount == 1)
		{
			$moduleName = $params[0];
			$docNames = array();
			foreach (glob("modules/$moduleName/persistentdocument/*.xml") as $docFile)
			{
				$docName = basename($docFile, ".xml");
				if (!file_exists(dirname($docFile)."/".$docName.".class.php"))
				{
					$docNames[] = $docName;	
				}
			}
			return $docNames;
		}
	}

	function getOptions()
	{
		return array("--update");
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add document ==");

		$moduleName = $params[0];
		$documentName = $params[1];
		$update = isset($options["update"]);

		if (!file_exists("modules/$moduleName/persistentdocument/$documentName.xml"))
		{
			return $this->quitError("Document $moduleName/$documentName does not exists.
Please create the document using 'create-document $moduleName $documentName'.");
		}

		$this->loadFramework();

		// Get a document Generator
		$documentGenerator = new builder_DocumentGenerator($moduleName, $documentName);
		$documentGenerator->setAuthor($this->getAuthor());
		
		$documentGenerator->generatePersistentDocumentFile();
		$documentGenerator->generateLocaleFile();

		// Generate document service
		$documentGenerator->generateDocumentService();
		
		// Generate SQL files and import it if it's not an update
		$documentGenerator->generateSqlDocumentFile(!$update);
		
		$documentGenerator->updateRights();

		$this->changecmd("compile-locales", array($moduleName));
		$this->changecmd("compile-tags");
		$this->changecmd("compile-documents");
		$this->changecmd("compile-db-schema");
		$this->changecmd("compile-config");
			
		if (!$update)
		{
			$this->quitOk("Document $documentName added in module $moduleName.");
		}
		else
		{
			$this->warnMessage("The SQL code to build the table may have changed, but it has not been executed. Please check this before going on.");
			$this->quitOk("Document $documentName updated in module $moduleName");
		}
	}
}
