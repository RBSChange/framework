<?php
class import_ScriptI18nElement extends import_ScriptBaseElement
{
	private $prevLang;
	
	public function process()
	{
		$lang = $this->attributes['lang'];		
		$this->prevLang = RequestContext::getInstance()->getLang();
		RequestContext::getInstance()->setLang($lang);
	}
	
	public function endProcess()
	{
		RequestContext::getInstance()->setLang($this->prevLang);
	}
}