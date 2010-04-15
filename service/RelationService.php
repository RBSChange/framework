<?php
class RelationService extends BaseService
{
	/**
	 * the singleton instance
	 * @var RelationService
	 */
	private static $instance = null;
	
	
	private static $relations;

	/**
	 * @return RelationService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	public final function compile()
	{
		$result = array();	
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('relationNameInfos.ser');
		if (!file_exists($compiledFilePath))
		{
			throw new Exception('Please execute compile-documents before this command');
		}
		$names = unserialize(file_get_contents($compiledFilePath));
		foreach ($names as $name) 
		{
			$result[$name] = $this->getPersistentProvider()->getRelationId($name);
		}
		$relationsPath = f_util_FileUtils::buildChangeBuildPath('relations.php');
		f_util_FileUtils::writeAndCreateContainer($relationsPath , '<?php $relations = unserialize('.var_export(serialize($result), true).');', f_util_FileUtils::OVERRIDE);
						
		self::$relations = $result;
	}
	
	private function loadRelations()
	{
		if (self::$relations === null)
		{
			$relationsPath = f_util_FileUtils::buildChangeBuildPath('relations.php');
			if (!file_exists($relationsPath))
			{
				throw new Exception("$relationsPath does not exists, please run change generate-database");
			}
			include_once($relationsPath);
			self::$relations = $relations;
		}
	}

	public final function getRelationId($propertyName)
	{
		$this->loadRelations();
		if (!isset(self::$relations[$propertyName]))
		{
			Framework::warn(f_util_ProcessUtils::getBackTrace());
			return 0;
		}
		return self::$relations[$propertyName];
	}
}