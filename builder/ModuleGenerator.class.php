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
	protected $name;

	/**
	 * Module author. Is used in header of generated file
	 * @var string
	 */
	private $author;

	/**
	 * Module version
	 * @var string
	 */
	private $version;


	/**
	 * Current date. This date is write in header of generated file.
	 * @var string
	 */
	private $date;

	/**
	 * Icon name
	 * @var string
	 */
	private $icon;
	
	/**
	 * Category (emplacement) of the module in the menu bar
	 * @var string
	 */
	private $category;
	
	/**
	 * @var boolean
	 */
	private $visibility = true;

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
	 * @param string $value
	 * @return builder_ModuleGenerator
	 */
	public function setAuthor($value)
	{
		$this->author = $value;
		return $this;
	}

	/**
	 * Version setter
	 * @param string $value
	 * @return builder_ModuleGenerator
	 */
	public function setVersion($value)
	{
		$this->version = $value;
		return $this;
	}

	/**
	 * Icon setter
	 * @param string $value
	 * @return builder_ModuleGenerator
	 */
	public function setIcon($value)
	{
		$this->icon = $value;
		return $this;
	}
	
	/**
	 * @param string $value
	 * @return builder_ModuleGenerator
	 */
	public function setCategory($category)
	{
		$this->category = $category;
		return $this;
	}
	
	/**
	 * @param boolean $visibility
	 * @return builder_ModuleGenerator
	 */
	public function setVisibility($visibility)
	{
		$this->visibility = $visibility;
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
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'lib', 'services'));
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'setup'));
		f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'style'));
				
		if ($this->visibility)
		{
			f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'templates', 'perspectives'));
			f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'forms', 'editor', 'rootfolder'));
			f_util_FileUtils::mkdir(f_util_FileUtils::buildAbsolutePath($pathBase, 'forms', 'editor', 'folder'));
		}
	}
	
	private function addConfiguration()
	{
		$info = array('VISIBLE' => $this->visibility, 'CATEGORY' => $this->category, 'ICON' => $this->icon);
		Framework::addPackageConfiguration('modules_' . $this->name, $info);
		ModuleService::clearInstance();
	}

	/**
	 * Generate files that developper can over write.
	 * @return builder_ModuleGenerator
	 */
	public function generateOnce()
	{
		$licensePath = f_util_FileUtils::buildFrameworkPath('builder', 'templates', 'modules', 'LICENSE.txt');
		f_util_FileUtils::cp($licensePath, f_util_FileUtils::buildModulesPath($this->name, 'LICENSE.txt'));
		
		// Generate configuration files
		f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'install.xml'), $this->generateFile('install.xml'));
		f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'config', 'module.xml'), $this->generateFile('config_module.xml'));
		
		if ($this->visibility)
		{
			f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'config', 'actions.xml'), $this->generateFile('config_actions.xml'));
			f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'config', 'rights.xml'), $this->generateFile('config_rights.xml'));
			f_util_FileUtils::write(f_util_FileUtils::buildModulesPath($this->name, 'config', 'perspective.xml'), $this->generateFile('config_perspective.xml'));
		}

		// Generate localisation files		
		$this->generateGeneralLocales();
		
		// Generate setup file
		$path = f_util_FileUtils::buildModulesPath($this->name, 'setup', 'initData.php');
		f_util_FileUtils::write($path, $this->generateFile('initData'));
		change_AutoloadBuilder::getInstance()->appendFile($path);

		// Generate services files
		$path = f_util_FileUtils::buildModulesPath($this->name, 'lib', 'services', 'ModuleService.class.php');
		f_util_FileUtils::write($path, $this->generateFile('ModuleService.class.php'));
		change_AutoloadBuilder::getInstance()->appendFile($path);
		
		if ($this->visibility)
		{
			// Generate perspectives file
			$path = f_util_FileUtils::buildModulesPath($this->name, 'templates', 'perspectives', 'default.all.all.xul');
			f_util_FileUtils::write($path, $this->generateFile('default.all.all.xul'));
			
			// Generate editor for rootfolder
			$path = f_util_FileUtils::buildModulesPath($this->name, 'forms', 'editor', 'rootfolder',  'empty.txt');
			f_util_FileUtils::write($path, '');
					
			// Generate editor for folders
			$path = f_util_FileUtils::buildModulesPath($this->name, 'forms', 'editor', 'folder',  'empty.txt');
			f_util_FileUtils::write($path, '');
		}
		
		// return the current object
		return $this;
	}

	public function generateGeneralLocales()
	{
		$ls = LocaleService::getInstance();
		$ids = array('module-name' => $this->name, 'system-folder-name' => $this->name);
		
		$keysInfos = array();
		$keysInfos[$ls->getLCID('fr')] = $ids;
		$baseKey = 'm.' . $this->name . '.bo.general';
		$ls->updatePackage($baseKey, $keysInfos, false, true);
		
		if ($this->visibility)
		{
			$keysInfos[$ls->getLCID('fr')] = array('create_' => 'créer');
			$baseKey = 'm.' . $this->name . '.bo.actions';
			$ls->updatePackage($baseKey, $keysInfos, false, true);
			
			$keysInfos[$ls->getLCID('fr')] = array();
			$baseKey = 'm.' . $this->name . '.document.permission';
			$ls->updatePackage($baseKey, $keysInfos, false, true, 'm.generic.document.permission');		
		}	
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
		$generator->assign('version', $this->version);
		$generator->assign('icon', $this->icon);
		$generator->assign('category', $this->category);
		$generator->assign('visibility', $this->visibility ? 'true' : 'false');
		
		// Execute template and return result
		$result = $generator->fetch($templateName .'.tpl');
		return $result;

	}


	/**
	 * Generate a service
	 *
	 * @param string $name
	 * @param string $module
	 * @return string
	 */
	public function generateService($name, $module)
	{
		$generator = new builder_Generator('models');
		$generator->assign('name', ucfirst($name));
		$generator->assign('module', $module);
		$result = $generator->fetch('ServiceModel.tpl');
		return $result;
	}

	/**
	 * Generate an action
	 *
	 * @param string $name
	 * @return string
	 */
	public function generateFrontAction($name)
	{
		$generator = new builder_Generator('modules');
		$generator->assign('name', $name);
		$generator->assign('module', $this->name);
		$result = $generator->fetch('FrontAction.tpl');
		return $result;
	}
	
	/**
	 * Generate a JSON action
	 *
	 * @param string $name
	 * @return string
	 */
	public function generateJSONAction($name)
	{
		$generator = new builder_Generator('modules');
		$generator->assign('name', $name);
		$generator->assign('module', $this->name);
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