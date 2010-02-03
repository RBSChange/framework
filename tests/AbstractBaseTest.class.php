<?php
abstract class tests_AbstractBaseTest extends PHPUnit_Extensions_SeleniumTestCase/*PHPUnit_Framework_TestCase*/
{
	private $memUsage;
	private $beginTime;
	private $endTime;
	private static $testCounts = array();
	private static $testTotals = array();
	protected $pp;

	private static $registeredTestFactories = array();

	protected function prepareTestCase()
	{
		//echo "Begin TestCase\n";
	}

	protected function endTestCase()
	{
		//echo "End TestCase\n";
	}

	/**
	 * @see PHPUnit2_Framework_TestCase
	 */
	protected function setUp()
	{
		$this->clearServicesCache();
		$this->pp = f_persistentdocument_PersistentProvider::getInstance();
		echo "  ".$this->getTestName()."\n";
		$className = get_class($this);

		if (!array_key_exists($className, self::$testCounts))
		{
			self::$testCounts[$className] = 0;
			self::$testTotals[$className] = 0;
			$class = new ReflectionClass($this);
			foreach ($class->getMethods() as $method)
			{
				if ($method->isPublic() && preg_match("/^test.*/", $method->getName()))
				{
					self::$testTotals[$className]++;
				}
			}
			Controller::getInstance()->getContext()->getUser()->clearAttributes();
			$this->prepareTestCase();
		}
		self::$testCounts[$className]++;
		// FIXME : normally *already* specified in php.ini by default?
		error_reporting(E_ALL ^ E_NOTICE);
		$this->memUsage = memory_get_usage();
		$this->beginTime = microtime(true);
		$preTestCallbackName = 'pre'.ucfirst($this->getName());
		if (is_callable(array($this, $preTestCallbackName)))
		{
			$this->$preTestCallbackName();
		}
		$this->prepareTest();
	}

	/**
	 * @return void
	 */
	protected function clearServicesCache()
	{
		ModuleService::getInstance()->clearCache();
		f_persistentdocument_PersistentProvider::getInstance()->reset();
	}

	/**
	 * @return void
	 */
	protected function prepareTest()
	{

	}

	/**
	 * @see PHPUnit2_Framework_TestCase
	 */
	protected function tearDown()
	{
		$postTestCallbackName = 'post'.ucfirst($this->getName());
		if (is_callable(array($this, $postTestCallbackName)))
		{
			$this->$postTestCallbackName();
		}
		$this->endTest();
		$this->endTime = microtime(true);
		echo "  -- Memory ammount : ".(memory_get_usage() - $this->memUsage) . "\n";

		$className = get_class($this);
		if (self::$testCounts[$className] == self::$testTotals[$className])
		{
			$this->endTestCase();
		}
	}

	protected function endTest()
	{

	}

	protected function initDatabase()
	{

	}

	/**
	 * Load an sql file.
	 * <ul>
	 *   <li>Each SQL instruction must be terminated by a ';' character.</li>
	 *   <li>A line beginning with '--' string is considered as a comment</li>
	 * </ul>
	 *
	 * @param String $relativePath relative to resources test directory
	 * @param bool $exitOnError
	 * @param bool $verbose Facultative, true by default, write information about loading.
	 */
	protected function loadSQLResource($relativePath, $exitOnError = true, $verbose = true)
	{
		if ($verbose)
		{
			echo "loading '$relativePath' ... \n";
		}
		$path = $this->getResourcePath($relativePath);
		if (!is_readable($path))
		{
			throw new Exception("Unable to load $relativePath => $path");
		}
		//TODO : Recode execute sql script
		throw new Exception("Deprecated test initialization method");
		
		if ($verbose)
		{
			echo "loaded.\n";
		}
		if ($retCode != 0)
		{
			echo "Returned value : \n$retStr\n";
		}
	}

	/**
	 * Reset the database with the SQL file generated with "generate-unit-tests-database".
	 */
	protected function resetDatabase()
	{
		$path = f_util_FileUtils::buildChangeCachePath('reset-database.sql');
		if (!is_readable($path))
		{
			throw new Exception("Unable to load \"$path\": please run \"change generate-unit-test-database\" and try again.");
		}

		//TODO : Recode execute sql script
		throw new Exception("Deprecated test reset method");
		
		if ($retCode != 0)
		{
			echo "Oops... MySQL returned: \n$retStr\n";
		}

		// clear all module_TestFactory instances
		self::clearTestFactories();
	}

	protected function assertEmpty($actual, $message = '')
	{
		$constraint = new f_tests_constraints_EmptyConstraint();
		$this->assertThat($actual, $constraint, $message);
	}

	protected function assertNotEmpty($actual, $message = '')
	{
		$constraint = new PHPUnit_Framework_Constraint_Not(new f_tests_constraints_EmptyConstraint());
		$this->assertThat($actual, $constraint, $message);
	}

	protected function assertArrayEqualsIgnoreOrder($expected, $actual, $message = '')
	{
		$constraint = new f_tests_constraints_ArrayEqualsIgnoreOrder($expected);
		$this->assertThat($actual, $constraint, $message);
	}

	protected function assertArrayNotEqualsIgnoreOrder($expected, $actual, $message = '')
	{
		$constraint = new PHPUnit_Framework_Constraint_Not(new f_tests_constraints_ArrayEqualsIgnoreOrder($expected));
		$this->assertThat($actual, $constraint, $message);
	}

	protected function assertEqualsIgnoreCase($expected, $actual, $message = '')
	{
		$this->assertEquals(0, strcasecmp($expected, $actual), $message);
	}

	/**
	 * @param Integer $asserted
	 * @param array $actual
	 * @param String $message
	 */
	protected function assertCount($asserted, $actual, $message = '')
	{
		if ($asserted == 0)
		{
			$this->assertEmpty($actual, $message);
			return;
		}
		$this->assertNotEmpty($actual, $message);
		$this->assertEquals($asserted, count($actual), $message);
	}

	protected function assertLength($asserted, $actual, $message = '')
	{
		if ($asserted == 0)
		{
			$this->assertEmpty($actual, $message);
			return;
		}
		$this->assertNotEmpty($actual, $message);
		$this->assertEquals($asserted, strlen($actual), $message);
	}

	/**
	 * @param Float $asserted
	 * @param Float $actual
	 * @param Float $precision
	 * @param String $message
	 */
	protected function assertEqualsFloat($asserted, $actual, $precision = 0.0001, $message = '')
	{
		$this->assertTrue((abs($asserted - $actual) < $precision), $message);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $message
	 * @return void
	 */
	protected function assertPublished($document, $message = '')
	{
		$this->assertTrue($document->isPublished(), $message);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $message
	 * @return void
	 */
	protected function assertNotPublished($document, $message = '')
	{
		$this->assertFalse($document->isPublished(), $message);
	}

	protected function runTest()
	{
		$this->clearServicesCache();
		// Run the tests.
		try
		{
			parent::runTest();
		}
		catch (BaseException $e)
		{
			echo "ERROR : ".$e->getMessage()."\n";
			$e->printStackTrace('xml');
			flush();
			throw $e;
		}
		catch (Exception $e)
		{
			echo "ERROR : ".$e->getMessage()."\n";
			echo $e->getTraceAsString()."\n";
			flush();
			throw $e;
		}
	}

    /**
     * @return  string
     * @access public
     */
    public function start()
    {
        if ($this->isSeleniumTestCase())
        {
	    	parent::start();
        }
    }

    /**
     * @return  string
     * @access public
     */
    public function stop()
    {
        if ($this->isSeleniumTestCase())
        {
	    	parent::stop();
        }
    }

    /**
     * @return Boolean
     */
    protected function isSeleniumTestCase()
    {
    	return false;
    }

	// private methods

	private function getTestName()
	{
		$className = get_class($this);
		$lastIndex = strrpos($className, '_');
		return substr($className, $lastIndex+1).DIRECTORY_SEPARATOR.$this->getName();
	}

	/**
	 * @param String $relativePath
	 * @return String absolute path
	 */
	protected final function getResourcePath($relativePath)
	{
		return FileResolver::getInstance()
			->setPackageName($this->getPackageName())
			->getPath('tests' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $relativePath);
	}

	/**
	 * @return String
	 */
	protected abstract function getPackageName();

	/**
	 * Return a mock persistentDocument that return the id you wanted to
	 *
	 * @param Integer $id
	 * @return f_persistentdocument_PersistentDocumentImpl
	 */
	protected function getMockPersistentDocument($id = null)
	{
		$mock = $this->getMock('f_persistentdocument_PersistentDocumentImpl');
		if (!is_null($id))
		{
			$mock->expects($this->any())
			->method('getId')
			->will($this->returnValue($id));
		}
		return $mock;
	}

	protected function truncateAllTables()
	{
		$models = f_persistentdocument_PersistentDocumentModel::getDocumentModels();

		$pp = f_persistentdocument_PersistentProvider::getInstance();

		foreach ($models as $model)
		{
			$pp->executeSQLScript('TRUNCATE TABLE ' . $model->getTableName());
			if ($model->isLocalized())
			{
				$pp->executeSQLScript('TRUNCATE TABLE ' . $model->getTableName() . '_i18n');
			}
		}

		$pp->executeSQLScript('TRUNCATE `f_availabletags`');
		$pp->executeSQLScript('TRUNCATE `f_cache`');
		$pp->executeSQLScript('TRUNCATE `f_document`');
		$pp->executeSQLScript('TRUNCATE `f_document_revision`');
		$pp->executeSQLScript('TRUNCATE `f_history`');
		$pp->executeSQLScript('TRUNCATE `f_locale`');
		$pp->executeSQLScript('TRUNCATE `f_permission_compiled`');
		$pp->executeSQLScript('TRUNCATE `f_relation`');
		$pp->executeSQLScript('TRUNCATE `f_settings`');
		$pp->executeSQLScript('TRUNCATE `f_tags`');
		$pp->executeSQLScript('TRUNCATE `f_tree`');

		// Clear document cache, but keep on using it.
		$pp->setDocumentCache(false);
		$pp->setDocumentCache(true);
	}

	protected static function clearTestFactories()
	{
		foreach (self::$registeredTestFactories as $tf)
		{
			f_util_ClassUtils::callMethod($tf, 'clearInstance');
		}
		self::$registeredTestFactories = array();
	}

	/**
	 * Register test factory
	 *
	 * @param object $instance
	 */
	public static function registerTestFactory($instance)
	{
		self::$registeredTestFactories[] = get_class($instance);
	}
}

/**
 * @deprecated use tests_AbstractBaseTest
 */
abstract class f_tests_AbstractBaseTest extends tests_AbstractBaseTest
{

}

