<?php
/**
 * This is an array of indexer_FacetCount
 */
class indexer_FacetResult extends ArrayObject
{
	private $fieldName;
	private $nonEmptyCount = 0;
	private $missingCount = 0;
	private $nonMissingPercentage;

	function __construct($lstElem, $totalHits)
	{
		$this->fieldName = $lstElem->getAttribute("name");
		$facetCounts = array();
		$words = $lstElem->getElementsByTagName("int");
		for ($i = 0; $i < $words->length; $i++)
		{
			$facetElem = $words->item($i);
			if ($facetElem->hasAttribute("name"))
			{
				$facetCount = new indexer_FacetCount($facetElem->getAttribute("name"), intval($facetElem->textContent));
				if (!$facetCount->isEmpty())
				{
					$this->nonEmptyCount++;
				}
				$facetCounts[] = $facetCount;
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

	function getFieldName()
	{
		return $this->fieldName;
	}

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
		return f_Locale::translate("&modules.alsatica.frontoffice.Facet-".$this->fieldName."-label;");
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
	
	private function setTotalHits($totalHit)
	{
		$this->nonMissingPercentage = (($totalHit - $this->missingCount) * 100) / $totalHit;
	}
	
	function getNonMissingPercentage()
	{
		return $this->nonMissingPercentage;
	}
}