<?php
interface change_SchemaManager
{
	
	/**
	 * @return boolean
	 */
	function check();

	/**
	 * @throws Exception on error
	 */
	function createDB();
	
	/**
	 * @param string $sql
	 * @return integer the number of affected rows
	 * @throws Exception on error
	 */
	function execute($sql);
	
	/**
	 * @param string $script
	 * @param boolean $throwOnError
	 * @throws Exception on error
	 */
	function executeBatch($script, $throwOnError = false);
	
	/**
	 * @throws Exception on error
	 */
	function clearDB();
	
	/**
	 * @return string[]
	 */
	function getTables();	
	
	/**
	 * @param string $tableName
	 */
	function getTableFields($tableName);
	
	/**
	 * @return string
	 */
	function getSQLScriptSufixName();
		
	/**
	 * @param string $lang
	 * @return boolean
	 */
	public function addLang($lang);
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function dropModelTables($moduleName, $documentName, $apply = true);
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param generator_PersistentProperty $property
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function addProperty($moduleName, $documentName, $property, $apply = true);
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param generator_PersistentProperty $oldProperty
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function delProperty($moduleName, $documentName, $oldProperty, $apply = true);

	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param generator_PersistentProperty $oldProperty
	 * @param generator_PersistentProperty $newProperty
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function renameProperty($moduleName, $documentName, $oldProperty, $newProperty, $apply = true);
		
	
	/**
	 * @param generator_PersistentProperty $buildProperty
	 * @return string
	 */
	function generateSQLField($buildProperty, $localizedField = false);
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param string $dbMapping
	 * @return string
	 */
	function generateSQLModelTableName($moduleName, $documentName, $dbMapping = null);
	
	/**
	 * @param string $propertyName
	 * @param string $dbMapping
	 */
	function generateSQLModelFieldName($propertyName, $dbMapping = null);
	
	
	/**
	 * @param generator_PersistentModel $buildModel
	 * @return string
	 */
	function generateSQLModel($buildModel);
	
	/**
	 * @param generator_PersistentModel $buildModel
	 * @return string
	 */
	function generateSQLI18nModel($buildModel);
	
	
	/**
	 * @param integer $treeId
	 * @return string the SQL statements that where executed
	 */
	public function createTreeTable($treeId);

	/**
	 * @param integer $treeId
	 * @return string the SQL statements that where executed
	 */
	public function dropTreeTable($treeId);
}