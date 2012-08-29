<?php
class commands_CompileDocuments extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "[--verbose]";
	}
	
	function getAlias()
	{
		return "cd";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "compile documents";
	}
	
	function getOptions()
	{
		return array('--verbose');
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile documents ==");
		
		$this->loadFramework();
		
		
		// Get the list of model and generate all persistent object.
		$models = generator_PersistentModel::loadModels();
		$this->log("== Clean php class ==");
		
		//Cleaning dataobject folders
		$sqlpath = f_util_FileUtils::buildChangeBuildPath('modules', '*', 'dataobject', '*');		
		foreach (glob($sqlpath) as $path) 
		{
			unlink($path);
		}
		
		//Cleaning persistentdocument folders
		$docpath = f_util_FileUtils::buildChangeBuildPath('modules', '*', 'persistentdocument', '*');		
		foreach (glob($docpath) as $path) 
		{
			unlink($path);
		}
		
		$this->log("== Compile php class ==");
		
		// For the list of models generate persistent.
		foreach ($models as $model)
		{
			
			// Get a document Generator.
			$documentGenerator = new builder_DocumentGenerator($model->getModuleName(), $model->getDocumentName());

			// Generate persistent document file.
			$documentGenerator->generatePersistentDocumentFile();
			
			// Generate SQL document file.
			$documentGenerator->generateSqlDocumentFile(false);
			
			if (isset($options['verbose']))
			{
				$this->log('Model modules_' . $model->getModuleName() . '/' . $model->getDocumentName() . ' generated.');
			}
		}
		
		$this->log("== Compile Models by Module ==");
		generator_PersistentModel::buildModelsByModuleNameCache();
		
		$this->log("== Compile Models children ==");
		generator_PersistentModel::buildModelsChildrenCache();
		
		$this->log("== Compile Publication Infos ==");
		generator_PersistentModel::buildPublishListenerInfos();
		
		$this->log("== Compile Document Property ==");
		generator_PersistentModel::buildDocumentPropertyInfos();
		
		$this->log("== Compile Indexable Document ==");
		generator_PersistentModel::buildIndexableDocumentInfos();
		
		$this->log("== Compile Allowed Document ==");
		generator_PersistentModel::buildAllowedDocumentInfos();
		
		
		$this->log("== Compile Icons Document ==");
		generator_PersistentModel::getCssBoDocumentIcon();
		
		$this->quitOk(count($models) . " Documents compiled");
	}
}