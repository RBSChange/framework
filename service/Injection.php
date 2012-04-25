<?php
/**
 * @deprecated
 */
class Injection
{
	/**
	 * Returns provided the class name or a subclass name as indicated in injection config section
	 * <pre>
	 * <project>
	 *   <config>
	 *     <injection>
	 *	     <entry name="$className">anextensionmodule_aSubClassOfClassName</entry>
	 *     </injection>
	 *   </config>
	 * </project>
	 * </pre>
	 *
	 * A typical usage is:
	 * <pre>
	 * private static $instance;
	 * public static function getInstance()
	 * {
	 *   if (self::$instance === null)
	 *	 {
	 *     $finalClassName = Injection::getFinalClassName(get_class()); 
	 *     self::$instance = new $finalClassName();
	 *	 }
	 *   return self::$instance;
	 * }
	 * </pre>
	 * @param String $className
	 * @return String provided the class name or a subclass name as indicated in injection config section
	 * @throws ConfigurationException if the overriden class is not a subclass of $className
	 */
	public static final function getFinalClassName($className)
	{
		$modulesConf = Framework::getConfiguration("injection");
		if (isset($modulesConf[$className]))
		{
			$injectedClassName = $modulesConf[$className];
			$injectedClass = new ReflectionClass($injectedClassName);
			if (!($injectedClass->isSubclassOf(new ReflectionClass($className))))
			{
				throw new ConfigurationException($injectedClassName." is not a $className");
			}
			return $injectedClassName;
		}
		return $className;
	}
}