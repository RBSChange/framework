<?php
class f_DataCachePatternHelper
{
	/**
	 * @param String $modelName valid model name with optionnal '[]' decorator
	 * @return String
	 */
	public static function getModelPattern($modelName)
	{
		return $modelName;
	}
	
	/**
	 * @param String $tagName
	 * @return String
	 */
	public static function getTagPattern($tagName)
	{
		return 'tags/' . $tagName;
	}
	
	/**
	 * @param Integer $docId
	 * @return String
	 */
	public static function getIdPattern($docId)
	{
		return $docId;
	}
	
	/**
	 * @param Integer $docId
	 * @return String
	 */
	public static function getTTLPattern($seconds)
	{
		return 'ttl/' . $seconds;
	}
}