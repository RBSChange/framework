<?php
/**
 * Auto-generated doc comment
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
	 * @param String $word
	 * @param String $lang
	 */
	public function __construct($word, $lang = null)
	{
		$this->query = $word;
		$this->lang = is_null($lang) ? RequestContext::getInstance()->getLang() : $lang;
	}
	
	/**
	 * Get the actual solr query string.
	 * 
	 * @return String
	 */
	public function getQueryString()
	{
		$clientFilter = '';
		
		if (!f_util_StringUtils::isEmpty($this->clientId))
		{
			$clientFilter = 'client=' . $this->clientId . '&';
		}
		$queryString = $clientFilter . 'qt=spellchecker_' . $this->lang . '&q=' . $this->query . '&suggestionCount=' . $this->suggestionCount;
		return trim($queryString);
	}
	
	/**
	 * Getter for $suggestionCount
	 *
	 * @return Integer
	 */
	public function getSuggestionCount()
	{
		return $this->suggestionCount;
	}
	
	/**
	 * Setter for $suggestionCount
	 *
	 * @param Integer $count
	 */
	public function setSuggestionCount($count)
	{
		$this->suggestionCount = $count;
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
}