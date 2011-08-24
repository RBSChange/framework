<?php
class indexer_SolrIndexService extends indexer_IndexService
{
	/**
	 * @var indexer_SolrManager
	 */
	private $manager = null;
	
	private $config = null;
	
	/**
	 * @var indexer_SolrIndexService
	 */
	private static $instance = null;
	
	/**
	 * @return indexer_SolrIndexService
	 */
	public static function getInstance()
	{
		if (null === self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * @return indexer_SolrManager
	 */
	protected final function getManager()
	{
		if ($this->manager === null)
		{
			$this->config = Framework::getConfiguration('solr');
			$solrURL = $this->config['url'];
			if (!f_util_StringUtils::endsWith($solrURL, '/'))
			{
				$solrURL .= '/';
				$this->config['url'] = $solrURL;
			}
			$this->manager = new indexer_SolrManager($solrURL, $this->config);
		}
		return $this->manager;
	}
	
	/**
	 * @param boolean $autoCommit
	 */
	public function setAutoCommit($autoCommit)
	{
		if (is_bool($autoCommit))
		{
			$this->getManager()->setAutoCommit($autoCommit);
		}
	}
	
	/**
	 * @param integer $maxDocumentId
	 * @param integer $chunkSize
	 * @return integer || 'ERROR'
	 */	
	public function backgroundIndex($maxDocumentId, $chunkSize = 100)
	{
		if ($this->getManager()->getDisableDocumentCache())
		{
			$this->getPersistentProvider()->setDocumentCache(false);
		}
		$result = parent::backgroundIndex($maxDocumentId, $chunkSize);
			
		$this->getManager()->commit();
		return $result;
	}

	/**
	 * @param string $modelName
	 * @param integer $documentIndex
	 * @param integer $chunkSize
	 * @param boolean $delayed
	 * @return integer || 'ERROR'
	 */
	public function indexDocumentChunk($modelName, $documentIndex, $chunkSize, $delayed = false)
	{
		if (!$delayed && $this->getManager()->getDisableDocumentCache())
		{
			$this->getPersistentProvider()->setDocumentCache(false);
		}
		
		$result = parent::indexDocumentChunk($modelName, $documentIndex, $chunkSize, $delayed);
		
		if (!$delayed)
		{
			$this->getManager()->commit();
		}
		return $result;
	}
	
	public function clearIndex()
	{
		$this->getManager()->clearIndexQuery();
		parent::clearIndex();
	}
	
	public function optimizeIndex()
	{
		$this->getManager()->optimizeIndexQuery();
	}
	
	public function rebuildSpellCheckIndexForLang($lang)
	{
		$this->getManager()->rebuildSpellCheckIndexForLang($lang);
	}
	
	/**
	 * @param integer $documentId
	 * @param string[] $langs
	 * @return string
	 */	
	protected function deleteDocumentIdForLangs($documentId, $langs)
	{
		foreach ($langs as $lang) 
		{
			$this->getManager()->delete($documentId .'/' .$lang);
		}
		return parent::deleteDocumentIdForLangs($documentId, $langs);
	}
	
	/**
	 * @param indexer_IndexedDocument $indexedDocument
	 * @return string
	 */
	protected function addInIndex($indexedDocument)
	{
		$this->getManager()->add($indexedDocument);
		return parent::addInIndex($indexedDocument);
	}

	/**
	 * Execute $query on the configured <strong>frontoffice</strong> indexer using the standard request handler
	 * (search on label and full text with a boost on the label). 
	 * 
	 * @param indexer_Query $query
	 * @param String[] $suggestionTerms
	 * @return indexer_SolrSearchResults
	 */
	public function search(indexer_Query $query, $suggestionTerms = null)
	{
		try
		{
			$query->addFilterQuery(indexer_QueryHelper::integerFieldInstance('SEARCHFO', 1));
			$solrSearch = new indexer_StandardSolrSearch($query);
			if (f_util_ArrayUtils::isNotEmpty($suggestionTerms))
			{
				$solrSearch->doSuggestion($suggestionTerms);
			}
			$data = $this->getManager()->query($solrSearch);
			$searchResults = new indexer_SolrSearchResults($data, $solrSearch);;
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return parent::search($query, $suggestionTerms);
		}
		return $searchResults;
	}
	
	/**
	 * Execute $query on the configured <strong>backoffice</strong> indexer using the standard request handler
	 * (search on label and full text with a boost on the label). 
	 * 
	 * @param indexer_Query $query
	 * @return indexer_SearchResults
	 */
	public function searchBackoffice(indexer_Query $query)
	{
		try
		{
			$query->addFilterQuery(indexer_QueryHelper::integerFieldInstance('SEARCHBO', 1));
			$solrSearch = new indexer_StandardSolrSearch($query);
			$data = $this->getManager()->query($solrSearch);
			$searchResults = new indexer_SolrSearchResults($data, $solrSearch);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return parent::searchBackoffice($query);
		}
		return $searchResults;
	}	
	
	/**
	 * Get an array of at most $count suggestions for the word $word from the spellchecker for $lang. 
	 *
	 * @param String $word
	 * @param String $lang
	 * @param String $count
	 * @return Array<String>
	 */
	public function getSuggestionArrayForWord($word, $lang = null, $count = null)
	{
		try
		{
			$manager = $this->getManager();
			$query = new indexer_SuggestionSolrSearch($word, $lang);
			if (!is_null($count) && $count > 0)
			{
				$query->setSuggestionCount($count);
			}
			$data = $manager->query($query);

			$dataDom = f_util_DOMUtils::fromString($data);
			$elems = $dataDom->find("lst[@name='spellcheck']/lst[@name='suggestions']/lst[@name='".str_replace("'", "&#39;", $word)."']/arr[@name='suggestion']/str");
			$searchResults = array();
			foreach ($elems as $elem)
			{
				$searchResults[] = $elem->textContent;
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return $searchResults;
	}
}