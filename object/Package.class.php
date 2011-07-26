<?php
/**
 * A class to manage elegant class inclusions and instanciations.
 *
 * Please note that a Package object is attached to one (and only one) class
 * path.
 * Any call to Package::getInstance with the same given class path will give you
 * the same instance (say it's a "class path singleton").
 */
class Package
{
	public $classPath = null;
	static private $packageInstance = array();

	const PHP_EXTENSION = '.php';
	const CLASS_PHP_EXTENSION = '.class.php';

	/**
	 * Construct a Package object with the specified classPath.
	 *
	 * @param string $classPath Class path to use in this Package object.
	 */
    private function __construct($classPath)
    {
	$this->classPath = $classPath;
    }


    /**
     * Returns the instance associated with the specified classPath. If the
     * instance associated to the given classPath does not exist, this method
     * creates a new one and returns it.
     *
     * @param string $classPath Class path where to find classes.
     *
     * @return object Package instance associated to the class path.
     */
    static public function getInstance($classPath)
    {
	if (!array_key_exists($classPath, self::$packageInstance)) {
		self::$packageInstance[$classPath] = new Package($classPath);
	}
	return self::$packageInstance[$classPath];
    }
    /**
     * Instanciates a new object of the specified className in the associated
     * classPath, and returns it.
     *
     * @param string $className The name of the class to instanciate.
     * @param mixed Any number of optional parameters - the first one is
     * considered as an instance id.
     *
     * @return object New instance of the class.
     */
    public function newClassInstance($className)
    {
	$className = $this->getClassName($className);
	$funArgs = func_get_args();
	if (count($funArgs) > 1) {
		$id = $funArgs[1];
	} else {
		$id = null;
	}
	$newObj = new $className($id);
	if (is_callable(array($className, 'initialize')) === true) {
		call_user_func_array(array($newObj, 'initialize'), array_slice($funArgs, 2));
	}
	return $newObj;
    }


    /**
     * Returns the full className from a short className in the associated
     * classPath.
     *
     * @param string $className Short version of the name of the class.
     *
     * @return string Full name of the class.
     */
    public function getClassName($className)
    {
	$className = str_replace('.', '_', $this->classPath) . '_' . $className;

	return $className;
    }

    /**
     * Returns the *short* class name associated with the given *real* class name.
     *
     * @param string $realClassName Real class name.
     *
     * @return string Short class name.
     */
    static public function getShortClassName($realClassName)
    {
	$className = explode('_', $realClassName);
	return $className[count($className) - 1];
    }





    /**
     * Calls the given methodName of className in the associated classPath.
     *
     * @param string $className Short version of the name of the class.
     * @param string $methodName Name of the method to call.
     * @param mixed Any number of optional parameters...
     *
     * @return mixed Result of the call of methodName.
     *
     * @throws BaseException if the method is not callable.
     */
    public function callClassMethod($className, $methodName)
    {
	try {
		//Framework::log($className, Logger::DEBUG);
		if (!is_callable(array($this->getClassName($className), $methodName))) {
			$error = sprintf('Unknown method "%s" for class "%s" in package "%s"', $methodName, $className, $this->classPath);
			throw new Exception($error);
		}
		$funArgs = func_get_args();
		$result = call_user_func_array(array($this->getClassName($className), $methodName), array_slice($funArgs, 2));
	}
	catch (Exception $e) {

			$e = new AgaviException($e->getMessage(), null,$e);
			$e->printStackTrace();
		}
		return $result;
    }


    public function isInstance($object, $className)
    {
	$longClassName = $this->getClassName($className);
	return ($object instanceof $longClassName);
    }

    public static function makeSystemPath($dotPathPath)
    {
	return str_replace(
		array('.', '/', '\\'),
		array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR),
		$dotPathPath
		);
    }
}
?>