<?php
/**
 * framework_patch_0362
 * @package modules.framework
 */
class framework_patch_0362 extends patch_BasePatch
{

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->addProjectConfigurationEntry('databases/webapp/utf8charset', 'false');
		$this->addProjectConfigurationEntry('databases/read-only/utf8charset', 'false');
	}
}