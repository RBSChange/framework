<?php
/**
 * @package framework.builder
 */
class builder_TestFactoryGenerator
{
	/**
	 * Document models
	 * @var array<generator_PersistentModel>
	 */
	private $models = null;

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
	 * Path of module where the test must be added
	 * @var string
	 */
	private $pathBaseModule = null;

	/**
	 * Alternate path of module where the test must be added
	 * @var string
	 */
	private $pathBaseModuleAlt = null;

	public static function generateTestFactoryBaseFiles()
	{
		foreach (array_keys(ModuleService::getInstance()->getModuleVersionList()) as $packageName)
		{
			$moduleName = substr($packageName, 8);
			$testFactoryGenerator = new builder_TestFactoryGenerator($moduleName);
			$testFactoryGenerator->generateTestFactoryBaseFile();
		}
	}
	
	/**
	 * Constructor of builder_TestFactoryGenerator
	 */
	public function __construct($module)
	{
		$this->module = $module;
		$this->date = date('r');
		$this->pathBaseModule = AG_MODULE_DIR . DIRECTORY_SEPARATOR . $module;
		$this->pathBaseModuleAlt = PROJECT_OVERRIDE . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . $module;
		$allModels = generator_PersistentModel::loadModels();
		//$allModels = f_persistentdocument_PersistentDocumentModel::getDocumentModels();
		$models = array();
		
		foreach ($allModels as $model)
		{
			if ($model->getModuleName() == $module)
			{
				$models[] = $model;
			}
		}
		$this->models = $models;
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
	 * PathBaseModule setter
	 * @param string $value
	 * @return builder_DocumentGenerator
	 */
	protected function setPathBaseModuleAlt($value)
	{
		$this->pathBaseModuleAlt = $value;
		return $this;
	}

	/**
	 * Generate TestFactory base file
	 */
	public function generateTestFactoryBaseFile()
	{
		$classResolver = ClassResolver::getInstance();
		$buildPathTests = CHANGE_BUILD_DIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $this->module . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;
		f_util_FileUtils::mkdir($buildPathTests);

		$filePath = $buildPathTests . 'TestFactoryBase.class.php';
		// Generate TestFactoryBase and save it in /modules/moduleName/tests/TestFactoryBase.class.php
		f_util_FileUtils::saveFile($filePath, $this->generateTestFactoryBaseClass(), true);
		// Add the class path in autoload file. It's necessary to call without regenerate cache_autoload.php
		$classResolver->appendToAutoloadFile($this->module .'_TestFactoryBase', $filePath);
	}
	
	/**
	 * Generate TestFactory file
	 */
	public function generateTestFactoryFile()
	{
		$relativePath = '/tests/TestFactory.class.php';
		// put the file into webapp if this is a generic module
		if(is_link($this->pathBaseModule))
//		if(is_link("/home/intolexm/change4/aubertbelge/modules/media"))
		{
			$filePath = $this->pathBaseModule . $relativePath;
			$filePathAlt = $this->pathBaseModuleAlt . $relativePath;
			if (file_exists($filePath))
			{
				if(file_exists($filePathAlt))
				{
					echo ("file '$filePathAlt'' will be overriden by '$filePath'' and should be removed");				
				}
				return;
			}
			$pathBase = $this->pathBaseModuleAlt;
		}
		else
		{
			$pathBase = $this->pathBaseModule;
		}
		$filePath = $pathBase . $relativePath;
		
		f_util_FileUtils::mkdir($pathBase . '/tests');
		
		$classResolver = ClassResolver::getInstance();

		// Generate TestFactory and save it in /modules/moduleName/tests/TestFactory.class.php
		f_util_FileUtils::saveFile($filePath, $this->generateTestFactoryClass(), false);
		// Add the class path in autoload file. It's necessary to call without regenerate cache_autoload.php
		$classResolver->appendToAutoloadFile($this->module .'_TestFactory', $filePath);
	}
	
	private function generateTestFactoryBaseClass()
	{
		$generator = $this->getBuilderGenerator();
		
		// Execute template and return result
		$result = $generator->fetch('TestFactoryBase.tpl');
		return $result;
	
	}
	
	private function generateTestFactoryClass()
	{
		$generator = $this->getBuilderGenerator();
		
		// Execute template and return result
		$result = $generator->fetch('TestFactory.tpl');
		return $result;
	}
	
	private function getBuilderGenerator()
	{
		$generator = new builder_Generator('tests');
		$generator->assign_by_ref('module', $this->module);
		$generator->assign_by_ref('date', date_Calendar::now()->toString());
		$generator->assign_by_ref('author', $this->author);
		$generator->assign_by_ref('models', $this->models);
		return $generator;
	}
}
?>
