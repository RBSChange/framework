<?php
/**
 * @package framework.indexer
 */
class indexer_StandardSolrSearch
{
	/**
	 * @var indexer_Query
	 */
	private $query = null;
	
	/**
	 * @var String
	 */
	private $clientId;
	
	/**
	 * @var Boolean
	 */
	private $doSuggestion = false;
	
	/**
	 * @param indexer_Query $q
	 */
	public function __construct($q)
	{
		$this->query = $q;
	}

	/**
	 * Get the actual solr query string
	 *
	 * @return String
	 */
	public function getQueryString()
	{
		$lang = $this->query->getLang();
		$this->query->setClientId($this->clientId);
		$queryString = $this->getBaseQueryString();

		$sorting = $this->query->getSortArray();
		if (count($sorting))
		{
			$sortingString = array();
			foreach($sorting as $name => $descending)
			{
				if ($descending == true)
				{
					$sortingString[] = $name."+desc";
				}
				else
				{
					$sortingString[] = $name."+asc";
				}
			}
			
			$queryString .= "&sort=" . join(',', $sortingString). "&";
		}

		// Pagination
		$queryString .= "&start=" . $this->query->getFirstHitOffset() . "&rows=" . $this->query->getReturnedHitsCount();

		// Field limit and score
		$limits = $this->query->getFieldsLimit();
		if (is_array($limits) && count($limits)>0)
		{
			$queryString .=  "&fl=" . join(',', $limits);
			if (array_search('score', $limits) === false)
			{
				if ($this->query->getShowScore())
				{
					$queryString .= ",score";
				}
			}
		}
		else
		{	
			// Show the score if needed
			if ($this->query->getShowScore())
			{
				$queryString .= "&fl=*,score";
			}
		}
		//filter + lang
		if (!is_null($this->query->getFilterQuery()))
		{
			if (!is_null($lang))
			{
				$globalRestriction = indexer_QueryHelper::andInstance();
				$globalRestriction->add($this->query->getFilterQuery());
				$globalRestriction->add(indexer_QueryHelper::langRestrictionInstance($lang));
			}
			else 
			{
				$globalRestriction = $this->query->getFilterQuery();
			}
			$queryString .= "&fq=".$globalRestriction->toSolrString();
		}
		else
		{
			if (!is_null($lang))
			{
				$queryString .= "&fq=".indexer_QueryHelper::langRestrictionInstance($lang)->toSolrString();
			}
		}
		//higlighting
		if ($this->query->getHighlighting() === true)
		{
			$queryString.="&hl=true;&hl.fl=label_$lang,text_$lang";
		}
		
		// facets
		if ($this->query->hasFacet())
		{
			$queryString .= "&facet=true&facet.missing=true&facet.mincount=1";
			foreach ($this->query->getFacets() as $facet)
			{
				$queryString .= $facet->toSolrString();
			}
		}
		return trim($queryString);
	}
	
	/**
	 * Enable suggestion
	 */
	function doSuggestion()
	{
		$this->doSuggestion = true;
	}
	
	/**
	 * @return Boolean
	 */
	function getDoSuggestion()
	{
		return $this->doSuggestion;
	}
	
	/**
	 * @return String
	 */
	private function getBaseQueryString()
	{
		$query = array();
		if (f_util_StringUtils::isNotEmpty($this->clientId))
		{
			$query[] = 'client=' . $this->clientId;	
		}
		if ($this->doSuggestion)
		{
			$terms = $this->query->getTerms();
			if (f_util_ArrayUtils::isNotEmpty($terms))
			{
				$query[] = '&spellcheck.collate=true&spellcheck=true&spellcheck.q='.join("+", $terms).'&qt=/spellchecker_' . $this->query->getLang().'&spellcheck.count=1';
			}
		}
		$query[] = 'q=' . $this->query->toSolrString();
		return join("&", $query);
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
