<?php
class f_AOP
{
	/**
	 * @var String[]
	 */
	private $classDirectories = array();

	/**
	 * @var String[]
	 */
	private $alteredFiles = array();
	private $lags = array();
	/**
	 * @var array<String, Integer>
	 */
	private $alteredCount = array();

	/**
	 * @var array<String, array<String, String[]>>
	 */
	private $alterations;
	/**
	 * @var Integer
	 */
	private $alterationsDefTime;

	/**
	 * @param String $className
	 * @param String $methodName
	 * @param String $adviceName
	 * @param String $adviceMethodName
	 * @return String
	 */
	function applyAfterReturningAdvice($className, $methodName, $adviceName, $adviceMethodName, $parameters = null)
	{
		// echo __METHOD__."\n";
		return $this->applyAdvice($className, $methodName, $adviceName, $adviceMethodName, 'advice_after-returning', array("adviceParameters" => $parameters));
	}

	/**
	 * @param String $className
	 * @param String $methodName
	 * @param String $adviceName
	 * @param String $adviceMethodName
	 * @param String $exceptionToCatch
	 * @return String
	 */
	function applyAfterThrowingAdvice($className, $methodName, $adviceName, $adviceMethodName, $exceptionToCatch = "Exception", $parameters = null)
	{
		//echo __METHOD__." $exceptionToCatch\n";
		$params = array("exceptionToCatch" => $exceptionToCatch);
		if ($parameters !== null)
		{
			$params = array_merge($params, array("adviceParameters" => $parameters));
		}
		return $this->applyAdvice($className, $methodName, $adviceName, $adviceMethodName, 'advice_after-throwing', $params);
	}

	/**
	 * @param String $className
	 * @param String $methodName
	 * @param String $adviceName
	 * @param String $adviceMethodName
	 * @return String
	 */
	function applyBeforeAdvice($className, $methodName, $adviceName, $adviceMethodName, $parameters = null)
	{
		// echo __METHOD__."\n";
		return $this->applyAdvice($className, $methodName, $adviceName, $adviceMethodName, 'advice_before', array("adviceParameters" => $parameters));
	}

	/**
	 * @param String $className
	 * @param String $methodName
	 * @param String $adviceName
	 * @param String $adviceMethodName
	 * @return String
	 */
	function applyAfterAdvice($className, $methodName, $adviceName, $adviceMethodName, $parameters = null)
	{
		// echo __METHOD__."\n";
		return $this->applyAdvice($className, $methodName, $adviceName, $adviceMethodName, 'advice_after', array("adviceParameters" => $parameters));
	}

	/**
	 * @param String $className
	 * @param String $methodName
	 * @param String $adviceName
	 * @param String $adviceMethodName
	 * @return String
	 */
	function applyAroundAdvice($className, $methodName, $adviceName, $adviceMethodName, $parameters = null)
	{
		//echo __METHOD__."\n";
		list($beforeAdviceCode, ) = $this->getMethodCode($adviceName, "before".ucfirst($adviceMethodName), false);
		$params = array("beforeAdviceCode" => $beforeAdviceCode);
		if ($parameters !== null)
		{
			$params = array_merge($params, array("adviceParameters" => $parameters));
		}
		return $this->applyAdvice($className, $methodName, $adviceName, $adviceMethodName, 'advice_around', $params);
	}

	private function renameClass($className, $newClassName)
	{
		list($tokens, $fileName) = $this->getTokens($className);
		$tokenCount = count($tokens);
		foreach ($tokens as $index => $token)
		{
			if ($token[0] == T_CLASS && $tokens[$index+2][1] == $className)
			{
				$tokens[$index+2][1] = $newClassName;
				break;
			}
		}
		return $this->tokensToString($tokens);
	}

	function renameParentClass($className, $newParentClassName)
	{
		list($tokens, $fileName) = $this->getTokens($className);
		$tokenCount = count($tokens);
		foreach ($tokens as $index => $token)
		{
			if ($token[0] == T_CLASS && $tokens[$index+2][1] == $className)
			{
				for ($i = $index; $i < $tokenCount; $i++)
				{
					if ($tokens[$i][0] == T_EXTENDS)
					{
						$tokens[$i+2][1] = $newParentClassName;
						break 2;
					}
				}
			}
		}
		return $this->tokensToString($tokens);
	}

	private function tokensToString($tokens)
	{
		ob_start();
		foreach ($tokens as $token)
		{
			$token_data = is_array($token) ? $token[1] : $token;
			echo $token_data;
		}
		return trim(ob_get_clean());
	}

	private function getTokens($className)
	{
		$path = ClassResolver::getInstance()->getRessourcePath($className);
		if ($path === null)
		{
			throw new Exception(__METHOD__." could not find $className definition file");
		}
		if (isset($this->alteredFiles[$path]))
		{
			$tokens = token_get_all(join("\n", $this->alteredFiles[$path]));
		}
		else
		{
			$tokens = token_get_all(f_util_FileUtils::read($path));
		}
		return array($tokens, $path);
	}

	/**
	 * @param String $className
	 * @param String $replacerClassName
	 * @return String
	 */
	function replaceClass($className, $replacerClassName)
	{
		//echo "Replacer $className => $replacerClassName\n";
		// you can only replace a class with a subclass of it

		// check parent-child relationship
		$this->getMethod($className, "__construct");
		$parentName = $this->getParentName($replacerClassName, false);
		while ($parentName !== null && $parentName !== $className)
		{
			$parents[] = $parentName;
			$parentName = $this->getParentName($parentName, false);
		}
		if ($parentName !== $className)
		{
			throw new Exception($replacerClassName." is not a subclass of ".$className);
		}

		// verify constructor compatibility
		// TODO: re-check constructors (was simplier when using Reflection API ... :( )
		/*
		list($dummy, $replacerConstruct) = $this->getMethodCode($replacerClassName, "__construct");
		$i = 0;
		while ($replacerConstruct === null && $i < count($parent[$i]) - 2)
		{
		list($dummy, $replacerConstruct) = $this->getMethodCode($className, "__construct");
		$i++;
		}
		if ($replacerConstruct !== null)
		{
		// Construct was specialized
		list($dummy, $originalConstructor) = $this->getMethodCode($replacerClassName, "__construct");
			
			
		//if ($class->getConstructor()->getNumberOfParameters() > $replacer->getConstructor()->getNumberOfParameters()
		// || $replacer->getConstructor()->getNumberOfRequiredParameters() > $class->getConstructor()->getNumberOfRequiredParameters())
		//{
		//	throw new Exception($replacer->getName()." is not compatible with ".$class->getName()." constructor. Please fix ".$replacer->getName()." constructor parameters");
		//	}
		}
		*/

		list($classCode, $class) = $this->getMethodCode($className, null);
		list($replacerCode, $replacer) = $this->getMethodCode($replacerClassName, null);

		if (!isset($this->alteredCount[$className]))
		{
			$this->alteredCount[$className] = 0;
		}

		$replacedCount = $this->alteredCount[$className];
		$replacedClassName = $className."_replaced".$replacedCount;
		$newCode = $this->renameClass($className, $replacedClassName);
		$this->alteredCount[$className]++;
		$aopPath = ClassResolver::getInstance()->getAOPPath($replacedClassName);
		f_util_FileUtils::writeAndCreateContainer($aopPath, $newCode, f_util_FileUtils::OVERRIDE);
		ClassResolver::getInstance()->appendToAutoloadFile($replacedClassName, $aopPath);

		ob_start();
		echo "<?php\n";
		echo $this->getMethodModifiers($class);
		echo " class ";
		echo $className;
		echo " extends ";
		$replacerParentName = $this->getParentName($replacerClassName);
		if ($replacerParentName == $className)
		{
			echo $replacedClassName;
		}
		else
		{
			echo $replacerParentName;
		}

		echo "\n";
		$replacerCode = $this->getReflectionObjCode($replacer);
		$openingBracketIndex = strpos($replacerCode, "{");
		$closingBracketIndex = strrpos($replacerCode, "}");
		echo substr($replacerCode, $openingBracketIndex, $closingBracketIndex-$openingBracketIndex+1);
		echo "\n";

		$newReplacerCode = trim(ob_get_clean());

		// end echos
		return $newReplacerCode;
	}


	/**
	 * @param String $className
	 * @return array<String, String[]>
	 * @example return array(
	 array("f_aop_samples_ArroundAdvice", "applyAroundAdvice", $className, "getInstance", "f_aop_samples_ArroundAdvice", "save"),
	 array("f_aop_samples_ArroundAdvice", "applyAroundAdvice", $className, "createQuery", "f_aop_samples_ArroundAdvice", "save")
	 );
	 */
	function getAlterationsByClassName($className)
	{
		$this->loadAlterations();
		if (isset($this->alterations[$className]))
		{
			return $this->alterations[$className];
		}
		//echo "No alteration for $className\n";
		return null;
	}

	/**
	 * @param String $originalClassName
	 * @param String $replacerClassName
	 */
	function addReplaceClassAlteration($originalClassName, $replacerClassName)
	{
		$this->loadAlterations();
		try
		{
			$firstChildName = $this->findFirstChild($originalClassName, $replacerClassName);
			$newClassName = $originalClassName."_replaced".($this->getAlterationDefCount($originalClassName));
			$this->addAlteration($originalClassName, array("", "replaceClass", $originalClassName, $replacerClassName));
			if ($firstChildName != $replacerClassName)
			{
				$this->addAlteration($firstChildName, array("", "renameParentClass", $firstChildName, $newClassName));
			}
		}
		catch (Exception $e)
		{
			// The only known case of an exception here is classes not available
		}
	}

	/**
	 * @param String $originalClassName
	 * @param array $alteration
	 */
	private function addAlteration($originalClassName, $alteration)
	{
		if (!isset($this->alterations[$originalClassName]))
		{
			$this->alterations[$originalClassName] = array();
		}
		$this->alterations[$originalClassName][] = $alteration;
	}

	private function getAlterationDefCount($className)
	{
		if (!isset($this->alterations[$className]))
		{
			return 0;
		}
		return count($this->alterations[$className]);
	}

	private function loadAlterations()
	{
		if ($this->alterations === null)
		{
			$aopConfigFile = f_util_FileUtils::buildWebeditPath("config", "aop.xml");
			if (file_exists($aopConfigFile))
			{
				$doc = new DOMDocument();
				if ($doc->load($aopConfigFile) === false)
				{
					throw new Exception("Could not load XML file ".$aopConfigFile);
				}
				// load pointcuts
				$pointcuts = array();
				foreach ($doc->documentElement->getElementsByTagName("pointcut") as $pointcutElem)
				{
					$pointcuts[$pointcutElem->getAttribute("id")] = $pointcutElem->getAttribute("expression");
				}

				// load advices
				$alterations = array();
				$adviceNames = array("before" => "applyBeforeAdvice", "after-returning" => "applyAfterReturningAdvice", "after-throwing" => "applyAfterThrowingAdvice", "after" => "applyAfterAdvice", "around" => "applyAroundAdvice");
				foreach ($doc->documentElement->childNodes as $childNode)
				{
					if ($childNode->nodeType === XML_ELEMENT_NODE)
					{
						$tagName = $childNode->tagName;
						if (array_key_exists($tagName, $adviceNames))
						{
							$aopMethod = $adviceNames[$tagName];
							$adviceClass = $childNode->getAttribute("class");
							$adviceMethod = $childNode->getAttribute("method");
							// pointcut
							if ($childNode->hasAttribute("pointcut"))
							{
								$pointcut = $childNode->getAttribute("pointcut");
							}
							elseif ($childNode->hasAttribute("pointcut-ref"))
							{
								$pointcutRef = $childNode->hasAttribute("pointcut-ref");
								if (!isset($pointcuts[$pointcutRef]))
								{
									throw new Exception("Unknown pointcut ".$pointcutRef);
								}
								$pointcut = $pointcuts[$pointcutRef];
							}
							else
							{
								throw new Exception("An advice config element must have pointcut or pointcut-ref attribute defined");
							}
							list($pointcutClass, $pointcutMethod) = explode("::", $pointcut);
							$alteration = array($adviceClass, $aopMethod, $pointcutClass, $pointcutMethod, $adviceClass, $adviceMethod);
							if ($tagName === "after-throwing" && $childNode->hasAttribute("exception"))
							{
								$alteration[] =	$childNode->getAttribute("exception");
							}

							if ($childNode->hasAttribute("parameters"))
							{
								// "message: $this->__toString()"
								$parameters = array();
								foreach (explode(";", $childNode->getAttribute("parameters")) as $paramDef)
								{
									$index = strpos($paramDef, ":");
									if ($index === false)
									{
										throw new Exception("Advice parameters not well formed");
									}
									$paramName = trim(substr($paramDef, 0, $index));
									$paramValue = trim(substr($paramDef, $index+1));
									$parameters[$paramName] = $paramValue;
								}
								$alteration[] = $parameters;
							}

							if (!isset($alterations[$pointcutClass]))
							{
								$alterations[$pointcutClass] = array();
							}
							$alterations[$pointcutClass][] = $alteration;
						}
						elseif ($tagName === "replace")
						{
							// pointcut TODO: refactor
							if ($childNode->hasAttribute("pointcut"))
							{
								$pointcut = $childNode->getAttribute("pointcut");
							}
							elseif ($childNode->hasAttribute("pointcut-ref"))
							{
								$pointcutRef = $childNode->hasAttribute("pointcut-ref");
								if (!isset($pointcuts[$pointcutRef]))
								{
									throw new Exception("Unknown pointcut ".$pointcutRef);
								}
								$pointcut = $pointcuts[$pointcutRef];
							}
							else
							{
								throw new Exception("An advice config element must have pointcut or pointcut-ref attribute defined");
							}

							// class
							if (!$childNode->hasAttribute("class"))
							{
								throw new Exception("A replace config element must have a class attribute");
							}
							$replacerClassName = $childNode->getAttribute("class");
							$originalClassName = $pointcut;

							// TODO: first argument stinks...
							$alteration = array("", "replaceClass", $originalClassName, $replacerClassName);

							// TODO: refactor
							if (!isset($alterations[$originalClassName]))
							{
								$alterations[$originalClassName] = array();
							}
							$firstChildName = $this->findFirstChild($originalClassName, $replacerClassName);
							if ($firstChildName != $replacerClassName)
							{
								if (!isset($alterations[$firstChildName]))
								{
									$alterations[$firstChildName] = array();
								}
								$newParentClassName = $originalClassName."_replaced".count($alterations[$originalClassName]);
								$alterations[$firstChildName][] = array("", "renameParentClass", $firstChildName, $newParentClassName);
							}
							$alterations[$originalClassName][] = $alteration;
						}
						elseif ($tagName === "pointcut")
						{
							// nothing
						}
						else
						{
							throw new Exception("Unknown aop config element ".$tagName." in ".$aopConfigFile);
						}
					}
				}

				$this->alterations = $alterations;
				$this->alterationsDefTime = filemtime($aopConfigFile);
			}
			else
			{
				$this->alterations = array();
			}
		}
	}

	private function findFirstChild($originalClassName, $replacerClassName)
	{
		//echo "Find first child $originalClassName $replacerClassName\n";
		$firstChild = $replacerClassName;
		$parentName = $this->getParentName($firstChild);
		while ($parentName != $originalClassName)
		{
			$firstChild = $parentName;
			$parentName = $this->getParentName($firstChild);
		}
		//echo "First child : $firstChild\n";
		return $firstChild;
	}

	private function getParentName($className, $strict = true)
	{
		$path = ClassResolver::getInstance()->getRessourcePath($className);
		if ($path === null)
		{
			throw new Exception(__METHOD__." could not find $className definition file");
		}
		$tokenArray = token_get_all(file_get_contents($path));
		$tokenArrayCount = count($tokenArray);
		foreach ($tokenArray as $index => $token)
		{
			if ($token[0] == T_CLASS && $tokenArray[$index+2][1] == $className)
			{
				for ($i = $index; $i < $tokenArrayCount; $i++)
				{
					if ($tokenArray[$i][0] == T_EXTENDS)
					{
						$parentName = $tokenArray[$i+2][1];
						return $parentName;
					}
				}
			}
		}
		if ($strict)
		{
			throw new Exception("Could not find $className parent in $path");
		}
		return null;
	}

	/**
	 * N.B.: Must be called before getAlterations()
	 * @return Integer
	 */
	function getAlterationsDefTime()
	{
		return $this->alterationsDefTime;
	}

	/**
	 * Apply the given alterations for a given className an
	 * returns the associated alterated file content
	 * @param String[][]$alterations
	 * @return String
	 */
	function applyAlterations($alterations)
	{
		$className = null;
		$fileName = null;
		foreach ($alterations as $alteration)
		{
			$method = $alteration[1];
			if ($className === null)
			{
				$className = $alteration[2];
				$fileName = ClassResolver::getInstance()->getRessourcePath($className);
			}
			elseif ($className != $alteration[2])
			{
				$otherFileName = ClassResolver::getInstance()->getRessourcePath($alteration[2]);
				if ($otherFileName != $fileName)
				{
					throw new Exception($alteration[2]." is not defined in the same file as ".$className);
				}
			}
			$args = array_slice($alteration, 2);
			$code = @call_user_func_array(array($this, $method), $args);
			//echo "******************\n$code\n****************\n";
		}
		return $code;
	}

	/**
	 * Get all defined alterations, grouped by className
	 * @return array<String, String[][]>
	 */
	function getAlterations()
	{
		$this->loadAlterations();
		return $this->alterations;
	}

	/**
	 * @return Boolean
	 */
	function hasAlterations()
	{
		$this->loadAlterations();
		return count($this->alterations) > 0;
	}

	/**
	 * @param String $directory
	 * @return void
	 */
	function addDirectory($directory)
	{
		$this->classDirectories[] = $directory;
	}

	// private methods

	/**
	 * @param String $fileName
	 * @return String[]
	 */
	private function getFileNameLines($fileName)
	{
		if (isset($this->alteredFiles[$fileName]))
		{
			return $this->alteredFiles[$fileName];
		}
		$lines = file($fileName, FILE_IGNORE_NEW_LINES);
		if ($lines === false)
		{
			// this should not happen
			throw new Exception("Could not read ".$fileName." file");
		}
		$this->alteredFiles[$fileName] = $lines;
		return $lines;
	}

	/**
	 * @param String $className
	 * @param String $methodName
	 * @param String $adviceName
	 * @param String $adviceMethodName
	 * @param String $templateName
	 * @param array<String, String> $additionalParameters
	 * @return String
	 */
	private function applyAdvice($className, $methodName, $adviceName, $adviceMethodName, $templateName, $additionalParameters = null)
	{
		//echo __METHOD__." $className, $methodName, $adviceName, $adviceMethodName, $templateName, ".var_export($additionalParameters, true)."\n";
		$classFileName = ClassResolver::getInstance()->getRessourcePath($className);
		list($adviceCode, ) = $this->getMethodCode($adviceName, $adviceMethodName, false);
		list($originalCode, $originalMethod) = $this->getMethodCode($className, $methodName);

		$originalMethodModifiers = $this->getMethodModifiers($originalMethod);
		$originalMethodName = $originalMethod->getName();

		$originalParametersArray = array();
		foreach ($originalMethod->getParameters() as $parameter)
		{
			$originalParametersArray[] = $parameter->__toString();
		}
		$originalParameters = join(", ", $originalParametersArray);

		$openingBracketIndex = strpos($originalCode, "{");
		$closingBracketIndex = strrpos($originalCode, "}");
		$originalMethodBody = trim(substr($originalCode, $openingBracketIndex+1, $closingBracketIndex-$openingBracketIndex-1));

		$originalCallOp = ($originalMethod->isStatic()) ? 'self::' : '$this->';
		$originalStatic = ($originalMethod->isStatic()) ? 'static ' : '';

		// construct $originalParametersCall
		ob_start();
		$params = $originalMethod->getParameters();
		$paramsCount = count($params);
		for ($i = 0; $i < $paramsCount; $i++)
		{
			if ($i > 0) echo ', ';
			echo '$';
			$param = $params[$i];
			echo $param->getName();
		}
		$originalParametersCall = ob_get_clean();

		$adviceParameters = "";
		// some additional parameters could be there
		if ($additionalParameters !== null)
		{
			foreach ($additionalParameters as $paramName => $paramValue)
			{
				if ($paramName === "adviceParameters")
				{
					foreach ($paramValue as $adviceParam => $adviceValue)
					{
						$adviceParameters .= '$'.$adviceParam.' = '.$adviceValue.";\n";
					}
				}
				else
				{
					$$paramName = $paramValue;
				}
			}
		}

		$fullMethodName = $className."::".$methodName;
		if (!isset($this->alteredCount[$fullMethodName]))
		{
			$this->alteredCount[$fullMethodName] = 0;
		}
		$replacedCount = $this->alteredCount[$fullMethodName];

		// call the template
		ob_start();
		require("templates/".$templateName.".php");
		$newLines = explode("\n", ob_get_clean());

		// replace the original method lines
		$lines = $this->replaceCode($classFileName, $originalMethod->getStartLine(), $originalMethod->getEndLine(), $newLines);
		$this->alteredCount[$fullMethodName]++;
		return join("\n", $lines);
	}

	/**
	 * @param ReflectionMethod $method
	 * @return String
	 */
	private function getMethodModifiers($method)
	{
		return join(" ", Reflection::getModifierNames($method->getModifiers()));
	}

	private $methods = array();
	private function getMethod($className, $methodName)
	{
		if ($methodName === null)
		{
			$fullMethodName = $className;
		}
		else
		{
			$fullMethodName = $className."::".$methodName;
		}
		if (isset($this->methods[$fullMethodName]))
		{
			return $this->methods[$fullMethodName];
		}
		return null;
	}

	/**
	 * @param String $className
	 * @param String $methodName
	 * @param Boolean $withDeclaration
	 * @return mixed[] (String code, <ReflectionMethod> $method)
	 */
	function getMethodCode($className, $methodName, $withDeclaration = true)
	{
		// echo __METHOD__." $className, $methodName, ".var_export($withDeclaration, true)."\n";

		$method = $this->getMethod($className, $methodName);
		if ($method !== null)
		{
			$code = $this->getReflectionObjCode($method);
			if ($withDeclaration) return array($code, $method);
			$startIndex = strpos($code, "{");
			$endIndex = strrpos($code, "}");
			return array(trim(substr($code, $startIndex+1, $endIndex-$startIndex-1)), $method);
		}

		list($tokens, $fileName) = $this->getTokens($className);
		$startIndex = 1;
		$inClass = false;
		$inMethod = false;
		$bracketLevel = 0;
		$methodIndexStart = null;
		$line = 1;
		foreach ($tokens as $index => $token)
		{
			$token_data = is_array($token) ? $token[1] : $token;
			// Split the data up by newlines
			$split_data = preg_split('#(\r\n|\n)#', $token_data, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			foreach ($split_data as $data)
			{
				if ($data == "\r\n" || $data == "\n")
				{
					$line++;
				}
			}

			if ($token[0] == T_CLASS && $tokens[$index+2][1] == $className)
			{
				$inClass = true;
				$class = new f_aop_ReflectionClass($className);
				$class->fileName = $fileName;
				$class->startLine = $line;

				// modifiers
				$i = $index-1;
				while ($i > 0)
				{
					switch ($tokens[$i][0])
					{
						case T_FINAL:
							$class->addModifier(ReflectionMethod::IS_FINAL);
							break;
						case T_ABSTRACT:
							$class->addModifier(ReflectionMethod::IS_ABSTRACT);
							break;
						case T_WHITESPACE:
							break;
						default:
							break 2;
					}
					$i--;
				}

				$i = $index+3;
				while ($tokens[$i] != "{")
				{
					$i++;

				}
				$classIndexStart = $i;
				$classBracketLevel = $bracketLevel;
			}
			else
			{
				if ($inClass)
				{
					if (is_array($token))
					{
						//
						if ($token[0] == T_FUNCTION && $tokens[$index+2][1] == $methodName)
						{
							$inMethod = true;
							$methodIndexStart = $index;
							$method = new f_AOP_ReflectionMethod($methodName);
							$method->fileName = $fileName;
							$method->startLine = $line;

							$i = $index-1;
							while ($i > 0)
							{
								switch ($tokens[$i][0])
								{
									case T_PRIVATE:
										$method->addModifier(ReflectionMethod::IS_PRIVATE);
										break;
									case T_PUBLIC:
										$method->addModifier(ReflectionMethod::IS_PUBLIC);
										break;
									case T_PROTECTED:
										$method->addModifier(ReflectionMethod::IS_PROTECTED);
										break;
									case T_STATIC:
										$method->addModifier(ReflectionMethod::IS_STATIC);
										break;
									case T_FINAL:
										$method->addModifier(ReflectionMethod::IS_FINAL);
										break;
									case T_ABSTRACT:
										$method->addModifier(ReflectionMethod::IS_ABSTRACT);
										break;
									case T_WHITESPACE:
										break;
									default:
										break 2;
								}
								$i--;
							}

							if ($withDeclaration)
							{
								$methodIndexStart = $i+1;
							}

							$i = $index+3;
							while ($tokens[$i] != "{")
							{
								if ($tokens[$i][0] == T_VARIABLE)
								{
									$param = new f_AOP_ReflectionParameter(substr($tokens[$i][1], 1));
									$method->addParameter($param);
									$j = $i-1;
									$paramPrefix = null;
									while ($tokens[$j] != "," && $tokens[$j] != "(")
									{
										$valueStr = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
										$paramPrefix = $valueStr.$paramPrefix;
										$j--;
									}
									if ($paramPrefix == "array")
									{
										$param->isArray = true;
									}
									elseif ($paramPrefix !== null)
									{
										$param->class = trim($paramPrefix);
										if ($param->class == "")
										{
											$param->class = null;
										}
									}
								}
								elseif ($tokens[$i] == "=")
								{
									$valueStr = "";
									$j = $i+1;
									$parenthesisLevel = 0;
									while ($parenthesisLevel >= 0 && $tokens[$j] != ",")
									{
										if ($tokens[$j] == ")")
										{
											$parenthesisLevel--;
										}
										else if($tokens[$j] == "(")
										{
											$parenthesisLevel++;
										}
										if ($parenthesisLevel >= 0)
										{
											$valueStr .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
										}
										else
										{
											break;
										}
										$j++;
									}

									$param->defaultValue = trim($valueStr);
								}
								$i++;
							}

							if (!$withDeclaration)
							{
								$methodIndexStart = $i+1;
							}

							$methodBracketLevel = $bracketLevel;
						}
					}
					else
					{
						if ($token == "{")
						{
							$bracketLevel++;
						}
						elseif ($token == "}")
						{
							$bracketLevel--;
							if ($inMethod && $bracketLevel == $methodBracketLevel)
							{
								if ($withDeclaration)
								{
									$methodIndexEnd = $index+1;
								}
								else
								{
									$methodIndexEnd = $index-1;
								}
								$inMethod = false;
								$method->endLine = $line;

							}
							if ($inClass && $bracketLevel == $classBracketLevel)
							{
								$inClass = false;
								$classIndexEnd = $index;
								$class->endLine = $line;
							}
						}
					}
				}
			}
		}


		if ($methodName === null)
		{
			$fullMethodName = $className;
			$this->methods[$fullMethodName] = $class;
			return array($this->tokensToString(array_slice($tokens, $classIndexStart, $classIndexEnd-$classIndexStart)), $class);
		}
		$fullMethodName = $className."::".$methodName;
		$this->methods[$fullMethodName] = $method;
		return array($this->tokensToString(array_slice($tokens, $methodIndexStart, $methodIndexEnd-$methodIndexStart)), $method);
	}

	/**
	 * @param $obj
	 * @return String
	 */
	private function getReflectionObjCode($reflectionObj)
	{
		return $this->getCode($reflectionObj->getFileName(), $reflectionObj->getStartLine(), $reflectionObj->getEndLine());
	}

	/**
	 * @param String $fileName
	 * @param Integer $start
	 * @param Integer $end
	 * @return String
	 */
	private function getCode($fileName, $startLine, $endLine)
	{
		$lines = $this->getFileNameLines($fileName);
		list($start, $length) = $this->computeStartLength($fileName, $startLine, $endLine);
		return join("\n", array_slice($lines, $start, $length));
	}

	private function computeStartLength($fileName, $startLine, $endLine)
	{
		$start = $startLine-1;
		$length = $endLine-$startLine+1;

		//echo "OLD: ".$start." ".$length."\n";
		if (isset($this->lags[$fileName]))
		{
			foreach ($this->lags[$fileName] as $lag)
			{
				list($lagStart, $oldLagLength, $newLagLength) = $lag;
				//echo "LAG: $lagStart, $oldLagLength, $newLagLength\n";
				if ($startLine > $lagStart+1)
				{
					//echo "BLA $start ";
					$start += ($newLagLength - $oldLagLength);
					//echo "=> $start\n";
				}
				if ($startLine == $lagStart+1)
				{
					//echo "BLI $length ";
					// we there assume that all ends with "}"...
					$length = $newLagLength;
					//echo "=> $length\n";
				}
			}
			//echo "NEW: ".$start." ".$length."\n";
		}

		return array($start, $length);
	}

	/**
	 * @param String $fileName
	 * @param Integer $start
	 * @param Integer $end
	 * @param String[] $newLines
	 * @return String[]
	 */
	private function replaceCode($fileName, $startLine, $endLine, $newLines)
	{
		// echo "ReplaceCode $fileName, $startLine, $endLine: \n==============".join("\n", $newLines)."==============\n";
		$lines = $this->getFileNameLines($fileName);
		list($start, $length) = $this->computeStartLength($fileName, $startLine, $endLine);
		array_splice($lines, $start, $length, $newLines);
		$this->alteredFiles[$fileName] = $lines;
		if (!isset($this->lags[$fileName]))
		{
			$this->lags[$fileName] = array();
		}
		$this->lags[$fileName][] = array($startLine-1, $length, count($newLines));
		return $lines;
	}
}

class f_AOP_ReflectionClass
{
	private $name;
	public $startLine;
	public $endLine;
	public $fileName;
	private $modifiers = 0;

	function __construct($name)
	{
		$this->name = $name;
	}

	function getName()
	{
		return $this->name;
	}

	function getFileName()
	{
		return $this->fileName;
	}

	function getStartLine()
	{
		return $this->startLine;
	}

	function getEndLine()
	{
		return $this->endLine;
	}

	function addModifier($modifier)
	{
		$this->modifiers += $modifier;
	}

	function getModifiers()
	{
		return $this->modifiers;
	}
}

class f_AOP_ReflectionMethod
{
	private $name;
	private $modifiers = 0;
	private $parameters = array();
	public $startLine;
	public $endLine;
	public $fileName;

	function __construct($name)
	{
		$this->name = $name;
	}

	function getName()
	{
		return $this->name;
	}

	function addModifier($modifier)
	{
		$this->modifiers += $modifier;
	}

	function getModifiers()
	{
		return $this->modifiers;
	}

	function addParameter($parameter)
	{
		$parameter->position = count($this->parameters);
		$this->parameters[] = $parameter;
	}

	function getParameters()
	{
		return $this->parameters;
	}

	function getFileName()
	{
		return $this->fileName;
	}

	function getStartLine()
	{
		return $this->startLine;
	}

	function getEndLine()
	{
		return $this->endLine;
	}

	public function isAbstract  ()
	{
		return ($this->modifiers & ReflectionMethod::IS_ABSTRACT) !== 0;
	}
	public function isConstructor ()
	{
		return $this->name == "__construct";
	}
	public function isDestructor ()
	{
		return $this->name == "__destruct";
	}
	public function isFinal ()
	{
		return ($this->modifiers & ReflectionMethod::IS_FINAL) !== 0;
	}
	public function isPrivate ()
	{
		return ($this->modifiers & ReflectionMethod::IS_PRIVATE) !== 0;
	}
	public function isProtected ()
	{
		return ($this->modifiers & ReflectionMethod::IS_PROTECTED) !== 0;
	}
	public function isPublic ()
	{
		return ($this->modifiers & ReflectionMethod::IS_PUBLIC) !== 0 || $this->modifiers < 512;
	}
	public function isStatic ()
	{
		return ($this->modifiers & ReflectionMethod::IS_STATIC) !== 0;
	}
}

class f_AOP_ReflectionParameter
{
	public $name;
	public $position;
	public $defaultValue;
	public $class;
	/**
	 * @var Boolean
	 */
	public $isArray = false;

	function __construct($name)
	{
		$this->name = $name;
	}

	public function allowsNull ()
	{

	}

	function getClass()
	{
		return $this->class;
	}

	public function getDefaultValue ()
	{
		return $this->defaultValue;
	}
	public function getName ()
	{
		return $this->name;
	}
	public function getPosition ()
	{
		return $this->position;
	}
	public function isArray ()
	{
		return $this->isArray;
	}
	public function isDefaultValueAvailable ()
	{
		return $this->defaultValue !== null;
	}
	public function isOptional ()
	{
		return $this->defaultValue !== null;
	}
	public function isPassedByReference (  )
	{

	}
	public function __toString (  )
	{
		$str = "";
		if ($this->class !== null)
		{
			$str .= $this->class." ";
		}
		if ($this->isArray)
		{
			$str .= "array ";
		}
		$str .= '$'.$this->name;
		if ($this->defaultValue !== null)
		{
			$str .= " = ".$this->defaultValue;
		}
		return $str;
	}
}