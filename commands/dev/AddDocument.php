<?php
class commands_AddDocument extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "<moduleName> <name>";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "add a document to the project.";
	}

	/**
	 * @param integer $completeParamCount the parameters that are already complete in the command line
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return string[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach ($this->getBootStrap()->getProjectDependencies() as $package)
			{
				/* @var $package c_Package */
				if ($package->isModule())
				{
					if (count($this->getNewDocument($package->getPath() .  '/persistentdocument')))
					{
						$components[] = $package->getName();
					}
				}
			}
			return $components;
		}
		if ($completeParamCount == 1)
		{
			$package = $this->getPackageByName($params[0]);
			$docNames = $this->getNewDocument($package->getPath() .  '/persistentdocument');
			return $docNames;
		}
		return null;
	}
	
	/**
	 * @param string $path
	 * @return array
	 */
	private function getNewDocument($path)
	{
		$result = array();
		if (is_dir($path))
		{
			foreach (scandir($path) as $fileName) 
			{
				if (substr($fileName, -4) === '.xml')
				{
					$docName = substr($fileName,0, -4);
					if (!file_exists(f_util_FileUtils::buildPath($path, $docName . '.class.php')))
					{
						$result[] = $docName;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) == 2)
		{
			$package = $this->getPackageByName($params[0]);
			if ($package->isModule() && $package->isInProject())
			{
				$docs = $this->getNewDocument($package->getPath() .  '/persistentdocument');
				if (in_array($params[1], $docs))
				{
					return true;
				}
				$this->errorMessage('Invalid document name: ' . $params[1]);
			}
			else
			{
				$this->errorMessage('Invalid module name: ' . $params[0]);
			}
		}
		return false;
	}
	
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->loadFramework();
		$this->message("== Add document ==");
		
		
		$package = $this->getPackageByName($params[0]);
		$moduleName = $package->getName();
		$documentName = $params[1];
		
		$modelName = 'modules_' .$moduleName .'/' .$documentName;
		
		$xmlPath = f_util_FileUtils::buildPath($package->getPath(), 'persistentdocument', $documentName . '.xml');
		
		$xmlDoc = f_util_DOMUtils::fromPath($xmlPath);
		if ($xmlDoc->documentElement->localName != 'document')
		{
			return $this->quitError($xmlPath . ' is not a valid document');
		}
		
		$extendModelName = null;
		$inject = false;
		if ($xmlDoc->documentElement->hasAttribute('extend'))
		{
			$extendModelName = trim($xmlDoc->documentElement->getAttribute('extend'));
			if ($xmlDoc->documentElement->getAttribute('inject') === 'true')
			{
				$inject = true;
				config_ProjectParser::addProjectConfigurationNamedEntry('injection/document',$extendModelName, $modelName);
			}
		}
		
		$path = builder_DocumentGenerator::generateDocumentService($moduleName, $documentName, $extendModelName, $inject);
		$this->log('Add : ' . $path);
		
		
		$paths = builder_DocumentGenerator::generateFinalPersistentDocumentFile($moduleName, $documentName, $extendModelName, $inject);
		foreach ($paths as $path) 
		{
			$this->log('Add : ' . $path);
		}
		
		$this->executeCommand("compile-config", array('--ignoreListener'));
		
		$this->executeCommand("compile-documents");
		
		if (!$inject)
		{
			$generator = new builder_DocumentGenerator($moduleName, $documentName);
			$baseKey = $generator->generateLocaleFile();
			$this->log('Add i18n baseKey: ' . $baseKey);
			
			$path = builder_DocumentGenerator::updateRights($moduleName, $documentName, $extendModelName, $inject);
			if ($path)
			{
				$this->log('Add : ' . $path);
			}	
		}
		
		$this->executeCommand("generate-database", array($moduleName));
		
		$this->executeCommand("compile-db-schema");

		$this->executeCommand("compile-locales", array($moduleName));
		
		$this->executeCommand("compile-tags");
			
		return $this->quitOk("Document $documentName added in module $moduleName.");
	}
}
