<?php
class import_ScriptBindingElement extends import_ScriptBaseElement
{
	public function process()
	{
		if (isset($this->attributes['fileName']))
		{
			$fileName = f_util_FileUtils::buildAbsolutePath(WEBEDIT_HOME, $this->attributes['fileName']);
			if (file_exists($fileName))
			{
				$this->script->executeInternal($fileName);
			}
			else
			{
				echo "Binding '$fileName' not found.\n";
			}
		}
		else if (isset($this->attributes['name']) && isset($this->attributes['className']))
		{
			$className = $this->attributes['className'];
			if (f_util_ClassUtils::classExists($className))
			{
				$this->script->registerElementClass($this->attributes['name'], $className);
			}
			else
			{
				echo "Class '$className' not found.\n";
			}
		}
	}
}