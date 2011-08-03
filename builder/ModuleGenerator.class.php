<?php
/**
 * @package framework.builder
 */
class builder_ModuleGenerator
{
	/**
	 * Module name
	 * @var string
	 */
	protected $name = null;

	/**
	 * Module author. Is used in header of generated file
	 * @var string
	 */
	private $author = null;

	/**
	 * Module version
	 * @var string
	 */
	private $version = null;

	/**
	 * Module title. Save in module.xml
	 * @var string
	 */
	private $title = null;

	/**
	 * Current date. This date is write in header of generated file.
	 * @var string
	 */
	private $date = null;

	/**
	 * Icon name
	 * @var string
	 */
	private $icon = null;
	
	/**
	 * Category (emplacement) of the module in the menu bar
	 * @var string
	 */
	private $category;

	/**
	 * Constructor of builder_ModuleGenerator
	 */
	public function __construct($name)
	{
		$this->name = $name;
		$this->date = date('r');	
		$path = realpath(f_util_FileUtils::buildModulesPath($this->name));
		
		// Test if module directory is writeable
		if ($path === false || !is_writeable($path))
		{
			throw new IOException('Cannot write in directory: '. f_util_FileUtils::buildModulesPath($this->name));
		}
	}

	/**
	 * Author setter
	 * @param String $value
	 * @return builder_ModuleGenerator
	 */
	public function setAuthor($value)
	{
		$this->author = $value;
		return $this;
	}

	/**
	 * Version setter
	 * @param String $value
	 * @return builder_ModuleGenerator
	 */
	public function setVersion($value)
	{
		$this->version = $value;
		return $this;
	}

	/**
	 * Title setter
	 * @param String $value
	 * @return builder_ModuleGenerator
	 */
	public function setTitle($value)
	{
		$this->title = $value;
		return $this;
	}

	/**
	 * Icon setter
	 * @param String $value
	 * @return builder_ModuleGenerator
	 */
	public function setIcon($value)
	{
		$this->icon = $value;
		return $this;
	}
	
	/**
	 * @param String $value
	 * @return builder_ModuleGenerator
	 */
	public function setCategory($category)
	{
		$this->category = $category;
		return $this;
	}

	/**
	 * Generate all module files. Used when a new module added to generate over writable or not files.
	 * @return builder_ModuleGenerator
	 */
	public function generateAllFile()
	{
		$this->generateDirectories();
		
		// Launch the generation of over writable files
		$this->generateOnce();
		$this->addConfiguration();
		
		// return the current object
		return $this;
	}
	
	private function generateDirectories()
	{
		$pathBase = f_util_FileUtils::buildModulesPath($this->name);
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase,  'config'));
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'forms', 'editor', 'rootfolder'));	
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'lib', 'services'));
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'persistentdocument', 'import'));
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'setup'));
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'style'));
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'templates', 'perspectives'));
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'forms', 'editor', 'rootfolder'));
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'forms', 'editor', 'folder'));
	}
	
	private function addConfiguration()
	{
		$info = array('ENABLED' => true, 'VISIBLE' => true, 'CATEGORY' => $this->category, 'ICON' => $this->icon);
		Framework::addPackageConfiguration('modules_' . $this->name, $info);
		ModuleService::clearInstance();
	}

	/**
	 * Generate files that developper can over write.
	 * @return builder_ModuleGenerator
	 */
	public function generateOnce()
	{
		$crs = ClassResolver::getInstance();
		
		// Generate configuration files
		f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'change.xml'), $this->generateFile('change.xml'));
		f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'config', 'module.xml'), $this->generateFile('config_module.xml'));
		f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'config', 'actions.xml'), $this->generateFile('config_actions.xml'));
		f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'config', 'rights.xml'), $this->generateFile('config_rights.xml'));
		f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'config', 'perspective.xml'), $this->generateFile('config_perspective.xml'));

		// Generate localisation files		
		$this->generateGeneralLocales();
		
		// Generate setup file
		$path = f_util_FileUtils::buildModulesPath($this->name, 'setup', 'initData.php');
		f_util_FileUtils::write($path, $this->generateFile('initData'));
		$crs->appendToAutoloadFile($this->name . '_Setup', $path);

		// Generate services files
		$path = f_util_FileUtils::buildModulesPath($this->name, 'lib', 'services', 'ModuleService.class.php');
		f_util_FileUtils::write($path, $this->generateFile('ModuleService.class.php'));
		$crs->appendToAutoloadFile($this->name . '_ModuleService', $path);

		// Generate perspectives file
		$path = f_util_FileUtils::buildModulesPath($this->name, 'templates', 'perspectives', 'default.all.all.xul');
		f_util_FileUtils::write($path, $this->generateFile('default.all.all.xul'));

		// Generate persistentdocument/import file
		$path = f_util_FileUtils::buildModulesPath($this->name, 'persistentdocument', 'import', $this->name . '_binding.xml');
		f_util_FileUtils::write($path, $this->generateFile('import_binding.xml'));
		
		// Generate editor for rootfolder
		$path = f_util_FileUtils::buildModulesPath($this->name, 'forms', 'editor', 'rootfolder',  'empty.txt');
		
		// Generate editor for folders
		$path = f_util_FileUtils::buildModulesPath($this->name, 'forms', 'editor', 'folder',  'empty.txt');
		f_util_FileUtils::write($path, '');
		
		// return the current object
		return $this;
	}

	public function generateGeneralLocales()
	{
		$ls = LocaleService::getInstance();
		$ids = array('module-name' => $this->name, 'system-folder-name' => $this->name);
		
		$keysInfos = array();
		$keysInfos[$ls->getLCID('fr')] = $ids;
		$keysInfos[$ls->getLCID('en')] = $ids;
		$keysInfos[$ls->getLCID('de')] = $ids;
		$baseKey = 'm.' . $this->name . '.bo.general';
		$ls->updatePackage($baseKey, $keysInfos, false, true);
		
		$keysInfos[$ls->getLCID('fr')] = array('create_' => 'créer');
		$keysInfos[$ls->getLCID('en')] = array('create_' => 'create');
		$keysInfos[$ls->getLCID('de')] = array('create_' => 'neu');
		$baseKey = 'm.' . $this->name . '.bo.actions';
		$ls->updatePackage($baseKey, $keysInfos, false, true);
		
		$keysInfos[$ls->getLCID('fr')] = array();
		$keysInfos[$ls->getLCID('en')] = array();
		$keysInfos[$ls->getLCID('de')] = array();
		$baseKey = 'm.' . $this->name . '.document.permission';
		$ls->updatePackage($baseKey, $keysInfos, false, true, 'm.generic.document.permission');			
	}

	/**
	 * Generate a file with a template
	 * @param string $templateName
	 * @param string $directory
	 * @return string
	 */
	private function generateFile($templateName, $directory = 'modules')
	{
		// Instance a new object generator based on smarty
		$generator = new builder_Generator($directory);

		// Assign all necessary variable
		$generator->assign('name', $this->name);
		$generator->assign('date', $this->date);
		$generator->assign('author', $this->author);
		$generator->assign('title', $this->getTitle());
		$generator->assign('version', $this->version);
		$generator->assign('frameworkVersion', Framework::getVersion());
		$generator->assign('icon', $this->icon);
		$generator->assign('category', $this->category);
		
		// Execute template and return result
		$result = $generator->fetch($templateName .'.tpl');
		return $result;

	}

	/**
	 * Get the title. Used in internal to get the defined title or a automatly generated title
	 * @return string
	 */
	private function getTitle()
	{
		// If no title defined construct it
		if ( is_null($this->title) )
		{
			$this->setTitle( ucfirst( $this->name ) . ' module');
		}
		return $this->title;
	}

	/**
	 * Generate a service
	 *
	 * @param String $name
	 * @param String $module
	 * @return String
	 */
	public function generateService($name, $module)
	{
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('author', $this->author);
		$generator->assign_by_ref('name', ucfirst($name));
		$generator->assign_by_ref('module', $module);
		$generator->assign_by_ref('date', $this->date);
		$result = $generator->fetch('ServiceModel.tpl');
		return $result;
	}

	/**
	 * Generate an action
	 *
	 * @param String $name
	 * @return String
	 */
	public function generateFrontAction($name)
	{
		$generator = new builder_Generator('modules');
		$generator->assign_by_ref('author', $this->author);
		$generator->assign_by_ref('name', $name);
		$generator->assign_by_ref('module', $this->name);
		$generator->assign_by_ref('date', $this->date);
		$result = $generator->fetch('FrontAction.tpl');
		return $result;
	}

	/**
	 * Generate a JSON action
	 *
	 * @param String $name
	 * @return String
	 */
	public function generateJSONAction($name)
	{
		$generator = new builder_Generator('modules');
		$generator->assign_by_ref('author', $this->author);
		$generator->assign_by_ref('name', $name);
		$generator->assign_by_ref('module', $this->name);
		$generator->assign_by_ref('date', $this->date);
		$result = $generator->fetch('JSONAction.tpl');
		return $result;
	}

	protected function _getTpl($folder, $tpl, $nom, $icon = null)
	{
		$generator = new builder_Generator($folder);
		$generator->assign_by_ref('author', $this->author);
		$generator->assign_by_ref('name', $nom);
		$generator->assign_by_ref('module', $this->name);
		$generator->assign_by_ref('icon', $icon);
		$generator->assign_by_ref('date', $this->date);
		$result = $generator->fetch($tpl);
		return $result;
	}
}