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
	 * @return string
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
		$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'") ;
		$pdo = new PDO($dsn, $username, $password, $options);
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
	
	
	protected function escapeFieldName($fieldName)
	{
		return '`' . $fieldName . '`';
	}
	
	private $schemaManager = null;

	/**
	 * @return change_SchemaManager
	 */
	public function getSchemaManager()
	{
		if ($this->schemaManager === null)
		{
			$this->schemaManager = new change_SchemaManagerMySql();
		}
		return $this->schemaManager;
	}
	
	
	/**
	 * @param string $script
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
	 * @param string $name
	 * @param mixed $value
	 * @param string $propertyType
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
		 * @return string
		 */
		protected function mutateFrameworkQuery()
		{
			return 'UPDATE `f_document` SET `document_model` = :destmodelname WHERE `document_id` = :id AND `document_model` = :sourcemodelname';
		}

		/**
		 * @return string
		 */
		protected function mutateRelationId1Query()
		{
			return 'UPDATE `f_relation` SET `document_model_id1` = :destmodelname WHERE `relation_id1` = :id AND `document_model_id1` = :sourcemodelname';
		}
		/**
		 * @return string
		 */
		protected function mutateRelationId2Query()
		{
			return 'UPDATE `f_relation` SET `document_model_id2` = :destmodelname WHERE `relation_id2` = :id AND `document_model_id1` = :sourcemodelname';
		}

		/**
		 * @return string
		 */
		protected function mutateDocumentQuery($table)
		{
			return 'UPDATE `'.$table.'` SET `document_model` = :destmodelname WHERE `document_id` = :id AND `document_model` = :sourcemodelname';
		}

		/**
		 * @return string
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
		 * @param string $tableName
		 * @return string
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
		 * @param string $tableName
		 * @return string
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
		 * @return string
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


		protected function getCacheIdsByPatternQuery()
		{
			return "select distinct(cache_id) from f_simplecache_registration where pattern = :pattern";
		}

		protected function getRegisterSimpleCacheQuery()
		{
			return "insert into f_simplecache_registration values (:pattern, :cacheId)";
		}

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
		 * @return string
		 */
		protected function getCompileACLQuery()
		{
			return 'insert into `f_permission_compiled` values(:accessorId, :permission, :nodeId)';
		}

		/**
		 * ACL check exists for node query
		 *
		 * @return string
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
		 * @return string
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
		 * @return string
		 */
		protected function getRemoveACLForNodeQuery()
		{
			return 'DELETE FROM `f_permission_compiled` WHERE `node_id` = :nodeId';
		}

		/**
		 * ACL remove compiled node entries for a package
		 *
		 * @return string
		 */
		protected function getRemovePackageACLForNodeQuery()
		{
			return 'DELETE FROM `f_permission_compiled` WHERE `node_id` = :nodeId AND permission LIKE :permission';
		}


		/**
		 * @return string
		 */
		protected function getACLDefinitionPointQuery()
		{
			return 'SELECT DISTINCT node_id FROM f_permission_compiled WHERE permission LIKE :permission';
		}
		//
		// End of Permission Section
		//


		/**
		 * @return string
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
		 * @return string
		 */
		protected function getFromFrameworkCacheQuery()
		{
			return 'SELECT text_value from f_cache where cache_key = :key';
		}

		/**
		 * @return string
		 */
		protected function getMultipleFromFrameworkCacheQuery($keys)
		{
			return 'SELECT cache_key, text_value from f_cache where cache_key in ('.join(',', $keys).')';
		}

		/**
		 * @return string
		 */
		protected function deleteFrameworkCacheKeysQuery($keys)
		{
			return 'DELETE FROM f_cache WHERE cache_key in ('.join(',', $keys).')';
		}

		/**
		 * @return string
		 */
		protected function setInFrameworkCacheDeleteQuery()
		{
			return 'DELETE FROM f_cache WHERE cache_key = :key';
		}

		/**
		 * @return string
		 */
		protected function setInFrameworkCacheQuery()
		{
			return 'SELECT cache_key from f_cache where cache_key = :key';
		}

		/**
		 * @return string
		 */
		protected function setInFrameworkCacheInsertQuery()
		{
			return 'INSERT INTO f_cache (cache_key, text_value) VALUES (:key, :text_value)';
		}

		/**
		 * @return string
		 */
		protected function setInFrameworkCacheUpdateQuery()
		{
			return 'UPDATE f_cache SET text_value = :text_value WHERE cache_key = :key';
		}

		/**
		 * @return string
		 */
		protected function clearFrameworkCacheDeleteQuery()
		{
			return 'DELETE FROM f_cache';
		}

		/**
		 * @return string
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
		
		//I18nSynchro
		
		/**
		 * @param integer $id
		 * @param string $lang
		 * @param string $synchroStatus 'MODIFIED'|'VALID'|'SYNCHRONIZED'
		 * @param string|null $fromLang
		 */
		public function setI18nSynchroStatus($id, $lang, $synchroStatus, $fromLang = null)
		{
			$sql = "INSERT INTO `f_i18n` (`document_id`, `document_lang`, `synchro_status`, `synchro_from`)
			VALUES (:document_id, :document_lang, :synchro_status, :synchro_from)
			ON DUPLICATE KEY UPDATE `synchro_status` = VALUES(`synchro_status`), `synchro_from` = VALUES(`synchro_from`)";
		
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $id, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':document_lang', $lang, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':synchro_status', $synchroStatus, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':synchro_from', $fromLang, ($fromLang === null ? PersistentProviderConst::PARAM_NULL : PersistentProviderConst::PARAM_STR));
			$this->executeStatement($stmt);
			return $stmt->rowCount();
		}
		
		/**
		 * @param integer $id
		 * @return array
		 * 		- 'fr'|'en'|'??' : array
		 * 			- status : 'MODIFIED'|'VALID'|'SYNCHRONIZED'
		 * 			- from : fr'|'en'|'??'|null
		 */
		public function getI18nSynchroStatus($id)
		{
			$sql = "SELECT `document_lang`, `synchro_status`, `synchro_from` FROM `f_i18n` WHERE `document_id` = :document_id";
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $id, PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
			$result = array();
			$row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
			while ($row)
			{
				$result[$row['document_lang']] = array('status' => $row['synchro_status'], 'from' => $row['synchro_from']);
				$row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
			}
			$stmt->closeCursor();
			return $result;
		}
		
		/**
		 * @return integer[]
		 */
		public function getI18nSynchroIds()
		{
			$sql = "SELECT DISTINCT `document_id` FROM `f_i18n` WHERE `synchro_status` = 'MODIFIED' LIMIT 0, 100";
		
			$stmt = $this->prepareStatement($sql);
			$this->executeStatement($stmt);
			return $stmt->fetchAll(PersistentProviderConst::FETCH_COLUMN);
		}
		
		/**
		 * @param f_persistentdocument_PersistentDocumentModel $pm
		 * @param integer $id
		 * @param string $lang
		 * @param string $fromLang
		 */
		public function prepareI18nSynchro($pm, $documentId, $lang, $fromLang)
		{
			$suf = $this->getI18nSuffix();
			$tableName = $pm->getTableName() . $suf;
			$className = $this->getI18nDocumentClassFromModel($pm->getName());
			$fields = array();
			foreach ($pm->getPropertiesInfos() as $key => $propertyInfo)
			{
				if ($propertyInfo->isLocalized())
				{
					$fields[] = '`' . $propertyInfo->getDbMapping() . $suf. '` AS `' . $key . '`';
				}
			}
		
			$sql =  "SELECT ". implode(', ', $fields)." FROM ".$tableName." WHERE document_id = :document_id and lang_i18n = :lang";
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':lang', $fromLang, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
			$fromResult = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
			$stmt->closeCursor();
		
			$from = new $className($documentId, $fromLang, false);
			$from->setDocumentProperties($fromResult);
		
			$sql =  "SELECT `document_publicationstatus_i18n` AS `publicationstatus` FROM ".$tableName." WHERE document_id = :document_id and lang_i18n = :lang";
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':lang', $lang, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
			$toResult = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
			$stmt->closeCursor();
			$isNew = true;
			if ($toResult)
			{
				$fromResult['publicationstatus'] = $toResult['publicationstatus'];
				$isNew = false;
			}
			$to = new $className($documentId, $lang, $isNew);
			$to->setDocumentProperties($fromResult);
		
			return array($from, $to);
		}
		
		/**
		 * @param f_persistentdocument_PersistentDocumentModel $pm
		 * @param f_persistentdocument_I18nPersistentDocument $to
		 */
		public function setI18nSynchro($pm, $to)
		{
		
			$suf = $this->getI18nSuffix();
			$tableName = $pm->getTableName() . $suf;
			$sql = $this->getI18nDocumentQuery($tableName);
			$id = $to->getId();
			$lang = $to->getLang();
		
			$sqlInsert = array('`document_id`', '`lang_i18n`');
			$sqlValues =  array(':document_id' => $id, ':lang_i18n' => $lang);
			$sqlUpdate = array();
		
			foreach ($to->getDocumentProperties() as $propertyName => $value)
			{
				$property = $pm->getProperty($propertyName);
				$fieldName = $property->getDbMapping() . $suf;
					
				if ($propertyName === 'publicationstatus')
				{
					$sqlInsert[] = '`' . $fieldName . '`';
					$sqlValues[':' . $fieldName] = $value;
				}
				elseif ($propertyName !== 'correctionid')
				{
					$sqlInsert[] = '`' . $fieldName . '`';
					$sqlValues[':' . $fieldName] = $value;
					$sqlUpdate[] = '`' . $fieldName . '` = VALUES(`' . $fieldName . '`)';
				}
			}
			$sql = 'INSERT INTO `'.$tableName.'` (' . implode(', ', $sqlInsert) .
			') VALUES (' . implode(', ', array_keys($sqlValues)) .
			') ON DUPLICATE KEY UPDATE' . implode(', ', $sqlUpdate);
		
			$stmt = $this->prepareStatement($sql);
			foreach ($sqlValues as $bn => $value)
			{
				$stmt->bindValue($bn, $value, $value === null ? PersistentProviderConst::PARAM_NULL : PersistentProviderConst::PARAM_STR);
			}
			$this->executeStatement($stmt);
			$this->m_i18nDocumentInstances[$id] = array();
		
			$sql = 'UPDATE `f_document` SET `label_' . $lang . '` = :label  WHERE (document_id = :document_id)';
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':label', $sqlValues[':document_label_i18n'], PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':document_id', $id, PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
			$this->deleteFromCache($id);
		}
		
		/**
		 * @param integer $id
		 * @param string|null $lang
		 */
		public function deleteI18nSynchroStatus($id, $lang = null)
		{
			$sql = "DELETE FROM `f_i18n` WHERE `document_id` = :document_id";
			if ($lang !== null)
			{
				$sql .= " AND `document_lang` = :document_lang";
			}
		
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $id, PersistentProviderConst::PARAM_INT);
			if ($lang !== null)
			{
				$stmt->bindValue(':document_lang', $lang, PersistentProviderConst::PARAM_STR);
			}
			$this->executeStatement($stmt);
			return $stmt->rowCount();
		}
		
		/**
		 * @param f_persistentdocument_criteria_ExecutableQuery $query
		 * @param array $params the parameters for the query, created and filled by buildQueryString
		 * @return string the queryString as a preparedStatement
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
		 * @return string
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
					$qBuilder->addField('count(distinct '.$qBuilder->getTableAlias().'.document_id) as ' . $projection->getAs());
				}
				else if ($projection instanceof f_persistentdocument_criteria_DistinctCountProjection)
				{
					$columnName = $qBuilder->getQualifiedColumnName($projection->getPropertyName());
					$qBuilder->addField('count(distinct ' . $columnName .') as ' . $projection->getAs());
				}
				else if ($projection instanceof f_persistentdocument_criteria_OperationProjection)
				{
					$columnName = $qBuilder->getQualifiedColumnName($projection->getPropertyName());
					$qBuilder->addField($projection->getOperation() . '(' . $columnName .') as ' . $projection->getAs());
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
					if ($property === null)
					{
						throw new Exception('Property [' . $propNameInfo[0] . '] not found on document: ' . $qBuilder->getModel()->getName());
					}
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
						$qBuilder->addField($columnName .' as ' . $projection->getAs());
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
	 * @param string $propertyName
	 * @param mixed $value
	 * @return string
	 */
	public function addParam($propertyName, $value)
	{
		$key = ':p'.(count($this->params)+1);
		$this->params[$key] = $this->translateValue($propertyName, $value);
		return $key;
	}

	/**
	 * @param string $sql
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
	 * @param string $field
	 */
	public function addField($field)
	{
		// avoid duplicate field using $field as key
		$this->fields[$field] = $field;
	}

	/**
	 * @param string $from
	 */
	public function addFrom($from)
	{
		$this->from[] = $from;
	}

	/**
	 * @param string $where
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
	 * @param string $groupBy
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
				$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($prop->getDocumentType());
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
	 * @return string
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
	 * @return string
	 */
	public function getQueryString()
	{
		$query = 'select '.(($this->distinctNeeded)?'distinct ':''). implode(', ', $this->fields).
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
	 * @param string $sql
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
		if (Framework::isBenchEnabled())
		{
			$this->params[$parameterName] = $value;
		}
	}

	public function execute($parameters = null)
	{
		if (Framework::isBenchEnabled())
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
			if (Framework::isBenchEnabled())
			{
				$trace = "SQL ERROR||". str_replace(array("\n", "\t"), '', $this->sql.'||'. var_export($this->params, true));
				Framework::endBench(str_replace('||', "\t", $trace));
			}
			return false;
		}

		$time = (microtime(true) - $start);
		self::$time['exec'] += $time;

		if (Framework::isBenchEnabled())
		{
			if ($time > 0.01)
			{
				$trace = 'SQL SLOWQUERY||' .$time . '||' . str_replace(array("\n", "\t"), '', $this->sql . "||" . var_export($this->params, true));
			}
			else
			{
				$trace = "SQL||". str_replace(array("\n", "\t"), '', $this->sql.'||'. var_export($this->params, true));
			}
				
			Framework::endBench(str_replace('||', "\t", $trace));
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
