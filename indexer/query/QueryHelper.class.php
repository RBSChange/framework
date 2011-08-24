<?php
/**
 * Auto-generated doc comment
 * @package framework.indexer.query
 */

class indexer_QueryHelper
{
	/**
	 * @return indexer_BooleanQuery
	 */
	public static function andInstance()
	{
		return indexer_BooleanQuery::andInstance();
	}

	/**
	 * @return indexer_BooleanQuery
	 */
	public static function orInstance()
	{
		return indexer_BooleanQuery::orInstance();
	}
	
	/**
	 * @param Integer $id
	 * @return indexer_Query
	 */
	public static function descendantOfInstance($id)
	{
		return new indexer_TermQuery('document_ancestor', $id);
	}
	/**
	 * indexer_Query typically used to express restrictions
	 */

	/**
	 * Build a restricion on the accessors id's $id to be passed as filter on the query.
	 * By default, the search is not strict: it returns also document viewable by
	 * anyone.  
	 * 
	 * @param Array<Integer> $idArray
	 * @return indexer_BooleanQuery
	 */
	public static function accessRestrictionInstance($idArray, $strict=false)
	{
		$result = self::orInstance();
		if ($strict && count($idArray) == 0)
		{
			return null;
		}

		if(!$strict)
		{
			$result->add(new indexer_TermQuery('document_accessor', 0));
		}

		foreach($idArray as $id)
		{
			$result->add(new indexer_TermQuery('document_accessor', $id));
		}
		return $result;
	}
	
	public static function websiteIdRestrictionInstance($int)
	{
		$fieldName = indexer_Field::getVolatileIntegerMultiFieldName('websiteIds');
		$result = self::orInstance();		
		$result->add(new indexer_TermQuery($fieldName,  $int));
		$result->add(new indexer_TermQuery($fieldName, 0));
		return $result;
	}

	/**
	 * Build a restricion on the lang of the documents searched to be passed as filter on the query
	 *
	 * @param String $lang
	 * @return indexer_Query
	 */
	public static function langRestrictionInstance($lang=null)
	{
		if (is_null($lang))
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		return new indexer_TermQuery('lang', $lang);
	}

	/**
	 * Queries typically for field searches (or field restrictions).
	 */

	/**
	 * Get a simple indexer_TermQuery for a localized field $name on the value $value
	 *
	 * @param String $name
	 * @param mixed $value
	 * @return indexer_TermQuery
	 */
	public static function localizedFieldInstance($name, $value, $lang=null)
	{
		if (is_null($lang))
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		$res = new indexer_TermQuery($name, $value);
		$res->setLang($lang);
		return $res;
	}

	/**
	 * Get an indexer_TermQuery for the default field on the $value
	 *
	 * @param mixed $value
	 * @return indexer_TermQuery
	 */
	public static function defaultFieldInstance($value)
	{
		return self::localizedFieldInstance('label', $value);
	}

	/**
	 * Combines queries $first and $second so that the query $first NOT $second is returned.
	 * 
	 * @param Mixed $first
	 * @param Mixed $second
	 * @return indexer_Query
	 */
	public static function notInstance($first, $second)
	{
		$result = indexer_BooleanQuery::notInstance();
		$result->add($first);
		$result->add($second);
		return $result;
	}

	/**
	 * Term query on a dynamic string field $name with $value
	 *
	 * @param String $name
	 * @param Mixed $value
	 * @return indexer_TermQuery
	 */
	public static function stringFieldInstance($name, $value)
	{
		return new indexer_TermQuery($name . indexer_Field::STRING, $value);
	}

	/**
	 * Term query on a dynamic date field $name with $value
	 *
	 * @param String $name
	 * @param date_Calendar $value
	 * @return indexer_TermQuery
	 */
	public static function dateFieldInstance($name, $value)
	{
		return new indexer_TermQuery($name . indexer_Field::DATE, date_Formatter::format($value, indexer_SolrManager::SOLR_DATE_FORMAT));
	}

	/**
	 * Term query on a dynamic integer field $name with $value
	 *
	 * @param String $name
	 * @param Integer $value
	 * @param Boolean $multivalued
	 * @return indexer_TermQuery
	 */
	public static function integerFieldInstance($name, $value, $multivalued = false)
	{
		$suffix = $multivalued ? indexer_Field::INTEGER_MULTI : indexer_Field::INTEGER;
		return new indexer_TermQuery($name . $suffix, $value);
	}
	
	/**
	 * Term query on a dynamic float field $name with $value
	 *
	 * @param String $name
	 * @param Float $value
	 * @return indexer_TermQuery
	 */
	public static function floatFieldInstance($name, $value)
	{
		return new indexer_TermQuery($name . indexer_Field::FLOAT, $value);
	}


}