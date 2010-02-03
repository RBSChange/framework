<?php
/**
 * <{$module}>_<{$name}>Service
 * @package modules.<{$module}>.lib.services
 */
class <{$module}>_<{$name}>Service extends BaseService
{
	/**
	 * Singleton
	 * @var <{$module}>_<{$name}>Service
	 */
	private static $instance = null;

	/**
	 * @return <{$module}>_<{$name}>Service
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
}