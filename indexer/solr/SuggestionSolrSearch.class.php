<?php
/**
 * @deprecated use indexer_StandardSolrSearch::doSuggestion() method with
 * solr-1.4 and change >= 3.0.3
 * @package framework.indexer
 */
class indexer_SuggestionSolrSearch
{
	/**
	 * @var indexer_SuggestionQuery
	 */
	private $query = null;
	
	/**
	 * @var String
	 */
	private $lang = null;
	
	/**
	 * By default, we ask the spellchecker for 5 suggestions as it seems to be a 
	 * good lower-boundary to get most-relevant suggestions.
	 * 
	 * @var Integer
	 */
	private $suggestionCount = 5;
	
	/**
	 * @var String
	 */
	private $clientId;
	
	/**
	 * @var Boolean
	 */
	private $multiple;
	
	/**
	 * @param string|String[] $wordOrWords can be an array only if Solr1.4 & change schema 3.0.3 used
	 * @param string $lang
	 */
	public function __construct($wordOrWords, $lang = null)
	{
		$this->multiple = is_array($wordOrWords);
		if ($this->multiple)
		{
			$wordOrWords = join("+", $wordOrWords);
		}
		$this->query = $wordOrWords;
		$this->lang = is_null($lang) ? RequestContext::getInstance()->getLang() : $lang;
	}
	
	/**
	 * Get the actual solr query string.
	 * 
	 * @return string
	 */
	public function getQueryString()
	{
		$clientFilter = '';		
		if (!f_util_StringUtils::isEmpty($this->clientId))
		{
			$clientFilter = 'client=' . $this->clientId . '&';
		}
		$schemaVersion = indexer_SolrManager::getSchemaVersion();
		$queryString = $clientFilter . '&q=*:*&rows=0&spellcheck=true&spellcheck.q='.$this->query.'&qt=/spellchecker_' . $this->lang.'&spellcheck.count='.$this->suggestionCount;
		if ($this->multiple)
		{
			$queryString .= "&spellcheck.collate=true";	
		}
		return trim($queryString);
	}
	
	/**
	 * Getter for $suggestionCount
	 *
	 * @return integer
	 */
	public function getSuggestionCount()
	{
		return $this->suggestionCount;
	}
	
	/**
	 * Setter for $suggestionCount
	 *
	 * @param integer $count
	 */
	public function setSuggestionCount($count)
	{
		$this->suggestionCount = $count;
	}
	
	/**
	 * @return string
	 */
	public function getClientId()
	{
		return $this->clientId;
	}
	
	/**
	 * @param string $clientId
	 */
	public function setClientId($clientId)
	{
		$this->clientId = $clientId;
	}
}