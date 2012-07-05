<?php
class change_FileResolver
{
	/**
	 * @return change_FileResolver
	 */
	public static function getNewInstance()
	{
		$className = get_called_class();
		return new $className();
	}
	
	/**
	 * @var string[]
	 */
	protected $potentialDirectories;
	
	/**
	 * @var string[]
	 */
	protected $potentialSuffixes;
	
	/**
	 * @var string|NULL
	 */
	protected $extension;
	
	
	protected function __construct()
	{
		$this->potentialDirectories = array(f_util_FileUtils::buildOverridePath(), 
			f_util_FileUtils::buildProjectPath(), 
			f_util_FileUtils::buildChangeBuildPath());
		
		$this->potentialSuffixes = array();
	}
	
	/**
	 * @param string $directory
	 * @param boolean $before
	 * @return change_FileResolver
	 */
	public function addPotentialDirectory($directory, $before = true)
	{
		if (!in_array($directory, $this->potentialDirectories))
		{
			if ($before)
			{
				array_unshift($this->potentialDirectories, $directory);
			}
			else
			{
				$this->potentialDirectories[] = $directory;
			}
		}
		return $this;
	}
	
	/**
	 * @return change_FileResolver
	 */
	public function addCurrentThemePotentialDirectories()
	{
		$currentPageId = website_PageService::getInstance()->getCurrentPageId();
		if ($currentPageId)
		{
			$currentPage = website_persistentdocument_page::getInstanceById($currentPageId);
			list($themeName, ) = explode('/', $currentPage->getTemplate());
			$this->addThemePotentialDirectories($themeName);
		}
		return $this;
	}
	
	/**
	 * @param string $themeName
	 * @return change_FileResolver
	 */
	public function addThemePotentialDirectories($themeName)
	{
		$themeDir = f_util_FileUtils::buildProjectPath('themes', $themeName);
		if (is_dir($themeDir))
		{
			$this->addPotentialDirectory($themeDir);
			$overrideThemeDir = f_util_FileUtils::buildOverridePath('themes', $themeName);
			if (is_dir($overrideThemeDir))
			{
				$this->addPotentialDirectory($overrideThemeDir);
			}
		}
		return $this;
	}
	
	
	/**
	 * @param string $suffix
	 * @param boolean $before
	 * @return change_FileResolver
	 */
	public function addPotentialSuffix($suffix, $before = true)
	{
		if (!in_array($suffix, $this->potentialSuffixes))
		{
			if ($before)
			{
				array_unshift($this->potentialSuffixes, $suffix);
			}
			else
			{
				$this->potentialSuffixes[] = $suffix;
			}
		}
		return $this;
	}
	
	/**
	 * @param string $extension For exemple html
	 * @return change_FileResolver
	 */
	public function setExtension($extension)
	{
		if (!empty($extension))
		{
			$this->extension = $extension;
			$this->addPotentialSuffix('.'. $extension);
			$strategy = change_FileResolverExtensionStrategy::getInstance();
			$strategy->updateResolver($this, $extension);
		}
		else
		{
			$this->extension = null;
		}
		return $this;
	}
	
	/**
	 * @param string $relativePath
	 * @return string|null
	 */
	public function getPath($relativePath)
	{
		$cleanRelativePath = $this->cleanRelativePath(func_num_args() > 1 ? implode(DIRECTORY_SEPARATOR, func_get_args()) : $relativePath);
		if (!count($this->potentialSuffixes)) {$this->potentialSuffixes[] = '';}
		
		foreach ($this->potentialSuffixes as $suffix)
		{
			/* @var $suffix string */
			foreach ($this->potentialDirectories as $directory)
			{
				$fullPath = f_util_FileUtils::buildPath($directory, $cleanRelativePath) . $suffix;
				if (file_exists($fullPath))
				{
					return $fullPath;
				}
			}
		}
		return null;
	}
	
	/**
	 * @param string $relativePath
	 * @return string[]
	 */
	public function getPaths($relativePath)
	{
		$cleanRelativePath = $this->cleanRelativePath(func_num_args() > 1 ? implode(DIRECTORY_SEPARATOR, func_get_args()) : $relativePath);
		$fullPaths = array();
		if (!count($this->potentialSuffixes)) {$this->potentialSuffixes[] = '';}
	
		foreach ($this->potentialSuffixes as $suffix)
		{
			/* @var $suffix string */
			foreach ($this->potentialDirectories as $directory)
			{
				$fullPath = f_util_FileUtils::buildPath($directory, $cleanRelativePath) . $suffix;
				if (file_exists($fullPath))
				{
					$fullPaths[] = $fullPath;
				}
			}
		}
		return $fullPaths;
	}	
	
	/**
	 * @param string $relativePath
	 * @return string
	 */
	protected function cleanRelativePath($relativePath)
	{
		if (!is_string($relativePath) || $relativePath == null)
		{
			return '';
		}
		
		while (strlen($relativePath) && $relativePath[0] == DIRECTORY_SEPARATOR)
		{
			$relativePath = substr($relativePath, 1);
		}
		return $relativePath;
	}
}

/**
 * @method change_TemplateLoader getNewInstance()
 * @method change_TemplateLoader setExtension()
 */
class change_TemplateLoader extends change_FileResolver
{
	
	protected function __construct()
	{
		parent::__construct();
		$this->addCurrentThemePotentialDirectories();
	}
		
	/**
	 * @param string $relativePath
	 * @param string $extension
	 * @return TemplateObject|NULL
	 */
	public function load($relativePath)
	{
		$path = $this->getPath(func_num_args() > 1 ? implode(DIRECTORY_SEPARATOR, func_get_args()) : $relativePath);
		if ($path !== null)
		{
			$template = new TemplateObject($path, $this->extension);	
			if (Framework::inDevelopmentMode() && $this->extension === 'html')
			{
				$template->setOriginalPath($path);
			}
			return $template;
		}		
		Framework::warn('Template not found: ' . $this->cleanRelativePath(func_num_args() > 1 ? implode(DIRECTORY_SEPARATOR, func_get_args()) : $relativePath));
		return null;
	}
}

/**
 * @method change_FileResolverExtensionStrategy getInstance()
 */
class change_FileResolverExtensionStrategy extends change_Singleton
{
	protected $usedKeys = array('iphone.all', 'gecko.all', 'all.all');
	
	/**
	 * @param string $extension For exemple html
	 * @return string
	 */
	public function getKeyExtension($extension)
	{
		switch ($extension) 
		{
			case 'php':
				$requestContext = RequestContext::getInstance();
				return $requestContext->getUserAgentType() . '.all';
			case 'html':
			case 'xul':
			case 'xml':
				$requestContext = RequestContext::getInstance();
				return $requestContext->getUserAgentType() . '.' . $requestContext->getUserAgentTypeVersion();
			default:
				return '';
		}
	}
	
	/**
	 * @param change_FileResolver $resolver
	 * @param string $extension
	 */
	public function updateResolver($resolver, $extension)
	{
		$current = $this->getKeyExtension($extension);
		if (!empty($current))
		{
			$key = 'all.all';
			list($engine, $engineVersion) = explode('.', $current);
			if (in_array($key, $this->usedKeys)) {$resolver->addPotentialSuffix('.' . $key .'.' .  $extension);}
			if ($engine !== 'all')
			{
				$key = $engine . '.all';
				if (in_array($key, $this->usedKeys)) {$resolver->addPotentialSuffix('.' . $key .'.' .  $extension);}
				if ($engineVersion !== 'all')
				{
					$key = $engine . '.' . $engineVersion;
					if (in_array($key, $this->usedKeys)) {$resolver->addPotentialSuffix('.' . $key .'.' .  $extension);}
				}
			}
		}
	}
}