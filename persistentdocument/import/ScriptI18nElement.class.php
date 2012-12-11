<?php
class import_ScriptI18nElement extends import_ScriptBaseElement
{
	
	public function process()
	{
		$lang = $this->attributes['lang'];
		RequestContext::getInstance()->beginI18nWork($lang);
	}
	
	public function endProcess()
	{
		RequestContext::getInstance()->endI18nWork();
	}
}