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
	
	/**
	 * @return string
	 */
	public function getSuggestion();
}


class indexer_EmptySearchResults extends ArrayObject implements indexer_SearchResults
{
	/**
	 * @see indexer_SearchResults::getFacetResult()
	 */
	public function getFacetResult($fieldName)
	{
		return null;	
	}

	/**
	 * @see indexer_SearchResults::getFacetResults()
	 */
	public function getFacetResults()
	{
		return array();
	}

	/**
	 * @see indexer_SearchResults::getFirstHitOffset()
	 */
	public function getFirstHitOffset()
	{
		return 0;	
	}

	/**
	 * @see indexer_SearchResults::getRequestedHitsPerPageCount()
	 */
	public function getRequestedHitsPerPageCount()
	{
		return 0;	
	}

	/**
	 * @see indexer_SearchResults::getReturnedHits()
	 */
	public function getReturnedHits()
	{
		return array();
	}

	/**
	 * @see indexer_SearchResults::getReturnedHitsCount()
	 */
	public function getReturnedHitsCount()
	{
		return 0;
	}

	/**
	 * @see indexer_SearchResults::getTotalHitsCount()
	 */
	public function getTotalHitsCount()
	{
		return 0;
	}
	
	/**
	 * @see indexer_SearchResults::getSuggestion()
	 */
	public function getSuggestion()
	{
		return null;
	}
}

