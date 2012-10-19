<?php
/**
 * @deprecated
 */
class change_SchemaManager extends \Change\Db\Mysql\SchemaManager
{
	
	/**
	 * @param \Change\Db\Mysql\DbProvider $dbProvider
	 */
	public function __construct(\Change\Db\Mysql\DbProvider $dbProvider)
	{
		Framework::fatal(__METHOD__);
		parent::__construct($dbProvider);
	}
	
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function dropModelTables($moduleName, $documentName, $apply = true)
	{
		//TODO Old class Usage
		$documentModel = \f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
		$tableName = $documentModel->getTableName();
		$sqls = array();
		$sqls[] = 'DROP TABLE IF EXISTS `' . $tableName . '`';
		if ($documentModel->isLocalized())
		{
			$tableName = $documentModel->getTableName() . $this->getI18nSuffix();
			$sqls[] = 'DROP TABLE IF EXISTS `' . $tableName . '`';
		}
		$script = implode(';'.PHP_EOL, $sqls);
		if ($apply)
		{
			$this->executeBatch($script);
		}
		return $script;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param \generator_PersistentProperty $property TODO Old class Usage
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function addProperty($moduleName, $documentName, $property, $apply = true)
	{
		//TODO Old class Usage
		$documentModel = \f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
		$sqls = array();
	
		$tableName = $documentModel->getTableName();
		$columnName = $property->getDbName();
		$infos = $this->getTableDefinition($tableName);
		if ($infos->getField($columnName) === null)
		{
			$sqls[] = "ALTER TABLE `" . $tableName . "` ADD COLUMN " . $this->generateSQLField($property);
		}
		else
		{
			$sqls[] = "ALTER TABLE `" . $tableName . "` MODIFY COLUMN " .  $this->generateSQLField($property);
		}
	
		if ($property->isLocalized())
		{
			$i18nInfos = $this->getTableDefinition($tableName . '_i18n');
			$i18nColumnName = $property->getDbName() . '_i18n';
			if ($i18nInfos->getField($i18nColumnName) === null)
			{
				$sqls[] = "ALTER TABLE `" . $tableName . $this->getI18nSuffix() . "` ADD COLUMN " . $this->generateSQLField($property, true);
			}
			else
			{
				$sqls[] = "ALTER TABLE `" . $tableName . $this->getI18nSuffix() . "` MODIFY " . $this->generateSQLField($property, true);
			}
		}
		$script = implode(';'.PHP_EOL, $sqls);
		if ($apply)
		{
			$this->executeBatch($script);
		}
		return $script;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param \generator_PersistentProperty $oldProperty TODO Old class Usage
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function delProperty($moduleName, $documentName, $oldProperty, $apply = true)
	{
		//TODO Old class Usage
		$documentModel = \f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
		$sqls = array();
		$sqls[] = "ALTER TABLE `" . $documentModel->getTableName() . "` DROP COLUMN `" . $oldProperty->getDbName() . "`";
		if ($oldProperty->isLocalized())
		{
			$sqls[] = "ALTER TABLE `" . $documentModel->getTableName() . "_i18n` DROP COLUMN `" . $oldProperty->getDbName() . "_i18n`";
		}
	
		if ($oldProperty->isDocument())
		{
			$oldRelationId = $this->getRelationId($oldProperty->getName());
			$modelNames = array("'" . $documentModel->getName() . "'");
			if ($documentModel->hasChildren())
			{
				foreach ($documentModel->getChildrenNames() as $childName)
				{
					$modelNames = "'" . $childName . "'";
				}
			}
			$sqls[] = "DELETE FROM `f_relation` WHERE relation_id = $oldRelationId AND document_model_id1 IN (" . implode(", ", $modelNames) . ")";
		}
	
		$script = implode(';'.PHP_EOL, $sqls);
		if ($apply)
		{
			$this->executeBatch($script);
		}
		return $script;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param \generator_PersistentProperty $oldProperty TODO Old class Usage
	 * @param \generator_PersistentProperty $newProperty TODO Old class Usage
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function renameProperty($moduleName, $documentName, $oldProperty, $newProperty, $apply = true)
	{
		//TODO Old class Usage
		$documentModel = \f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
		$sqls = array();
		$oldDbMapping = $oldProperty->getDbName();
		$oldPropertyName = $oldProperty->getName();
		$sqls[] = "ALTER TABLE `" . $documentModel->getTableName() . "` CHANGE COLUMN `" . $oldDbMapping . "` " . $this->generateSQLField($newProperty);
	
		if ($oldProperty->isLocalized())
		{
			$sqls[] = "ALTER TABLE `" . $documentModel->getTableName() . "_i18n` CHANGE COLUMN `" . $oldDbMapping . "_i18n` " . $this->generateSQLField($newProperty, true);
		}
	
		if ($oldProperty->isDocument())
		{
			$oldRelationId = $this->getRelationId($oldProperty->getName());
			$newRelationId = $this->getRelationId($newProperty->getName());
				
			$modelNames = array("'" . $documentModel->getName() . "'");
			if ($documentModel->hasChildren())
			{
				foreach ($documentModel->getChildrenNames() as $childName)
				{
					$modelNames[] = "'" . $childName . "'";
				}
			}
	
			$sqls[] = "UPDATE `f_relation` SET relation_name = '" . $newProperty->getName() . "', relation_id = " .$newRelationId.
			" WHERE document_model_id1 IN (" . implode(", ", $modelNames) . ") AND relation_id = " . $oldRelationId;
		}
	
		$script = implode(';'.PHP_EOL, $sqls);
		if ($apply)
		{
			$this->executeBatch($script);
		}
		return $script;
	}
	
	/**
	 * @param string $propertyName
	 * @return integer
	 */
	protected function getRelationId($propertyName)
	{
		return  $this->dbProvider->getRelationId($propertyName);
	}
	
	/**
	 * @return string
	 */
	public function getSQLScriptSufixName()
	{
		return '.mysql.sql';
	}
	
	/**
	 * @param \generator_PersistentProperty $buildProperty TODO Old class Usage
	 * @return string
	 */
	public function generateSQLField($buildProperty, $localizedField = false)
	{
		$localizedSuffix = $localizedField ? '_i18n' : '';
		$dbName = $buildProperty->getDbName();
		$fp = array('`' . $dbName . $localizedSuffix . '`');
		if ($dbName === 'document_publicationstatus')
		{
			$fp[] = "ENUM('DRAFT', 'CORRECTION', 'ACTIVE', 'PUBLISHED', 'DEACTIVATED', 'FILED', 'DEPRECATED', 'TRASH', 'WORKFLOW') NULL DEFAULT NULL";
		}
		elseif ($buildProperty->isDocument())
		{
			if ($buildProperty->isArray())
			{
				$fp[] = "INT(11) DEFAULT '0'";
			}
			else
			{
				$fp[] = "INT(11) DEFAULT NULL";
			}
		}
		else
		{
			switch ($buildProperty->getType())
			{
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_STRING :
					$dbSize = intval($buildProperty->getDbSize());
					if ($dbSize <= 0 || $dbSize > 255) {$dbSize = 255;}
					$fp[] = "VARCHAR(" . $dbSize . ")";
					break;
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_LONGSTRING :
					$fp[] = "TEXT";
					break;
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_XHTMLFRAGMENT :
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_BBCODE :
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_JSON :
					$fp[] = "MEDIUMTEXT";
					break;
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_LOB :
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_OBJECT :
					$fp[] = "MEDIUMBLOB";
					break;
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_BOOLEAN :
					$fp[] = "TINYINT(1) NOT NULL DEFAULT '0'";
					break;
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_DATETIME :
					$fp[] = "DATETIME";
					break;
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_DOUBLE :
					$fp[] = "DOUBLE";
					break;
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_DECIMAL :
					$dbSize = $buildProperty->getDbSize();
					if (!empty($dbSize) && strpos($dbSize, ','))
					{
						$fp[] = "DECIMAL(" . $dbSize . ")";
					}
					else
					{
						$fp[] = "DECIMAL(13,4)";
					}
					break;
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_INTEGER :
				case \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENTID :
					$fp[] = "INT(11)";
					break;
			}
		}
		return implode(' ', $fp);
	}
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param string $dbMapping
	 * @return string
	 */
	public function generateSQLModelTableName($moduleName, $documentName, $dbMapping = null)
	{
		if (!empty($dbMapping))
		{
			return $dbMapping;
		}
		return strtolower("m_". $moduleName ."_doc_" . $documentName);
	}
	
	/**
	 * @param string $propertyName
	 * @param string $dbMapping
	 */
	public function generateSQLModelFieldName($propertyName, $dbMapping = null)
	{
		if (!empty($dbMapping))
		{
			return $dbMapping;
		}
		return strtolower($propertyName);
	}
	
	/**
	 * @param \generator_PersistentModel $buildModel TODO Old class Usage
	 * @return string
	 */
	public function generateSQLModel($buildModel)
	{
		$sql = array();
		$fields = $buildModel->getTableField();
		if (count($fields))
		{
			if ($buildModel->hasParentModel())
			{
				foreach ($fields as $property)
				{
					$sql[] = 'ALTER TABLE `' . $buildModel->getTableName() . '` ADD ';
					$sql[] = $this->generateSQLField($property);
					$sql[] = ';' .PHP_EOL;
				}
			}
			else
			{
				$sql = array('CREATE TABLE IF NOT EXISTS `' . $buildModel->getTableName() . '` (');
				$sql[] = '`document_id` INT(11) NOT NULL DEFAULT \'0\',' . PHP_EOL;
				$sql[] = '`document_model` VARCHAR(50) NOT NULL DEFAULT \'\',' . PHP_EOL;
				foreach ($fields as $property)
				{
					$sql[] = $this->generateSQLField($property) . ',' . PHP_EOL;
				}
				$sql[] = 'PRIMARY KEY  (`document_id`)) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci';
				$sql[] = ';' .PHP_EOL;
			}
		}
		return implode('', $sql);
	}
	
	/**
	 * @param \generator_PersistentModel $buildModel //TODO Old class Usage
	 * @return string
	 */
	public function generateSQLI18nModel($buildModel)
	{
		$sql = array();
		$fields = $buildModel->getTableI18nField();
		if (count($fields))
		{
			if ($buildModel->hasParentModel())
			{
				foreach ($fields as $property)
				{
					$sql[] = 'ALTER TABLE `' . $buildModel->getTableName() . '_i18n` ADD ';
					$sql[] = $this->generateSQLField($property, true);
					$sql[] = ';' .PHP_EOL;
				}
			}
			else
			{
				$sql = array('CREATE TABLE IF NOT EXISTS `' . $buildModel->getTableName() . '_i18n` (');
				$sql[] = '`document_id` INT(11) NOT NULL DEFAULT \'0\',' . PHP_EOL;
				$sql[] = '`lang_i18n` VARCHAR(2) NOT NULL DEFAULT \'fr\',' . PHP_EOL;
				foreach ($fields as $property)
				{
					$sql[] = $this->generateSQLField($property, true) . ',' . PHP_EOL;
				}
				$sql[] = 'PRIMARY KEY  (`document_id`, `lang_i18n`)) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci';
				$sql[] = ';' .PHP_EOL;
			}
		}
		return implode('', $sql);
	}	
}