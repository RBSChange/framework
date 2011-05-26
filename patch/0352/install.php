<?php
/**
 * framework_patch_0352
 * @package modules.framework
 */
class framework_patch_0352 extends patch_BasePatch
{

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->log('compile-documents...');
		$this->execChangeCommand('compile-documents');
		
		$this->log('update f_url_rules...');
		$sql= "ALTER TABLE `f_url_rules` ADD `origine` int(11) NOT NULL DEFAULT '0' AFTER `rule_id` , 
			ADD `modulename` VARCHAR( 50 ) NULL AFTER `origine` , 
			ADD `actionname` VARCHAR( 50 ) NULL AFTER `modulename`";
		$this->executeSQLQuery($sql);
		
		$sql= "ALTER TABLE `f_url_rules` CHANGE `document_lang` `website_lang` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'fr'";
		$this->executeSQLQuery($sql);
		
		$sql = "ALTER TABLE `f_url_rules` DROP INDEX `website_id`";
		$this->executeSQLQuery($sql);
		
		$sql = "ALTER TABLE `f_url_rules` ADD UNIQUE `website_id` ( `website_id` , `website_lang` , `from_url` )";
		$this->executeSQLQuery($sql);		
		
		$sql = "SELECT r.`rule_id` , r.`document_id` , d.document_model FROM `f_url_rules` AS r INNER JOIN f_document AS d ON r.`document_id` = d.`document_id`
				WHERE `modulename` IS NULL LIMIT 0 , 100";
		
		
		$this->log('fill modulename field...');
		$sql2 = "UPDATE `f_url_rules` SET modulename = ':modulename', actionname='ViewDetail' WHERE `rule_id` = :rule_id";
		$search = array(':modulename', ':rule_id');
		while (true) 
		{
			$stmt = $this->executeSQLSelect($sql);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			foreach ($rows as $row) 
			{
				list(,$moduleName,) = explode('/', str_replace('_', '/', $row['document_model']));
				$replace = array($moduleName, $row['rule_id'] );
				$query = str_replace($search, $replace, $sql2);
				$this->executeSQLQuery($query);
			}
			if (count($rows) === 0) {break;}
		}
		$this->log('remove deprecated rules...');
		$sql = "DELETE FROM `f_url_rules` WHERE modulename IS NULL AND document_id > 0";
		$stmt = $this->executeSQLSelect($sql);
		
		$this->log('update website_id field...');
		$website = website_WebsiteModuleService::getInstance()->getDefaultWebsite();
		if (!$website->isNew())
		{
			$sql2 = "UPDATE `f_url_rules` SET website_id = :website_id WHERE `website_id` = 0";
			$sql = str_replace(':website_id', $website->getId(), $sql2);	
			$this->executeSQLQuery($sql);
		}
	}
}