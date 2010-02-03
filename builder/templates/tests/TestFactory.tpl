<?php
/**
 * @date <{$date}>
 * @author <{$author}>
 * @package modules.<{$module}>
 */
class <{$module}>_TestFactory extends <{$module}>_TestFactoryBase
{
	/**
	 * @var <{$module}>_TestFactory
	 */
	private static $instance;

	/**
	 * @return <{$module}>_TestFactory
	 * @throws Exception
	 */
	public static function getInstance()
	{
		if (PROFILE != 'test')
		{
			throw new Exception('This method is only usable in test mode.');
		}
		if (self::$instance === null)
		{
			self::$instance = new <{$module}>_TestFactory;
			// register the testFactory in order to be cleared after each test case.
			tests_AbstractBaseTest::registerTestFactory(self::$instance);
		}
		return self::$instance;
	}

	/**
	 * Clear the TestFactory instance.
	 * 
	 * @return void
	 * @throws Exception
	 */
	public static function clearInstance()
	{
		if (PROFILE != 'test')
		{
			throw new Exception('This method is only usable in test mode.');
		}
		self::$instance = null;
	}
	
	/**
	 * Initialize documents default properties
	 * @return void
	 */
	public function init()
	{
<{foreach from=$models item=model}>
		$this->set<{$model->getDocumentName()|capitalize}>DefaultProperty('label', '<{$model->getDocumentName()}> test');
<{/foreach}>
	}
}