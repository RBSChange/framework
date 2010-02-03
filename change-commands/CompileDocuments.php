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
		// TODO: remove exec
		exec('rm -f ' . $sqlpath);

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
		generator_PersistentModel::buildModelsByModuleNameCache();
		
		// For the list of models generate backoffice styles.
		// FIXME: this must be done after all model files are generated.
		foreach ($models as $model)
		{
			try
			{
				$documentGenerator->addStyleInBackofficeFile();
			}
			catch (Exception $e)
			{
				$this->errorMessage("Update of ".$model->getModuleName()."/style/backoffice.css failed: ".$e->getMessage());
				$this->debugMessage($e->getTraceAsString());
			}
		}
		$this->okMessage("Backoffice styles generated.");
		$this->quitOk("Documents compiled");
	}
}