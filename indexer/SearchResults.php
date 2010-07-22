<?php
/**
 * @package framework.indexer
 */
interface indexer_SearchResults 
{
	/**
	 * Get the total number of hits found.
	 *
	 * @return Integer
	 */
	public function getTotalHitsCount();
	
	/**
	 * Get the number of hits effectively returned.
	 * 
	 * @return Integer
	 */
	public function getReturnedHitsCount();

	/**
	 * Get the pagination offset.
	 *
	 * @return Integer
	 */
	public function getFirstHitOffset();
	
	/**
	 * Get the array of indexer_SearchResult that were returned by the indexer (returns an empty if no hits were returned).
	 *
	 * @return Array<indexer_SearchResult>
	 */
	public function getReturnedHits();
	
	public function getRequestedHitsPerPageCount();
	
	/**
	 * @return indexer_FacetResult
	 */
	public function getFacetResult($fieldName);
	
	/**
	 * @return indexer_FacetResult[]
	 */
	public function getFacetResults();
}

