<?php


class change_Injection
{
	/**
	 *
	 * @var array 
	 */
	private $originalClassInfo;
	
	/**
	 *
	 * @var array 
	 */
	private $replacingClassInfo;
	

	/**
	 * 
	 */
	public function isValid()
	{
		$infos = change_InjectionService::getInstance()->getInfos();
		$declaredClasses = get_declared_classes();
     
		if (in_array($this->originalClassInfo['name'], $declaredClasses) || in_array($this->replacingClassInfo['name'], $declaredClasses) )
        {
            throw new Exception('Too late....I\'m not Chuck Norris');
        }
        if (isset($infos[$this->originalClassInfo['name']]['path']))
        {
            require_once $infos[$this->originalClassInfo['name']]['path'];
        }
		if (isset($infos[$this->replacingClassInfo['name']]['path']))
        {
            require_once $infos[$this->replacingClassInfo['name']]['path'];
        }
		
		$originalReflectionClass = new Zend_Reflection_Class($this->originalClassInfo['name']);
		$reflectionClass = new Zend_Reflection_Class($this->replacingClassInfo['name']);
		return $originalReflectionClass->isUserDefined() && $reflectionClass->isSubclassOf($originalReflectionClass->getName()) && !$originalReflectionClass->isFinal(); 

	}
    
    public function __construct($originalClassInfo, $classInfo)
    {
        $this->originalClassInfo = $originalClassInfo;
		$this->replacingClassInfo = $classInfo;
    }

	
	private function processInjectedFile($source, $targetName, $newName, $newExtends, $defaultContent = PHP_EOL)
	{
		$inBrace = 0;
		$inClass = false;
		$classes = array();
		$currentClassContent = array($defaultContent);
		$currentClassName = null;
		$currentClassExtends = null;
		$classDeclaration = false;
		$tokens = token_get_all($source);
		$size = count($tokens);
		for ($index = 0; $index < $size; $index++)
		{
			$token = $tokens[$index];
			if (!is_array($token))
			{
				switch ($token)
				{
					case '{';
						if ($classDeclaration && $inBrace == 0)
						{
							$classDeclaration = false;
							$inClass = true;
						}
						$inBrace++;
						break;
					case '}';
						$inBrace--;
						break;
				}
				if ($inClass || $classDeclaration)
				{
					$currentClassContent[] = $token; 
					if ($inClass && $inBrace == 0)
					{
						$inClass = false;
						$classes[trim($currentClassName)] = implode('', $currentClassContent);
						$currentClassContent = array($defaultContent);
						$currentClassName = null;
						$currentClassExtends = null;
					}
				}
			}
			else
			{
				switch ($token[0])
				{
					case T_ABSTRACT:
						if (!$inClass)
						{
							$classDeclaration = true;
						}
						break;
					case T_CLASS:
					case T_INTERFACE:
						$classDeclaration = true;	
						if ($tokens[$index + 2][1] == $targetName)
						{
							$tokens[$index + 2][1] = $newName;
						}
						$currentClassName = $tokens[$index + 2][1];
						break;
					case T_EXTENDS:
						$hasExtend = true;
						if ($currentClassName == $newName && $newExtends !== null)
						{
							$tokens[$index + 2][1] = $newExtends;
						}
						break;
				}
				if ($classDeclaration || $inClass)
				{
					$currentClassContent[] = $token[1];
				}
			}
		}
		return $classes;
	}
    
    public function generate()
    {
		$originalFileInfo = new SplFileInfo($this->originalClassInfo['path']);
		$originalFileContent = file_get_contents($originalFileInfo->getPathname());
		$originalClassName = $this->originalClassInfo['name'];

		$newClassName = $originalClassName . '_h4x0r3d';
		$infos = array();
		// Process the original file
		$classes = $this->processInjectedFile($originalFileContent, $originalClassName , $newClassName, null, '<?php' . PHP_EOL);
		foreach ($classes as $className => $classContent)
		{   
            if ($className === $newClassName)
            {
                $infos[$originalClassName]['path'] = $originalFileInfo->getPathname();
			    $infos[$originalClassName]['mtime'] = $originalFileInfo->getMTime();
                $infos[$originalClassName]['link'] = f_util_FileUtils::buildProjectPath('build', 'injection', $originalClassName . '.class.php');
            }
			else
			{
				$infos[$className]['path'] = $originalFileInfo->getPathname();
			    $infos[$className]['mtime'] = $originalFileInfo->getMTime();
                $infos[$className]['link'] = f_util_FileUtils::buildProjectPath('build', 'injection', $className . '.class.php');
				f_util_FileUtils::writeAndCreateContainer($infos[$className]['link'], $classContent, f_util_FileUtils::OVERRIDE);
			}
		}
		
		// Process the combined file
		$combinedContent = $classes[$newClassName];
		
		$injectFileInfo = new SplFileInfo($this->replacingClassInfo['path']);
		$injectFileContent = file_get_contents($injectFileInfo->getPathname());
		$injectClassName = $this->replacingClassInfo['name'];
		$classes = $this->processInjectedFile($injectFileContent, $injectClassName , $originalClassName, $newClassName, PHP_EOL);
		$combinedContent .= $classes[$originalClassName];
		
		$infos[$injectClassName]['path'] = $injectFileInfo->getPathname();
        $infos[$injectClassName]['mtime'] = $injectFileInfo->getMTime();
		f_util_FileUtils::writeAndCreateContainer(f_util_FileUtils::buildProjectPath('build', 'injection', $originalClassName . '.class.php'), $combinedContent, f_util_FileUtils::OVERRIDE);
        return $infos;
    }
}