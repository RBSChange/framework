<?php
/**
 * @package modules.<{$name}>.lib.services
 */
class <{$name}>_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var <{$name}>_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return <{$name}>_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
//	public function getParentNodeForPermissions($documentId)
//	{
//		// Define this method to handle permissions on a virtual tree node. Example available in list module.
//	}
}