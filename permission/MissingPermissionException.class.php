<?php
class permission_MissingPermissionException extends BaseException 
{
	public function __construct($login, $permission, $node)
	{
		$message = str_replace(array('_', '.'), '-', $permission);
		$data = explode('-', $message);
		if (count($data) > 1 && $data[0] == 'modules')
		{
			$moduleName = $data[1];
		}
		else
		{
			$moduleName = 'generic';
		}
		$key = 'modules.'. $moduleName . '.errors.' . ucfirst($message);
		parent::__construct($message, $key);
	}
}
