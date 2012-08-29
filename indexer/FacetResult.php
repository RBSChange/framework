<?php
/**
 * This is an array of indexer_FacetCount
 */
class indexer_FacetResult extends ArrayObject
{
	protected $fieldName, $fieldLabel, $simpleFieldName;
	protected $nonEmptyCount = 0;
	protected $missingCount = 0;
	protected $nonMissingPercentage;

	function __construct($lstElem, $totalHits)
	{
		$this->setFieldName($lstElem->getAttribute("name"));
		$facetCounts = array();
		$words = $lstElem->getElementsByTagName("int");
		for ($i = 0; $i < $words->length; $i++)
		{
			$facetElem = $words->item($i);
			if ($facetElem->hasAttribute("name"))
			{
				$facetCount = new indexer_FacetCount($facetElem->getAttribute("name"),
					intval($facetElem->textContent));
				if (!$facetCount->isEmpty())
				{
					$this->nonEmptyCount++;
				}
				$facetCounts[$facetCount->getValue()] = $facetCount;
			}
			else
			{
				$this->missingCount = intval($facetElem->textContent);
			}
		}
		if ($totalHits > 0)
		{
			$this->setTotalHits($totalHits);
		}
		parent::__construct($facetCounts);
	}

	/**
	 * @return string
	 */
	function getFieldName()
	{
		return $this->fieldName;
	}
	
	protected function setFieldName($fieldName)
	{
		$this->fieldName = $fieldName;
		$this->simpleFieldName = indexer_Field::getSimpleFieldName($fieldName);
	}
	
	/**
	 * @return string
	 */
	function getSimpleFieldName()
	{
		return $this->simpleFieldName;
	}

	/**
	 * @return indexer_FacetCount[]
	 */
	function getFirstEntries()
	{
		return array_slice($this->getArrayCopy(), 0, min($this->count(), 6));
	}

	function getOtherEntries()
	{
		return array_slice($this->getArrayCopy(), min($this->count(), 6));
	}
	
	function getFieldLabel()
	{
		if ($this->fieldLabel !== null)
		{
			return $this->fieldLabel;
		}
		return LocaleService::getInstance()->trans('f.indexer.facet-'.$this->getSimpleFieldName().'-label', array('ucf'));
	}
	
	function setFieldLabel($fieldLabel)
	{
		$this->fieldLabel = $fieldLabel;
	}
	
	function getEntryCount()
	{
		$count = $this->getNonEmptyCount();
		if ($this->missingCount > 0)
		{
			$count++;
		}
		return $count;
	}

	function getNonEmptyCount()
	{
		return $this->nonEmptyCount;
	}

	function getMissingCount()
	{
		return $this->missingCount;
	}
	
	protected function setTotalHits($totalHit)
	{
		$this->nonMissingPercentage = (($totalHit - $this->missingCount) * 100) / $totalHit;
	}
	
	function getNonMissingPercentage()
	{
		return $this->nonMissingPercentage;
	}
	
	/**
	 * @param string $value
	 * @return indexer_FacetCount
	 */
	function getByValue($value)
	{
		foreach ($this as $facetCount)
		{
			if ($facetCount->getValue() == $value)
			{
				return $facetCount;
			}
		}
		return null;
	}
}

/**
 * This is an array of indexer_RangeFacetCount
 */
class indexer_RangeFacetResult extends indexer_FacetResult
{
	function __construct($fieldName, $facetCounts, $totalHits)
	{
		$this->setFieldName($fieldName);
		foreach ($facetCounts as $facetCount)
		{
			if (!$facetCount->isEmpty())
			{
				$this->nonEmptyCount++;
			}
			$this->offsetSet($facetCount->getValue(), $facetCount);
		}
		// no meaning for range query
		$this->missingCount = null;
		if ($totalHits > 0)
		{
			$this->setTotalHits($totalHits);
		}
	}
}