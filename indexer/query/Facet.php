<?php
class indexer_Facet
{
	const METHOD_FC = "fc";
	const METHOD_ENUM = "enum";

	/**
	 * @see http://wiki.apache.org/solr/SimpleFacetParameters
	 */
	public $field, $prefix, $sort = true, $limit = 100, $offet = 0, $mincount = 0, $missing = false, $method = self::METHOD_FC, $enum_cache_minDf = 0;

	function __construct($field, $prefix = null)
	{
		$this->field = $field;
		$this->prefix = $prefix;
	}
	
	function toSolrString()
	{
		$solrStr = "&facet.field=".$this->field;
		if (f_util_StringUtils::isNotEmpty($this->prefix))
		{
			$solrStr .= "&f.".$this->field.".facet.prefix=".urlencode($this->prefix);	
		}
		$solrStr .= "&f.".$this->field.".facet.limit=".$this->limit;
		$solrStr .= "&f.".$this->field.".facet.method=".$this->method;
		return $solrStr;
	}
}