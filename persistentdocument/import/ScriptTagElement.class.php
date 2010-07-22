<?php
class import_ScriptTagElement extends import_ScriptBaseElement
{
	public function process()
	{
		$parent = $this->getParent();
		if ($parent instanceof import_ScriptDocumentElement)
		{
			$parent->addTag($this->attributes['name']);
		}
	}
}