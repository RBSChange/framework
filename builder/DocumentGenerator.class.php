<?php
class builder_DocumentGenerator
{
	/**
	 * Document name
	 * @var string
	 */
	private $name = null;

	/**
	 * Module name
	 * @var string
	 */
	private $module = null;

	/**
	 * Doucment author. Is used in header of generated file
	 * @var string
	 */
	private $author = null;

	/**
	 * Current date. This date is write in header of generated file.
	 * @var string
	 */
	private $date = null;

	/**
	 * Path of module where the document must be added
	 * @var string
	 */
	private $pathBaseModule = null;

	/**
	 * Model name. Example:modules_moduleName/DocumentName
	 * @var string
	 */
	private $model = null;

	/**
	 * Model name. Example:modules_moduleName/DocumentName
	 * @var generator_PersistentModel
	 */
	private $modelObject = null;
	
	static function getPropertyTypes()
	{
		$types = array('Boolean', 'Integer', 'Double', 'DateTime', 'String', 'Lob', 'LongString', 'XHTMLFragment', 'Document');
		foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModelNamesByModules() as $moduleName => $modelNames)
		{
			$types = array_merge($types, $modelNames);
		}		
		return $types;
	}

	/**
	 * Constructor of builder_DocumentGenerator
	 */
	public function __construct($module, $name, $checkLink = true)
	{
		$this->name = $name;
		$this->module = $module;
		$this->date = date('r');
		$this->pathBaseModule = AG_MODULE_DIR . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR;
		$this->model = 'modules_' . $module . '/' . $name;
		$this->modelObject = generator_PersistentModel::getModelByName('modules_' . $module . '/' . $name);

		// Test if module directory is a symbolic link
		if ($checkLink && !is_writable(AG_MODULE_DIR . DIRECTORY_SEPARATOR . $module) )
		{
			throw new IOException('Cannot write in module directory.');
		}
	}

	/**
	 * Author setter
	 * @param string $value
	 * @return builder_DocumentGenerator
	 */
	public function setAuthor($value)
	{
		$this->author = $value;
		return $this;
	}

	/**
	 * Name setter
	 * @param string $value
	 * @return builder_DocumentGenerator
	 */
	protected function setName($value)
	{
		$this->name = $value;
		return $this;
	}

	/**
	 * Module setter
	 * @param string $value
	 * @return builder_DocumentGenerator
	 */
	protected function setModule($value)
	{
		$this->module = $value;
		return $this;
	}

	/**
	 * Date setter
	 * @param string $value
	 * @return builder_DocumentGenerator
	 */
	protected function setDate($value)
	{
		$this->date = $value;
		return $this;
	}

	/**
	 * PathBaseModule setter
	 * @param string $value
	 * @return builder_DocumentGenerator
	 */
	protected function setPathBaseModule($value)
	{
		$this->pathBaseModule = $value;
		return $this;
	}

	/**
	 * Model setter
	 * @param string $value
	 * @return builder_DocumentGenerator
	 */
	protected function setModel($value)
	{
		$this->model = $value;
		return $this;
	}

	/**
	 * Generate the service used by the document and save it
	 */
	public function generateDocumentService()
	{
		// Define the path to save the file
		$filePath = $this->pathBaseModule . 'lib/services/' . ucfirst($this->name) . 'Service.class.php';

		echo "Generating $filePath\n";
		// Generate moduleName_DocumentNameService.class.php and save it in /modules/moduleName/lib/services/moduleName_DocumentNameService.class.php
		f_util_FileUtils::saveFile($filePath, $this->generateFile('serviceModel', 'documents'), false);

		// Add the class path in autoload file. It's necessary to call without regenerate cache_autoload.php
		$class = $this->module . '_' . ucfirst($this->name) . 'Service';
		ClassResolver::getInstance()->appendToAutoloadFile($class, $filePath);
	}

	private function getModuleBuilderGenerator($extraparams = array())
	{
		$generator = new builder_Generator('modules');
		$generator->assign_by_ref('name', $this->name);
		$generator->assign_by_ref('module', $this->module);
		$generator->assign_by_ref('date', date_Calendar::now()->toString());
		$generator->assign_by_ref('author', $this->author);
		foreach ($extraparams as $key => $value)
		{
			$generator->assign_by_ref($key, $value);
		}
		return $generator;
	}

	/**
	 * Generate the locale file.
	 */
	public function generateLocaleFile()
	{
		// Get a locale file generator object
		$localeUpdater = new builder_LocaleFileGenerator($this->modelObject);
		// Update or create the locale file for the document
		$localeUpdater->updateLocale();
	}

	/**
	 * Generate the locale file.
	 */
	public function generateBoLocaleFile()
	{
		// Get a locale file generator object
		$localeUpdater = new builder_LocaleFileGenerator($this->modelObject);
		// Update or create the locale file for the document
		$localeUpdater->updateBoLocale();
	}

	/**
	 * Generate persistent document files
	 */
	public function generateFinalPersistentDocumentFile()
	{
		$filePath = $this->pathBaseModule . 'persistentdocument' . DIRECTORY_SEPARATOR . $this->name . '.class.php';
		// Generate documentName.class.php and save it in /modules/moduleName/persistentdocument/documentName.class.php
		if (!file_exists($filePath))
		{
			echo "Generating $filePath\n";
			f_util_FileUtils::write($filePath, $this->modelObject->generatePhpOverride());
			// Add the class path in autoload file. It's necessary to call without regenerate cache_autoload.php
			ClassResolver::getInstance()->appendToAutoloadFile($this->module .'_persistentdocument_' . $this->name, $filePath);
		}
		elseif ($this->modelObject->isIndexable())
		{
			$ok = false;
			$model = $this->modelObject;
			while ($model)
			{
				$parentfilePath = f_util_FileUtils::buildWebeditPath('modules', $model->getModuleName(), 'persistentdocument', $model->getDocumentName() . '.class.php');
				if (file_exists($parentfilePath))
				{
					$content = file_get_contents($parentfilePath);
					if (strpos($content, ' indexer_IndexableDocument'))
					{
						$ok = true;
						break;	
					}
				}
				$model = $model->hasParentModel() ? $model->getParentModel() : null;
			}
			
			if (!$ok)
			{
				echo "WARNING: $filePath not implement indexer_IndexableDocument interface\n";
			}
		}

		//Add import class file
		$filePath = $this->pathBaseModule . 'persistentdocument' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . ucfirst($this->name) . 'ScriptDocumentElement.class.php';
		if (!file_exists($filePath))
		{

			f_util_FileUtils::mkdir(dirname($filePath));
			echo "Generating $filePath\n";
			f_util_FileUtils::write($filePath, $this->modelObject->generateImportClass());
			ClassResolver::getInstance()->appendToAutoloadFile($this->modelObject->getImportScriptDocumentClassName(), $filePath);
			$bindingsPath = $this->pathBaseModule . 'persistentdocument' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . $this->module . '_binding.xml';
			if (file_exists($bindingsPath))
			{
				echo "Updating $bindingsPath\n";
				$document = f_util_DOMUtils::getDocument($bindingsPath);
				$script = $document->documentElement;
			}
			else
			{
				echo "Generating $bindingsPath\n";
				$document = f_util_DOMUtils::newDocument();
				$script = $document->createElement('script');
				$document->appendChild($script);
				$generic = $document->createElement('binding');
				$generic->setAttribute('fileName', 'modules/generic/persistentdocument/import/generic_binding.xml');
				$script->appendChild($generic);
			}
			$binding = $document->createElement('binding');
			$binding->setAttribute('name', $this->name);
			$binding->setAttribute('className', $this->modelObject->getImportScriptDocumentClassName());
			$script->appendChild($binding);
			f_util_DOMUtils::save($document, $bindingsPath);
		}
	}

	/**
	 * Generate persistent document files
	 */
	public function generatePersistentDocumentFile()
	{
		$buildPathPersistentDocuments = CHANGE_BUILD_DIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $this->module . DIRECTORY_SEPARATOR . 'persistentdocument' . DIRECTORY_SEPARATOR;
		f_util_FileUtils::mkdir($buildPathPersistentDocuments);
		$filePath = $buildPathPersistentDocuments . $this->name . 'model_and_base.class.php';
		$fileContent = "<?php\n";
		if (!$this->modelObject->injected())
		{
			if (!$this->modelObject->inject())
			{
				$fileContent .= $this->modelObject->generatePhpModel();
			}
		}
		else
		{
			$fileContent .= $this->modelObject->getReplacer()->generatePhpModel();
		}
		$fileContent .= "\n".$this->modelObject->generatePhpBaseClass();
		
		if (!$this->modelObject->inject())
		{
			if ($this->modelObject->isInternationalized())
			{
				$fileContent .= "\n".$this->modelObject->generatePhpI18nClass();
			}
		}
		f_util_FileUtils::saveFile($filePath, $fileContent, true);	
			
		$classResolver = ClassResolver::getInstance();
		// Add the classes to autoload file. It's necessary to call without regenerate cache_autoload.php
		if (!$this->modelObject->inject())
		{
			$classResolver->appendToAutoloadFile($this->modelObject->getDocumentClassName() . 'model', $filePath);
		}
		$classResolver->appendToAutoloadFile($this->module .'_persistentdocument_' . $this->name . 'base', $filePath);
		if ($this->modelObject->isInternationalized())
		{
			$classResolver->appendToAutoloadFile($this->module .'_persistentdocument_' . $this->name . 'I18n', $filePath);
		}
		

	}

	public function updateRights()
	{
		$rightsPath = f_util_FileUtils::buildWebeditPath("modules", $this->module, "config", "rights.xml");
		$rights = f_util_DOMUtils::getDocument($rightsPath);
		if ($rights->exists("actions/document[@name = '".$this->name."']"))
		{
			echo "$this->name is already declared in $rightsPath\n";
			return;
		}
		$docElem = $rights->createElement("document");
		$docElem->setAttribute("name", $this->name);
		$actionsElem = $rights->findUnique("actions");
		$actionsElem->appendChild($docElem);
		echo "Add $this->name in $rightsPath\n";
		f_util_DOMUtils::save($rights, $rightsPath);
	}

	/**
	 * Generate sql file for document model and create the table in databases
	 */
	public function generateSqlDocumentFile($add = true)
	{
		$buildPathDataobject = CHANGE_BUILD_DIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $this->module . DIRECTORY_SEPARATOR . 'dataobject' . DIRECTORY_SEPARATOR;
		f_util_FileUtils::mkdir($buildPathDataobject);

		$provider = f_persistentdocument_PersistentProvider::getInstance();
		$fileExtension = $provider->getSQLScriptSufixName();

		$tablefilename = $this->modelObject->getTableName();
		if ($this->modelObject->hasParentModel())
		{
			$tablefilename .= '_' . $this->modelObject->getModuleName() . '_' . $this->modelObject->getDocumentName();
		}

		// Create a sql file corresponding to document model
		$sqlFileName = $buildPathDataobject . $tablefilename . $fileExtension;
		
		$sql = $this->modelObject->generateSQLScript($provider->getType());

		if ( $add )
		{
			// Execute the sql script and save it in file
			$this->addAndSaveSql($provider, $sql, $sqlFileName);
		}
		else
		{
			// Save the sql script
			$this->saveSql($sql, $sqlFileName);
		}

		// If document is internationnalized. Excute the same previous action
		if ( $this->modelObject->isInternationalized() )
		{
			$tablefilename = $this->modelObject->getTableName(). '_i18n';
			if ($this->modelObject->hasParentModel())
			{
				$tablefilename .= '_' . $this->modelObject->getModuleName() . '_' . $this->modelObject->getDocumentName();
			}

			$sqlI18nFileName = $buildPathDataobject . $tablefilename . $fileExtension;
			$sqlI18n = $this->modelObject->generateSQLI18nScript($provider->getType());

			if ( $add )
			{
				// Execute the sql script and save it in file
				$this->addAndSaveSql($provider, $sqlI18n, $sqlI18nFileName);
			}
			else
			{
				// Save the sql script
				$this->saveSql($sqlI18n, $sqlI18nFileName);
			}
		}
	}

	/**
	 * Add a part in backoffice style file to manage icon of document in tree
	 */
	public function addStyleInBackofficeFile()
	{
		// Get backoffice style updater
		$styleUpdater = new builder_BackofficeStyleUpdater($this->model);
		// Update the file module/moduleName/style/backoffice.xml
		$styleUpdater->updateXmlDocument();
	}

	/**
	 * Add in backoffice action, the create action for the new document.
	 * If this action name not exist in file module/moduleName/config/action.xml add it.
	 * If action has been added, now we can add in tree to acces the action
	 * This action's entry is inserted if possible on an event with :
	 *  - type = %select%
	 *  - target = %modules_generic_folder%
	 * An action group is created with all necessaries informations
	 */
	public function addBackofficeAction($parents)
	{
		// Get backoffice action updater
		$actionUpdater = new builder_BackofficeActionUpdater($this->model);
		// Update the files module/moduleName/config/action.xml and module/moduleName/config/widgets/leftTree.xml
		$actionUpdater->updateXmlDocument($parents);
	}


	/**
	 * Generate file with smarty
	 *
	 * @param string $templateName
	 * @param string $directory
	 * @return string
	 */
	private function generateFile($templateName, $directory)
	{

		// Instance a new object generator based on smarty
		$generator = new builder_Generator($directory);

		// Assign all necessary variable
		$generator->assign_by_ref('name', $this->name);
		$generator->assign_by_ref('module', $this->module);
		$generator->assign_by_ref('moduleUCFirst', ucfirst($this->module));
		$generator->assign_by_ref('date', $this->date );
		$generator->assign_by_ref('author', $this->author );
		$generator->assign_by_ref('nameUCFirst', ucfirst($this->name));
		$generator->assign_by_ref('model', $this->modelObject);

		// Execute template and return result
		$result = $generator->fetch($templateName .'.tpl');
		return $result;

	}

	/**
	 * Generate the sql script to add table of document and save it.
	 *
	 * @param f_persistentdocument_PersistentProvider $provider
	 * @param string $sql
	 * @param string $path
	 */
	private function addAndSaveSql($provider, $sql, $path)
	{

		foreach(explode(";",$sql) as $query)
		{
			$query = trim($query);
			if (empty($query))
			{
				continue;
			}
			try
			{
				$provider->executeSQLScript($query);
				Framework::debug('[DocumentGenerator] addAndSaveSql : Script execute with success.');
			}
			catch (Exception $e)
			{
				Framework::exception($e);
				Framework::debug('[DocumentGenerator] addAndSaveSql : ERROR in execution script ' . $query);
			}
		}

		$this->saveSql($sql, $path);
	}

	/**
	 * Save the sql script
	 *
	 * @param string $sql
	 * @param string $path
	 */
	private function saveSql($sql, $path)
	{
		try
		{
			// Save file
			f_util_FileUtils::write($path, $sql, f_util_FileUtils::OVERRIDE);
		}
		catch (IOException $e)
		{
			Framework::debug('[DocumentGenerator] addAndSaveSql : Cannot save sql file.');
			Framework::exception($e);
		}
	}
}
