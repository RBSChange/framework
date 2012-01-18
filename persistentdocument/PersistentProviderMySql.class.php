<?php
/**
 * @package framework.persistentdocument
 */
class f_persistentdocument_PersistentProviderMySql extends f_persistentdocument_PersistentProvider
{
	/**
	 * @var Array<string, MysqlStatment>
	 */
	private $statements = array();

	/**
	 * @var MysqlStatment
	 */
	private $oldStatement = null;

	/**
	 * @var boolean
	 */
	private $emulatePrepares = false;

	protected function errorCode()
	{
		return $this->getDriver()->errorCode();
	}
	
	/**
	 * @return array("sqlstate" => ..., "errorcode" => ..., "errormessage" => ...)
	 */
	protected function getErrorParameters()
	{
		$errorInfo = $this->getDriver()->errorInfo();
		return array("sqlstate" => $errorInfo[0], "errorcode" => $errorInfo[1], "errormessage" => $errorInfo[2]);
	}

	protected function errorInfo()
	{
		return print_r($this->getDriver()->errorInfo(), true);
	}

	protected function beginTransactionInternal()
	{
		// apparently, reuse a no transactionnal statement in a transactionnal
		// context results in tranparent auto commit ?? Intsimoa php-5.1.6
		$this->statements = array();
		$this->oldStatement = null;
		$this->getDriver()->beginTransaction();
	}

	protected function commitInternal()
	{
		// apparently, reuse a no transactionnal statement in a transactionnal
		// context results in tranparent auto commit ?? Intsimoa php-5.1.6
		$this->statements = array();
		$this->oldStatement = null;
		$this->getDriver()->commit();
	}

	protected function rollBackInternal()
	{
		$this->getDriver()->rollBack();
	}


	protected function getLastInsertId($tableName)
	{
		return $this->getDriver()->lastInsertId();
	}
	
	/**
	 * Drop all tables from current configured database
	 */
	function clearDB()
	{
		$stmt = $this->executeSQLSelect("show tables");
		foreach ($stmt->fetchAll() as $table)
		{
			try
			{
				$this->executeSQLScript("drop table " . $table[0]);
			} 
			catch (Exception $e) 
			{
				Framework::warn($e->getMessage());
			}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see persistentdocument/f_persistentdocument_PersistentProvider#delProperty($moduleName, $documentName, $propertyName)
	 */
	function delProperty($moduleName, $documentName, $oldProperty)
	{
		$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
		$sqls = array();
		$sqls[] = "ALTER TABLE `".$documentModel->getTableName()."` DROP COLUMN `".$oldProperty->getDbName()."`";
		if ($oldProperty->isLocalized())
		{
			$sqls[] = "ALTER TABLE `".$documentModel->getTableName().$this->getI18nSuffix()."` DROP COLUMN `".$oldProperty->getDbName().$this->getI18nSuffix()."`";
		}
		if ($oldProperty->isDocument())
		{
			$modelNames = array("'".$documentModel->getName()."'");
			if ($documentModel->hasChildren())
			{
				foreach ($documentModel->getChildrenNames() as $childName)
				{
					$modelNames = "'".$childName."'";	
				}
			}
			$sqls[] = "DELETE FROM `f_relation` WHERE relation_name = '".$oldProperty->getName()."' AND document_model_id1 IN (".join(",", $modelNames).")";
		}
		foreach ($sqls as $sql)
		{
			$this->executeSQLScript($sql);
		}
		return $sqls;
	}
	
	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @param generator_PersistentProperty $oldProperty
	 * @param generator_PersistentProperty $newProperty
	 * @return String[] the SQL statements that where executed
	 * @see persistentdocument/f_persistentdocument_PersistentProvider#renameProperty($moduleName, $documentName, $propertyName, $newPropertyName)
	 */
	function renameProperty($moduleName, $documentName, $oldProperty, $newProperty)
	{
		$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
		$sqls = array();
		$oldDbMapping = $oldProperty->getDbName();
		$oldPropertyName = $oldProperty->getName();
		$sqls[] = "ALTER TABLE `".$documentModel->getTableName()."` CHANGE COLUMN `".$oldDbMapping."` ".$newProperty->generateSql("mysql");
		if ($oldProperty->isLocalized())
		{
			$sqls[] = "ALTER TABLE `".$documentModel->getTableName().$this->getI18nSuffix()."` CHANGE COLUMN `".$oldDbMapping.$this->getI18nSuffix()."` ".$newProperty->generateSql("mysql", true);
		}
		if ($oldProperty->isDocument())
		{
			$modelNames = array("'".$documentModel->getName()."'");
			if ($documentModel->hasChildren())
			{
				foreach ($documentModel->getChildrenNames() as $childName)
				{
					$modelNames[] = "'".$childName."'";	
				}
			}
			
			$models = f_persistentdocument_PersistentDocumentModel::getDocumentModels();
			$mustUpdateRelationId = false;
			foreach ($models as $model)
			{
				if ($model->getName() == $documentModel->getName())
				{
					continue;
				}
				foreach ($model->getPropertiesInfos() as $name => $info) 
				{
					if ($name == $oldPropertyName && $info->isDocument())
					{
						$mustUpdateRelationId = true;
						break 2;
					}
				}
			}
			$sql = "UPDATE `f_relation` SET relation_name = '".$newProperty->getName()."'";
			if ($mustUpdateRelationId)
			{
				$newRelationId = $this->getRelationId($newProperty->getName());
				$sql .= ", relation_id = $newRelationId";
			}
			$sql .= " WHERE document_model_id1 IN (".implode(",", $modelNames).") AND relation_name = '$oldPropertyName'";
			$sqls[] = $sql;
		}
		foreach ($sqls as $sql)
		{
			$this->executeSQLScript($sql);
		}
		return $sqls;
	}
	
	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @param generator_PersistentProperty $property
	 */
	function addProperty($moduleName, $documentName, $property)
	{
		$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
		$sqls = array();
		
		$tableName = $documentModel->getTableName();
		$columnName = $property->getDbName();
		$infos = $this->getTableInfo($tableName);
		if (!isset($infos[$columnName]))
		{
			$sqls[] = "ALTER TABLE `".$tableName."` ADD COLUMN ".$property->generateSql("mysql");	
		}
		else
		{
			$sqls[] = "ALTER TABLE `".$tableName."` MODIFY COLUMN ".$property->generateSql("mysql");
		}
		if ($property->isLocalized())
		{
			$i18nInfos = $this->getTableInfo($tableName.$this->getI18nSuffix());
			$i18nColumnName = $property->getDbName().$this->getI18nSuffix();
			if (!isset($i18nInfos[$i18nColumnName]))
			{
				$sqls[] = "ALTER TABLE `".$tableName.$this->getI18nSuffix()."` ADD COLUMN ".$property->generateSql("mysql", true);
			}
			else
			{
				$sqls[] = "ALTER TABLE `".$tableName.$this->getI18nSuffix()."` MODIFY ".$property->generateSql("mysql", true);
			}
		}
		foreach ($sqls as $sql)
		{
			$this->executeSQLScript($sql);
		}
		return $sqls;
	}
	
	private function getTableInfo($tableName)
	{
		$infos = array();
		foreach ($this->executeSQLSelect("desc ".$tableName)->fetchAll(PDO::FETCH_ASSOC) as $row)
		{
			$infos[$row["Field"]] = $row;
		}
		return $infos;
	}
	
	/**
	 * @param c_Properties $props
	 * (non-PHPdoc)
	 * @see persistentdocument/f_persistentdocument_PersistentProvider#createDB($conf)
	 * @return Boolean true if database was created
	 */
	function createDB($props)
	{
		$adminInfos = array();
		$adminInfos['user'] = $props->getProperty("dbAdminUser");
		$adminInfos['password'] = $props->getProperty("dbAdminPassword"); 
		$adminInfos['host'] = $props->getProperty("dbAdminHost");
		$adminInfos['port'] = $props->getProperty("dbAdminPort");
		
		if (!isset($adminInfos['user']) || !isset($adminInfos['password']))
		{
			return false;
		}
		
		$adminDriver = $this->getConnection($adminInfos);
		
		$dbInfos = $this->connectionInfos;
		if ($adminDriver->exec("create database if not exists `".$dbInfos['database']."`") === false)
		{
			throw new Exception("Could not create database ".$dbInfos['database']);
		}
		if ($adminInfos['host'] === null || $adminInfos['host'] == 'localhost')
		{
			$fromHost = 'localhost';
		}
		else
		{
			$fromHost = $props->getProperty("dbAdminFromHost");
		}
		if ($adminDriver->exec("grant all privileges on `".$dbInfos['database']."`.* to '".$dbInfos['user']."'@'".$fromHost."' identified by '".$dbInfos['password']."'") === false)
		{
			throw new Exception("Could not grant privileges on ".$dbInfos['database']." to ".$dbInfos['user']."'@'".$fromHost);
		}
		return true;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return 'mysql';
	}

	/**
	 * @param boolean $bool
	 * @return boolean
	 */
	public function setAutoCommit($bool)
	{
		$previousValue = $this->getDriver()->getAttribute(PDO::ATTR_AUTOCOMMIT);
		$this->getDriver()->setAttribute(PDO::ATTR_AUTOCOMMIT, (bool)$bool);

		if ($bool != $previousValue)
		{
			// apparently, reuse a no transactionnal statement in a transactionnal
			// context results in tranparent auto commit ?? Intsimoa php-5.1.6
			$this->statements = array();
			$this->oldStatement = null;
		}
		return $previousValue;
	}



	/**
	 * @param array<String, String> $connectionInfos
	 * @return PDO
	 */
	protected function getConnection($connectionInfos)
	{
		$protocol = 'mysql';
		$dsnOptions = array();
		
		$database = isset($connectionInfos['database']) ? $connectionInfos['database'] : null;
		$password = isset($connectionInfos['password']) ? $connectionInfos['password'] : null;
		$username = isset($connectionInfos['user']) ? $connectionInfos['user'] : null;

		$dsn = $protocol.':';
		
		if ($database !== null)
		{
			$dsnOptions[] = 'dbname='.$database;	
		}
		$unix_socket = isset($connectionInfos['unix_socket']) ? $connectionInfos['unix_socket'] : null;
		if ($unix_socket != null)
		{
			$dsnOptions[] = 'unix_socket='.$unix_socket;
		}
		else
		{
			$host = isset($connectionInfos['host']) ? $connectionInfos['host'] : 'localhost';
			$dsnOptions[] = 'host='.$host;
			$port = isset($connectionInfos['port']) ? $connectionInfos['port'] : 3306;
			$dsnOptions[] = 'port='.$port;
		}
		
		$dsn = $protocol.':'.join(';', $dsnOptions);

		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ ."($dsn, $username)");
		}

		$this->sql_fieldescape = '`';
		$pdo = new PDO($dsn, $username, $password);
		$this->emulatePrepares = isset($connectionInfos['emulate_prepares']) ? f_util_Convert::toBoolean($connectionInfos['emulate_prepares']) : false;
		if ($this->emulatePrepares == true)
		{
			$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		}
		return $pdo;
	}

	public function closeConnection()
	{
		if (null !== $this->oldStatement)
		{
			$this->oldStatement->closeCursor();
			$this->oldStatement = null;
		}
		$this->setDriver(null);
	}
	

	/**
	 * @return array<".mysql.sql", ";">
	 */
	public function getScriptFileInfos()
	{
		return array(".mysql.sql", ";");
	}
	
	protected function escapeFieldName($fieldName)
	{
		return '`' . $fieldName . '`';
	}

	/**
	 * @param String $script
	 * @return Integer the number of affected rows
	 */
	public function executeSQLScript($script)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug('executeSQLScript :' .$script);
		}
		$result = $this->getDriver()->exec($script);
		$errcode = $this->errorCode();
		if ($result === false)
		{
			if ($errcode == '00000')
			{
				return 0;
			}
			
			$e = new BaseException("Unable to execute SQL: ".$this->errorCode().": ".$this->errorInfo()."\n".$script, "framework.persistentprovider.mysql.sql-error", $this->getErrorParameters());
			$e->setAttribute("sql", $script);
			throw $e;
		}		
		return $result;
	}

	/**
	 * @param String $script
	 * @return PDOStatement
	 */
	public function executeSQLSelect($script)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug('executeSQLSelect :' .$script);
		}
		return $this->getDriver()->query($script);
	}

	/**
	 * @param mysqli_stmt $statement
	 * @param String $name
	 * @param mixed $value
	 * @param String $propertyType
	 */
	protected function bindStatmentValue($statement, $name, $value, $propertyType)
	{
		switch ($propertyType)
		{
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME:
				if (empty($value))
				{
					$statement->bindValue($name, null, PersistentProviderConst::PARAM_NULL);
				}
				else if (is_long($value))
				{
					$statement->bindValue($name, date("Y-m-d H:i:s", $value), PersistentProviderConst::PARAM_STR);
				}
				else if ($value instanceof Date)
				{

					$statement->bindValue($name, $value->format("%Y-%m-%d %T"), PersistentProviderConst::PARAM_STR);
				}
				else
				{
					$statement->bindValue($name, $value, PersistentProviderConst::PARAM_STR);
				}
				break;
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN:
				$statement->bindValue($name, $value ? 1 : 0, PersistentProviderConst::PARAM_INT);
				break;
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER:
				if (is_null($value))
				{
					$statement->bindValue($name, null, PersistentProviderConst::PARAM_NULL);
				}
				else
				{
					$statement->bindValue($name, $value, PersistentProviderConst::PARAM_INT);
				}
				break;
			default:
				if (is_null($value))
				{
					$statement->bindValue($name, null, PersistentProviderConst::PARAM_NULL);
				}
				else
				{
					$statement->bindValue($name, strval($value), PersistentProviderConst::PARAM_STR);
				}
				break;
		}
	}

	/**
	 * @param string $sql
	 * @return MysqlStatment
	 */
	protected function prepareStatement($sql)
	{
		if (null !== $this->oldStatement)
		{
			$this->oldStatement->closeCursor();
		}

		//		if (!$this->emulatePrepares)
		//		{
		if (isset($this->statements[$sql]))
		{
			$stmt = $this->statements[$sql];
			$this->oldStatement = $stmt;
			return $stmt;
		}
		$stmt = new MysqlStatment($sql, $this->getDriver());
		$this->statements[$sql] = $stmt;
		//		}
		//		else
		//		{
		//			$stmt = new MysqlStatment($sql, $this);
		//		}
		$this->oldStatement = $stmt;
		return $stmt;
		}

		// All the function under this are function with request inside. This request are used in the persitent provider. They are here because the
		// sql is dependend of the database.
		protected function getDocumentInstanceQuery()
		{
			return 'SELECT document_model, treeid, ' . implode(', ', $this->getI18nFieldNames()) . ' FROM `f_document` WHERE `document_id` = :document_id';
		}

		protected function getDocumentModelNameQuery()
		{
			return 'SELECT `document_model` FROM `f_document` WHERE `document_id` = :document_id';
		}

		/**
		 * @return String
		 */
		protected function mutateFrameworkQuery()
		{
			return 'UPDATE `f_document` SET `document_model` = :destmodelname WHERE `document_id` = :id AND `document_model` = :sourcemodelname';
		}

		/**
		 * @return String
		 */
		protected function mutateRelationId1Query()
		{
			return 'UPDATE `f_relation` SET `document_model_id1` = :destmodelname WHERE `relation_id1` = :id AND `document_model_id1` = :sourcemodelname';
		}
		/**
		 * @return String
		 */
		protected function mutateRelationId2Query()
		{
			return 'UPDATE `f_relation` SET `document_model_id2` = :destmodelname WHERE `relation_id2` = :id AND `document_model_id1` = :sourcemodelname';
		}

		/**
		 * @return String
		 */
		protected function mutateDocumentQuery($table)
		{
			return 'UPDATE `'.$table.'` SET `document_model` = :destmodelname WHERE `document_id` = :id AND `document_model` = :sourcemodelname';
		}

		/**
		 * @return String
		 */
		protected function mutateDocumentI18NQuery($tableI18N)
		{
			return 'DELETE FROM `'.$tableI18N.'` WHERE `document_id` = :id';
		}


		protected function getLoadDocumentQuery($table)
		{
			return 'SELECT * FROM `'.$table.'` WHERE document_id = :document_id';
		}

		protected function getUpdateDocumentLangs()
		{
			$sqlFields = array();
			foreach ($this->getI18nFieldNames() as $i18nFieldName)
			{
				$sqlFields[] = $i18nFieldName . ' = :' .$i18nFieldName;
			}

			return 'UPDATE f_document SET ' . implode(', ', $sqlFields) . ' WHERE (document_id = :document_id)';
		}

		protected function getUpdateDocumentQuery($table, $mapping, $lobParameters = array())
		{
			return 'UPDATE `'.$table.'` SET ' . implode(', ', $mapping) . ' WHERE (`document_id` = :document_id)';
		}

		/**
		 * @param String $tableName
		 * @return String
		 */
		protected function getUpdateI18nDocumentQuery($table, $mapping, $lobParameters = array())
		{
			return 'UPDATE `'.$table.'` SET ' . implode(', ', $mapping) . ' WHERE `document_id` = :id AND `lang_i18n` = :lang';
		}

		protected function getDeleteDocumentQuery1()
		{
			return 'DELETE FROM `f_document` WHERE (`document_id` = :document_id)';
		}

		/**
		 * @param String $tableName
		 * @return String
		 */
		protected function getI18nDocumentQuery($tableName)
		{
			return "select * from ".$tableName." where document_id = :document_id and lang_i18n = :lang";
		}


		protected function getDeleteDocumentQuery2($table)
		{
			return 'DELETE FROM `'.$table.'` WHERE (`document_id` = :document_id)';
		}

		protected function getNewDocumentIdQuery1()
		{
			$i18nFieldNames = $this->getI18nFieldNames();
			return 'INSERT INTO f_document (document_model, '. implode(', ', $i18nFieldNames) .') VALUES (:document_model, :'. implode(', :', $i18nFieldNames) .')';
		}

		protected function getNewDocumentIdQuery2()
		{
			$i18nFieldNames = $this->getI18nFieldNames();
			return 'INSERT INTO f_document (document_id, document_model, '. implode(', ', $i18nFieldNames) .') VALUES (:document_id, :document_model, :'. implode(', :', $i18nFieldNames) .')';
		}

		/*
		 * Gestion de la table f_relation
		 */
		protected function getDeleteDocumentQuery3()
		{
			return 'DELETE FROM `f_relation` WHERE `relation_id1` = :relation_id1';
		}

		protected function get_insertDocumentQuery($table, $fieldsName, $parameters, $lobParameters = array())
		{
			return 'INSERT INTO `'.$table.'` (' . implode(', ', $fieldsName) .') VALUES (' . implode(', ', $parameters) .')';
		}

		protected function getLoadRelationsQuery()
		{
			return 'SELECT `relation_id2` AS document_id FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id ORDER BY `relation_order`';
		}

		protected function getloadAllRelations()
		{
			return 'SELECT `relation_name`, `relation_order`, `relation_id2`, `relation_id` FROM `f_relation` WHERE `relation_id1` = :relation_id1 ORDER BY `relation_id`, `relation_order`';
		}

		protected function getSaveRelationsQuery()
		{
			return 'DELETE FROM `f_relation` WHERE `relation_id1` = :relation_id1';
		}
		
		protected function getSaveRelationDeleteAllQuery()
		{
			return 'DELETE FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id';
		}

		protected function getSaveRelationsPreviousQuery()
		{
			return 'SELECT `relation_id2` AS doc_id, `relation_order` AS doc_order FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id';
		}
		
		protected function getSaveRelationInsertQuery()
		{
			return 'INSERT INTO `f_relation` (relation_id1, relation_id2, relation_order, relation_name, document_model_id1, document_model_id2, relation_id) VALUES (:relation_id1, :relation_id2, :relation_order, :relation_name, :document_model_id1, :document_model_id2, :relation_id)';
		}

		protected function getSaveRelationUpdateQuery()
		{
			return 'UPDATE `f_relation` SET relation_order = :new_order WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id AND relation_order = :relation_order';
		}
		
		protected function getSaveRelationDeleteQuery()
		{
			return 'DELETE FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id AND relation_order = :relation_order';
		}

		protected function getSaveRelationReorderQuery()
		{
			return 'UPDATE `f_relation` SET relation_order = -relation_order - 1 WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id AND relation_order < 0';
		}
		
		protected function getDeleteI18nDocument($tableName)
		{
			return "DELETE FROM `". $tableName . "` WHERE `document_id` = :id AND `lang_i18n` = :lang";
		}

	/// TREE QUERIES
	protected function dropTreeTableQuery($treeId)
	{
		return 'DROP TABLE IF EXISTS `f_tree_'. $treeId .'`';
	}
	
	protected function createTreeTableQuery($treeId)
	{
		return 'CREATE TABLE IF NOT EXISTS `f_tree_'. $treeId .'` ('
		. ' `document_id` int(11) NOT NULL default \'0\','
		. ' `parent_id` int(11) NOT NULL default \'0\','
		. ' `node_order` int(11) NOT NULL default \'0\','
		. ' `node_level` int(11) NOT NULL default \'0\','
		. ' `node_path` varchar(255) collate latin1_general_ci NOT NULL default \'/\','
        . ' `children_count` int(11) NOT NULL default \'0\','
        . ' PRIMARY KEY (`document_id`),'
        . ' UNIQUE KEY `tree_node` (`parent_id`, `node_order`),'
        . ' UNIQUE KEY `descendant` (`node_level`,`node_order`,`node_path`)'
        . ' ) ENGINE=InnoDB CHARACTER SET latin1 COLLATE latin1_general_ci';
	}
		
	protected function getNodeInfoQuery($treeId)
	{
		return 'SELECT document_id, parent_id, node_order, node_level, node_path, children_count FROM f_tree_'.$treeId
		. ' WHERE document_id = :document_id';
	}
	
	protected function getNodesInfoQuery($treeId, $documentCount)
	{
		$sql = 'SELECT document_id, parent_id, node_order, node_level, node_path, children_count FROM f_tree_'.$treeId
		 . ' WHERE document_id in ';
		$params = array();
		for($i = 0; $i < $documentCount; $i++) {$params[] = ':p' . $i;}
		return $sql . '(' . implode(', ', $params) . ')';
	}
	
	protected function getChildrenNodesInfoQuery($treeId)
	{
		return 'SELECT t.document_id, parent_id, node_order, node_level, node_path, children_count, d.document_model'
		. ' FROM f_tree_'.$treeId. ' AS t INNER JOIN f_document AS d ON t.document_id = d.document_id'
		. ' WHERE parent_id = :parent_id ORDER BY node_order';
	}
	
	protected function getDescendantsNodesInfoQuery($treeId)
	{
		return 'SELECT t.document_id, parent_id, node_order, node_level, node_path, children_count, d.document_model' 
		. ' FROM f_tree_'.$treeId. ' AS t INNER JOIN f_document AS d ON t.document_id = d.document_id'
		. '	WHERE node_level > :min_level AND node_level <= :max_level AND node_path like :node_path ORDER BY node_level, node_order';
	}
		
	protected function getChildrenIdQuery($treeId)
	{
		return 'SELECT document_id FROM f_tree_'.$treeId
		. ' WHERE parent_id = :parent_id ORDER BY node_order';
	}
	
	protected function getDescendantsIdQuery($treeId)
	{
		return 'SELECT document_id FROM f_tree_'.$treeId
		. ' WHERE node_level > :node_level AND node_path like :node_path';
	}	
	
	protected function getDeleteTreeDocumentQuery()
	{
		return 'UPDATE f_document SET treeid = NULL WHERE treeid = :treeid AND document_id <> :document_id';
	}
	
	protected function getDeleteTreeQuery($treeId)
	{
		return 'DELETE FROM f_tree_'.$treeId;
	}
	
	protected function getInsertNodeQuery($treeId)
	{
		return 'INSERT INTO f_tree_'.$treeId
		. ' (`document_id`, `parent_id`, `node_order`, `node_level`, `node_path`, `children_count`) VALUES (:document_id, :parent_id, :node_order, :node_level, :node_path, :children_count)';
	}
	
	protected function getUpdateDocumentTreeQuery()
	{
		return 'UPDATE f_document SET treeid = :treeid WHERE document_id = :document_id';
	}
	
	
	protected function orderNodesPrepareQuery($treeId, $countIds)
	{
		$sql = 'UPDATE f_tree_'.$treeId . ' SET node_order = - node_order - 1 WHERE document_id in ';
		$params = array();
		for($i = 0; $i < $countIds; $i++) {$params[] = ':p' . $i;}
		return $sql . '(' . implode(', ', $params) . ')';		
	}
	
	protected function orderNodesQuery($treeId)
	{
		return 'UPDATE f_tree_'.$treeId. ' SET node_order = :node_order WHERE document_id = :document_id';		
	}
	
	protected function deleteEmptyNodeDocumentQuery($treeId)
	{
		return 'DELETE FROM f_tree_'.$treeId
		. ' WHERE document_id = :document_id';
	}
	
	protected function getUpdateDocumentsTreeQuery($treeId)
	{
		return 'UPDATE f_document SET treeid = NULL WHERE document_id IN (SELECT document_id FROM f_tree_'.$treeId
		. ' WHERE node_level > :node_level AND node_path like :node_path)';
	}
	
	protected function deleteNodeRecursivelyQuery($treeId)
	{
		return 'DELETE FROM f_tree_'.$treeId . ' WHERE node_level > :node_level AND node_path like :node_path';
	}

	protected function updateChildenCountQuery($treeId)
	{
		return 'UPDATE f_tree_'.$treeId
		. ' SET children_count = children_count + :offest WHERE document_id = :document_id';
	}
	
	protected function updateChildrenOrderQuery($treeId, $offset)
	{
		return 'UPDATE f_tree_'.$treeId
		. ' SET node_order = node_order + :offest WHERE parent_id = :parent_id AND node_order >= :node_order order by node_order'. ($offset < 0 ? ' asc' : ' desc');
	}	

	protected function moveNodeBaseQuery($treeId)
	{
		return 'UPDATE f_tree_'.$treeId
		. ' SET parent_id = :parent_id, node_order = :node_order, node_level = node_level + :offestlvl, node_path = :node_path'
		. ' WHERE document_id = :document_id';
	}
	
	protected function moveNodeRecurseQuery($treeId)
	{
		return 'UPDATE f_tree_'.$treeId
		. ' SET node_level = node_level + :offestlvl, node_path = REPLACE(node_path, :from_path, :to_path)'
		. ' WHERE node_level > :node_level AND node_path like :node_path';
	}	
	
		protected function getTagsQuery()
		{
			return 'SELECT tag FROM f_tags WHERE id = :id';
		}

		protected function getDocumentIdsByTagQuery()
		{
			return 'SELECT id FROM f_tags WHERE tag = :tag';
		}

		protected function getHasTagsQuery($tags)
		{
			return 'SELECT count(*) nbtags FROM f_tags WHERE id = :id AND tag IN (\'' . join("', '", $tags) . '\')';
		}

		protected function getHasTagQuery()
		{
			return 'SELECT id FROM f_tags WHERE id = :id AND tag = :tag';
		}

		protected function getAddTagQuery()
		{
			return 'INSERT INTO f_tags (id, tag) VALUES (:id, :tag)';
		}

		protected function getRemoveTagQuery()
		{
			return 'DELETE FROM f_tags WHERE id = :id AND tag = :tag';
		}

		protected function getRemoveExclusiveTagQuery()
		{
			return 'DELETE FROM f_tags WHERE tag = :tag';
		}



		/**
		 * URL REWRITING f_url_rules
		 * rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type
		 */
		protected function getUrlRewritingQuery()
		{
			return 'SELECT from_url	FROM f_url_rules WHERE document_id = :id AND website_lang = :lang AND website_id = :website_id AND actionname = :actionname AND redirect_type = 200';
		}
		
		protected function getUrlRewritingInfoQuery()
		{
			return 'SELECT rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type FROM f_url_rules WHERE document_id = :id AND website_lang = :lang';
		}
		
		protected function setUrlRewritingQuery()
		{
			return 'INSERT INTO f_url_rules (document_id, website_lang, website_id, from_url, to_url, redirect_type, modulename, actionname, origine) VALUES (:document_id, :website_lang, :website_id, :from_url, :to_url, :redirect_type, :modulename, :actionname, :origine)';
		}

		protected function clearUrlRewritingQuery()
		{
			return 'DELETE FROM f_url_rules WHERE document_id = :document_id';
		}		
		
		protected function getUrlRewritingInfoByUrlQuery()
		{
			return "SELECT `rule_id`, `origine`, `modulename`, `actionname`, `document_id`, `website_lang`, `website_id`, `to_url`, `redirect_type` FROM `f_url_rules` WHERE from_url = :url AND website_id = :website_id AND `website_lang` = :website_lang";
		}

		protected function getPageForUrlQuery()
		{
			return 'SELECT rule_id, document_id, website_lang, website_id, to_url, redirect_type FROM f_url_rules WHERE from_url = :url AND (website_id = 0 OR website_id = :website_id)';
		}

		/**
		 * @return String
		 */
		protected function getAllTagsTagsQuery()
		{
			return 'SELECT tags.tag, tags.id FROM f_tags tags';
		}

		protected function getSettingQuery()
		{
			return 'SELECT value FROM f_settings WHERE package = :package AND name = :name AND userid = 0';
		}

		protected function getSettingPackageQuery()
		{
			return
		'SELECT package FROM f_settings'
		. ' WHERE value = :value AND name = :name AND userid = 0';
		}

		protected function getUserSettingQuery()
		{
			return
		'SELECT value FROM f_settings'
		. ' WHERE package = :package AND name = :name AND userid = :userid';
		}

		protected function getSaveSettingDeleteQuery()
		{
			return 'DELETE FROM `f_settings` WHERE `package` = :package AND `name` = :name AND `userid` = :userid';
		}

		protected function getSaveUserSettingQuery()
		{
			return 'INSERT INTO `f_settings` (`package`, `name`, `userid`, `value`) VALUES (:package, :name, :userid, :value)';
		}

		protected function getTruncateTableQuery($tableName)
		{
			return 'TRUNCATE TABLE '.$tableName;
		}

		protected function getWebsiteIdAndLangFromUrlQuery()
		{
			return 'SELECT document_id, lang_i18n FROM m_website_doc_website_i18n WHERE url_i18n = :url';
		}

		/**
		 * @return String
		 */
		protected function getCacheIdsByPatternQuery()
		{
			return "select distinct(cache_id) from f_simplecache_registration where pattern = :pattern";
		}
		/**
		 * @return String
		 */
		protected function getRegisterSimpleCacheQuery()
		{
			return "insert into f_simplecache_registration values (:pattern, :cacheId)";
		}

		/**
		 * @return String
		 */
		protected function getUnregisterSimpleCacheQuery()
		{
			return "delete from f_simplecache_registration where cache_id = :cacheId";
		}

		//
		// Permission Section
		//

		/**
		 * ACL Compilation query
		 *
		 * @return String
		 */
		protected function getCompileACLQuery()
		{
			return 'insert into `f_permission_compiled` values(:accessorId, :permission, :nodeId)';
		}

		/**
		 * ACL check exists for node query
		 *
		 * @return String
		 */
		protected function getCheckACLQuery($accessors)
		{
			return 'select count(*) from `f_permission_compiled` where `accessor_id` in (' . implode(', ', $accessors). ') and `permission` = :permission and `node_id` = :nodeId';
		}

		protected function getAccessorsByPermissionForNodeQuery()
		{
			return 'select distinct accessor_id from `f_permission_compiled` where `permission` = :permission and `node_id` = :nodeId';
		}

		/**
		 * ACL check entry compiled
		 *
		 * @return String
		 */
		protected function getHasPermissionACLQuery()
		{
			return 'SELECT COUNT(*) FROM `f_permission_compiled` WHERE `node_id` = :nodeId';
		}

		protected function getHasPermissionForPackageQuery()
		{
			return 'SELECT COUNT(*) FROM `f_permission_compiled` WHERE `node_id` = :nodeId AND `permission` LIKE :permission';
		}

		protected function getPermissionsForUserByNodeQuery($accessors)
		{
			return 'SELECT DISTINCT `permission` FROM `f_permission_compiled` WHERE `node_id` = :nodeId and `accessor_id` in (' . implode(', ', $accessors). ')';
		}

		/**
		 * ACL remove compiled node entries
		 *
		 * @return String
		 */
		protected function getRemoveACLForNodeQuery()
		{
			return 'DELETE FROM `f_permission_compiled` WHERE `node_id` = :nodeId';
		}

		/**
		 * ACL remove compiled node entries for a package
		 *
		 * @return String
		 */
		protected function getRemovePackageACLForNodeQuery()
		{
			return 'DELETE FROM `f_permission_compiled` WHERE `node_id` = :nodeId AND permission LIKE :permission';
		}


		/**
		 * @return String
		 */
		protected function getACLDefinitionPointQuery()
		{
			return 'SELECT DISTINCT node_id FROM f_permission_compiled WHERE permission LIKE :permission';
		}
		//
		// End of Permission Section
		//


		/**
		 * @return String
		 */
		protected function getRelationsQuery($documentId1, $documentId2, $relationId, $documentModel1, $documentModel2)
		{
			$sql = 'SELECT * FROM f_relation WHERE ';
			$where = array();
			if (!is_null($documentId1)) { $where[] = 'relation_id1 = :relation_id1'; }
			if (!is_null($documentModel1)) { $where[] = 'document_model_id1 = :document_model_id1'; }
			if (!is_null($documentId2)) { $where[] = 'relation_id2 = :relation_id2'; }
			if (!is_null($documentModel2)) { $where[] = 'document_model_id2 = :document_model_id2'; }
			if (!is_null($relationId)) { $where[] = 'relation_id = :relation_id'; }

			return $sql . join(' AND ', $where) . ' ORDER BY relation_order ASC';
		}

		/**
		 * @return String
		 */
		protected function getFromFrameworkCacheQuery()
		{
			return 'SELECT text_value from f_cache where cache_key = :key';
		}

		/**
		 * @return String
		 */
		protected function getMultipleFromFrameworkCacheQuery($keys)
		{
			return 'SELECT cache_key, text_value from f_cache where cache_key in ('.join(',', $keys).')';
		}

		/**
		 * @return String
		 */
		protected function deleteFrameworkCacheKeysQuery($keys)
		{
			return 'DELETE FROM f_cache WHERE cache_key in ('.join(',', $keys).')';
		}

		/**
		 * @return String
		 */
		protected function setInFrameworkCacheDeleteQuery()
		{
			return 'DELETE FROM f_cache WHERE cache_key = :key';
		}

		/**
		 * @return String
		 */
		protected function setInFrameworkCacheQuery()
		{
			return 'SELECT cache_key from f_cache where cache_key = :key';
		}

		/**
		 * @return String
		 */
		protected function setInFrameworkCacheInsertQuery()
		{
			return 'INSERT INTO f_cache (cache_key, text_value) VALUES (:key, :text_value)';
		}

		/**
		 * @return String
		 */
		protected function setInFrameworkCacheUpdateQuery()
		{
			return 'UPDATE f_cache SET text_value = :text_value WHERE cache_key = :key';
		}

		/**
		 * @return String
		 */
		protected function clearFrameworkCacheDeletePatternQuery()
		{
			return 'DELETE FROM f_cache where cache_key like :pattern';
		}

		/**
		 * @return String
		 */
		protected function clearFrameworkCacheDeleteQuery()
		{
			return 'DELETE FROM f_cache';
		}

		/**
		 * @return String
		 */
		protected function clearFrameworkCacheTruncateQuery()
		{
			return 'TRUNCATE TABLE f_cache';
		}
		
		/**
		 * DELETE FROM f_cache WHERE (insert_time + :ttl) < NOW()
		 * @return string
		 */
		protected function clearFrameworkCacheByTTLQuery()
		{
			return 'DELETE FROM f_cache WHERE (insert_time + :ttl) < NOW()';
		}

		// f_locale 
		
		/**
		 * @return string
		 */
		protected function getTranslateQuery()
		{
			return 'SELECT `content`, `format` FROM `f_locale` WHERE `lang` = :lang AND `id` = :id AND `key_path` = :key_path';
		}
		
		/**
		 * @return string
		 */		
		protected function addTranslateSelectQuery()
		{
			return 'SELECT `useredited` FROM `f_locale` WHERE `lang` = :lang AND `id` = :id  AND `key_path` = :key_path';
		}

		/**
		 * @return string
		 */
		protected function addTranslateQuery()
		{
			return 'INSERT INTO `f_locale` (`lang`, `id`, `key_path`, `content`, `useredited`, `format`) VALUES (:lang, :id, :key_path, :content, :useredited, :format)';
		}

		/**
		 * @return string
		 */
		protected function updateTranslateQuery()
		{
			return 'UPDATE `f_locale` SET `content` = :content, `useredited` = :useredited, `format` = :format WHERE `lang` = :lang AND `id` = :id  AND `key_path` = :key_path';
		}

		/**
		 * @return string
		 */
		protected function clearTranslationCacheQuery($package = null)
		{
			if ($package === null)
			{
				return 'DELETE FROM `f_locale` WHERE `useredited` != 1';
			}
			else
			{
				return "DELETE FROM `f_locale` WHERE `useredited` != 1 AND `key_path` LIKE '" . $package . ".%'";
			}
		}
		
		/**
		 * @return string
		 */
		protected function getPackageNamesQuery()
		{
			return "SELECT COUNT(*) AS `nbkeys`, `key_path` FROM `f_locale` GROUP BY `key_path` ORDER BY `key_path`";
		}

		/**
		 * @return string
		 */
		protected function getUserEditedPackageNamesQuery()
		{
			return "SELECT COUNT(*) AS `nbkeys`, `key_path` FROM `f_locale` WHERE `useredited` = 1 GROUP BY `key_path` ORDER BY `key_path`";
		}
		
		/**
		 * @return string
		 */
		protected function getPackageDataQuery()
		{
			return "SELECT `id`,`lang`,`content`,`useredited`,`format` FROM `f_locale` WHERE `key_path` = :key_path";
		}
		
		
		protected function deleteI18nKeyQuery($id, $lcid)
		{
			$query = "DELETE FROM `f_locale` WHERE `key_path` = :key_path";
			if ($id !== null)
			{
				$query .= " AND `id` = :id";
				if ($lcid !== null)
				{
					$query .= " AND `lang` = :lang";
				}
			}
			return $query;
		}

		/**
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 * @param array $params the parameters for the query, created and filled by buildQueryString
		 * @return String the queryString as a preparedStatement
		 */
		protected function buildQueryString($query, &$params)
		{
			$model = $query->getDocumentModel();
			if ($model !== null)
			{
				$qBuilder = new f_persistentdocument_DocumentQueryBuilder($model);
				$qBuilder->setFirstResult($query->getFirstResult());
				$qBuilder->setMaxResults($query->getMaxResults());

				$this->processProjections($query, $qBuilder, $model);

				if ($query->hasCriterions())
				{
					$this->processCriterions($query, $qBuilder);
				}
				if ($query->hasTreeCriterions())
				{
					$this->processTreeCriterions($query, $qBuilder);
				}
				if ($query->hasCriterias())
				{
					$this->processCriterias($query, $qBuilder);
				}
				if ($query->hasHavingCriterion())
				{
					$this->processHavingCriterion($query, $qBuilder);
				}
				if ($query->hasOrders())
				{
					$this->processOrders($query, $qBuilder);
				}

				$params = $qBuilder->getParams();
				return $qBuilder->getQueryString();
			}
			else
			{
				$qBuilder = new f_persistentdocument_DocumentQueryBuilder();
				$qBuilder->setFirstResult($query->getFirstResult());
				$qBuilder->setMaxResults($query->getMaxResults());

				$this->processProjections($query, $qBuilder, $model);

				if ($query->hasCriterions())
				{
					$this->processCriterions($query, $qBuilder);
				}
				if ($query->hasTreeCriterions())
				{
					$this->processTreeCriterions($query, $qBuilder);
				}
				if ($query->hasOrders())
				{
					$this->processOrders($query, $qBuilder);
				}
				$params = $qBuilder->getParams();
				return $qBuilder->getQueryString();
			}
		}

		/**
		 * @param f_persistentdocument_DocumentQueryBuilder $qBuilder
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 * @return array<String>
		 */
		private function processHavingCriterion($query, $qBuilder)
		{
			foreach ($query->getHavingCriterion() as $havingCriterion)
			{
				if ($havingCriterion instanceof f_persistentdocument_criteria_HavingSimpleExpression)
				{
					$propName = $this->resolveHavingCriterionPropName($havingCriterion, $qBuilder);
					$paramKey = $qBuilder->addParam($propName, $havingCriterion->getValue());
					$sql = $propName." ".$havingCriterion->getOp()." ".$paramKey;
				}
				elseif ($havingCriterion instanceof f_persistentdocument_criteria_HavingBetweenExpression)
				{
					$propName = $this->resolveHavingCriterionPropName($havingCriterion, $qBuilder);
					$minKey = $qBuilder->addParam($propName, $havingCriterion->getMin());
					$maxKey = $qBuilder->addParam($propName, $havingCriterion->getMax());
					if ($havingCriterion->isStrict())
					{
						$sql = "(".$propName." >= ".$minKey." and ".$propName." < ".$maxKey.")";
					}
					else
					{
						$sql = "(".$propName." between ".$minKey." and ".$maxKey.")";
					}
				}
				elseif ($havingCriterion instanceof f_persistentdocument_criteria_HavingInExpression)
				{
					$propName = $this->resolveHavingCriterionPropName($havingCriterion, $qBuilder);
					$paramKey = $qBuilder->addParam($propName, $havingCriterion->getValues());
					$sql = $propName.(($havingCriterion->getNot())?" not": "")." in ".$paramKey;
				}
				else
				{
					throw new Exception("Unsupported havingCriterion ".get_class($havingCriterion));
				}

				$qBuilder->addHaving($sql);
			}
		}

		/**
		 *
		 * @param f_persistentdocument_criteria_HavingCriterion $havingCriterion
		 * @return String
		 */
		private function resolveHavingCriterionPropName($havingCriterion, $qBuilder)
		{
			$propName = $havingCriterion->getPropertyName();
			if (is_string($propName))
			{
				return $propName;
			}
			if ($propName instanceof f_persistentdocument_criteria_RowCountProjection)
			{
				return "count(distinct ".$qBuilder->getTableAlias().".document_id)";
			}
			if ($propName instanceof f_persistentdocument_criteria_DistinctCountProjection)
			{
				$columnName = $qBuilder->getQualifiedColumnName($propName->getPropertyName());
				return 'count(distinct ' . $columnName .')';	
			}
			if ($propName instanceof f_persistentdocument_criteria_OperationProjection)
			{
				$columnName = $qBuilder->getQualifiedColumnName($propName->getPropertyName());
				return $propName->getOperation() . '(' . $columnName .')';
			}
		}
		
		/**
		 * @return boolean
		 */
		protected function handleProjectionsFirstPosition()
		{
			return true;
		}

		/**
		 * WARN: if you introduce a new kind of projection, be aware of "Projection->inFirstPosition".
		 * Cf. PersistentProvider.findColumn()
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 * @param f_persistentdocument_DocumentQueryBuilder $qBuilder
		 */
		private function processProjections($query, $qBuilder, $model)
		{
			if ($model === null)
			{
				$qBuilder->addFrom('f_document t0');
			}
			elseif (!$query->hasParent())
			{
				$qBuilder->addFrom($model->getTableName().' '.$qBuilder->getModelAlias() .' inner join f_document t0 using(document_id)');
			}

			if (!$query->hasProjection())
			{
				$qBuilder->addField('t0.document_id');
				$qBuilder->addField('t0.document_model');
				$qBuilder->addField('t0.treeid');
				foreach ($this->getI18nFieldNames() as $i18nFieldName)
				{
					$qBuilder->addField('t0.' . $i18nFieldName);
				}
				if ($query->getFetchMode() === QueryConstants::FETCH_MODE_DIRECT)
				{
					// TODO: explicit field filter (field by field)
					$qBuilder->addField($qBuilder->getModelAlias().'.*');
				}
				return;
			}

			$subdoc = 0;
			foreach ($query->getProjection() as $projection)
			{
				if ($projection instanceof f_persistentdocument_criteria_RowCountProjection)
				{
					$qBuilder->addFieldForProjection('count(distinct '.$qBuilder->getTableAlias().'.document_id) as ' . $projection->getAs(), $projection);
				}
				else if ($projection instanceof f_persistentdocument_criteria_DistinctCountProjection)
				{
					$columnName = $qBuilder->getQualifiedColumnName($projection->getPropertyName());
					$qBuilder->addFieldForProjection('count(distinct ' . $columnName .') as ' . $projection->getAs(), $projection);
				}
				else if ($projection instanceof f_persistentdocument_criteria_OperationProjection)
				{
					$columnName = $qBuilder->getQualifiedColumnName($projection->getPropertyName());
					$qBuilder->addFieldForProjection($projection->getOperation() . '(' . $columnName .') as ' . $projection->getAs(), $projection);
				}
				else if ($projection instanceof f_persistentdocument_criteria_ThisProjection)
				{
					$as = f_persistentdocument_criteria_ThisProjection::NAME;
					$query->addDocumentProjection($as);
					if ($query->hasParent())
					{
						throw new Exception("Can not handle ThisProjection on a criteria");
					}
					$alias = "t0";
					$qBuilder->addField($alias . '.document_id as '.$as.'_id');
					$qBuilder->addField($alias . '.document_model as '.$as.'_model');
					$qBuilder->addField($alias . '.treeid as ' . $as . '_treeid');
					foreach ($this->getI18nFieldNames() as $i18nFieldName)
					{
						$qBuilder->addField($alias . '.' . $i18nFieldName.' as '.$as.'_'.$i18nFieldName);
					}
					$qBuilder->addGroupBy($as . '_id');
				}
				else if ($projection instanceof f_persistentdocument_criteria_PropertyProjection)
				{
					$propNameInfo = explode(".", $projection->getPropertyName());
					$propNameInfoCount = count($propNameInfo);
					$property = $qBuilder->getModel()->getProperty($propNameInfo[0]);
					if ($property->isDocument())
					{
						$relationAlias = 'ra' . $subdoc;
						$documentalias = 'sd' . $subdoc;
						
						if ($propNameInfoCount == 1)
						{
							$query->addDocumentProjection($projection->getAs());
							$qBuilder->addField($documentalias . '.document_id as ' . $projection->getAs() . '_id');
							$qBuilder->addField($documentalias . '.document_model as ' . $projection->getAs() . '_model');
							$qBuilder->addField($documentalias . '.treeid as ' . $projection->getAs() . '_treeid');

							foreach ($this->getI18nFieldNames() as $i18nFieldName)
							{
								$qBuilder->addField($documentalias . '.' . $i18nFieldName . ' as ' . $projection->getAs() . '_' . $i18nFieldName);
							}
							
							$documentTableName = "f_document";
						}
						elseif ($propNameInfoCount == 2)
						{
							$projectionModel = $property->getDocumentModel();
							$subProperty = $projectionModel->getProperty($propNameInfo[1]);
							$subPropertyDbMapping = $subProperty->getDbMapping();
							$documentTableName = $projectionModel->getTableName();
							if ($subProperty->isLocalized())
							{
								$documentTableName .= $this->getI18nSuffix();
								$subPropertyDbMapping .= $this->getI18nSuffix();
								$qBuilder->addWhere($documentalias.'.lang_i18n = \'' . RequestContext::getInstance()->getLang() . '\'');
							}
							
							$qBuilder->addField($documentalias.'.'.$subPropertyDbMapping.' as '.$projection->getAs());
						}
						else
						{
							throw new Exception("Unsupported nested projection count (> 1): ".$projection->getPropertyName());
						}
						
						if ($property->isArray())
						{
							$qBuilder->addFrom('inner join f_relation '.$relationAlias.' on '.$relationAlias.'.relation_id1 = t0.document_id');
							$qBuilder->addFrom('inner join '.$documentTableName.' '.$documentalias.' on '.$documentalias.'.document_id = '.$relationAlias.'.relation_id2');
						}
						else
						{
							$columnName = $qBuilder->getQualifiedColumnName($projection->getPropertyName());
							$qBuilder->addFrom('inner join '.$documentTableName.' '.$documentalias.' on '.$documentalias.'.document_id = '.$columnName);
						}
							
						if ($projection->getGroup())
						{
							if ($propNameInfoCount == 1)
							{
								$qBuilder->addGroupBy($documentalias . '.document_id');
								$qBuilder->addGroupBy($documentalias . '.document_model');
								$qBuilder->addGroupBy($documentalias . '.treeid');
								foreach ($this->getI18nFieldNames() as $i18nFieldName)
								{
									$qBuilder->addGroupBy($documentalias . '.' . $i18nFieldName);
								}
							}
							else
							{
								// TODO
								throw new Exception("Unsupported operation: group");
							}
						}

						$subdoc++;
					}
					else
					{
						$columnName = $qBuilder->getQualifiedColumnName($projection->getPropertyName());
						$qBuilder->addFieldForProjection($columnName .' as ' . $projection->getAs(), $projection);
						if ($projection->getGroup())
						{
							$qBuilder->addGroupBy($columnName);
						}
					}
				}
			}
		}

		/**
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 * @param f_persistentdocument_DocumentQueryBuilder $qBuilder
		 */
		private function processCriterias($query, $qBuilder)
		{
			$currentTableAlias = $qBuilder->getModelAlias();
			foreach ($query->getCriterias() as $propertyName => $criteria)
			{
				$this->processCriteria($propertyName, $criteria, $currentTableAlias, $query, $qBuilder);
			}
		}

		/**
		 * @param string $propertyName
		 * @param f_persistentdocument_criteria_ExecutableQuery $criteria
		 * @param string $currentTableAlias
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 * @param f_persistentdocument_DocumentQueryBuilder $qBuilder
		 */
		private function processCriteria($propertyName, $criteria, $currentTableAlias, $query, $qBuilder)
		{
			$inverseProperty = $criteria->getInverseQuery();
			$subModel = $criteria->getDocumentModel();
			$qBuilder->pushModel($subModel, $propertyName);
			$subTableAlias = $qBuilder->getModelAlias();

			$currentModel = $query->getDocumentModel();
			$propertyInfo = $currentModel->getProperty($propertyName);
			if (is_null($propertyInfo))
			{
				if ($currentModel->hasInverseProperty($propertyName))
				{
					$propertyInfo = $currentModel->getInverseProperty($propertyName);
				}
				else
				{
					$propertyInfo = $subModel->getProperty($propertyName);
				}
				$inverseProperty = true;
			}
			$join = $criteria->getLeftJoin() ? 'left outer join ' : 'inner join ';
			if ($propertyInfo->getMaxOccurs() == 1)
			{
				// mono-valued property
				if ($inverseProperty)
				{
					$qBuilder->distinctNeeded();
					$qBuilder->addFrom($join.$subModel->getTableName().' '.$subTableAlias.' on '.$currentTableAlias.'.document_id = '.$subTableAlias.'.'.$propertyInfo->getDbMapping());
				}
				else
				{
					$qBuilder->addFrom($join.$subModel->getTableName().' '.$subTableAlias.' on '.$subTableAlias.'.document_id = '.$currentTableAlias.'.'.$propertyInfo->getDbMapping());
				}
			}
			else
			{
				// multi-valued property
				$qBuilder->distinctNeeded();

				$qBuilder->newRelation();
				$relationAlias = $qBuilder->getRelationAlias();

				if ($inverseProperty)
				{
					$relation_id = RelationService::getInstance()->getRelationId($propertyInfo->getDbMapping());
					$qBuilder->addFrom($join.'f_relation '.$relationAlias.' on '.$relationAlias.'.relation_id2 = '.$currentTableAlias.'.document_id AND '.$relationAlias.'.relation_id = '.$relation_id);
					$qBuilder->addFrom($join.$subModel->getTableName().' '.$subTableAlias.' on '.$subTableAlias.'.document_id = '.$relationAlias.'.relation_id1');
				}
				else
				{
					$relation_id = RelationService::getInstance()->getRelationId($propertyName);
					$qBuilder->addFrom($join.'f_relation '.$relationAlias.' on '.$relationAlias.'.relation_id1 = '.$currentTableAlias.'.document_id AND '.$relationAlias.'.relation_id = '.$relation_id);
					$qBuilder->addFrom($join.$subModel->getTableName().' '.$subTableAlias.' on '.$subTableAlias.'.document_id = '.$relationAlias.'.relation_id2');
				}

			}

			if ($criteria->hasProjection())
			{
				$this->processProjections($criteria, $qBuilder, $qBuilder->getModel());
			}

			$this->processTreeCriterions($criteria, $qBuilder);
			$this->processCriterions($criteria, $qBuilder);

			if ($criteria->hasCriterias())
			{
				$this->processCriterias($criteria, $qBuilder);
			}
			$qBuilder->popModel();
		}

		/**
		 * @param f_persistentdocument_DocumentQueryBuilder $qBuilder
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 */
		private function processCriterions($query, $qBuilder)
		{
			foreach ($query->getCriterions() as $criterion)
			{
				$this->processCriterion($criterion, $query, $qBuilder);
			}
		}

		/**
		 * @param f_persistentdocument_DocumentQueryBuilder $qBuilder
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 */
		private function processCriterion($criterion, $query, $qBuilder)
		{
			if ($criterion instanceof f_persistentdocument_criteria_HasTagExpression)
			{
				$currentAlias = $qBuilder->getModelAlias();
				$tagAlias = $qBuilder->newTableAlias();
				$qBuilder->addFrom('inner join f_tags '.$tagAlias.' on '.$currentAlias.'.document_id = '.$tagAlias.'.id');
				$pAlias = $qBuilder->addParam('tag', $criterion->getTagName());
				$qBuilder->addWhere($tagAlias.'.tag = '.$pAlias);
				return;
			}

			if ($criterion instanceof f_persistentdocument_criteria_IsTaggedExpression)
			{
				$qBuilder->distinctNeeded();
				$currentAlias = $qBuilder->getModelAlias();
				$tagAlias = $qBuilder->newTableAlias();
				$qBuilder->addFrom('inner join f_tags '.$tagAlias.' on '.$currentAlias.'.document_id = '.$tagAlias.'.id');
				return;
			}

			if ($criterion instanceof f_persistentdocument_criteria_Junction)
			{
				$qBuilder->beginJunction($criterion);
				$subCriterions = $criterion->getCriterions();
				foreach ($subCriterions as $subcriterion)
				{
					if ($subcriterion instanceof f_persistentdocument_criteria_TreeCriterion)
					{
						$this->processTreeCriterion($subcriterion, $query, $qBuilder);
					}
					else if ($subcriterion instanceof f_persistentdocument_criteria_Criterion || $subcriterion instanceof f_persistentdocument_criteria_Junction)
					{
						$this->processCriterion($subcriterion, $query, $qBuilder);
					}
					else
					{
						throw new Exception('Invalide type : '.get_class($subcriterion) .', Criterion expected');
					}
				}
				$qBuilder->endJunction();
				return;
			}

			//TODO ehau : experimental
			$property = $criterion->popPropertyName();
			if ($property !== null)
			{
				list($relationName, $c) = $query->createSubCriteria($property);
				$c->add($criterion);
				$currentTableAlias = $qBuilder->getModelAlias();
				$this->processCriteria($relationName, $c, $currentTableAlias, $query, $qBuilder);
				return;
			}

			$propertyName = $criterion->getPropertyName();
			$columnName = $qBuilder->getQualifiedColumnName($propertyName);

			if ($criterion instanceof f_persistentdocument_criteria_BetweenExpression)
			{
				$minKey = $qBuilder->addParam($propertyName, $criterion->getMin());
				$maxKey = $qBuilder->addParam($propertyName, $criterion->getMax());
				$qBuilder->addWhere('('.$columnName.' between '.$minKey.' and '.$maxKey.')');
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_EmptyExpression)
			{
				if ($qBuilder->getModel()->isDocumentProperty($propertyName))
				{
					if ($qBuilder->getModel()->isUniqueProperty($propertyName))
					{
						// intsimoa : I prefer NULL values for this case, but ...
						$qBuilder->addWhere('('.$columnName. ' IS NULL)');
					}
					else
					{
						$qBuilder->addWhere('('.$columnName. ' = 0)');
					}
				}
				else
				{
					$qBuilder->addWhere('('.$columnName. ' IS NULL OR '.$columnName.' = \'\')');
				}
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_InExpression)
			{
				$values = $criterion->getValues();
				if (count($values) >= 1)
				{
					$sql = '('.$columnName;

					if ($criterion->getNot())
					{
						$sql .= ' NOT';
					}
	
					$sql .= ' IN (';
					$keys = array();
					foreach ($values as $value)
					{
						$keys[] = $qBuilder->addParam($propertyName, $value);
					}
					$sql .= join(',', $keys);
					$sql .= '))';
					$qBuilder->addWhere($sql);
				}
				else if ($criterion->getNot())
				{
					// Nothing to do: nothing is excluded, so no restriction.
				}
				else
				{
					// Nothing is included, so nothing should be returned...
					$qBuilder->addWhere('(0)');
				}
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_LikeExpression)
			{
				$value = $criterion->getValue();
				if ($criterion->getIgnoreCase())
				{
					$value = strtolower($value);
					$columnName = 'lower('.$columnName.')';
				}
				$op = $criterion->getNot() ? 'NOT LIKE' : 'LIKE';
					
				$key = $qBuilder->addParam($propertyName, $criterion->getMatchMode()->toMatchString($value));
				$qBuilder->addWhere('('.$columnName.' '.$op.' '.$key.')');
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_NotEmptyExpression)
			{
				if ($qBuilder->getModel()->isDocumentProperty($propertyName))
				{
					if ($qBuilder->getModel()->isUniqueProperty($propertyName))
					{
						// intsimoa : I prefer NULL values for this case, but ...
						$qBuilder->addWhere('('.$columnName. ' IS NOT NULL)');
					}
					else
					{
						$qBuilder->addWhere('('.$columnName. ' > 0)');
					}
				}
				else
				{
					$qBuilder->addWhere('('.$columnName. ' IS NOT NULL and '.$columnName.' != \'\')');
				}
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_NotNullExpression)
			{
				$qBuilder->addWhere('('.$columnName. ' IS NOT NULL)');
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_NullExpression)
			{
				$qBuilder->addWhere('('.$columnName. ' IS NULL)');
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_PropertyExpression)
			{
				$otherPropertyName = $criterion->getOtherPropertyName();
				$otherColumnName = $qBuilder->getQualifiedColumnName($otherPropertyName);

				switch ($criterion->getOp())
				{
					case '=':
					case '!=':
					case '<=':
					case '>=':
					case '<':
					case '>':
						$qBuilder->addWhere('('.$columnName.' '.$criterion->getOp().' '.$otherColumnName.')');
						break;
					default:
						throw new Exception('Unknown operator '.$criterion->getOp());
						break;
				}
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_SimpleExpression)
			{
				switch ($criterion->getOp())
				{
					case '=':
					case '!=':
					case '<=':
					case '>=':
					case '<':
					case '>':
						$value = $criterion->getValue();
						if ($criterion->getIgnoreCase())
						{
							$value = strtolower($value);
							$columnName = 'lower('.$columnName.')';
						}
						$key = $qBuilder->addParam($propertyName, $value);
						$qBuilder->addWhere('('.$columnName.' '.$criterion->getOp().' '.$key.')');
						break;
					default:
						throw new Exception('Unknown operator '.$criterion->getOp());
						break;
				}
			}
		}

		/**
		 * @param f_persistentdocument_DocumentQueryBuilder $qBuilder
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 */
		private function processTreeCriterions($query, $qBuilder)
		{
			foreach ($query->getTreeCriterions() as $criterion)
			{
				$this->processTreeCriterion($criterion, $query, $qBuilder);
			}
		}


		/**
		 * @param f_persistentdocument_criteria_TreeCriterion $criterion
		 * @param f_persistentdocument_DocumentQueryBuilder $qBuilder
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 */
	private function processTreeCriterion($criterion, $query, $qBuilder)
	{
		$modelAlias = $qBuilder->getModelAlias();

		if ($criterion instanceof f_persistentdocument_criteria_AncestorOfExpression)
		{
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$qBuilder->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'AncestorOfExpression Node ' . $docId . ' not in tree');
				return;				
			}
			
			$level = $criterion->getLevel();	
			$treeAlias = $qBuilder->newTableAlias();
			$qBuilder->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			$childTreeAlias = $qBuilder->newTableAlias();
			if ($level === 1)
			{
				$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias.' INNER JOIN f_tree_'.$treeId.' '.$childTreeAlias.' ON ('.$childTreeAlias.'.document_id = '. $docId
					. ' AND '.$treeAlias.'.document_id = '.$childTreeAlias.'.parent_id)';
				$qBuilder->addWhere($modelAlias.'.document_id = (' .$subquery. ')');
			}
			else
			{
				$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias.' INNER JOIN f_tree_'.$treeId.' '.$childTreeAlias.' ON ('.$childTreeAlias.'.document_id = '. $docId
					. ' AND '.$treeAlias.'.node_level < '.$childTreeAlias.'.node_level';
				if ($level > 1)
				{
					$subquery .= ' AND '.$treeAlias.'.node_level >= ('.$childTreeAlias.'.node_level - '.$level.')';
				}				
				$subquery .= ' AND LOCATE(CONCAT(\'/\', '.$treeAlias.'.document_id, \'/\' ) , '.$childTreeAlias.'.node_path ) > 0)';
				$qBuilder->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');	
			}
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_DescendentOfExpression)
		{
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$qBuilder->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'DescendentOfExpression Node ' . $docId . ' not in tree');
				return;				
			}			

			$level = $criterion->getLevel();
			
			$qBuilder->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			if ($level == 1)
			{
				$treeAlias = $qBuilder->newTableAlias();
				$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
					. ' WHERE '.$treeAlias.'.parent_id = '.$docId;
				$qBuilder->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');
			}
			else
			{
				$treeAlias = $qBuilder->newTableAlias();
				
				$parenTreeAlias = $qBuilder->newTableAlias();
				
				$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
					. ' INNER JOIN f_tree_'.$treeId.' '.$parenTreeAlias.' ON ('.$parenTreeAlias.'.document_id = '. $docId
					. ' AND '.$treeAlias.'.node_level > '.$parenTreeAlias.'.node_level'
					. ' AND '.$treeAlias.'.node_path LIKE CONCAT('.$parenTreeAlias.'.node_path, \''.$docId.'/%\')';
				if ($level > 1)
				{
					$subquery .= ' AND '.$treeAlias.'.node_level <= '.$parenTreeAlias.'.node_level + '.$level;
				}
				$qBuilder->addWhere($modelAlias.'.document_id IN (' .$subquery. '))');
			}
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_NextSiblingOfExpression)
		{		
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$qBuilder->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'NextSiblingOfExpression Node ' . $docId . ' not in tree');
				return;				
			}	
			$treeAlias = $qBuilder->newTableAlias();
			$qBuilder->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			$nodeTreeAlias = $qBuilder->newTableAlias();
			
			$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
				. ' INNER JOIN f_tree_'.$treeId.' '.$nodeTreeAlias.' ON ('.$nodeTreeAlias.'.document_id = '. $docId
				. ' AND '.$treeAlias.'.parent_id = '.$nodeTreeAlias.'.parent_id'
				. ' AND '.$treeAlias.'.node_order > '.$nodeTreeAlias.'.node_order)';
			
			$qBuilder->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_PreviousSiblingOfExpression)
		{
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$qBuilder->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'PreviousSiblingOfExpression Node ' . $docId . ' not in tree');
				return;				
			}	
			$treeAlias = $qBuilder->newTableAlias();
			$qBuilder->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			$nodeTreeAlias = $qBuilder->newTableAlias();
			
			$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
				. ' INNER JOIN f_tree_'.$treeId.' '.$nodeTreeAlias.' ON ('.$nodeTreeAlias.'.document_id = '. $docId
				. ' AND '.$treeAlias.'.parent_id = '.$nodeTreeAlias.'.parent_id'
				. ' AND '.$treeAlias.'.node_order < '.$nodeTreeAlias.'.node_order)';
				
			$qBuilder->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_SiblingOfExpression)
		{
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$qBuilder->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'SiblingOfExpression Node ' . $docId . ' not in tree');
				return;				
			}	
			$treeAlias = $qBuilder->newTableAlias();
			$qBuilder->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			$nodeTreeAlias = $qBuilder->newTableAlias();
			
			$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
				. ' INNER JOIN f_tree_'.$treeId.' '.$nodeTreeAlias.' ON ('.$nodeTreeAlias.'.document_id = '. $docId
				. ' AND '.$treeAlias.'.parent_id = '.$nodeTreeAlias.'.parent_id'
				. ' AND '.$treeAlias.'.document_id != '.$docId.')';
			
			$qBuilder->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');	
		}
	}
	
		/**
		 * @param f_persistentdocument_DocumentQueryBuilder $qBuilder
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 * @return array<String>
		 */
		private function processOrders($query, $qBuilder)
		{
			foreach ($query->getOrders() as $order)
			{
				$qBuilder->addOrder($order);
			}
		}

		/**
		 * @see f_persistentdocument_PersistentProvider::addLangQuery()
		 *
		 * @param string $lang
		 * @return string
		 */
		protected function addLangQuery($lang)
		{
			return "ALTER TABLE `f_document` ADD `label_$lang` VARCHAR( 255 ) NULL";
		}
		
		protected function columnExists($tableName, $colName)
		{
			return f_util_ArrayUtils::isNotEmpty($this->executeSQLSelect("show columns from $tableName where field = '$colName'")->fetchAll());
		}

		/**
		 * @return string
		 */
		protected function getRelationIdQuery1()
		{
			return "SELECT relation_id FROM f_relationname WHERE property_name = :property_name";
		}

		/**
		 * @return string
		 */
		protected function getRelationIdQuery2()
		{
			return "INSERT INTO f_relationname (property_name) VALUES (:property_name)";
		}
}

/**
 * "Inner" class. Helper for query construction
 */
class f_persistentdocument_DocumentQueryBuilder
{
	private $fields = array();
	private $toEndFields = array();
	private $distinctNeeded = false;
	private $params = array();

	private $modelCount = 0;
	private $models = array();
	private $modelsAlias = array();

	private $from = array();
	private $where = array();
	private $order = array();

	private $aliasCount = 0;
	private $relationAliasCount = 0;
	private $aliasByPath = array();
	private $currentPropertyPath = array();
	private $groupBy = array();
	private $having = array();

	private $junctions = array();
	private $currentSqlJunction = null;

	private $localizedTables = array();
	private $i18nSuffix = '_i18n';

	private $treeTableName;
	private $treeTableNameCurrentModelAlias;

	/**
	 * @var Integer
	 */
	private $firstResult = 0;
	/**
	 * @var Integer
	 */
	private $maxResults = -1;

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	public function __construct($model = null)
	{
		$this->pushModel($model);
	}

	public function setFirstResult($firstResult)
	{
		$this->firstResult = $firstResult;
	}

	public function setMaxResults($maxResults)
	{
		$this->maxResults = $maxResults;
	}


	public function setTreeTableName($tableAlias, $currentModelAlias)
	{
		if ($this->treeTableName === null)
		{
			$this->treeTableName = $tableAlias;
			$this->treeTableNameCurrentModelAlias = $currentModelAlias;
		}
	}
	
	
	/**
	 * @param String $propertyName
	 * @param mixed $value
	 * @return String
	 */
	public function addParam($propertyName, $value)
	{
		$key = ':p'.(count($this->params)+1);
		$this->params[$key] = $this->translateValue($propertyName, $value);
		return $key;
	}

	/**
	 * @param String $sql
	 */
	public function addHaving($sql)
	{
		$this->having[] = $sql;
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	public function pushModel($model, $propertyName = null)
	{
		if (!is_null($model))
		{
			$this->models[] = $model;
			$this->modelCount++;
			$this->aliasCount++;
			$this->modelsAlias[] = $this->getTableAlias();

			if (!is_null($propertyName))
			{
				$this->currentPropertyPath[] = $propertyName;
				$this->aliasByPath[join('.', $this->currentPropertyPath)] = $this->getTableAlias();
			}
		}
	}

	public function newTableAlias()
	{
		$this->aliasCount++;
		return $this->getTableAlias();
	}

	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public function popModel()
	{
		$this->modelCount--;
		array_pop($this->currentPropertyPath);
		array_pop($this->modelsAlias);

		return array_pop($this->models);
	}

	public function beginJunction($junction)
	{
		$this->currentSqlJunction = (object)array('op' => $junction->getOp(), 'where' => array());
		$this->junctions[] = $this->currentSqlJunction;
	}

	public function endJunction()
	{
		$sqlJunction = array_pop($this->junctions);
		if (!empty($this->junctions))
		{
			$this->currentSqlJunction = end($this->junctions);
		}
		else
		{
			$this->currentSqlJunction = null;
		}

		$this->addWhere('(' . join(' '.$sqlJunction->op.' ', $sqlJunction->where) . ')');
	}

	/**
	 * @param String $field
	 */
	public function addField($field)
	{
		// avoid duplicate field using $field as key
		$this->fields[$field] = $field;
	}
	
	/**
	 * @param String $field
	 * @param $projection
	 */
	public function addFieldForProjection($field, $projection)
	{
		if (isset($projection->inFirstPosition))
		{
			$this->setFirstField($field);
		}
		else
		{
			// avoid duplicate field using $field as key
			$this->fields[$field] = $field;
		}
	}
	
	/**
	 * @var string
	 */
	protected $firstField;
	public function setFirstField($field)
	{
		if ($this->firstField !== null)
		{
			throw new Exception("only one first field");
		}
		$this->firstField = $field;
	}
	
	/**
	 * @return string[]
	 */
	protected function getFields()
	{
		if ($this->firstField !== null)
		{
			array_unshift($this->fields, $this->firstField);
			$this->firstField = null;
		}
		return $this->fields;
	}

	/**
	 * @param String $from
	 */
	public function addFrom($from)
	{
		$this->from[] = $from;
	}

	/**
	 * @param String $where
	 */
	public function addWhere($where)
	{
		if (is_null($this->currentSqlJunction))
		{
			$this->where[] = $where;
		}
		else
		{
			$this->currentSqlJunction->where[] = $where;
		}
	}

	/**
	 * @param String $groupBy
	 */
	public function addGroupBy($groupBy)
	{
		$this->groupBy[] = $groupBy;
	}

	/**
	 * @param Order $order
	 */
	public function addOrder($order)
	{
		$orderStr = '';
		$propertyName = $order->getPropertyName();
		if (strpos($propertyName, '.') !== false)
		{
			$propInfo = explode(".", $propertyName);
			$lastPropName = array_pop($propInfo);
			if ($this->modelCount == 0)
			{
				throw new Exception("Could not resolve $propertyName. Did you made any criteria ?");
			}
			$model = $this->getModel();
			foreach ($propInfo as $propName)
			{
				$prop = $model->getProperty($propName);
				if ($prop === null)
				{
					$prop = $model->getInverseProperty($propName);
				}
				if ($prop === null || !$prop->isDocument())
				{
					throw new Exception("$propName is not a document property");
				}
				$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($prop->getType());
			}
			if ($model->hasProperty($lastPropName))
			{
				$columnName = $model->getProperty($lastPropName)->getDbMapping();
			}
			else
			{
				$columnName = $lastPropName;
			}
			
			$tablePropName = $this->getTablePropertyName($propertyName);
			if (!isset($this->aliasByPath[$tablePropName]))
			{
				throw new Exception("Could not resolve $tablePropName. Did you made a criteria on it ?");
			}
			$orderStr = $this->aliasByPath[$tablePropName].'.'.$columnName;
		}
		else
		{
			if ($this->modelCount == 0)
			{
				$tableAlias = 't0';
				$model = f_persistentdocument_PersistentDocumentModel::getInstance("generic", "Document");
			}
			else
			{
				// FIXME: using statically t1 as table alias ... but is it *really* a problem ?
				$tableAlias = 't1';
				$model = $this->getModel();
			}
			$propertyName = $order->getPropertyName();
			if ($model->hasProperty($propertyName))
			{
				$columnName = $model->getProperty($propertyName)->getDbMapping();
			}
			else
			{
				$columnName = $propertyName;
			}
			$orderStr = $tableAlias.'.'.$columnName;
		}
		if ($order->getIgnorecase())
		{
			$orderStr = "lower(".$orderStr.")";
		}
		if (!$order->getAscending())
		{
			$orderStr .= ' desc';
		}
		$this->order[] = $orderStr;
	}

	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public function getModel()
	{
		return $this->models[$this->modelCount-1];
	}

	public function getModelAlias()
	{
		return ($this->modelCount == 0) ? 't0' : $this->modelsAlias[$this->modelCount-1];
	}

	/**
	 * @return String
	 */
	public function getTableAlias()
	{
		return 't'.$this->aliasCount;
	}

	/**
	 * If called, a "DISTINCT" selector will be added
	 */
	public function distinctNeeded()
	{
		$this->distinctNeeded = true;
	}

	/**
	 * @return String
	 */
	public function getQueryString()
	{
		$query = 'select '.(($this->distinctNeeded)?'distinct ':''). implode(', ', $this->getFields()).
		' from '.join(' ', $this->from);
		if ($this->treeTableName !== null && empty($this->order) && $this->maxResults != 2)
		{
			$query .= ' inner join '.$this->treeTableName.' as treeOrder on '.$this->treeTableNameCurrentModelAlias.'.document_id = treeOrder.document_id';
			$this->order[] = 'treeOrder.node_level, treeOrder.node_order';
		}
		$query .= (!empty($this->where)? ' where '.implode(' and ', $this->where) : '').
			(!empty($this->groupBy)? ' group by '.implode(',', $this->groupBy) : '').
			(!empty($this->having)? ' having '.implode(' and ', $this->having) : '').                                     
			((!empty($this->order))? ' order by '.implode(', ', $this->order) : '').
			(($this->maxResults == -1)? '' : ' LIMIT '.$this->firstResult.', '.$this->maxResults);
		return $query;
	}

	public function newRelation()
	{
		$this->relationAliasCount++;
	}

	public function getRelationAlias()
	{
		return 'r'.$this->relationAliasCount;
	}

	public function getComponentDbMapping($propertyName)
	{
		if ($this->modelCount == 0)
		{
			return $propertyName;
		}
		else
		{
			return $this->getModel()->getProperty($propertyName)->getDbMapping();
		}
	}

	public function getQualifiedColumnName($propertyName)
	{
		if ($this->modelCount == 0)
		{
			return $this->getModelAlias() . '.' . $propertyName;
		}

		$model = $this->getModel();
		$property = $model->getProperty($propertyName);
		if ($property === NULL)
		{
			throw new Exception('Invalid property name : '.$propertyName);
			// TODO ...
			$property = $model->getInverseProperty($propertyName);
			if ($property === NULL)
			{
				throw new Exception('Invalid property name : '.$propertyName);
			}
		}

		if ($property->isLocalized())
		{
			$this->checkLocalizedTable($model);
			$qName = 'l' . $this->getModelAlias() . '.' . $property->getDbMapping() . $this->getI18nSuffix();
		}
		else
		{
			$qName = $this->getModelAlias() . '.' . $property->getDbMapping();
		}

		return $qName;
	}

	// private methods

	private function getI18nSuffix()
	{
		return $this->i18nSuffix;
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	private function checkLocalizedTable($model)
	{

		$tableAlias = $this->getModelAlias();
		if (!array_key_exists($tableAlias, $this->localizedTables))
		{
			$table  = $model->getTableName() . $this->getI18nSuffix();
			$localizedTableAlias = 'l'. $tableAlias;
			$lang  = RequestContext::getInstance()->getLang();

			$from = 'inner join ' . $table . ' ' . $localizedTableAlias . ' on '
			. $tableAlias .'.document_id = ' . $localizedTableAlias .'.document_id and '
			. $localizedTableAlias .'.lang_i18n = \'' . $lang . '\'';

			$this->addFrom($from);
			$this->localizedTables[$tableAlias] = $table;
		}
	}

	private function translateValue($propertyName, $value)
	{
		// TODO something with model
		return $value;
	}

	private function getTablePropertyName($propertyName)
	{
		$lastIndex = strrpos($propertyName, '.');
		return substr($propertyName, 0, $lastIndex);
	}

	private function getRelativePropertyName($propertyName)
	{
		$lastIndex = strrpos($propertyName, '.');
		return substr($propertyName, $lastIndex+1);
	}
}

class MysqlStatment
{
	/**
	 * @var PDOStatement
	 */
	private $stmt;

	/**
	 * @var String
	 */
	private $sql;

	/**
	 * @var array
	 */
	private $replacements;

	private $params = array();

	public static $time = array('count' => 0, 'prepare' => 0 , 'countexec' => 0, 'err' => 0, 'exec' => 0, 'read' => 0);


	/**
	 * @param String $sql
	 * @param PDO $driver
	 */
	function __construct($sql, $driver)
	{
		self::$time['count']++;
		$start = microtime(true);
		$this->sql = $sql;
		$this->stmt = $driver->prepare($sql);
		self::$time['prepare'] += (microtime(true) - $start);

		if ($this->stmt === false)
		{
			self::$time['err']++;
			Framework::error($this->sql);
			$errorCode = $driver->errorCode();
			$msg = "Driver ERROR Code (". $errorCode . ") : " . var_export($driver->errorInfo(), true)."\n";
			$e = new f_DatabaseException($errorCode, $msg);
			Framework::exception($e);
			throw $e;
		}

	}

	public function bindValue($parameterName, $value, $type = null)
	{
		$this->stmt->bindValue($parameterName, $value, $this->getStatmentType($type));
		if (Framework::isDebugEnabled())
		{
			$this->params[$parameterName] = $value;
		}
	}

	public function execute($parameters = null)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::startBench();
				
			if (is_array($parameters))
			{
				foreach ($parameters as $parameterName => $parameterValue)
				{
					$this->params[$parameterName] = $parameterValue;
				}
			}
		}

		self::$time['countexec']++;
		$start = microtime(true);

		if (!$this->stmt->execute($parameters) && $this->stmt->errorCode() != '00000')
		{
			self::$time['err']++;
			if (Framework::isDebugEnabled())
			{
				$trace = "SQL|". str_replace(array("\n", "\t"), '', $this->sql.'|'. var_export($this->params, true));
				Framework::endBench($trace);
			}
			return false;
		}

		$time = (microtime(true) - $start);
		self::$time['exec'] += $time;

		if (Framework::isDebugEnabled())
		{
			if ($time > 0.01)
			{
				$trace = 'SQL|' .$time . '|SLOWQUERY|' . str_replace(array("\n", "\t"), '', $this->sql . "|" . var_export($this->params, true));
			}
			else
			{
				$trace = "SQL|". str_replace(array("\n", "\t"), '', $this->sql.'|'. var_export($this->params, true));
			}
				
			Framework::endBench($trace);
		}
		return true;
	}

	public function fetch($mode)
	{
		$start = microtime(true);
		$fetch =  $this->stmt->fetch($this->getStatmentFetchMode($mode));
		self::$time['read'] += (microtime(true) - $start);
		return $fetch;
	}

	public function fetchAll($mode)
	{
		$start = microtime(true);
		$fetch =  $this->stmt->fetchAll($this->getStatmentFetchMode($mode));
		self::$time['read'] += (microtime(true) - $start);
		return $fetch;
	}

	public function fetchColumn($columnNumber = 0)
	{
		return $this->stmt->fetchColumn($columnNumber);
	}

	public function closeCursor()
	{
		if ($this->stmt !== false)
		{
			$this->stmt->closeCursor();
		}
	}

	public function errorCode()
	{
		return $this->stmt->errorCode();
	}

	public function errorInfo()
	{
		return $this->stmt->errorInfo();
	}

	public function rowCount()
	{
		return $this->stmt->rowCount();
	}

	private function getStatmentType($type)
	{
		switch ($type)
		{
			case PersistentProviderConst::PARAM_INT:
				return PDO::PARAM_INT;
			case PersistentProviderConst::PARAM_NULL:
				return PDO::PARAM_NULL;
			case PersistentProviderConst::PARAM_STR:
			case PersistentProviderConst::PARAM_DATE:
			case PersistentProviderConst::PARAM_LOB:
			case PersistentProviderConst::FETCH_NUM:
				return PDO::PARAM_STR;
			default:
				return PDO::PARAM_STR;
		}
	}

	private function getStatmentFetchMode($mode)
	{
		switch ($mode)
		{
			case PersistentProviderConst::FETCH_NUM:
				return PDO::FETCH_NUM;
			case PersistentProviderConst::FETCH_COLUMN:
				return PDO::FETCH_COLUMN;
			default:
				return PDO::FETCH_ASSOC;
		}
	}

	public function __destruct()
	{
		$this->stmt->closeCursor();
	}
}
