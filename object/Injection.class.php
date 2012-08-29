<?php

class change_Injection
{	
	const REPLACED_CLASS_SUFFIX = '_injected';
		
	/**
	 * @var array 
	 */
	private $originalClassInfo;
	/**
	 *
	 * @var array 
	 */
	private $replacingClassInfos;

	/**
	 * Checks the validity of the given injection :
	 *  - injecting class has to be *directly* extend the injected class
	 *  - the injected class has to be user defined
	 *  - the injected class should not be final
	 */
	public function isValid()
	{
		$infos = change_InjectionService::getInstance()->getInfos();
		$declaredClasses = get_declared_classes();
		if (in_array($this->originalClassInfo['name'], $declaredClasses))
		{
			throw new Exception('Could not check injection validity at run time - please make sure that ' . $this->originalClassInfo['name'] . ' can be injected.');
		}
		
		foreach ($this->replacingClassInfos as $replacingClassInfo)
		{
			if (in_array($replacingClassInfo['name'], $declaredClasses))
			{
				throw new Exception('Could not check injection validity at run time - please make sure that ' . $this->originalClassInfo['name'] . ' can be injected by ' . $replacingClassInfo['name']);
			}
		}
		
		if (isset($infos[$this->originalClassInfo['name']]['path']))
		{
			require_once $infos[$this->originalClassInfo['name']]['path'];
		}
		$originalReflectionClass = new Zend_Reflection_Class($this->originalClassInfo['name']);
		if (!$originalReflectionClass->isUserDefined() || $originalReflectionClass->isFinal()) {return false;}
		
		foreach ($this->replacingClassInfos as $replacingClassInfo)
		{
			if (isset($infos[$replacingClassInfo['name']]['path']))
			{
				require_once $infos[$replacingClassInfo['name']]['path'];
			}
			$reflectionClass = new Zend_Reflection_Class($replacingClassInfo['name']);
			if ($reflectionClass->getParentClass()->getName() != $originalReflectionClass->getName())
			{
				return false;
			}
		}
		return true;
	}

	public function __construct($originalClassInfo, $classInfos)
	{
		$this->originalClassInfo = $originalClassInfo;
		$this->replacingClassInfos = $classInfos;
	}

	/**
	 * This method performs the actual injection generating the necessary files for injection to work. It returns an array whose
	 * keys are the name of the generated classes and the values are arrays with :
	 *  - the key "path" containing the path of the file the original class was defined in 
	 *  - the key "mtime" containing the modification time of the above file
	 *  - optionnaly the key "link" which indicates the path of the generated class which will be later used to update RBS Change's autoload
	 *  
	 * @return Array
	 */
	public function generate()
	{
		$originalFileInfo = new SplFileInfo($this->originalClassInfo['path']);
		$originalFileContent = file_get_contents($originalFileInfo->getPathname());
		$originalClassName = $this->originalClassInfo['name'];
		if (strpos($originalFileInfo->getPathname(), 'build' . DIRECTORY_SEPARATOR . 'injection') !== false)
		{
			throw new Exception('Your autoload seems to be corrupted - please run ' . CHANGE_COMMAND . 'compile-autoload');
		}
		$suffixIndex =  0;
		$newClassName = $originalClassName . self::REPLACED_CLASS_SUFFIX . $suffixIndex;
		
		$infos = array();
		
		/**
		 * Process the file containing the injected class and rename it - all other classes defined in the same file will remain untouched but will be
		 * extracted in separate files
		 */ 		
		$classes = change_PhpCodeManipulation::processContentForInjection($originalFileContent, array($originalClassName => array('name' => $newClassName)));

		foreach ($classes as $className => $classContent)
		{
			$infos[$className]['path'] = $originalFileInfo->getPathname();
			$infos[$className]['mtime'] = $originalFileInfo->getMTime();
			$infos[$className]['checkmtime'] = true;
			$infos[$className]['link'] = f_util_FileUtils::buildProjectPath('build', 'injection', $className . '.class.php');
			if ($className !== $originalClassName)
			{
				f_util_FileUtils::writeAndCreateContainer($infos[$className]['link'], '<?php' . PHP_EOL . $classContent, f_util_FileUtils::OVERRIDE);
			}
		}
		
		// Process the combined file
		$combinedContent = array('<?php', $classes[$originalClassName]);
		
		foreach ($this->replacingClassInfos as $replacingClassInfo)
		{
			$suffixIndex++;
			$extendClassName = $newClassName;
			$newClassName = ($suffixIndex < count($this->replacingClassInfos)) ? $originalClassName . self::REPLACED_CLASS_SUFFIX . $suffixIndex : $originalClassName;
			
			$injectFileInfo = new SplFileInfo($replacingClassInfo['path']);
			$injectFileContent = file_get_contents($injectFileInfo->getPathname());
			$injectClassName = $replacingClassInfo['name'];
			if (strpos($injectFileInfo->getPathname(), 'build' . DIRECTORY_SEPARATOR . 'injection') !== false)
			{
				throw new Exception('Your autoload seems to be corrupted - please run ' . CHANGE_COMMAND . 'compile-autoload');
			}		
			$classes = change_PhpCodeManipulation::processContentForInjection($injectFileContent, array($injectClassName => array('name' => $newClassName, 'extends' => $extendClassName)));
			$combinedContent[] = $classes[$injectClassName];
			$combinedContent[] = 'class ' . $injectClassName . ' extends ' . $newClassName . ' {}';
			$infos[$injectClassName]['path'] = $injectFileInfo->getPathname();
			$infos[$injectClassName]['mtime'] = $injectFileInfo->getMTime();
			$infos[$injectClassName]['link'] = f_util_FileUtils::buildProjectPath('build', 'injection', $originalClassName . '.class.php');
			$infos[$injectClassName]['checkmtime'] = true;			
			f_util_FileUtils::writeAndCreateContainer(f_util_FileUtils::buildProjectPath('build', 'injection', $originalClassName . '.class.php'), implode(PHP_EOL . PHP_EOL, $combinedContent), f_util_FileUtils::OVERRIDE);
		}
		return $infos;
	}
}

class change_DocumentInjection
{
	private $originalModelName;
	private $replacingModelName;
	
	private function getReplacementsFilePath($modelName)
	{
		list ($moduleName, $docName) = explode('/', str_replace('modules_', '', $modelName));
		return f_util_FileUtils::buildProjectPath('build', 'injection', $moduleName , $docName . '.class.php');
	}
	
	private function getBuildClassesPathForModel($modelName)
	{
		list ($moduleName, $docName) = explode('/', str_replace('modules_', '', $modelName));
		return f_util_FileUtils::buildChangeBuildPath('modules', $moduleName, 'persistentdocument', $docName . 'model_and_base.class.php');
	}
	
	private function getFinalClassPathForModel($modelName)
	{
		list ($moduleName, $docName) = explode('/', str_replace('modules_', '', $modelName));
		return f_util_FileUtils::buildModulesPath( $moduleName, 'persistentdocument', $docName . '.class.php');
	}

	private function getFinalDocumentClassNameForModel($modelName)
	{
		list ($moduleName, $docName) = explode('/', str_replace('modules_', '', $modelName));
		return $moduleName.'_persistentdocument_' .$docName;
	}
	
	private function getBaseDocumentClassNameForModel($modelName)
	{
		list ($moduleName, $docName) = explode('/', str_replace('modules_', '', $modelName));
		return $moduleName.'_persistentdocument_' .$docName . 'base';
	}
	
	private function getI18nDocumentClassNameForModel($modelName)
	{
		list ($moduleName, $docName) = explode('/', str_replace('modules_', '', $modelName));
		return $moduleName.'_persistentdocument_' .$docName . 'I18n';
	}
	
	private function getModelClassNameForModel($modelName)
	{
		list ($moduleName, $docName) = explode('/', str_replace('modules_', '', $modelName));
		return $moduleName.'_persistentdocument_' .$docName . 'model';
	}

	public function __construct($originalModelName, $replacingModel)
	{
		$this->originalModelName = $originalModelName;
		$this->replacingModelName = $replacingModel;
	}

	public function isValid()
	{
		return true;
	}

	private function processInjectedModelBuildFile()
	{
		$originalI18nName = $this->getI18nDocumentClassNameForModel($this->originalModelName);
		$originalModelClassName = $this->getModelClassNameForModel($this->originalModelName);		
		$replacementInfos = array(
							$originalI18nName => array('name' => $originalI18nName . change_Injection::REPLACED_CLASS_SUFFIX), 
							$originalModelClassName => array('name' => $originalModelClassName . change_Injection::REPLACED_CLASS_SUFFIX)
							);
		return change_PhpCodeManipulation::processContentForInjection(file_get_contents($this->getBuildClassesPathForModel($this->originalModelName)), $replacementInfos);
	}
	
	private function processInjectingModelBuildFile()
	{
		$originalI18nName = $this->getI18nDocumentClassNameForModel($this->originalModelName);
		$originalModelClassName = $this->getModelClassNameForModel($this->originalModelName);
		$originalFinalName = $this->getFinalDocumentClassNameForModel($this->originalModelName);
		
		$replacingClassContent = file_get_contents($this->getBuildClassesPathForModel($this->replacingModelName));
		$replacingI18nName = $this->getI18nDocumentClassNameForModel($this->replacingModelName);
		$replacingBaseName = $this->getBaseDocumentClassNameForModel($this->replacingModelName);
		
		$replacingModelClassName = $this->getModelClassNameForModel($this->replacingModelName);
		$replacementInfos = array(
							$replacingI18nName => array('name' => $originalI18nName, 'extends' => $originalI18nName . change_Injection::REPLACED_CLASS_SUFFIX), 
							$replacingBaseName =>array('extends' => $originalFinalName . change_Injection::REPLACED_CLASS_SUFFIX), 
							$replacingModelClassName =>  array('name' => $originalModelClassName, 'extends' => $originalModelClassName . change_Injection::REPLACED_CLASS_SUFFIX), 
							);
							

		$replacingBuildClasses = change_PhpCodeManipulation::processContentForInjection($replacingClassContent, $replacementInfos);
		return $replacingBuildClasses;
	}
	
	/**
	 * 
	 */
	public function generate()
	{
		$infos = array();
		// This is the file we're gonna write all that's related to injecting generated classes 
		$generatedReplacementPath = $this->getReplacementsFilePath($this->originalModelName);
		// Process all the generated classes for the injected model
		$injectedBuildPath = $this->getBuildClassesPathForModel($this->originalModelName);
		$injectedBuildPathMtime = filemtime($injectedBuildPath);
		$injectedBuildClasses = $this->processInjectedModelBuildFile();
		foreach ($injectedBuildClasses as $className => $content)
		{
			$infos[$className] = array('path' => $injectedBuildPath, 'mtime' => $injectedBuildPathMtime, 'model' => $this->originalModelName, 'link' => $generatedReplacementPath);
		}
		
		// Rename the final class of the injected model
		$injectedFinalClassName = $this->getFinalDocumentClassNameForModel($this->originalModelName);
		$injectedFinalClassPath = $this->getFinalClassPathForModel($this->originalModelName);
		$injectedFinalClassPathMtime = filemtime($injectedFinalClassPath);
		
		$replacementInfos = array($injectedFinalClassName => array('name' => $injectedFinalClassName . change_Injection::REPLACED_CLASS_SUFFIX));
		$processedClasses = change_PhpCodeManipulation::processContentForInjection(file_get_contents($injectedFinalClassPath), $replacementInfos);

		if (count($processedClasses) != 1 || !isset($processedClasses[$injectedFinalClassName]))
		{
			throw new Exception('In order to inject ' . $this->originalModelName . ' the file ' . $injectedFinalClassPath . ' should only contain the declaration for class ' . $injectedFinalClassName);
		}

		$infos[$injectedFinalClassName] = array('path' => $injectedFinalClassPath, 'mtime' => $injectedFinalClassPathMtime, 'model' => $this->originalModelName, 'link' => $generatedReplacementPath);
		$injectedBuildClasses = array_merge($injectedBuildClasses, $processedClasses);

		// Process all the generated classes for the injecting model
		$injectingBuildPath =  $this->getBuildClassesPathForModel($this->replacingModelName);
		$injectingBuildPathMtime = filemtime($injectingBuildPath);
		$injectingBuildClasses = $this->processInjectingModelBuildFile();
		foreach ($injectingBuildClasses as $className => $content)
		{
			$infos[$className] = array('path' => $injectingBuildPath, 'mtime' => $injectingBuildPathMtime, 'model' => $this->replacingModelName, 'link' => $generatedReplacementPath);
		}
		// Add empty classes
		$injectingBuildClasses[] =  'class ' . $this->getModelClassNameForModel($this->replacingModelName) . ' extends ' . $this->getModelClassNameForModel($this->originalModelName) . ' {}';
		$injectingBuildClasses[] =  'class ' . $this->getI18nDocumentClassNameForModel($this->replacingModelName) . ' extends ' . $this->getI18nDocumentClassNameForModel($this->originalModelName) . ' {}';
		

		// Rename the final class of the injecting model
		$injectingFinalClassName = $this->getFinalDocumentClassNameForModel($this->replacingModelName);
		$injectingFinalClassPath = $this->getFinalClassPathForModel($this->replacingModelName);
		$injectingFinalClassPathMtime = filemtime($injectingFinalClassPath);
		
		$replacementInfos = array($injectingFinalClassName => array('name' => $injectedFinalClassName));
		$processedClasses = change_PhpCodeManipulation::processContentForInjection(file_get_contents($injectingFinalClassPath), $replacementInfos);
		// $processedClasses should now contain exactly one entry
		if (count($processedClasses) != 1 || !isset($processedClasses[$injectingFinalClassName]))
		{
			throw new Exception('In order to inject ' . $this->replacingModelName . ' the file ' . $injectingFinalClassPath . ' should only contain the declaration for class ' . $injectingFinalClassName);
		}
		$infos[$injectingFinalClassName]  = array('path' => $injectingFinalClassPath, 'mtime' => $injectingFinalClassPathMtime, 'model' => $this->replacingModelName, 'link' => $generatedReplacementPath ,'checkmtime' => true);
		$injectingBuildClasses = array_merge($injectingBuildClasses, $processedClasses);
		// Write everything replacing final
		f_util_FileUtils::writeAndCreateContainer($generatedReplacementPath,'<?php' . PHP_EOL  . implode(PHP_EOL.PHP_EOL, array_merge($injectedBuildClasses, $injectingBuildClasses)), f_util_FileUtils::OVERRIDE);		
		return $infos;
	}
}

class change_PhpCodeManipulation
{

	/**
	 * @param string $source
	 * @param Array $replacementInfos
	 */
	public static function processContentForInjection($source, $replacementInfos)
	{
		$inBrace = 0;
		$inClass = false;
		$classes = array();
		$currentClassContent = array();
		$currentClassName = null;
		$currentClassExtends = null;
		$classDeclaration = false;
		$currentClassComment = null;
		$tokens = token_get_all($source);
		$size = count($tokens);
		for ($index = 0; $index < $size; $index ++)
		{
			$token = $tokens[$index];
			if (! is_array($token))
			{
				switch ($token)
				{
					case '{':
						if ($classDeclaration && $inBrace == 0)
						{
							$classDeclaration = false;
							$inClass = true;
						}
						$inBrace ++;
						break;
					case '}':
						$inBrace --;
						break;
				}
				if ($inClass || $classDeclaration)
				{
					$currentClassContent[] = $token;
					if ($inClass && $inBrace == 0)
					{
						$inClass = false;
						$classes[$currentClassName] = implode('', $currentClassContent);
						$currentClassContent = array();
						$currentClassName = null;
						$currentClassExtends = null;
					}
				}
			}
			else
			{
				switch ($token[0])
				{
					case T_DOC_COMMENT:
						if (! $inClass)
						{
							$currentClassComment = $token[1];
						}
						break;
					case T_ABSTRACT:
						if (! $inClass)
						{
							$classDeclaration = true;
						}
						break;
					case T_CLASS:
					case T_INTERFACE:
						$classDeclaration = true;
						$currentClassName = $tokens[$index + 2][1];
						if (isset($replacementInfos[$currentClassName]) && isset($replacementInfos[$currentClassName]['name']))
						{
							$tokens[$index + 2][1] = $replacementInfos[$currentClassName]['name'];
						}
						break;
					case T_EXTENDS:
						$hasExtend = true;
						if (isset($replacementInfos[$currentClassName]) && isset($replacementInfos[$currentClassName]['extends']))
						{
							$tokens[$index + 2][1] = $replacementInfos[$currentClassName]['extends'];
						}
						break;
				}
				if ($classDeclaration || $inClass)
				{
					if ($currentClassComment)
					{
						$currentClassContent[] = $currentClassComment;
						$currentClassContent[] = PHP_EOL;
						$currentClassComment = null;
						
					}
					$currentClassContent[] = $token[1];
				}
			}
		}
		return $classes;
	}
}