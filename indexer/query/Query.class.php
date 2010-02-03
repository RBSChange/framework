<?php
/**
 * Auto-generated doc comment
 * @package framework.indexer.query
 */

interface indexer_Query
{

	/**
	 * Sort results according to field named $name. If $descending is set to true, results 
	 * are sorted in descending order and ascending otherwise.  Returns $this.
	 * 
	 * @param String $name
	 * @param boolean $descending
	 * @return indexer_Query
	 */
	public function setSortOnField($name, $descending=true);
	
	/**
	 * Returns the associative array fieldname => sortOrder containing the query's sort option.
	 * 
	 * @return Array<String, Boolean>
	 */
	public function getSortArray();
	
	
	/**
	 * Filters the query according to the $query passed as argument.
	 *
	 * @example $myQuery->setField(new indexer_TermQuery('lang', 'fr')) will only return french document.
	 * @param indexer_Query $query
	 * @return indexer_Query
	 */
	public function setFilterQuery($query);
	
	/**
	 * Get the current query filter
	 * 
	 * @return indexer_Query
	 */
	public function getFilterQuery();
	
	/**
	 * Set the pagination offset. Returns $this.
	 *
	 * @param Integer $offset
	 * @return indexer_Query
	 */
	public function setFirstHitOffset($offset);
	
	/**
	 * Get the pagination offset.
	 * 
	 * @return Integer
	 */
	public function getFirstHitOffset();
	
	/**
	 * Set the maximum number of hits the query should return (pagination).  Returns $this.
	 *
	 * @param Integer $value
	 * @return indexer_Query
	 */
	public function setReturnedHitsCount($value);
	
	/**
	 * Get the maximum number of hits the query should return (pagination) 
	 *
	 * @return $value
	 */
	public function getReturnedHitsCount();
	
	/**
	 * Sets the fields the query should retrieve.  Returns $this.
	 * 
	 * @example myQuery->setLimit(array('id', 'model')) will only return id and model of the matching hits.
	 * @param Array<String> $array
	 * @return indexer_Query
	 */
	public function setFieldsLimit($array);
	
	/**
	 * Gets the fields the query should retrieve.
	 * 
	 * @return Array<String> $array
	 */
	
	public function getFieldsLimit(); 
	
	/**
	 * Sets wether or not the query should fetch the score.  Returns $this.
	 *
	 * @param boolean $bool
	 * @return indexer_Query
	 */
	public function setShowScore($bool);
	
	/**
	 * Gets wether or not the query should fetch the score
	 *
	 * @return boolean
	 */
	public function getShowScore();
	
	/**
	 * Set the higlighting status.  Returns $this.
	 *
	 * @param Boolean $bool
	 * @return indexer_Query
	 */
	public function setHighlighting($bool);
	/**
	 * Get the highling status.
	 * 
	 * @return Boolean
	 */
	public function getHighlighting();
	
	/**
	 * Set the lang.
	 *
	 * @param String $lang
	 * @return indexer_Query
	 */
	public function setLang($lang);
	
	/**
	 * Get the lang.
	 * 
	 * @return String
	 */
	public function getLang();
	
	/**
	 * Set the query boost value.  Returns $this.
	 *
	 * @param float $value
	 * @return indexer_Query
	 */
	public function setBoost($value);
	
	/**
	 * get the query boost value
	 * 
	 * 
	 * @return float
	 */
	public function getBoost();
	
	/**
	 * Get a Solr compatible query string.
	 * 
	 * @return String
	 */
	public function toSolrString();
	
	public function setClientId($value);
}


