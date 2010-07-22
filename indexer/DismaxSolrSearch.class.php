<?php
/**
 * @package framework.indexer
 */
class indexer_DismaxSolrSearch
{
	/**
	 * @var indexer_Query
	 */
	private $query = null;
	
	
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
		if (is_null($lang))
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		$queryString = "&qt=dismax_". $lang  ."&q=" . $this->query->toSolrString();
		// Sorting
		$sorting = $this->query->getSortArray();
		if (count($sorting))
		{
			$sortingString = array();
			foreach($sorting as $name => $descending)
			{
				if ($descending == true)
				{
					$sortingString[] = $name." desc";
				}
				else
				{
					$sortingString[] = $name." asc";
				}
			}
			$queryString .= "&sort=" . join(',', $sortingString);
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
		return trim($queryString);
	}
}