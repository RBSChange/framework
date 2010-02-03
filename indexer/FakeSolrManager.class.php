<?php
/**
 * @package framework.indexer
 * This class handles the transactions with the Solr server
 */
class indexer_FakeSolrManager
{
	
	private $emptyQueryResult = '<?xml version="1.0" encoding="UTF-8"?><response><lst name="responseHeader"><int name="status">0</int><int name="QTime">1</int><lst name="params"><str name="rows">10</str><str name="start">0</str><str name="indent">on</str><str name="q"></str><str name="version">2.2</str></lst></lst><result name="response" numFound="0" start="0"/></response>';
	
	/**
	 * @var String
	 */
	private $clientId;
	
	private $emptyWriteResult = '<result status="0"></result>';
	
	public function __construct()
	{
		// Empty
	}
	
	/**
	 * @return String
	 */
	public function getClientId()
	{
		return $this->clientId;
	}
	
	/**
	 * @param String $clientId
	 */
	public function setClientId($clientId)
	{
		$this->clientId = $clientId;
	}

	/**
	 * Executes the indexer_StandardSolrSearch $solrSearch and fetches result:
	 *  - If the **BOOLEAN** SOLR_USE_POST_QUERIES is defined and set to true, we will send the query using HTTP/POST.
	 *  - If it is set to false, or if it is not a boolean or if it is not set, we will use HTTP/GET
	 *
	 * @throws IndexException
	 * @param indexer_StandardSolrSearch $solrSearch
	 * @return String (raw server answer)
	 */
	public function query($solrSearch)
	{
		return $this->emptyQueryResult;
    }

	/**
	 * Add document to the solr indexer.
	 *
	 * @param Array<String, Array<String, String>> $indexableDocumentFields
	 */
	public function add($indexableDocumentFields)
	{
		// Empty
	}
	/**
	 * Update the indexable document
	 *
	 * @param Array<String, Array<String, String>> $indexableDocumentFields
	 */
	public function update($indexableDocumentFields)
	{
		// Empty
	}

	/**
	 * Delete an Indexable Document fron the indexer given it's id (eg: 10006/fr)
	 *
	 * @param String $id
	 */

	public function delete($id)
	{
		// Empty
	}

	/**
	 * @param String $postString
	 * @return String
	 */
	public function sendUpdate($postString)
	{
		return $this->emptyWriteResult;
	}

	/**
	 * @param String $id
	 * @return String
	 */
	public function sendDelete($id)
	{
		return $this->emptyWriteResult;
	}
	/**
	 * @return String
	 */
	public function sendCommit()
	{
		return $this->emptyWriteResult;
	}

	/**
	 * @return String
	 */
	public function sendOptimize()
	{
		return $this->emptyWriteResult;
	}

	public function getArrayPropertyFromData($name, $data)
	{
		return array();
	}

	/**
	 * @param Boolean $bool
	 */
	public function setAutoCommit($bool)
	{
		// Empty
	}

	/**
	 * @return Boolean
	 */
	public function isDirty()
	{
		return false;
	}

	/**
	 * @return Array
	 */
	public function getSynonymsLists()
	{
		return array();
	}

	/**
	 * @param String $name
	 * @param String $content
	 */
	public function updateSynonymsList($name, $content)
	{
		// Empty
	}
	
	public function clearIndexQuery()
	{
		// Empty
	}
	
	public function optimizeIndexQuery()
	{
		// Empty
	}
	
	public function rebuildSpellCheckIndexForLang($lang)
	{
		// Empty
	}
	
	public function commit()
	{
		// Empty
	}
}
