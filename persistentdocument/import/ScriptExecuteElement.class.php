<?php
class import_ScriptExecuteElement extends import_ScriptBaseElement
{
	public function process()
	{
		if (!isset($this->attributes['method']))
		{
			throw new Exception('Attribute method not defined');
		}
		
		if (isset($this->attributes['on-refid']))
		{
			$scriptDocument = $this->script->getElementById($this->attributes['on-refid']);
			if ($scriptDocument === null)
			{
				throw new Exception('Script element not found :' . $this->attributes['on-refid']);
			}
		}
		else
		{
			$scriptDocument = $this->getParent();
			if ($scriptDocument === null)
			{
				throw new Exception('Parent script element not found');
			}
		}
		$method = $this->attributes['method'];
		if (!f_util_ClassUtils::methodExists($scriptDocument, $method))
		{
			throw new Exception("Method ($method) not found on script document: " . get_class($scriptDocument));
		}
		$scriptDocument->{$method}($this);
	}
}