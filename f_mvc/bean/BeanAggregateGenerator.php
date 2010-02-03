<?php
class bean_BeanAggregateGenerator
{
	private $beanModels;
	private $beanClassNames = array();
	private $beanInstances = array();
	private $beanModelInstances = array();
	private $beanClassesCounter;
	private $beanComponentNames;
	
	private $className = "generatedBeanAggregate";
	
	public function __construct($beanClasses)
	{
		foreach ($beanClasses as $class)
		{
			$reflectionClass = new ReflectionClass($class);
			if (!$reflectionClass->implementsInterface('f_mvc_Bean'))
			{
				throw new Exception("$class does not implement f_mvc_Bean");
			}
			$this->beanInstances[$class] = BeanUtils::getNewBeanInstance($reflectionClass);
			$this->beanModelInstances[$class] = BeanUtils::getBeanModel(BeanUtils::getNewBeanInstance($reflectionClass));
			$this->beanClassNames[] = $class;
		}
	}
	
	static function generate($path, $beanClassName, $beanClasses)
	{
		$instance = new bean_BeanAggregateGenerator($beanClasses);
		$instance->className = $beanClassName;
		$generator = new builder_Generator('bean');
		$generator->assign_by_ref('aggregate', $instance);
		$result = $generator->fetch('BeanAggregate.tpl');
		f_util_FileUtils::writeAndCreateContainer($path, $result);
	}
	
	private $beanNames;
	
	public function getBeanNames()
	{
		if ($this->beanNames === null)
		{
			$counters = array();
			$this->beanNames = array();
			foreach ($this->beanClassNames as $className)
			{
				if (!isset($counters[$className]))
				{
					$counters[$className] = 1;
				}
				else
				{
					$counters[$className]++;
				}
			}
			
			$beanCount = count($this->beanClassNames);
			$currentCounter = array();
			for ($i = 0; $i < $beanCount; $i++)
			{
				$className = $this->beanClassNames[$i];
				if ($counters[$className] == 1)
				{
					$this->beanNames[$i] = $this->beanModelInstances[$className]->getBeanName();
				}
				else
				{
					if (!isset($currentCounter[$className]))
					{
						$currentCounter[$className] = 1;
					}
					$this->beanNames[$i] = $this->beanModelInstances[$className]->getBeanName() . strval($currentCounter[$className]++);
				}
			}
		}
		return $this->beanNames;
	}
	
	/**
	 * @return String
	 */
	public function getClassName()
	{
		return $this->className;
	}
	
	/**
	 * @return String
	 */
	public function getModelClassName()
	{
		return $this->getClassName() . 'Model';
	}
	
	/**
	 * @return String
	 */
	public function getBeanClassNames()
	{
		return $this->beanClassNames;
	}
	
	public function getMethodForBean($name)
	{
		$methods = array();
		$instanceIndex = array_search($name, $this->getBeanNames());
		$reflectionClass = new ReflectionClass($this->beanClassNames[$instanceIndex]);
		foreach ($reflectionClass->getMethods() as $method)
		{
			$methodName = $method->getName();
			$methodProps = array();
			$methodProps["orginalCall"] = $methodName;
			if ($method->isPublic() && !$method->isStatic() && !$method->isConstructor() && !$method->isDestructor() && !$method->isAbstract())
			{
				if (strpos($methodName, "get") === 0 || strpos($methodName, "set") === 0 || strpos($methodName, "add") === 0)
				{
					$methodProps["call"] = substr($methodName, 0, 3) . ucfirst($name) . substr($methodName, 3);
				}
				else
				{
					continue;
				}
				$methodProps["phpdoc"] = $method->getDocComment();
				$args = array();
				$callerargs = array();
				foreach ($method->getParameters() as $parameter)
				{
					$argumentString = "";
					if ($parameter->isPassedByReference())
					{
						$argumentString .= "&";
					}
					$argumentString .= '$' . $parameter->getName();
					$callerargs[] = '$' . $parameter->getName();
					if ($parameter->isDefaultValueAvailable())
					{
						$argumentString = $argumentString . " = " . str_replace("\n", "", var_export($parameter->getDefaultValue(), true));
					}
					$args[] = $argumentString;
				}
				$methodProps["call"] .= '(' . implode(", ", $args) . ')';
				$methodProps["orginalCall"] .= '(' . implode(", ", $callerargs) . ')';
				$methods[] = $methodProps;
			}
		}
		return $methods;
	}
}
