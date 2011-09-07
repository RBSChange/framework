<?php
class commands_CompileDocuments extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}
	
	function getAlias()
	{
		return "cd";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile documents";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile documents ==");
		
		$this->loadFramework();
		// Get the list of model and generate all persistent object.
		$models = generator_PersistentModel::loadModels();
		
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
		
		$docInjections = array();
		
		// For the list of models generate persistent.
		foreach ($models as $model)
		{
			
			// Get a document Generator.
			$documentGenerator = new builder_DocumentGenerator($model->getModuleName(), $model->getDocumentName(), false);

			// Generate persistent document file.
			$documentGenerator->generatePersistentDocumentFile();
			
			// Generate SQL document file.
			$documentGenerator->generateSqlDocumentFile(false);

			$this->message('Model modules_' . $model->getModuleName() . '/' . $model->getDocumentName() . ' generated.');
		}
		
		$this->message("== Compile Models by Module ==");
		generator_PersistentModel::buildModelsByModuleNameCache();
		
		$this->message("== Compile Models children ==");
		generator_PersistentModel::buildModelsChildrenCache();
		
		$this->message("== Compile Publication Infos ==");
		generator_PersistentModel::buildPublishListenerInfos();
		
		$this->message("== Compile Document Property ==");
		generator_PersistentModel::buildDocumentPropertyInfos();
		
		$this->message("== Compile Indexable Document ==");
		generator_PersistentModel::buildIndexableDocumentInfos();
		
		$this->message("== Compile Allowed Document ==");
		generator_PersistentModel::buildAllowedDocumentInfos();
		
		$this->message("== Generate final document class and bo style ==");
		foreach ($models as $model)
		{
			try
			{
				// Get a document Generator.
				$documentGenerator = new builder_DocumentGenerator($model->getModuleName(), $model->getDocumentName(), false);
				
				$documentGenerator->generateFinalPersistentDocumentFile();
				
				builder_BackofficeStyleUpdater::updateCssByDocument($model);

			}
			catch (Exception $e)
			{
				$this->errorMessage("Update of ".$model->getModuleName()."/style/backoffice.css failed: ".$e->getMessage());
				$this->debugMessage($e->getTraceAsString());
			}
		}
		
		$mustCompileConfig = false;
		$currentDocInjections = Framework::getConfigurationValue("injection/document", array());
		if (count($currentDocInjections) != count($docInjections))
		{
			$mustCompileConfig = true;
		}
		else
		{
			foreach ($docInjections as $replaced => $replacer)
			{
				if (!isset($currentDocInjections[$replaced]) || $currentDocInjections[$replaced] != $replacer)
				{
					$mustCompileConfig = true;
					break;
				}
			}
		}
		
		if ($mustCompileConfig)
		{
			$this->executeCommand("compile-config");
		}
		
		$this->quitOk("Documents compiled");
	}
}