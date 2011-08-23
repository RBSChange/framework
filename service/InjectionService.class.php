<?php

class change_InjectionService
{
	/**
	 * @var change_InjectionService 
	 */
	private static $instance;
	
	private $infos;
	
	/**
	 * @return change_InjectionService
	 */
	public function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Get the array containing all the injection related informations 
	 * 
	 * @return array 
	 */
	public function getInfos()
    {
        if ($this->infos === null)
        {
            $path = f_util_FileUtils::buildProjectPath('build', 'injection', 'info.ser');
            if (file_exists($path))
            {
               $this->infos = unserialize(file_get_contents($path));
            }
            else 
            {
                $this->infos = array();
            }
        }
        return $this->infos; 
    }
    
    public function update()
    {
        // Check if injection is up to date
    	$injectionInfos = $this->getInfos();
    	$injectionConfig = Framework::getConfiguration('injection/class');
    	$toRecompile = array();
    	foreach ($injectionInfos as $className => $value) 
    	{
    		$fileInfo = new SplFileInfo($value['path']);
    		
    		if ($fileInfo->getMTime() != $value['mtime'])
    		{
    			if (isset($injectionConfig[$className]))
    			{
    				$toRecompile[$className] = $injectionConfig[$className];
    			}
    			else if (($key = array_search($className, $injectionConfig)) !== false)
    			{
    				$toRecompile[$key] = $injectionConfig[$key];
    			}
    		}
    	}
    	if (count($toRecompile))
    	{
    		$this->compile($toRecompile, false);
    	}
    }
	
	/**
	 * Reset the array  containing all the injection related informations 
	 */
	public function resetInfos()
	{
		$path = f_util_FileUtils::buildProjectPath('build', 'injection', 'info.ser');
		if (file_exists($path))
		{
			@unlink($path);
		}
		$this->infos = null;
	}
	
	public function restore()
	{
		
	}
	
	/**
	 * @param array $toRecompile
	 * @param boolean $checkValidity
	 * @return array 
	 */
	public function compile($toRecompile = null, $checkValidity = true)
	{
		$returnValue = array();
		if ($toRecompile == null)
		{
			$toRecompile = Framework::getConfiguration("injection/class");
			$newInjectionInfos = array();
		}
		else 
		{
			$newInjectionInfos = $this->getInfos();
		}
		
		
		foreach ($toRecompile as $originalClassName => $className)
		{
			$originalClassInfo = $this->buildClassInfo($originalClassName);
			$replacingClassInfo = $this->buildClassInfo($className);

		    $injection = new change_Injection($originalClassInfo, $replacingClassInfo);
			if ($checkValidity && $injection->isValid())
			{
				$newInjectionInfos = array_merge($newInjectionInfos, $injection->generate());
				$returnValue[$originalClassName] = $className; 
			}
		}
		$this->setInfos($newInjectionInfos);
		
		foreach ($newInjectionInfos as $className => $info)
		{
		    $autoloadClassPath = ClassResolver::getInstance()->getPath($className);
			
		    if (isset($info['link']))
		    {
				@unlink($autoloadClassPath);
		        f_util_FileUtils::symlink($info['link'], $autoloadClassPath);
		    }  
		}

		return $returnValue;
	}
	
	/**
	 *
	 * @param type $className
	 * @return type 
	 */
	private function buildClassInfo($className)
	{
		$infos = $this->getInfos();
		if (isset($infos[$className]) && file_exists($infos[$className]['path']))
		{
			return array('name' => $className, 'path' => $infos[$className]['path']);
		}
		return array('name' => $className, 'path' =>  realpath(ClassResolver::getInstance()->getPath($className)));
	}
	
		
    protected function setInfos($infos)
    {
		$this->infos = $infos;
        f_util_FileUtils::writeAndCreateContainer(f_util_FileUtils::buildProjectPath('build', 'injection', 'info.ser'), serialize($this->infos), f_util_FileUtils::OVERRIDE);
    }
	
}