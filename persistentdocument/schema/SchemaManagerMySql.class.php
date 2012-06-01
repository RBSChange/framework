<?php
class change_SchemaManagerMySql implements change_SchemaManager
{
	/**
	 * @var PDO
	 */
	private $pdo;
	
	/**
	 * @return PDO
	 */
	private function getDriver()
	{
		if ($this->pdo === null)
		{
			$this->pdo = $this->getConnection(
					f_persistentdocument_PersistentProvider::getInstance()->getConnectionInfos());
		}
		return $this->pdo;
	}
	
	/**
	 * @param string query
	 * @return PDOStatement
	 */
	private function query($query)
	{
		try
		{
			return $this->getDriver()->query($query);
		}
		catch (PDOException $e)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::error('PDO: ' . $e->getMessage());
				Framework::error('SQL: ' . $query);
				Framework::exception($e);
			}
			throw $e;
		}
	}
	
	/**
	 * @param array<String, String> $connectionInfos
	 * @return PDO
	 */
	private function getConnection($connectionInfos)
	{
		$dsnOptions = array();
		$connectionInfos = array_merge(
				array('unix_socket' => null, 'host' => 'localhost', 'port' => 3306, 'user' => null, 
						'password' => null, 'database' => null), $connectionInfos);
		
		if (isset($connectionInfos['unix_socket']))
		{
			$dsnOptions[] = 'unix_socket=' . $connectionInfos['unix_socket'];
		}
		else
		{
			$dsnOptions[] = 'host=' . (isset($connectionInfos['host']) ? $connectionInfos['host'] : 'localhost');
			$dsnOptions[] = 'port=' . (isset($connectionInfos['port']) ? $connectionInfos['port'] : '3306');
		}
		if (isset($connectionInfos['database']))
		{
			$dsnOptions[] = 'dbname=' . $connectionInfos['database'];
		}
		
		$dsn = 'mysql:' . join(';', $dsnOptions);
		
		$username = isset($connectionInfos['user']) ? $connectionInfos['user'] : null;
		$password = isset($connectionInfos['password']) ? $connectionInfos['password'] : null;
		
		$options = array(
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");
		$pdo = new PDO($dsn, $username, $password, $options);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $pdo;
	}
	
	/**
	 * @return boolean
	 */
	function check()
	{
		try
		{
			$connectionInfos = f_persistentdocument_PersistentProvider::getInstance()->getConnectionInfos();
			$pdo = $this->getConnection($connectionInfos);
			return true;
		}
		catch (PDOException $e)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::error('PDO: ' . $e->getMessage());
				Framework::exception($e);
			}
			return false;
		}
	}
	
	/**
	 * @throws Exception on error
	 */
	function createDB()
	{
		try
		{
			$connectionInfos = f_persistentdocument_PersistentProvider::getInstance()->getConnectionInfos();
			$database = $connectionInfos['database'];
			unset($connectionInfos['database']);
			$pdo = $this->getConnection($connectionInfos);
			$pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
		}
		catch (PDOException $e)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::error('PDO: ' . $e->getMessage());
				Framework::error('SQL:  CREATE DATABASE IF NOT EXISTS `' . $database . '`');
				Framework::exception($e);
			}
			throw $e;
		}
	}
	
	/**
	 * @param string $sql
	 * @return integer the number of affected rows
	 * @throws Exception on error
	 */
	public function execute($sql)
	{
		try
		{
			return $this->getDriver()->exec($sql);
		}
		catch (PDOException $e)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::error('PDO: ' . $e->getMessage());
				Framework::error('SQL: ' . $sql);
				Framework::exception($e);
			}
			throw $e;
		}
	}

	/**
	 * @param string $script
	 * @param boolean $throwOnError
	 * @throws Exception on error
	 */
	function executeBatch($script, $throwOnError = false)
	{
		foreach(explode(';', $script) as $sql)
		{	
			if (f_util_StringUtils::isEmpty($sql))
			{
				continue;
			}
			try
			{
				$this->getDriver()->exec(trim($sql));
			}
			catch (Exception $e)
			{
				if ($e->getCode() != '42S21' && $throwOnError) //Duplicate column
				{
					throw $e;
				}
			}
		}		
	}
	
	/**
	 * Drop all tables from current configured database
	 */
	public function clearDB()
	{
		foreach ($this->getTables() as $table)
		{
			try
			{
				$this->execute('DROP TABLE `' . $table . '`');
			}
			catch (Exception $e)
			{
				Framework::warn($e->getMessage());
			}
		}
	}
	
	/**
	 * @return string[]
	 */
	public function getTables()
	{
		$tables = array();
		$stmt = $this->query("SHOW TABLES");
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}
	
	/**
	 * @param string $tableName
	 */
	function getTableFields($tableName)
	{
		$infos = array();
		foreach ($this->query('SHOW FULL COLUMNS FROM `' . $tableName . '`')->fetchAll(PDO::FETCH_ASSOC) as $row)
		{
			$infos[$row["Field"]] = $row;
		}
		return $infos;
	}

	/**
	 * @param string $lang
	 * @return boolean
	 */
	public function addLang($lang)
	{
		$infos = $this->getTableFields("f_document");
		if (!isset($infos["label_".$lang]))
		{
			$sql = "ALTER TABLE `f_document` ADD `label_$lang` VARCHAR(255) NULL";
			$this->execute($sql);
			return true;
		}
		return false;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function dropModelTables($moduleName, $documentName, $apply = true)
	{
		$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
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
	 * @param generator_PersistentProperty $property
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function addProperty($moduleName, $documentName, $property, $apply = true)
	{
		$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
		$sqls = array();
		
		$tableName = $documentModel->getTableName();
		$columnName = $property->getDbName();
		$infos = $this->getTableFields($tableName);
		if (!isset($infos[$columnName]))
		{
			$sqls[] = "ALTER TABLE `" . $tableName . "` ADD COLUMN " . $this->generateSQLField($property);
		}
		else
		{
			$sqls[] = "ALTER TABLE `" . $tableName . "` MODIFY COLUMN " .  $this->generateSQLField($property);
		}
		
		if ($property->isLocalized())
		{
			$i18nInfos = $this->getTableFields($tableName . '_i18n');
			$i18nColumnName = $property->getDbName() . '_i18n';
			if (!isset($i18nInfos[$i18nColumnName]))
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
	 * @param generator_PersistentProperty $oldProperty
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function delProperty($moduleName, $documentName, $oldProperty, $apply = true)
	{
		$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
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
	 * @param generator_PersistentProperty $oldProperty
	 * @param generator_PersistentProperty $newProperty
	 * @param boolean $apply
	 * @return string the SQL statements that where executed
	 */
	function renameProperty($moduleName, $documentName, $oldProperty, $newProperty, $apply = true)
	{
		$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
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
	
	protected function getRelationId($propertyName)
	{
		return f_persistentdocument_PersistentProvider::getInstance()->getRelationId($propertyName);
	}
	
	/**
	 * @return string
	 */
	public function getSQLScriptSufixName()
	{
		return '.mysql.sql';
	}
	
	/**
	 * @param generator_PersistentProperty $buildProperty
	 * @return string
	 */
	public function generateSQLField($buildProperty, $localizedField = false)
	{
		$localizedSuffix = $localizedField ? '_i18n' : '';
		$dbName = $buildProperty->getDbName();
		$fp = array('`' . $dbName . $localizedSuffix . '`');
		if ($dbName === 'document_publicationstatus')
		{
			$fp[] = "ENUM('DRAFT', 'CORRECTION', 'ACTIVE', 'PUBLICATED', 'DEACTIVATED', 'FILED', 'DEPRECATED', 'TRASH', 'WORKFLOW') NULL DEFAULT NULL";
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
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING :
					$dbSize = intval($buildProperty->getDbSize());
					if ($dbSize <= 0 || $dbSize > 255) {$dbSize = 255;} 
					$fp[] = "VARCHAR(" . $dbSize . ")";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LONGSTRING :
					$fp[] = "TEXT";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT :
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BBCODE :
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_JSON :
					$fp[] = "MEDIUMTEXT";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB :
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_OBJECT :
					$fp[] = "MEDIUMBLOB";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN :
					$fp[] = "TINYINT(1) NOT NULL DEFAULT '0'";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME :
					$fp[] = "DATETIME";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE :
					$fp[] = "DOUBLE";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DECIMAL :
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
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER :
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTID :
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
	 * @param generator_PersistentModel $buildModel
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
	 * @param generator_PersistentModel $buildModel
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
	
	
	/**
	 * @param integer $treeId
	 * @return string the SQL statements that where executed
	 */
	public function createTreeTable($treeId)
	{
		$dropSQL = $this->dropTreeTable($treeId);
		$sql = 'CREATE TABLE IF NOT EXISTS `f_tree_'. $treeId .'` ( 
			`document_id` int(11) NOT NULL default \'0\',
			`parent_id` int(11) NOT NULL default \'0\',
			`node_order` int(11) NOT NULL default \'0\',
			`node_level` int(11) NOT NULL default \'0\',
			`node_path` varchar(255) collate latin1_general_ci NOT NULL default \'/\',
			`children_count` int(11) NOT NULL default \'0\',
			PRIMARY KEY (`document_id`),
			UNIQUE KEY `tree_node` (`parent_id`, `node_order`),
			UNIQUE KEY `descendant` (`node_level`,`node_order`,`node_path`)
			) ENGINE=InnoDB CHARACTER SET latin1 COLLATE latin1_general_ci';
        
        $this->execute($sql);
        return $dropSQL . $sql . ';' . PHP_EOL;
	}

	/**
	 * @param integer $treeId
	 * @return string the SQL statements that where executed
	 */
	public function dropTreeTable($treeId)
	{
		$sql = 'DROP TABLE IF EXISTS `f_tree_'. $treeId .'`';
		$this->execute($sql);
		return $sql . ';' . PHP_EOL;
	}
}