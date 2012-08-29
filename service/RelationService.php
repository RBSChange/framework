<?php
/**
 * @method RelationService getInstance()
 */
class RelationService extends change_BaseService
{
	/**
	 * @var array
	 */
	private $relations = null;

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
						
		$this->relations = $result;
	}
	
	private function loadRelations()
	{
		if ($this->relations === null)
		{
			$relationsPath = f_util_FileUtils::buildChangeBuildPath('relations.php');
			if (!file_exists($relationsPath))
			{
				throw new Exception("$relationsPath does not exists, please run change generate-database");
			}
			include_once($relationsPath);
			$this->relations = $relations;
		}
	}

	public final function getRelationId($propertyName)
	{
		$this->loadRelations();
		if (!isset($this->relations[$propertyName]))
		{
			Framework::warn(f_util_ProcessUtils::getBackTrace());
			return 0;
		}
		return $this->relations[$propertyName];
	}
	
	/**
	 * @param integer $docId
	 * @param string $relationName
	 * @param string $documentModel
	 * @return f_persistentdocument_PersistentRelation[] the relations that documentId has
	 */
	public function getRelations($documentId, $relationName = null, $documentModel = null)
	{
		return $this->getPersistentProvider()->getChildRelationByMasterDocumentId($documentId, $relationName, $documentModel);
	}
	
	/**
	 * @param integer $docId
	 * @param string $relationName
	 * @param string $documentModel
	 * @return f_persistentdocument_PersistentRelation[] the relations that use documentId
	 */
	public function getUsageRelations($documentId, $relationName = null, $documentModel = null)
	{
		return $this->getPersistentProvider()->getChildRelationBySlaveDocumentId($documentId, $relationName, $documentModel);
	}
}