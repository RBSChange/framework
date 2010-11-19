<?php
class indexer_IsTermNullQuery extends indexer_BooleanQuery
{
	public function __construct($field)
	{
		parent::__construct('AND');
		$this->add(new indexer_TermQuery("*", "*"));
		$range = new indexer_RangeQuery($field, "*", "*");
		$range->setIsProhibited();
		$this->add($range);
	}
}

class indexer_IsTermNotNullQuery extends indexer_RangeQuery
{
	public function __construct($field)
	{
		parent::__construct($field, "*", "*");
	}
}