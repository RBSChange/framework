<?php
class validation_Errors extends ArrayObject
{
	public function rejectValue($name, $message, $args = null)
	{
		$substitution = array('field' => $name);
		if ($args !== null)
		{
			$substitution = array_merge($substitution, $args);
		}
		if (f_Locale::isLocaleKey($message))
		{
			$this->append(f_Locale::translate($message, $substitution));
		}
		else
		{
			$from = array();
			$to = array();
			foreach ($substitution as $key => $value)
			{
				$from[] = "{".$key."}";
				$to[] = $value;
			}
			$this->append(str_replace($from, $to, $message));
		}
	}

	public function isEmpty()
	{
		return $this->count() == 0;
	}
}