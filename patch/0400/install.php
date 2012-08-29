<?php
/**
 * framework_patch_0400
 * @package modules.framework
 */
class framework_patch_0400 extends change_Patch
{
	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return dirname(__FILE__);
	}
	
	/**
	 * @return false
	 */
	public function isCodePatch()
	{
		return true;
	}	

	/**
	 * @return string
	 */
	public function getExecutionOrderKey()
	{
		return '2011-09-13 10:56:57';
	}
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$sql = "CREATE TABLE `f_tmp_indexing` (
 `document_id` int(11) NOT NULL,
 `indexing_status` enum('TO_INDEX', 'INDEXED') NOT NULL DEFAULT 'TO_INDEX',
 `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		
		$this->executeSQLQuery($sql);
		
		$sql = "INSERT INTO f_tmp_indexing  SELECT `document_id`, 'TO_INDEX', min(`lastupdate`) FROM `f_indexing` GROUP BY  `document_id`";
		$this->executeSQLQuery($sql);
		
		$sql = "DROP TABLE `f_indexing`";
		$this->executeSQLQuery($sql);
		
		$sql = "RENAME TABLE `f_tmp_indexing` TO `f_indexing`";
		$this->executeSQLQuery($sql);
	}
}