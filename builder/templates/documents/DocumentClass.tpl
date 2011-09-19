<?php
/**
 * Class where to put your custom methods for document <{$className}>
 * @package modules.<{$moduleName}>.persistentdocument
 */
class <{$className}> extends <{$className}>base
{
<{if $documentName == 'preferences'}>
	/**
	 * @retrun String
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transBO('m.<{$moduleName}>.bo.general.module-name');
	}
<{/if}>
}