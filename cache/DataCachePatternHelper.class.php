<?php
class f_DataCachePatternHelper
{
	/**
	 * @param string $modelName valid model name with optionnal '[]' decorator
	 * @return string
	 */
	public static function getModelPattern($modelName)
	{
		return $modelName;
	}
	
	/**
	 * @param string $tagName
	 * @return string
	 */
	public static function getTagPattern($tagName)
	{
		return 'tags/' . $tagName;
	}
	
	/**
	 * @param integer $docId
	 * @return string
	 */
	public static function getIdPattern($docId)
	{
		return $docId;
	}
	
	/**
	 * @param integer $docId
	 * @return string
	 */
	public static function getTTLPattern($seconds)
	{
		return 'ttl/' . $seconds;
	}
}