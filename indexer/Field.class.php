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
	
	/**
	 * Helper to convert a Y-M-D H:M:S date to a "solr" date
	 * 
	 * @param string $fulldate
	 * @return string
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
	 * @return string
	 */
	static function getDateFieldName($baseName)
	{
		return $baseName . self::DATE;
	}
	
	/**
	 * @return string
	 */
	static function getStringFieldName($baseName)
	{
		return $baseName . self::STRING;
	}
	
	/**
	 * @return string
	 */
	static function getStringMultiFieldName($baseName)
	{
		return $baseName . self::STRING_MULTI;
	}
	
	/**
	 * @return string
	 */
	static function getVolatileStringFieldName($baseName)
	{
		return $baseName . self::STRING_VOLATILE;
	}
	
	/**
	 * @return string
	 */
	static function getVolatileStringMultiFieldName($baseName)
	{
		return $baseName . self::STRING_MULTI_VOLATILE;
	}
	
	/**
	 * @return string
	 */
	static function getIntegerFieldName($baseName)
	{
		return $baseName . self::INTEGER;
	}
	
	/**
	 * @return string
	 */
	static function getVolatileIntegerFieldName($baseName)
	{
		return $baseName . self::INTEGER_VOLATILE;
	}
	
	/**
	 * @return string
	 */
	static function getIntegerMultiFieldName($baseName)
	{
		return $baseName . self::INTEGER_MULTI;
	}
	
	/**
	 * @return string
	 */
	static function getVolatileIntegerMultiFieldName($baseName)
	{
		return $baseName . self::INTEGER_MULTI_VOLATILE;
	}
	
	/**
	 * @return string
	 */
	static function getFloatFieldName($baseName)
	{
		return $baseName . self::FLOAT;
	}
	
	/**
	 * @return string
	 */
	static function getVolatileFloatFieldName($baseName)
	{
		return $baseName . self::FLOAT_VOLATILE;
	}
	
	/**
	 * @param string $fieldName
	 * @return string
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