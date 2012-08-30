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
	public function __construct($module, $name)
	{
		$this->name = $name;
		$this->module = $module;
		$this->date = date('r');
		$this->pathBaseModule = f_util_FileUtils::buildModulesPath($module, "");
		$this->model = 'modules_' . $module . '/' . $name;
		$this->modelObject = generator_PersistentModel::getModelByName('modules_' . $module . '/' . $name);
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
	 * Generate the locale file.
	 */
	public function generateLocaleFile()
	{
		// Get a locale file generator object
		$localeUpdater = new builder_LocaleFileGenerator($this->modelObject);
		// Update or create the locale file for the document
		return $localeUpdater->updateLocale();
	}

	/**
	 * Generate the locale file.
	 */
	public function generateBoLocaleFile()
	{
		// Get a locale file generator object
		$localeUpdater = new builder_LocaleFileGenerator($this->modelObject);
		// Update or create the locale file for the document
		return $localeUpdater->updateBoLocale();
	}

	/**
	 * Generate persistent document files
	 */
	public function generatePersistentDocumentFile()
	{
		$buildPathPersistentDocuments = f_util_FileUtils::buildChangeBuildPath('modules', $this->module , 'persistentdocument') . DIRECTORY_SEPARATOR;
		f_util_FileUtils::mkdir($buildPathPersistentDocuments);
		
		$filePath = $buildPathPersistentDocuments . $this->name . 'model_and_base.class.php';
		$fileContent = array('<?php');
		$fileContent[] = $this->modelObject->generatePhpModel();
		$fileContent[] = $this->modelObject->generatePhpBaseClass();
		if ($this->modelObject->isLocalized())
		{
			$fileContent[] = $this->modelObject->generatePhpI18nClass();
		}
		f_util_FileUtils::write($filePath, implode(PHP_EOL, $fileContent), f_util_FileUtils::OVERRIDE);	
		change_AutoloadBuilder::getInstance()->appendFile($filePath);
	}

	/**
	 * Generate sql file for document model and create the table in databases
	 */
	public function generateSqlDocumentFile($add = true)
	{
		$buildPathDataobject = f_util_FileUtils::buildChangeBuildPath('modules' , $this->module , 'dataobject') . DIRECTORY_SEPARATOR;
		f_util_FileUtils::mkdir($buildPathDataobject);
		$sm = f_persistentdocument_PersistentProvider::getInstance()->getSchemaManager();
		
		$fileExtension = $sm->getSQLScriptSufixName();

		$tablefilename = $this->modelObject->getTableName();
		if ($this->modelObject->hasParentModel())
		{
			$tablefilename .= '_' . $this->modelObject->getModuleName() . '_' . $this->modelObject->getDocumentName();
		}

		// Create a sql file corresponding to document model
		$sqlFileName = $buildPathDataobject . $tablefilename . $fileExtension;
		$sql = $sm->generateSQLModel($this->modelObject);

		$this->saveSql($sql, $sqlFileName, $add);

		// If document is internationnalized. Excute the same previous action
		if ($this->modelObject->isLocalized() )
		{
			$tablefilename = $this->modelObject->getTableName(). '_i18n';
			if ($this->modelObject->hasParentModel())
			{
				$tablefilename .= '_' . $this->modelObject->getModuleName() . '_' . $this->modelObject->getDocumentName();
			}

			$sqlI18nFileName = $buildPathDataobject . $tablefilename . $fileExtension;
			$sqlI18n = $sm->generateSQLI18nModel($this->modelObject);
			
			$this->saveSql($sqlI18n, $sqlI18nFileName, $add);
		}
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
		$actionUpdater = new builder_BackofficeActionUpdater($this->model);
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
		$generator->assign('name', $this->name);
		$generator->assign('module', $this->module);
		$generator->assign('moduleUCFirst', ucfirst($this->module));
		$generator->assign('date', $this->date );
		$generator->assign('author', $this->author );
		$generator->assign('nameUCFirst', ucfirst($this->name));
		$generator->assign('model', $this->modelObject);

		// Execute template and return result
		$result = $generator->fetch($templateName .'.tpl');
		return $result;

	}

	/**
	 * Save the sql script
	 * @param string $sql
	 * @param string $path
	 * @param boolean $add
	 */
	private function saveSql($sql, $path, $add = false)
	{
		try
		{
			// Save file
			f_util_FileUtils::write($path, $sql, f_util_FileUtils::OVERRIDE);
			if ($add)
			{
				$sm = f_persistentdocument_PersistentProvider::getInstance()->getSchemaManager();
				foreach(explode(";",$sql) as $query)
				{
					$query = trim($query);
					if (empty($query))
					{
						continue;
					}
					
					try
					{
						$sm->execute($query);
					}
					catch (Exception $dbe)
					{
						Framework::exception($dbe);
					}
				}				
			}
		}
		catch (IOException $e)
		{
			Framework::exception($e);
		}
	}
	
	/**
	 * 
	 * @param string $moduleName
	 * @param string $documentName
	 * @param string $extendModelName
	 * @param boolean $inject
	 * @return string path of generated file
	 */
	public static function generateDocumentService($moduleName, $documentName, $extendModelName = null, $inject = false)
	{
		$filePath = f_util_FileUtils::buildModulesPath($moduleName, 'lib', 'services', ucfirst($documentName) . 'Service.class.php');
		$generator = new builder_Generator('documents');
		
		$className = $moduleName . '_' . ucfirst($documentName) . 'Service';
		if ($extendModelName)
		{
			list($extModule, $extDocument) = explode('/', substr($extendModelName, 8));
			$extendClass = $extModule . '_' . ucfirst($extDocument) . 'Service';
		}
		else
		{
			$extendClass = 'f_persistentdocument_DocumentService';
		}
		
		// Assign all necessary variable
		$generator->assign('moduleName', $moduleName);
		$generator->assign('moduleUCFirst', ucfirst($moduleName));
		
		$generator->assign('documentName', $documentName);
		$generator->assign('nameUCFirst', ucfirst($documentName));
		
		$generator->assign('className', $className);
		$generator->assign('extendClass', $extendClass);
		$generator->assign('inject', $inject);
		$generator->assign('hasParentModel', ($extendModelName !== null));

		// Execute template and return result
		$result = $generator->fetch('DocumentServiceModel.tpl');
		f_util_FileUtils::writeAndCreateContainer($filePath, $result, f_util_FileUtils::OVERRIDE);
		change_AutoloadBuilder::getInstance()->appendFile($filePath);
		return $filePath;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param string $extendModelName
	 * @param boolean $inject
	 * @return string[] path of generated files
	 */
	public static function generateFinalPersistentDocumentFile($moduleName, $documentName, $extendModelName = null, $inject = false)
	{
		$files = array();
		$generator = new builder_Generator('documents');
		
		$filePath = f_util_FileUtils::buildModulesPath($moduleName, 'persistentdocument' , $documentName . '.class.php');
		$files[] = $filePath;
		$className = $moduleName .'_persistentdocument_' . $documentName;
		
		$generator->assign('moduleName', $moduleName);
		$generator->assign('documentName', $documentName);		
		$generator->assign('className', $className);
		
		$serviceClassName = $moduleName . '_' . ucfirst($documentName) . 'Service';
		$generator->assign('serviceClassName', $serviceClassName);
		
		$result = $generator->fetch('DocumentClass.tpl');
		f_util_FileUtils::writeAndCreateContainer($filePath, $result, f_util_FileUtils::OVERRIDE);	
		change_AutoloadBuilder::getInstance()->appendFile($filePath);
		
		if (!$inject)
		{
			//Add import class file
			$filePath = f_util_FileUtils::buildModulesPath($moduleName, 'persistentdocument' , 'import' , ucfirst($documentName) . 'ScriptDocumentElement.class.php');
			$files[] = $filePath;
			$importClassName = $moduleName .'_'. ucfirst($documentName) . 'ScriptDocumentElement';
	
			$generator->assign('importClassName', $importClassName);
			$result = $generator->fetch('ImportDocumentClass.tpl');
			f_util_FileUtils::writeAndCreateContainer($filePath, $result, f_util_FileUtils::OVERRIDE);	
			change_AutoloadBuilder::getInstance()->appendFile($filePath);
			
			$filePath = f_util_FileUtils::buildModulesPath($moduleName, 'persistentdocument' , 'import' , $moduleName . '_binding.xml');
			$files[] = $filePath;
			
			if (file_exists($filePath))
			{
				$document = f_util_DOMUtils::fromPath($filePath);
				$script = $document->documentElement;
			}
			else
			{
				$document = f_util_DOMUtils::newDocument();
				$script = $document->createElement('script');
				$document->appendChild($script);
				$generic = $document->createElement('binding');
				$generic->setAttribute('fileName', 'modules/generic/persistentdocument/import/generic_binding.xml');
				$script->appendChild($generic);
			}
			
			$binding = $document->createElement('binding');
			$binding->setAttribute('name', $documentName);
			$binding->setAttribute('className', $importClassName);
			$script->appendChild($binding);
			f_util_DOMUtils::save($document, $filePath);
		}
		return $files;
	}
		
	public static function updateRights($moduleName, $documentName, $extendModelName = null, $inject = false)
	{
		$rightsPath = f_util_FileUtils::buildModulesPath($moduleName, "config", "rights.xml");
		if (is_readable($rightsPath))
		{
			$rights = f_util_DOMUtils::fromPath($rightsPath);
			if ($rights->exists("actions/document[@name = '".$documentName."']"))
			{
				return null;
			}
			$docElem = $rights->createElement("document");
			$docElem->setAttribute("name", $documentName);
			$actionsElem = $rights->findUnique("actions");
			$actionsElem->appendChild($docElem);
			f_util_DOMUtils::save($rights, $rightsPath);
			return $rightsPath;	
		}
		return null;
	}
}
