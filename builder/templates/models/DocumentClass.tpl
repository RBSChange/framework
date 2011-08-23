<?php
/**
 * Class where to put your custom methods for document <{$model->getDocumentClassName()}>
 * @package modules.<{$model->getModuleName()}>.persistentdocument
 */
class <{$model->getDocumentClassName()}> extends <{$model->getDocumentClassName()}>base
{
<{if $model->getFinalDocumentName() == 'preferences'}>
	/**
	 * @retrun String
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transBO('m.<{$model->getModuleName()}>.bo.general.module-name');
	}
<{/if}>
}