<?php
class indexer_PhraseQuery extends indexer_TermQuery 
{
	/**
	 * @var Integer
	 */
	protected $proximity;

	/**
	 * @param Float $proximity
	 */
	function setProximity($proximity)
	{
		$this->proximity = $proximity;
	}

	/**
	 * @return String
	 */
	function __toString()
	{
		return $this->toSolrString();
	}

	protected function toStringSuffix()
	{
		$suffix = "";
		if ($this->proximity != "")
		{
			$suffix .= "~".$this->proximity;
		}
		$suffix .= parent::toStringSuffix();
		return $suffix;
	}
	
	public function toSolrString()
	{
		return urlencode($this->toStringPrefix().'"'.$this->escapeValue($this->value).'"'.$this->toStringSuffix());
	}
	
	/**
	 * @return String[]
	 */
	public function getTerms()
	{
		return explode(" ", $this->value);
	}
}