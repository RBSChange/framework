<?php
/**
 * @package framework.builder
 */
class builder_ListenersGenerator
{
	/**
	 * @var array
	 */
	private $listenerArray = array();
	
	public function generateListenerLoader()
	{
		$this->listenerArray = $this->parseConfigListener(f_util_FileUtils::buildFrameworkPath('config', 'listeners.xml'));
		foreach (ModuleService::getInstance()->getPackageNames() as $moduleName)
		{
			$path = change_FileResolver::getNewInstance()->getPath('modules', $moduleName, 'config', 'listeners.xml');
			if (!is_null($path))
			{
				$this->listenerArray = $this->parseConfigListener($path, $this->listenerArray);
			}
		}

		$generator = new builder_Generator('listener');
		$this->assignGeneratorVars($generator);
		$result = $generator->fetch($this->getTemplateName());
		$listenersFile = f_util_FileUtils::buildChangeBuildPath('listeners.php');
		f_util_FileUtils::writeAndCreateContainer($listenersFile, $result, f_util_FileUtils::OVERRIDE);
	}
	
	/**
	 * @param builder_Generator $generator
	 */
	protected function assignGeneratorVars($generator)
	{
		$generator->assign('listeners', $this->listenerArray);
	}
	
	/**
	 * @return string
	 */
	protected function getTemplateName()
	{
		return 'listenerRegister.tpl';
	}
	
	private function parseConfigListener($path, $listenerArray = null)
	{
		if (is_null($listenerArray))
		{
			$listenerArray = array();
		}
		$listeners = new SimpleXMLElement(f_util_FileUtils::read($path));
		foreach ($listeners->listener as $listenerNode)
		{
			$listener = new stdClass();
			$listener->listenerClass = (string) $listenerNode['listenerClass'];
			if ($listener->listenerClass == '')
			{
				continue;
			}
			
			$listener->eventName = (string) $listenerNode['eventName'];
			$listener->eventName = $listener->eventName == '' ? 'null' : "'". $listener->eventName. "'";
			
			$listener->listenerMethod = (string) $listenerNode['listenerMethod'];
			$listener->listenerMethod = $listener->listenerMethod == '' ? 'null' : "'". $listener->listenerMethod. "'";
			
			$listenerArray[] = $listener;
		}	
		
		return 	$listenerArray;
	}
}