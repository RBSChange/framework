<?php
/**
 * @date Thu Oct 11 14:40:24 CEST 2007
 * @author inthause
 */

class f_persistentdocument_PersistentProviderOci extends f_persistentdocument_PersistentProvider
{
	private $inTransaction;

	private $useTransaction = true;

	private $pconnect = false;

	protected function errorCode()
	{
		$error = oci_error();
		if ($error)
		{
			return $error['code'];
		}
		return $error;
	}

	protected function errorInfo()
	{
		return print_r(oci_error(), true);
	}

	protected function beginTransactionInternal()
	{
		if ($this->useTransaction)
		{
			$this->inTransaction = true;
		}
	}

	protected function commitInternal()
	{
		if ($this->useTransaction)
		{
			$this->inTransaction = false;
			oci_commit($this->getDriver());
		}
	}

	protected function rollBackInternal()
	{
		if ($this->useTransaction)
		{
			$this->inTransaction = false;
			oci_rollback($this->getDriver());
		}
	}

	protected function getLastInsertId($tableName)
	{
		if ($tableName === 'f_relationname')
		{
			$stmt = $this->prepareStatement('SELECT "f_relationname_seq".currval FROM dual');
		}
		else
		{
			$stmt = $this->prepareStatement('SELECT "f_document_seq".currval FROM dual');
		}
		$stmt->execute();
		$lastInsertId = intval($stmt->fetchColumn(0));
		$stmt->closeCursor();
		return $lastInsertId;
	}

	public function getType()
	{
		return 'oci';
	}

	public function isInTransaction()
	{
		return $this->inTransaction && $this->useTransaction;
	}

	private function getExecuteMode()
	{
		return ($this->useTransaction && $this->inTransaction) ? OCI_DEFAULT : OCI_COMMIT_ON_SUCCESS;
	}
	
	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @param generator_PersistentProperty $oldProperty
	 * @return String[] the SQL statements that where executed
	 */
	function delProperty($moduleName, $documentName, $oldProperty)
	{
		throw new Exception(__METHOD__." is not implemented");	
	}
	
	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @param generator_PersistentProperty $oldProperty
	 * @param generator_PersistentProperty $newProperty
	 * @return String[] the SQL statements that where executed
	 */
	function renameProperty($moduleName, $documentName, $oldProperty, $newProperty)
	{
		throw new Exception(__METHOD__." is not implemented");	
	}
	
	/**
	 * @throws Exception on error
	 */
	function clearDB()
	{
		throw new Exception(__METHOD__." is not implemented");	
	}

	/**
	 * Warning: not implemented
	 * (non-PHPdoc)
	 * @see persistentdocument/f_persistentdocument_PersistentProvider#createDB($conf)
	 * @return Boolean
	 */
	function createDB($props)
	{
		return false;
	}

	/**
	 * //TODO : inthause check autocommit
	 * @param boolean $bool
	 * @return boolean
	 */
	public function setAutoCommit($bool)
	{
		if ($this->isInTransaction() && $bool)
		{
			$this->commitInternal();
		}

		$oldValue = $this->useTransaction;
		$this->useTransaction = $bool;
		if ($bool)
		{
			$this->inTransaction = false;
		}

		return $oldValue;
	}

	/**
	 * @param array<String, String> $connectionInfos
	 * @return mixed
	 */
	protected function getConnection($connectionInfos)
	{
		$protocol = isset($connectionInfos['protocol']) ? $connectionInfos['protocol'] : 'oci';
		$database = isset($connectionInfos['database']) ? $connectionInfos['database'] : null;
		$host = isset($connectionInfos['host']) ? $connectionInfos['host'] : 'localhost';
		$port = isset($connectionInfos['port']) ? $connectionInfos['port'] : 1521;
		$password = isset($connectionInfos['password']) ? $connectionInfos['password'] : null;
		$username = isset($connectionInfos['user']) ? $connectionInfos['user'] : null;

		$dsn = "//". $host .":". $port."/" . $database;
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ ."($dsn, $username)");
		}

		$this->sql_fieldescape = '"';
		$res = oci_pconnect($username, $password, $dsn, 'UTF8');;
		if (!$res)
		{
			Framework::error(__METHOD__ . '->'.var_export($connectionInfos, true));
		}
		else
		{
			$this->pconnect = true;
		}

		return $res;
	}


	public function closeConnection()
	{
		if(Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__);
		}

		if (null !== $this->oldStatement)
		{
			$this->oldStatement->closeCursor();
			$this->oldStatement = null;
		}

		if ($this->pconnect)
		{
			$this->pconnect = false;
				
			if(Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ .  " oci_close");
			}
			oci_close($this->getDriver());
		}
		$this->setDriver(null);
	}

	protected function escapeFieldName($fieldName)
	{
		return '"' . $fieldName . '"';
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
					$statement->bindValue($name, null, PersistentProviderConst::PARAM_STR);
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
					$statement->bindValue($name, null, PersistentProviderConst::PARAM_INT);
				}
				else
				{
					$statement->bindValue($name, $value, PersistentProviderConst::PARAM_INT);
				}
				break;
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LONGSTRING:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT:
				$statement->bindValue($name, $value, PersistentProviderConst::PARAM_LOB);
				break;
			default:
				if (is_null($value))
				{
					$statement->bindValue($name, null, PersistentProviderConst::PARAM_STR);
				}
				else
				{
					$statement->bindValue($name, strval($value), PersistentProviderConst::PARAM_STR);
				}
				break;
		}
	}

	/**
	 * @var OciStatment
	 */
	private $oldStatement = null;

	/**
	 * @param string $sql
	 * @return OciStatment
	 */
	protected function prepareStatement($sql)
	{
		if (null !== $this->oldStatement)
		{
			$this->oldStatement->closeCursor();
		}

		$stmt = new OciStatment($sql, $this, $this->getExecuteMode());
		$this->oldStatement = $stmt;
		return $stmt;
	}


	public function executeSQLScript($script)
	{
		$stmt = oci_parse($this->getDriver(), $script);
		if ($stmt !== false)
		{
			$result = oci_execute($stmt, $this->getExecuteMode());
			if ($result === false)
			{
				Framework::warn(__METHOD__ . ' ' . print_r(oci_error($stmt), true));
			}
			oci_free_statement($stmt);
			return $result;
		}
		else
		{
			Framework::warn(__METHOD__ . ' ' . $this->errorInfo());
		}
		return false;
	}

	/**
	 * @param String $script
	 * @return PDOStatement
	 */
	public function executeSQLSelect($script)
	{
		// TODO: implement
		throw new Exception(__METHOD__." not implemented");
	}


	// All the function under this are function with request inside. This request are used in the persitent provider. They are here because the
	// sql is dependend of the database.
	protected function getDocumentInstanceQuery()
	{
		return 'SELECT "document_model", "' . implode('", "', $this->getI18nFieldNames()) . '" FROM "f_document" WHERE "document_id" = :document_id';
	}

	protected function getDocumentModelNameQuery()
	{
		return 'SELECT "document_model" FROM "f_document" WHERE "document_id" = :document_id';
	}

	/**
	 * @return String
	 */
	protected function mutateFrameworkQuery()
	{
		return 'UPDATE "f_document" SET "document_model" = :destmodelname WHERE "document_id" = :id AND "document_model" = :sourcemodelname';
	}

	/**
	 * @return String
	 */
	protected function mutateRelationId1Query()
	{
		return 'UPDATE "f_relation" SET "document_model_id1" = :destmodelname WHERE "relation_id1" = :id AND "document_model_id1" = :sourcemodelname';
	}
	/**
	 * @return String
	 */
	protected function mutateRelationId2Query()
	{
		return 'UPDATE "f_relation" SET "document_model_id2" = :destmodelname WHERE "relation_id2" = :id AND "document_model_id1" = :sourcemodelname';
	}

	/**
	 * @return String
	 */
	protected function mutateDocumentQuery($table)
	{
		return 'UPDATE "'.$table.'" SET "document_model" = :destmodelname WHERE "document_id" = :id AND "document_model" = :sourcemodelname';
	}

	// For order method TODO: test on oracle!!
	protected function getTranslateNodesQuery($nodes)
	{
		$index = 0;
		$where = array();
		foreach ($nodes as $node)
		{
			$where[] = "(tree_left <= :treeLeft$index and tree_right >= :treeRight$index)";
			$index++;
		}
		$query = "update f_tree set tree_left = (-tree_left + :delta1), tree_right = (-tree_right + :delta2) where tree_id = :treeId and (".join(" or ", $where).")";
		return $query;
	}

	// For order method TODO: test on oracle !!
	protected function getPutNodesOutOfScopeQuery($nodes)
	{
		$index = 0;
		$where = array();
		foreach($nodes as $delta => $sameDeltaNodes)
		{
			foreach ($sameDeltaNodes as $node)
			{
				$where[] = "(tree_left >= :treeLeft$index and tree_right <= :treeRight$index)";
				$index++;
			}
		}

		$query = "update f_tree set tree_left = -tree_left, tree_right = -tree_right where tree_id = :treeId and (".join(" or ", $where).")";
		return $query;
	}

	/**
	 * @return String
	 */
	protected function mutateDocumentI18NQuery($tableI18N)
	{
		return 'DELETE FROM "'.$tableI18N.'" WHERE "document_id" = :id';
	}

	protected function getLoadDocumentQuery($table)
	{
		return 'SELECT * FROM "'.$table.'" WHERE "document_id" = :document_id';
	}

	protected function getUpdateDocumentLangs()
	{
		$sqlFields = array();
		foreach ($this->getI18nFieldNames() as $i18nFieldName)
		{
			$sqlFields[] = '"' . $i18nFieldName . '" = :' .$i18nFieldName;
		}

		return 'UPDATE "f_document" SET ' . implode(', ', $sqlFields) . ' WHERE ("document_id" = :document_id)';
	}

	protected function get_insertDocumentQuery($table, $fieldsName, $parameters, $lobParameters = array())
	{
		if (count($lobParameters) == 0)
		{
			return 'INSERT INTO "'.$table.'" (' . implode(', ', $fieldsName) .') VALUES (' . implode(', ', $parameters) .')';
		}
		else
		{
			$lobFieldsName = array();
			$lobParametersName = array();
			foreach ($lobParameters as $name => $value)
			{
				$lobFieldsName[] = $fieldsName[$name];
				$lobParametersName[] = $parameters[$name];
				$parameters[$name] = 'EMPTY_CLOB()';
			}
			return 'INSERT INTO "'.$table.'" (' . implode(', ', $fieldsName) .') VALUES (' . implode(', ', $parameters) .') RETURNING ' . implode(', ', $lobFieldsName) . ' INTO ' . implode(', ', $lobParametersName);
		}
	}

	protected function getUpdateDocumentQuery($table, $mapping, $lobParameters = array())
	{
		if (count($lobParameters) == 0)
		{
			return 'UPDATE "'.$table.'" SET ' . implode(', ', $mapping) . ' WHERE "document_id" = :document_id';
		}
		else
		{
			$lobFieldsName = array();
			$lobParametersName = array();
			foreach ($lobParameters as $name => $value)
			{
				$lobFieldsName[] = $value[0];
				$lobParametersName[] = $value[1];
				$mapping[$name] = $value[0] . ' = EMPTY_CLOB()';
			}
			return 'UPDATE "'.$table.'" SET ' . implode(', ', $mapping) . ' WHERE "document_id" = :document_id RETURNING ' . implode(', ', $lobFieldsName) . ' INTO ' . implode(', ', $lobParametersName);
		}
	}

	/**
	 * @param String $tableName
	 * @return String
	 */
	protected function getUpdateI18nDocumentQuery($table, $mapping, $lobParameters = array())
	{
		if (count($lobParameters) == 0)
		{
			return 'UPDATE "'.$table.'" SET ' . implode(', ', $mapping) . ' WHERE "document_id" = :id AND "lang_i18n" = :lang';
		}
		else
		{
			$lobFieldsName = array();
			$lobParametersName = array();
			foreach ($lobParameters as $name => $value)
			{
				$lobFieldsName[] = $value[0];
				$lobParametersName[] = $value[1];
				$mapping[$name] = $value[0] . ' = EMPTY_CLOB()';
			}
			return 'UPDATE "'.$table.'" SET ' . implode(', ', $mapping) . ' WHERE "document_id" = :id AND "lang_i18n" = :lang  RETURNING ' . implode(', ', $lobFieldsName) . ' INTO ' . implode(', ', $lobParametersName);
		}

	}

	protected function getDeleteDocumentQuery1()
	{
		return 'DELETE FROM "f_document" WHERE ("document_id" = :document_id)';
	}

	/**
	 * @param String $tableName
	 * @return String
	 */
	protected function getI18nDocumentQuery($tableName)
	{
		return 'SELECT * FROM "'.$tableName.'" WHERE "document_id" = :document_id AND "lang_i18n" = :lang';
	}


	protected function getDeleteDocumentQuery2($table)
	{
		return 'DELETE FROM "'.$table.'" WHERE ("document_id" = :document_id)';
	}

	protected function getNewDocumentIdQuery1()
	{
		$i18nFieldNames = $this->getI18nFieldNames();
		return 'INSERT INTO "f_document" ("document_id", "document_model", "'. implode('", "', $i18nFieldNames) .'") VALUES ("f_document_seq".nextval, :document_model, :'. implode(', :', $i18nFieldNames) .')';
	}

	protected function getNewDocumentIdQuery2()
	{
		$i18nFieldNames = $this->getI18nFieldNames();
		return 'INSERT INTO "f_document" ("document_id", "document_model", "'. implode('", "', $i18nFieldNames) .'") VALUES (:document_id, :document_model, :'. implode(', :', $i18nFieldNames) .')';
	}

	/*
	 * Gestion de la table f_relation
	 */
	protected function getDeleteDocumentQuery3()
	{
		return 'DELETE FROM "f_relation" WHERE "relation_id1" = :relation_id1';
	}

	protected function getLoadRelationsQuery()
	{
		return 'SELECT "relation_id2" AS document_id FROM "f_relation" WHERE "relation_id1" = :relation_id1 AND "relation_id" = :relation_id ORDER BY "relation_order"';
	}

	protected function getloadAllRelations()
	{
		return 'SELECT "relation_name", "relation_order", "relation_id2" FROM "f_relation" WHERE "relation_id1" = :relation_id1 ORDER BY "relation_id", "relation_order"';
	}
	
	protected function getSaveRelationsQuery()
	{
		return 'DELETE FROM "f_relation" WHERE "relation_id1" = :relation_id1';
	}

	protected function getSaveRelationDeleteAllQuery()
	{
			return 'DELETE FROM "f_relation" WHERE "relation_id1" = :relation_id1 AND "relation_id" = :relation_id';
	}

	protected function getSaveRelationsPreviousQuery()
	{
		return 'SELECT "relation_id2" AS doc_id, "relation_order" AS doc_order FROM "f_relation" WHERE "relation_id1" = :relation_id1 AND "relation_id" = :relation_id';
	}	
	
	protected function getSaveRelationInsertQuery()
	{
		return 'INSERT INTO "f_relation" ("relation_id1", "relation_id2", "relation_order", "relation_name", "document_model_id1", "document_model_id2", "relation_id") VALUES (:relation_id1, :relation_id2, :relation_order, :relation_name, :document_model_id1, :document_model_id2, :relation_id)';
	}

	protected function getSaveRelationUpdateQuery()
	{
		return 'UPDATE "f_relation" SET "relation_order" = :new_order WHERE "relation_id1" = :relation_id1 AND "relation_id" = :relation_id AND "relation_order" = :relation_order';
	}	
	
	
	protected function getSaveRelationDeleteQuery()
	{
		return 'DELETE FROM "f_relation" WHERE "relation_id1" = :relation_id1 AND "relation_id" = :relation_id AND "relation_order" = :relation_order';
	}

	protected function getSaveRelationReorderQuery()
	{
		return 'UPDATE "f_relation" SET "relation_order" = -"relation_order" - 1 WHERE "relation_id1" = :relation_id1 AND "relation_id" = :relation_id AND "relation_order" < 0';
	}	
	
	protected function getDeleteI18nDocument($tableName)
	{
		return 'DELETE FROM "'. $tableName . '" WHERE "document_id" = :id AND "lang_i18n" = :lang';
	}

	protected function getRootNodeQuery()
	{
		return 'SELECT "tree_id", "tree_left", "tree_right", "tree_level", "f_document"."document_id", "f_document"."document_model", "f_document"."'. implode('", "f_document"."', $this->getI18nFieldNames()) .'" FROM "f_tree" INNER JOIN "f_document" ON "f_tree"."document_id" = "f_document"."document_id" WHERE "tree_left" = :tree_left AND "tree_id" = :tree_id';
	}

	protected function getDeleteTreeQuery($keepRootNode = false)
	{
		$query = 'DELETE FROM "f_tree" WHERE "tree_id" = :tree_id';
		if ($keepRootNode)
		{
			$query .= ' AND "tree_level" > 0';
		}
		return $query;
	}

	protected function getNodeByDocumentIdQuery()
	{
		return 'SELECT "tree_id", "tree_left", "tree_right", "tree_level", "f_document"."document_id", "f_document"."document_model", "f_document"."'. implode('", "f_document"."', $this->getI18nFieldNames()) .'" FROM "f_tree" INNER JOIN "f_document" ON "f_tree"."document_id" = "f_document"."document_id" WHERE "f_tree"."document_id" = :document_id';
	}

	protected function getDeleteNodeQuery()
	{
		return 'DELETE FROM "f_tree" WHERE "tree_left" >= :tree_left AND "tree_right" <= :tree_right  AND "tree_id" = :tree_id';
	}

	protected function getUpdateNodeInfoQuery()
	{
		return 'UPDATE "f_tree" SET "document_id" = :document_id WHERE "tree_left" = :tree_left AND "tree_id" = :tree_id';
	}

	public function encloseString(&$str)
	{
		$str = '\''.$str.'\'';
	}

	/**
	 * @param array $modelNames the model name permitted
	 * @return unknown
	 */
	protected function getNodeChildrenQuery($modelNames)
	{
		if (!is_null($modelNames))
		{
			array_walk($modelNames, array($this, 'encloseString'));
			$modelNamesConstraint = ' AND "document_model" IN (' . join(',', $modelNames) . ')';
		}
		else
		{
			$modelNamesConstraint = '';
		}
		return 'SELECT "tree_left", "tree_right", "tree_level", "f_document"."document_id", "f_document"."document_model", "f_document"."'. implode('", "f_document"."', $this->getI18nFieldNames()) .'" FROM "f_tree" INNER JOIN "f_document" ON "f_tree"."document_id" = "f_document"."document_id"
		WHERE "tree_left" > :left AND "tree_right" < :right AND "tree_level" <= :maxlevel AND "tree_id" = :tree_id'
		.$modelNamesConstraint.' ORDER BY "tree_left"';
	}

	protected function getNodeAncestorQuery()
	{
		return 'SELECT "tree_left", "tree_right", "tree_level", "f_document"."document_id", "f_document"."document_model", "f_document"."'. implode('", "f_document"."', $this->getI18nFieldNames()) .'" FROM "f_tree" INNER JOIN "f_document" ON "f_tree"."document_id" = "f_document"."document_id"
		WHERE "tree_left" < :tree_left AND "tree_right" > :tree_right AND "tree_id" = :tree_id  ORDER BY "tree_left"';
	}

	protected function getUpdateNodeLevelQuery()
	{
		return 'UPDATE "f_tree" SET "tree_level" = "tree_level" + :offsetlevel WHERE "tree_left" >= :left AND "tree_right" <= :right AND "tree_id" = :tree_id';
	}

	protected function getShiftRLValuesQuery1()
	{
		return 'UPDATE "f_tree" SET "tree_left" = "tree_left" + :delta WHERE "tree_left" >= :first AND "tree_id" = :tree_id';
	}

	protected function getShiftRLValuesQuery2()
	{
		return 'UPDATE "f_tree" SET "tree_right" = "tree_right" + :delta WHERE "tree_right" >= :first AND "tree_id" = :tree_id';
	}

	protected function getShiftRLRangeQuery1()
	{
		return 'UPDATE "f_tree" SET "tree_left" = "tree_left" + :delta WHERE "tree_left" >= :first AND "tree_left" <= :last AND "tree_id" = :tree_id';
	}

	protected function getShiftRLRangeQuery2()
	{
		return 'UPDATE "f_tree" SET "tree_right" = "tree_right" + :delta WHERE "tree_right" >= :first AND "tree_right" <= :last AND "tree_id" = :tree_id';
	}

	protected function getInsertNodeQuery()
	{
		return 'INSERT INTO "f_tree" ("tree_id", "tree_left", "tree_right", "tree_level", "document_id") VALUES (:tree_id, :tree_left, :tree_right, :tree_level, :document_id)';
	}

	protected function getBulkInsertLeftNodeQuery()
	{
		return 'INSERT INTO "f_tree" ("tree_id", "tree_left", "tree_right", "tree_level", "document_id") VALUES (:tree_id, :tree_left, :tree_right, :tree_level, :document_id)';
	}

	protected function getBulkUpdateRightNodeQuery()
	{
		return 'UPDATE "f_tree" SET "tree_right" = :tree_right WHERE "tree_left" = :tree_left AND "tree_id" = :tree_id';
	}

	protected function getTagsQuery()
	{
		return 'SELECT "tag" FROM "f_tags" WHERE "id" = :id';
	}

	protected function getDocumentIdsByTagQuery()
	{
		return 'SELECT "id" FROM "f_tags" WHERE "tag" = :tag';
	}

	protected function getHasTagsQuery($tags)
	{
		return 'SELECT count(*) "nbtags" FROM "f_tags" WHERE "id" = :id AND "tag" IN (\'' . join("', '", $tags) . '\')';
	}

	protected function getHasTagQuery()
	{
		return 'SELECT "id" FROM "f_tags" WHERE "id" = :id AND "tag" = :tag';
	}

	protected function getAddTagQuery()
	{
		return 'INSERT INTO "f_tags" ("id", "tag") VALUES (:id, :tag)';
	}

	protected function getRemoveTagQuery()
	{
		return 'DELETE FROM "f_tags" WHERE "id" = :id AND "tag" = :tag';
	}

	protected function getRemoveExclusiveTagQuery()
	{
		return 'DELETE FROM "f_tags" WHERE "tag" = :tag';
	}

	protected function getTranslateQuery()
	{
		return 'SELECT "content" FROM "f_locale" WHERE "id" = :id AND "lang" = :lang';
	}

	protected function getCheckTranslateKeyQuery()
	{
		return 'SELECT "package" FROM "f_locale" WHERE "id" = :id';
	}

	protected function getUrlRewritingQuery()
	{
		return 'SELECT "document_id", "document_lang", "document_url", "document_moved" FROM "m_website_urlrewriting_rules" WHERE "document_id" = :id AND "document_lang" = :lang';
	}

	protected function getDeleteUrlRewritingQuery()
	{
		return 'DELETE FROM "m_website_urlrewriting_rules" WHERE ("document_url" = :url) OR (("document_id" = :id) AND ("document_lang" = :lang))';
	}

	protected function getSetUrlRewritingQuery()
	{
		return 'INSERT INTO "m_website_urlrewriting_rules" ("document_id", "document_lang", "document_url", "document_moved", "document_model") VALUES (:id, :lang, :url, :moved, :model)';
	}

	protected function getRemoveUrlRewritingQuery()
	{
		return 'DELETE FROM "m_website_urlrewriting_rules" WHERE "document_id" = :id AND "document_lang" = :lang';
	}

	protected function getPageForUrlQuery()
	{
		return 'SELECT "document_id", "document_lang", "document_moved", "document_model" FROM "m_website_urlrewriting_rules" WHERE "document_url" = :url';
	}

	protected function getAllTagsTagsQuery()
	{
		return 'SELECT "tags"."tag", "tags"."id" FROM "f_tags" "tags"';
	}

	protected function getSettingQuery()
	{
		return 'SELECT "value" FROM "f_settings" WHERE "package" = :package AND "name" = :name AND "userid" = 0';
	}

	protected function getSettingPackageQuery()
	{
		return 'SELECT package FROM "f_settings" WHERE "value" = :value AND "name" = :name AND "userid" = 0';
	}

	protected function getUserSettingQuery()
	{
		return 'SELECT value FROM "f_settings" WHERE "package" = :package AND "name" = :name AND "userid" = :userid';
	}

	protected function getSaveSettingDeleteQuery()
	{
		return 'DELETE FROM "f_settings" WHERE "package" = :package AND "name" = :name AND "userid" = :userid';
	}

	protected function getSaveUserSettingQuery()
	{
		return 'INSERT INTO "f_settings" ("package", "name", "userid", "value") VALUES (:package, :name, :userid, :value)';
	}

	protected function getTruncateTableQuery($tableName)
	{
		return 'TRUNCATE TABLE "'.$tableName.'"';
	}

	protected function getWebsiteIdAndLangFromUrlQuery()
	{
		return 'SELECT "document_id", "lang_i18n" FROM "m_website_doc_website_i18n" WHERE "url_i18n" = :url';
	}

	/**
	 * @return String
	 */
	protected function getCacheIdsByPatternQuery()
	{
		return 'SELECT DISTINCT "cache_id" FROM "f_simplecache_registration" WHERE "pattern" = :pattern';
	}

	/**
	 * @return String
	 */
	protected function getRegisterSimpleCacheQuery()
	{
		return 'INSERT INTO "f_simplecache_registration" ("pattern", "cache_id") VALUES (:pattern, :cacheId)';
	}

	/**
	 * @return String
	 */
	protected function getUnregisterSimpleCacheQuery()
	{
		return 'DELETE FROM "f_simplecache_registration" WHERE "cache_id" = :cacheId';
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
		return 'INSERT INTO "f_permission_compiled" VALUES (:accessorId, :permission, :nodeId)';
	}

	/**
	 * ACL check exists for node query
	 *
	 * @return String
	 */
	protected function getCheckACLQuery($accessors)
	{
		return 'SELECT COUNT(*) FROM "f_permission_compiled" WHERE "accessor_id" IN (' . implode(', ', $accessors). ') AND "permission" = :permission AND "node_id" = :nodeId';
	}

	protected function getAccessorsByPermissionForNodeQuery()
	{
		return 'SELECT DISTINCT "accessor_id" FROM "f_permission_compiled" WHERE "permission" = :permission AND "node_id" = :nodeId';
	}

	/**
	 * ACL check entry compiled
	 *
	 * @return String
	 */
	protected function getHasPermissionACLQuery()
	{
		return 'SELECT COUNT(*) FROM "f_permission_compiled" WHERE "node_id" = :nodeId';
	}

	protected function getHasPermissionForPackageQuery()
	{
		return 'SELECT COUNT(*) FROM "f_permission_compiled" WHERE "node_id" = :nodeId AND "permission" LIKE :permission';
	}

	protected function getPermissionsForUserByNodeQuery($accessors)
	{
		return 'SELECT DISTINCT "permission" FROM "f_permission_compiled" WHERE "node_id" = :nodeId AND "accessor_id" IN (' . implode(', ', $accessors). ')';
	}

	/**
	 * ACL remove compiled node entries
	 *
	 * @return String
	 */
	protected function getRemoveACLForNodeQuery()
	{
		return 'DELETE FROM "f_permission_compiled" WHERE "node_id" = :nodeId';
	}

	/**
	 * ACL remove compiled node entries for a package
	 *
	 * @return String
	 */
	protected function getRemovePackageACLForNodeQuery()
	{
		return 'DELETE FROM "f_permission_compiled" WHERE "node_id" = :nodeId AND "permission" LIKE :permission';
	}


	/**
	 * ACL tree definition points query
	 *
	 * @return String
	 */
	protected function getACLDefinitionPointQuery()
	{
		return 'SELECT DISTINCT "node_id" FROM "f_permission_compiled" WHERE "permission" LIKE :permission';
	}
	//
	// End of Permission Section
	//

	/**
	 * @return String
	 */
	protected function getRelationsQuery($documentId1, $documentId2, $relationId, $documentModel1, $documentModel2)
	{
		$sql = 'SELECT * FROM "f_relation" WHERE ';
		$where = array();
		if (!is_null($documentId1)) { $where[] = '"relation_id1" = :relation_id1'; }
		if (!is_null($documentModel1)) { $where[] = '"document_model_id1" = :document_model_id1'; }
		if (!is_null($documentId2)) { $where[] = '"relation_id2" = :relation_id2'; }
		if (!is_null($documentModel2)) { $where[] = '"document_model_id2" = :document_model_id2'; }
		if (!is_null($relationId)) { $where[] = '"relation_id" = :relation_id'; }

		return $sql . join(' AND ', $where) . ' ORDER BY "relation_order" ASC';
	}

	/**
	 * @return String
	 */
	protected function getFromFrameworkCacheQuery()
	{
		return 'SELECT "text_value" FROM "f_cache" WHERE "cache_key" = :key';
	}

	/**
	 * @return String
	 */
	protected function getMultipleFromFrameworkCacheQuery($keys)
	{
		return 'SELECT "cache_key", "text_value" FROM "f_cache" WHERE "cache_key" IN ('.join(',', $keys).')';
	}

	/**
	 * @return String
	 */
	protected function deleteFrameworkCacheKeysQuery($keys)
	{
		return 'DELETE FROM "f_cache" WHERE "cache_key" IN ('.join(',', $keys).')';
	}

	/**
	 * @return String
	 */
	protected function setInFrameworkCacheDeleteQuery()
	{
		return 'DELETE FROM "f_cache" WHERE "cache_key" = :key';
	}

	/**
	 * @return String
	 */
	protected function setInFrameworkCacheQuery()
	{
		return 'SELECT "cache_key" FROM "f_cache" WHERE "cache_key" = :key';
	}

	/**
	 * @return String
	 */
	protected function setInFrameworkCacheInsertQuery()
	{
		return 'INSERT INTO "f_cache" ("cache_key", "text_value") VALUES (:key, EMPTY_CLOB()) RETURNING "text_value" INTO :text_value';
	}

	/**
	 * @return String
	 */
	protected function setInFrameworkCacheUpdateQuery()
	{
		return 'UPDATE "f_cache" SET "text_value" = EMPTY_CLOB() WHERE "cache_key" = :key RETURNING "text_value" INTO :text_value';
	}

	/**
	 * @return String
	 */
	protected function clearFrameworkCacheDeletePatternQuery()
	{
		return 'DELETE FROM "f_cache" where "cache_key" like :pattern';
	}

	/**
	 * @return String
	 */
	protected function clearFrameworkCacheDeleteQuery()
	{
		return 'DELETE FROM "f_cache"';
	}

	/**
	 * @return String
	 */
	protected function clearFrameworkCacheTruncateQuery()
	{
		return 'TRUNCATE TABLE "f_cache"';
	}

	/**
	 * @return String
	 */
	protected function addTranslateQuery()
	{
		return 'INSERT INTO "f_locale" ("content", "originalcontent", "package", "overridden", "overridable", "useredited", "id", "lang") VALUES (EMPTY_CLOB(), EMPTY_CLOB(), :package, :overridden, :overridable, :useredited, :id, :lang) RETURNING "content", "originalcontent" INTO :content, :originalcontent';
	}

	/**
	 * @return String
	 */
	protected function updateTranslateQuery()
	{
		return 'UPDATE "f_locale" SET "content" = EMPTY_CLOB(), "originalcontent" = EMPTY_CLOB(), "package" = :package, "overridden" = :overridden, "overridable" = :overridable, "useredited" = :useredited WHERE "id" = :id AND "lang" = :lang  RETURNING "content", "originalcontent" INTO :content, :originalcontent';
	}

	/**
	 *
	 * @return String
	 */
	protected function getLocalesByPathQuery()
	{
		return 'SELECT "id", "lang", "content", "originalcontent", "overridable", "useredited" FROM "f_locale" WHERE "id" LIKE :path';
	}

	/**
	 * @return String
	 */
	protected function clearTranslationCacheQuery($package = null)
	{
		if ( is_null($package) )
		{
			return 'DELETE FROM "f_locale" WHERE "useredited" <> 1';
		}
		else
		{
			$package = str_replace('_', '.', $package);
			return 'DELETE FROM "f_locale" WHERE "useredited" <> 1 AND "id" LIKE \'" . $package . ".%\'';
		}
	}

	/**
	 * @return String
	 */
	protected function clearTranslationKeyForLangQuery()
	{
		return 'DELETE FROM "f_locale" WHERE "id" = :key AND "lang" = :lang';
	}

	/**
	 * @return String
	 */
	protected function clearTranslationKeyQuery()
	{
		return 'DELETE FROM "f_locale" WHERE "id" = :key';
	}

	/**
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 * @param array $params the parameters for the query, created and filled by buildQueryString
	 * @return String the queryString as a preparedStatement
	 */
	protected function buildQueryString($query, &$params)
	{
		$model = $query->getDocumentModel();
		if (!is_null($model))
		{
			$qBuilder = new f_persistentdocument_DocumentOciQueryBuilder($model);
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
			if ($query->hasOrders())
			{
				$this->processOrders($query, $qBuilder);
			}

			$params = $qBuilder->getParams();
			return $qBuilder->getQueryString();
		}
		else
		{
			$qBuilder = new f_persistentdocument_DocumentOciQueryBuilder();
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
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 * @param f_persistentdocument_DocumentOciQueryBuilder $qBuilder
	 */
	private function processProjections($query, $qBuilder, $model)
	{
		if (is_null($model))
		{
			$qBuilder->addFrom('"f_document" "t0"');
		}
		else
		{
			$qBuilder->addFrom('"' . $model->getTableName().'" "'.$qBuilder->getModelAlias() .'" INNER JOIN "f_document" "t0" ON "t0"."document_id" = "'.$qBuilder->getModelAlias() .'"."document_id"');
		}

		if (!$query->hasProjection())
		{
			$qBuilder->addField('"t0"."document_id"');
			$qBuilder->addField('"t0"."document_model"');
			foreach ($this->getI18nFieldNames() as $i18nFieldName)
			{
				$qBuilder->addField('"t0"."' . $i18nFieldName . '"');
			}
			return;
		}

		$subdoc = 0;
		foreach ($query->getProjection() as $projection)
		{
			if ($projection instanceof f_persistentdocument_criteria_RowCountProjection)
			{
				$qBuilder->addField('count("t0"."document_id") as "' . $projection->getAs() .'"');
			}
			else if ($projection instanceof f_persistentdocument_criteria_OperationProjection)
			{
				$columnName = $qBuilder->getQualifiedColumnName($projection->getPropertyName());
				$qBuilder->addField($projection->getOperation() . '(' . $columnName .') as "' . $projection->getAs().'"');
			}
			else if ($projection instanceof f_persistentdocument_criteria_PropertyProjection)
			{
				$property = $qBuilder->getModel()->getProperty($projection->getPropertyName());
				if ($property->isDocument())
				{
					$query->addDocumentProjection($projection->getAs());
					$relationAlias = 'ra' . $subdoc;
					$documentalias = 'sd' . $subdoc;
					if ($property->isArray())
					{
						$qBuilder->addFrom('INNER JOIN "f_relation" "'.$relationAlias.'" ON "'.$relationAlias.'"."relation_id1" = "t0"."document_id"');
						$qBuilder->addFrom('INNER JOIN "f_document" "'.$documentalias.'" ON "'.$documentalias.'"."document_id" = "'.$relationAlias.'"."relation_id2"');
					}
					else
					{
						$columnName = $qBuilder->getQualifiedColumnName($property->getDbMapping());
						$qBuilder->addFrom('INNER JOIN "f_document" "'.$documentalias.'" ON "'.$documentalias.'"."document_id" = '.$columnName);
					}

					$qBuilder->addField('"' .$documentalias . '"."document_id" AS "' . $projection->getAs() . '_id"');
					$qBuilder->addField('"' .$documentalias . '"."document_model" AS "' . $projection->getAs() . '_model"');
					foreach ($this->getI18nFieldNames() as $i18nFieldName)
					{
						$qBuilder->addField('"' .$documentalias . '"."' . $i18nFieldName . '" AS "' . $projection->getAs() . '_' . $i18nFieldName .'"');
					}
					if ($projection->getGroup())
					{
						$qBuilder->addGroupBy('"' .$documentalias . '"."document_id"');
						$qBuilder->addGroupBy('"' .$documentalias . '"."document_model"');
						foreach ($this->getI18nFieldNames() as $i18nFieldName)
						{
							$qBuilder->addGroupBy('"' .$documentalias . '"."' . $i18nFieldName . '"');
						}
					}

					$subdoc++;
				}
				else
				{
					$columnName = $qBuilder->getQualifiedColumnName($projection->getPropertyName());
					$qBuilder->addField($columnName .' AS "' . $projection->getAs().'"');
					if ($projection->getGroup())
					{
						$qBuilder->addGroupBy($columnName);
					}
				}
			}
		}
	}

	/**
	 * @param f_persistentdocument_criteria_ExecutableQuery $criteria
	 * @param f_persistentdocument_DocumentOciQueryBuilder $qBuilder
	 */
	private function processCriterias($query, $qBuilder)
	{
		$currentTableAlias = $qBuilder->getModelAlias();
		foreach ($query->getCriterias() as $propertyName => $criteria)
		{
			$this->processCriteria($propertyName, $criteria, $currentTableAlias, $query, $qBuilder);
		}
	}

	private function processCriteria($propertyName, $criteria, $currentTableAlias, $query, $qBuilder)
	{
		$inverseProperty = false;

		$subModel = $criteria->getDocumentModel();
		$qBuilder->pushModel($subModel, $propertyName);
		$subTableAlias = $qBuilder->getModelAlias();

		$currentModel = $query->getDocumentModel();
		$propertyInfo = $currentModel->getProperty($propertyName);
		if (is_null($propertyInfo))
		{
			$propertyInfo = $currentModel->getInverseProperty($propertyName);
			$inverseProperty = true;
		}


		if ($propertyInfo->getMaxOccurs() == 1)
		{
			// mono-valued property
			if ($inverseProperty)
			{
				$qBuilder->distinctNeeded();
				$qBuilder->addFrom('INNER JOIN "'.$subModel->getTableName().'" "'.$subTableAlias.'" ON "'.$currentTableAlias.'"."document_id" = "'.$subTableAlias.'"."'.$propertyInfo->getDbMapping().'"');
			}
			else
			{
				$qBuilder->addFrom('INNER JOIN "'.$subModel->getTableName().'" "'.$subTableAlias.'" ON "'.$subTableAlias.'"."document_id" = "'.$currentTableAlias.'"."'.$propertyInfo->getDbMapping().'"');
			}
		}
		else
		{
			// multi-valued property
			$qBuilder->distinctNeeded();

			//
			$qBuilder->newRelation();
			$relationAlias = $qBuilder->getRelationAlias();

			if ($inverseProperty)
			{
				$qBuilder->addFrom('INNER JOIN "f_relation" "'.$relationAlias.'" ON "'.$relationAlias.'"."relation_id2" = "'.$currentTableAlias.'"."document_id"');
				$qBuilder->addFrom('INNER JOIN "'.$subModel->getTableName().'" "'.$subTableAlias.'" ON "'.$subTableAlias.'"."document_id" = "'.$relationAlias.'"."relation_id1"');
			}
			else
			{
				$qBuilder->addFrom('INNER JOIN "f_relation" "'.$relationAlias.'" ON "'.$relationAlias.'"."relation_id1" = "'.$currentTableAlias.'"."document_id"');
				$qBuilder->addFrom('INNER JOIN "'.$subModel->getTableName().'" "'.$subTableAlias.'" ON "'.$subTableAlias.'"."document_id" = "'.$relationAlias.'"."relation_id2"');
			}

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
	 * @param f_persistentdocument_DocumentOciQueryBuilder $qBuilder
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
	 * @param f_persistentdocument_DocumentOciQueryBuilder $qBuilder
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	private function processCriterion($criterion, $query, $qBuilder)
	{
		if ($criterion instanceof f_persistentdocument_criteria_HasTagExpression)
		{
			$tagAlias = $qBuilder->newTableAlias();
			$qBuilder->addFrom('INNER JOIN "f_tags" "'.$tagAlias.'" ON "t0"."document_id" = "'.$tagAlias.'"."id"');
			$pAlias = $qBuilder->addParam('tag', $criterion->getTagName());
			$qBuilder->addWhere('"'.$tagAlias.'"."tag" = '.$pAlias);
			return;
		}

		if ($criterion instanceof f_persistentdocument_criteria_IsTaggedExpression)
		{
			$tagAlias = $qBuilder->newTableAlias();
			$qBuilder->addFrom('INNER JOIN "f_tags" "'.$tagAlias.'" ON "t0"."document_id" = "'.$tagAlias.'"."id"');
			return;
		}

		if ($criterion instanceof f_persistentdocument_criteria_Junction)
		{
			$qBuilder->beginJunction($criterion);
			$subCriterions = $criterion->getCriterions();
			foreach ($subCriterions as $subcriterion)
			{
				if ($subcriterion instanceof f_persistentdocument_criteria_Criterion)
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
		if (!is_null($property))
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
					$qBuilder->addWhere('('.$columnName. ' IS NULL)');
				}
				else
				{
					$currentAlias = $qBuilder->getModelAlias();
					$relAlias = $qBuilder->newTableAlias();
					$relationId = RelationService::getInstance()->getRelationId($propertyName);
					$qBuilder->addFrom('LEFT OUTER JOIN "f_relation" "'.$relAlias.'" ON "'.$currentAlias.'"."document_id" = "'.$relAlias.'"."relation_id1" AND "'.$relAlias.'"."relation_id" = ' . $relationId);
					$qBuilder->addWhere('"'.$relAlias.'"."relation_id1" IS NULL');
				}
			}
			else
			{
				$qBuilder->addWhere('('.$columnName. ' IS NULL OR '.$columnName.' = \'\')');
			}
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_InExpression)
		{
			$sql = '('.$columnName;

			if ($criterion->getNot())
			{
				$sql .= ' NOT';
			}

			$sql .= ' IN (';
			$keys = array();
			foreach ($criterion->getValues() as $value)
			{
				$keys[] = $qBuilder->addParam($propertyName, $value);
			}
			$sql .= join(',', $keys);
			$sql .= '))';
			$qBuilder->addWhere($sql);
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_LikeExpression)
		{
			$value = $criterion->getValue();
			if ($criterion->getIgnoreCase())
			{
				$value = strtolower($value);
				$columnName = 'LOWER('.$columnName.')';
			}
			$key = $qBuilder->addParam($propertyName, $criterion->getMatchMode()->toMatchString($value));
			$qBuilder->addWhere('('.$columnName.' LIKE '.$key.')');
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_NotEmptyExpression)
		{
			if ($qBuilder->getModel()->isDocumentProperty($propertyName))
			{
				if ($qBuilder->getModel()->isUniqueProperty($propertyName))
				{
					$qBuilder->addWhere('('.$columnName. ' IS NOT NULL)');
				}
				else
				{
					$relAlias = $qBuilder->newTableAlias();
					$relationId = RelationService::getInstance()->getRelationId($propertyName);
					// FIXME intsimoa : could this be optimized ?
					$qBuilder->distinctNeeded();
					$qBuilder->addFrom('INNER JOIN "f_relation" "'.$relAlias.'" ON "t0"."document_id" = "'.$relAlias.'"."relation_id1" AND "'.$relAlias.'"."relation_id" = '.$relationId);
				}
			}
			else
			{
				$qBuilder->addWhere('('.$columnName. ' IS NOT NULL AND '.$columnName.' != \'\')');
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
				case '<=':
				case '>=':
				case '<':
				case '>':
					$qBuilder->addWhere('('.$columnName.' '.$criterion->getOp().' "'.$otherColumnName.'")');
					break;
				case '!=':
					$qBuilder->addWhere('('.$columnName.' <> "'.$otherColumnName.'")');
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
						$columnName = 'LOWER('.$columnName.')';
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
	 * @param f_persistentdocument_DocumentOciQueryBuilder $qBuilder
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	private function processTreeCriterions($query, $qBuilder)
	{
		$modelAlias = $qBuilder->getModelAlias();
		foreach ($query->getTreeCriterions() as $criterion)
		{
			if ($criterion instanceof f_persistentdocument_criteria_AncestorOfExpression)
			{
				$ancestorAlias = $qBuilder->newTableAlias();
				$qBuilder->addFrom('INNER JOIN "f_tree" "'.$ancestorAlias.'" ON "'.$modelAlias.'"."document_id" = "'.$ancestorAlias.'"."document_id"');

				$documentAlias = $qBuilder->newTableAlias();
				$qBuilder->setTreeRelationTable($documentAlias);
				$ancestorCriteria = 'INNER JOIN "f_tree" "'.$documentAlias.'" ON ("'.$ancestorAlias.'"."tree_left" < "'.$documentAlias.'"."tree_left" AND "'.$ancestorAlias.'"."tree_right" > "'.$documentAlias.'"."tree_right"';
				$level = $criterion->getLevel();
				if ($level != -1)
				{
					$ancestorCriteria .= ' AND "'.$ancestorAlias.'"."tree_level" >= ("'.$documentAlias.'"."tree_level" - '.$level.')';
				}
				$ancestorCriteria .= ' AND "'.$ancestorAlias.'"."tree_id" = "'.$documentAlias.'"."tree_id")';
				$qBuilder->addFrom($ancestorCriteria);

				$qBuilder->addWhere('"'.$documentAlias.'"."document_id" = '.$criterion->getDocumentId());
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_DescendentOfExpression)
			{
				$descendentAlias = $qBuilder->newTableAlias();
				$qBuilder->addFrom('INNER JOIN "f_tree" "'.$descendentAlias.'" ON "'.$modelAlias.'"."document_id" = "'.$descendentAlias.'"."document_id"');

				$documentAlias = $qBuilder->newTableAlias();
				$qBuilder->setTreeRelationTable($documentAlias);
				$descendentCriteria = 'INNER JOIN "f_tree" "'.$documentAlias.'" ON ("'.$descendentAlias.'"."tree_left" > "'.$documentAlias.'"."tree_left" AND "'.$descendentAlias.'"."tree_right" < "'.$documentAlias.'"."tree_right"';
				$level = $criterion->getLevel();
				if ($level != -1)
				{
					$descendentCriteria .= ' AND "'.$descendentAlias.'"."tree_level" <= ("'.$documentAlias.'"."tree_level" + '.$level.')';
				}
				$descendentCriteria .= ' AND "'.$descendentAlias.'"."tree_id" = "'.$documentAlias.'"."tree_id")';
				$qBuilder->addFrom($descendentCriteria);

				$qBuilder->addWhere('"'.$documentAlias.'"."document_id" = '.$criterion->getDocumentId());
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_NextSiblingOfExpression)
			{
				$node = TreeService::getInstance()->getInstanceByDocumentId($criterion->getDocumentId());
				if (is_null($node))
				{
					throw new Exception('NextSiblingOfExpression nodeId not found : ' . $criterion->getDocumentId());
				}
				$parentNode = $node->getParent();
				$descendentAlias = $qBuilder->newTableAlias();
				$qBuilder->setTreeRelationTable($descendentAlias);
				$qBuilder->addFrom('INNER JOIN "f_tree" "'.$descendentAlias.'" ON "'.$modelAlias.'"."document_id" = "'.$descendentAlias.'"."document_id"' .
									' AND "'.$descendentAlias.'"."tree_left" > '.$node->getLeft().' AND "'.$descendentAlias.'"."tree_right" < '.$parentNode->getRight().
									' AND "'.$descendentAlias.'"."tree_level" = '.$node->getLevel().
									' AND "'.$descendentAlias.'"."tree_id" = '.$node->getTreeId());
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_PreviousSiblingOfExpression)
			{
				$node = TreeService::getInstance()->getInstanceByDocumentId($criterion->getDocumentId());
				if (is_null($node))
				{
					throw new Exception('PreviousSiblingOfExpression nodeId not found : ' . $criterion->getDocumentId());
				}
				$parentNode = $node->getParent();
				$descendentAlias = $qBuilder->newTableAlias();
				$qBuilder->setTreeRelationTable($descendentAlias);
				$qBuilder->addFrom('INNER JOIN "f_tree" "'.$descendentAlias.'" ON "'.$modelAlias.'"."document_id" = "'.$descendentAlias.'"."document_id"' .
									' AND "'.$descendentAlias.'"."tree_left" > '.$parentNode->getLeft().' AND "'.$descendentAlias.'"."tree_right" < '.$node->getRight().
									' AND "'.$descendentAlias.'"."tree_level" = '.$node->getLevel().
									' AND "'.$descendentAlias.'"."tree_id" = '.$node->getTreeId());
			}
			elseif ($criterion instanceof f_persistentdocument_criteria_SiblingOfExpression)
			{
				$node = TreeService::getInstance()->getInstanceByDocumentId($criterion->getDocumentId());
				if (is_null($node))
				{
					throw new Exception('SiblingOfExpression nodeId not found : ' . $criterion->getDocumentId());
				}
				$parentNode = $node->getParent();
				$descendentAlias = $qBuilder->newTableAlias();
				$qBuilder->setTreeRelationTable($descendentAlias);
				$qBuilder->addFrom('INNER JOIN "f_tree" "'.$descendentAlias.'" ON "'.$modelAlias.'"."document_id" = "'.$descendentAlias.'"."document_id"' .
									' AND "'.$descendentAlias.'"."tree_left" > '.$parentNode->getLeft().' AND "'.$descendentAlias.'"."tree_right" < '.$parentNode->getRight().
									' AND "'.$descendentAlias.'"."tree_level" = '.$node->getLevel().
									' AND "'.$descendentAlias.'"."tree_id" = '.$node->getTreeId());
			}
		}
	}

	/**
	 * @param f_persistentdocument_DocumentOciQueryBuilder $qBuilder
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
		return 'ALTER TABLE "f_document" ADD "label_'.$lang.'" VARCHAR2( 255 )';
	}
	
	/**
	 * Warn: not tested until now
	 * @param String $tableName
	 * @param String $fieldName
	 * @return Boolean
	 */
	protected function columnExists($tableName, $fieldName)
	{
		return f_util_ArrayUtils::isNotEmpty($this->executeSelect('select "column_name" from "user_tab_columns" where "table_name" = '.$tableName.'" and "column_name" = "'.$fieldName.'"')->fetchAll());
	}

	/**
	 * @return string
	 */
	protected function getRelationIdQuery1()
	{
		return 'SELECT "relation_id" FROM "f_relationname" WHERE "property_name" = :property_name';
	}

	/**
	 * @return string
	 */
	protected function getRelationIdQuery2()
	{
		return 'INSERT INTO "f_relationname" ("relation_id", "property_name") VALUES ("f_relationname_seq".nextval, :property_name)';
	}
}

/**
 * "Inner" class. Helper for query construction
 */
class f_persistentdocument_DocumentOciQueryBuilder
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

	private $junctions = array();
	private $currentSqlJunction = null;

	private $localizedTables = array();
	private $i18nSuffix = '_i18n';

	private $treeRelationTableAlias;

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


	public function setTreeRelationTable($tableAlias)
	{
		if (is_null($this->treeRelationTableAlias))
		{
			$this->treeRelationTableAlias = $tableAlias;
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
		$this->fields[] = $field;
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
			$orderStr = '"'.$this->aliasByPath[$this->getTablePropertyName($propertyName)].'"."'.$this->getRelativePropertyName($propertyName).'"';
		}
		else
		{
			$orderStr = '"t1"."'.$order->getPropertyName().'"'; // berk (t1)
		}
		if (!$order->getAscending())
		{
			$orderStr .= ' DESC';
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
		if (!is_null($this->treeRelationTableAlias) && empty($this->order) && $this->maxResults != 2)
		{
			$this->order[] = '"'.$this->treeRelationTableAlias .'"."tree_left"';
		}

		if (count($this->order) > 0)
		{
			if ($this->distinctNeeded)
			{
				foreach ($this->order as $orderField)
				{
					if (array_search($orderField, $this->fields) === false)
					{
						$this->addField($orderField);
					}
				}
			}
			else if (!empty($this->groupBy))
			{
				foreach ($this->order as $orderField)
				{
					if (array_search($orderField, $this->groupBy) === false)
					{
						$this->addGroupBy($orderField);
					}
				}
			}
		}


		$sql = 'SELECT '.(($this->distinctNeeded)? 'DISTINCT ' : ''). implode(', ', $this->fields).
		' FROM '.implode(' ', $this->from).
		(!empty($this->where)? ' WHERE '.implode(' AND ', $this->where) : '').
		(!empty($this->groupBy)? ' GROUP BY '.implode(',', $this->groupBy) : '').
		((!empty($this->order))? ' ORDER BY '.implode(', ', $this->order) : '');


		if ($this->maxResults > 0)
		{
			$sql = 'SELECT "c2".* FROM (SELECT "c1".*, ROWNUM "change_rownum" FROM (' . $sql . ') "c1") "c2" WHERE "c2"."change_rownum" BETWEEN ' . ($this->firstResult + 1) . ' AND ' . ($this->firstResult + $this->maxResults);
		}
		return $sql;
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
			return '"' . $this->getModelAlias() . '"."' . $propertyName .'"';
		}

		$model = $this->getModel();
		$property = $model->getProperty($propertyName);
		if ($property->isLocalized())
		{
			$this->checkLocalizedTable($model);
			$qName = '"l' . $this->getModelAlias() . '"."' . $property->getDbMapping() . $this->getI18nSuffix() .'"';
		}
		else
		{
			$qName = '"'. $this->getModelAlias() . '"."' . $property->getDbMapping().'"';
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

			$from = 'INNER JOIN "' . $table . '" "' . $localizedTableAlias . '" ON "'
			. $tableAlias .'"."document_id" = "' . $localizedTableAlias .'"."document_id" AND "'
			. $localizedTableAlias .'"."lang_i18n" = \'' . $lang . '\'';

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

class OciStatment
{

	private $stmt;

	/**
	 * @var String
	 */
	private $sql;

	/**
	 * @var f_persistentdocument_PersistentProviderOci
	 */
	private $provider;

	private $executeMode;

	private $clobParams = array();


	/**
	 * @param String $sql
	 * @param f_persistentdocument_PersistentProviderOci $provider
	 * @param Integer $executeMode
	 */
	function __construct($sql, $provider, $executeMode)
	{
		if (!$sql)
		{
			Framework::error(f_util_ProcessUtils::getBackTrace());
			die();
		}

		$this->executeMode = $executeMode;
		$this->sql = $sql;
		$this->provider = $provider;
		$this->stmt = oci_parse($this->provider->getDriver(), $sql);

		if ($this->stmt === false)
		{
			$errors = oci_error();
			$errorCode = $errors['code'];
			$msg = "Driver ERROR Code (". $errorCode . ") : " . var_export($errors, true)."\n";

			$e = new f_DatabaseException($errorCode, $msg);
			Framework::exception($e);
			throw $e;
		}
	}

	public function bindValue($parameterName, $value, $type = null)
	{
		$ociType = $this->getStatmentType($type);
		if ($ociType == OCI_B_CLOB)
		{
			$this->clobParams[$parameterName] = array();
			$this->clobParams[$parameterName][0] = oci_new_descriptor($this->provider->getDriver(), OCI_D_LOB);
			$this->clobParams[$parameterName][1] = $value;
			oci_bind_by_name ($this->stmt, $parameterName, $this->clobParams[$parameterName][0], -1, OCI_B_CLOB);
		}
		else
		{
			oci_bind_by_name($this->stmt, $parameterName, $value , -1, $ociType);
		}
	}

	public function execute($parameters = null)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::startBench();
		}

		if (!is_null($parameters))
		{
			foreach ($parameters as $parameterName => $value)
			{
				$this->bindValue($parameterName, $value);
			}
		}

		if (count($this->clobParams) == 0)
		{
			if (!oci_execute($this->stmt, $this->executeMode))
			{
				return false;
			}
		}
		else
		{
			$autoCommit = OCI_COMMIT_ON_SUCCESS === $this->executeMode;
			if (!oci_execute($this->stmt, OCI_DEFAULT))
			{
				return false;
			}
			foreach ($this->clobParams as $name => $datas)
			{
				if (!$datas[0]->save($datas[1]))
				{
					if ($autoCommit) {oci_rollback($this->provider->getDriver());}
					return false;
				}
				unset($this->clobParams[$name][1]);
			}
			if ($autoCommit) {oci_commit($this->provider->getDriver());}
		}

		if (Framework::isDebugEnabled())
		{
			Framework::endBench("SQL ".$this->sql);
		}

		return true;
	}

	public function fetch($mode)
	{
		$row = oci_fetch_array($this->stmt, $this->getStatmentFetchMode($mode));
		if ($row)
		{
			if ($mode === PersistentProviderConst::FETCH_ASSOC)
			{
				$rowlower = array();
				foreach ($row as $key => $value)
				{
					$rowlower[strtolower($key)] = $value;
				}
				$row = $rowlower;
			}
		}
		return $row;
	}

	public function fetchAll($mode)
	{
		$results = array();
		while ($row = $this->fetch($mode))
		{
			$results[] = $row;
		}
		return $results;
	}

	public function fetchColumn($columnNumber = 0)
	{
		$row = $this->fetch(PersistentProviderConst::FETCH_NUM);
		if (is_array($row) && $columnNumber < count($row))
		{
			return $row[$columnNumber];
		}
		return false;
	}

	public function closeCursor()
	{
		if (null !== $this->stmt)
		{
			oci_free_statement($this->stmt);
			foreach ($this->clobParams as $name => $datas)
			{
				$datas[0]->free();
				unset($this->clobParams[$name][0]);
			}
			$this->clobParams = array();
			$this->stmt = null;
		}
	}

	public function errorCode()
	{
		$errors = oci_error($this->stmt);
		if ($errors)
		{
			return $errors['code'];
		}
		return $errors;
	}

	public function errorInfo()
	{
		Framework::error(__METHOD__ . ' ###'. $this->sql . '###');
		return var_export(oci_error($this->stmt), true);
	}

	public function rowCount()
	{
		return oci_num_rows($this->stmt);
	}

	private function getStatmentType($type)
	{
		switch ($type)
		{
			case PersistentProviderConst::PARAM_INT:
				return OCI_B_INT;
			case PersistentProviderConst::PARAM_LOB:
				return OCI_B_CLOB;
			default:
				return null;
		}
	}

	private function getStatmentFetchMode($mode)
	{
		switch ($mode)
		{
			case PersistentProviderConst::FETCH_NUM:
				return OCI_NUM + OCI_RETURN_LOBS + OCI_RETURN_NULLS;
			default:
				return OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS;
		}
	}

	public function __destruct()
	{
		// This is important to release the provider as provider reference all the statments
		$this->provider = null;
		$this->closeCursor();
	}
}
