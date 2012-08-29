<?php
class import_ScriptAttributeElement extends import_ScriptBaseElement
{
	public function endProcess()
	{
		if (!isset($this->attributes['name']))
		{
			throw new Exception('Attribute name not defined');
		}
		$default = isset($this->attributes['default']) && $this->attributes['default'] === 'true';
		$this->script->setAttribute($this->attributes['name'], $this->getContent(), $default);
	}
}