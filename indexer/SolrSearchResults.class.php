<?php
/**
 * @package framework.indexer
 * Solr specific implementation of indexer_SearchResults
 */
class indexer_SolrSearchResults extends ArrayObject implements indexer_SearchResults
{
	private $totalHits;
	private $offset;
	private $returnedHits;
	private $maxScore;
	private $results = array();
	private $rows = 0;

	public function __construct($data = null)
	{
		if( ($xml = simplexml_load_string($data)) === false )
		{
			throw new IndexException(__METHOD__ . "Unexpected indexer reply content " .$data);
		}

		$status = $xml->result;
		$attr = $status->attributes();
		$this->totalHits = intval($attr->numFound);
		$this->offset = intval($attr->start);
		$this->maxScore = floatval($attr->maxScore);
		$this->returnedHits = count($xml->result->doc);

		foreach ($xml->result->doc as $doc)
		{
			$result = new indexer_SearchResult();
			foreach ($doc as $item)
			{
				$value = $item;
				// trim suffix if needed
				$name = $this->trimFieldSuffix($item->attributes()->name);
				if ($name == "score")
				{
					$result->setProperty("normalizedScore", $this->normalizeScore((float)$value));
				}
				$result->setProperty((string)$name, (string)$value);
			}
			$this->results[] = $result;
		}

		// Deal with highlighting
		foreach ($xml->lst as $lst)
		{
			if ($lst->attributes()->name == "responseHeader")
			{
				if ($lst->lst->attributes()->name == "params")
				{
					foreach ($lst->lst->str as $string)
					{
						if ($string->attributes()->name == "rows")
						{
							$this->rows = intval($string);
						}
					}
				}
			}

			if ($lst->attributes()->name == "highlighting")
			{
				// We received some highlighting results
				$idx = 0;
				foreach ($lst->lst as $docHighligting)
				{
					$hlArray = array();
					foreach ($docHighligting->arr as $field)
					{
						$fieldName = $this->trimFieldSuffix((string)$field->attributes()->name);
						$hlArray[$fieldName] = (string)$field->str;
					}
					$this->results[$idx]->setProperty("highlighting", $hlArray);
					$idx++;
				}
			}
		}
		parent::__construct($this->results); 
	}


	public function getTotalHitsCount()
	{
		return $this->totalHits;
	}
	public function getReturnedHitsCount()
	{
		return $this->returnedHits;
	}
	public function getFirstHitOffset()
	{
		return $this->offset;
	}
	public function getReturnedHits()
	{
		return $this->results;
	}

	public function getRequestedHitsPerPageCount()
	{
		return $this->rows;
	}

	private function trimFieldSuffix($fieldName)
	{
		$elems = preg_split('/_([a-z]{2}|idx_float|idx_int|idx_str|idx_dt)$/', $fieldName);
		return $elems[0];
	}
	
	private function normalizeScore($value)
	{
		if ( is_null($this->maxScore) || $this->maxScore < 0.1 )
		{
			return 0;
		}
		else
		{
			return $value/$this->maxScore;
		}
	}
}