<?php
/**
 * @package framework.indexer
 * 	Class declaring the masks determining how fields should be handled by the indexer.
 */
final class indexer_Field
{
	const IGNORED = 0;
	const INDEXED = 1;
	const STORED = 2;
	const TOKENIZED = 4;
	const MULTIVALUED = 8;
	
	// Custom fields
	
	// Dynamic (and stored) fields
	const STRING = '_idx_str';
	const STRING_MULTI = '_idx_mul_str';
	const DATE = '_idx_dt';
	const INTEGER = '_idx_int';
	const INTEGER_MULTI = '_idx_mul_int';
	const FLOAT = '_idx_float';
	
	// Dynamic (non stored) fields.
	// WARN: this only works with schema >= 3.5
	const STRING_VOLATILE = '_vol_str';
	const STRING_MULTI_VOLATILE = '_vol_mul_str';
	const DATE_VOLATILE = '_vol_dt';
	const INTEGER_VOLATILE = '_vol_int';
	const INTEGER_MULTI_VOLATILE = '_vol_mul_int';
	const FLOAT_VOLATILE = '_vol_float';
	
	const PARENT_WEBSITE = '__solrsearch_parentwebsite_id';
	const PARENT_TOPIC = 'parentTopicId';
	
	const SOLR_DATE_FORMAT = 'Y-m-dTH:i:sZ';
	
	/**
	 * Helper to convert a Y-M-D H:M:S date to a "solr" date
	 * 
	 * @param String $fulldate
	 * @return String
	 */
	static function dateToSolrDate($fulldate)
	{
		list($date, $time) = explode(' ', $fulldate);
		return $date . 'T' . $time . 'Z';
	}
	
	static function solrDateToDate($date)
	{
		return str_replace(array('T', 'Z'), array(' ', ''), $date);
	}
	
	/**
	 * @return String
	 */
	static function getDateFieldName($baseName)
	{
		return $baseName . self::DATE;
	}
	
	/**
	 * @return String
	 */
	static function getStringFieldName($baseName)
	{
		return $baseName . self::STRING;
	}
	
	/**
	 * @return String
	 */
	static function getStringMultiFieldName($baseName)
	{
		return $baseName . self::STRING_MULTI;
	}
	
	/**
	 * @return String
	 */
	static function getVolatileStringFieldName($baseName)
	{
		return $baseName . self::STRING_VOLATILE;
	}
	
	/**
	 * @return String
	 */
	static function getVolatileStringMultiFieldName($baseName)
	{
		return $baseName . self::STRING_MULTI_VOLATILE;
	}
	
	/**
	 * @return String
	 */
	static function getIntegerFieldName($baseName)
	{
		return $baseName . self::INTEGER;
	}
	
	/**
	 * @return String
	 */
	static function getVolatileIntegerFieldName($baseName)
	{
		return $baseName . self::INTEGER_VOLATILE;
	}
	
	/**
	 * @return String
	 */
	static function getIntegerMultiFieldName($baseName)
	{
		return $baseName . self::INTEGER;
	}
	
	/**
	 * @return String
	 */
	static function getVolatileIntegerMultiFieldName($baseName)
	{
		return $baseName . self::INTEGER_MULTI_VOLATILE;
	}
	
	/**
	 * @return String
	 */
	static function getFloatFieldName($baseName)
	{
		return $baseName . self::FLOAT;
	}
	
	/**
	 * @return String
	 */
	static function getVolatileFloatFieldName($baseName)
	{
		return $baseName . self::FLOAT_VOLATILE;
	}
	
	/**
	 * @param String $fieldName
	 * @return String
	 */
	static function getSimpleFieldName($fieldName)
	{
		$matches = null;
		if (preg_match('/^(.*)_(idx|vol)_(str|mul_str|float|int|mul_int|dt)$/', $fieldName, $matches))
		{
			return $matches[1];
		}
		return $fieldName;
	}
}