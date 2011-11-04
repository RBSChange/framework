<?php
class validation_Errors extends ArrayObject
{
	/**
	 * @param string $name
	 * @param string $message
	 * @param array $args
	 */
	public function rejectValue($name, $message, $args = null)
	{
		if ($this->document !== null)
		{
			$this->document->addPropertyErrors($name, $message);
		}
		
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

	/**
	 * @return boolean
	 */
	public function isEmpty()
	{
		return $this->count() == 0;
	}
	
	private $document;
	
	/**
	 * @deprecated for compatibility
	 * @param f_persistentdocument_PersistentDocumentImpl $document
	 */
	public function setDocument($document)
	{
		if ($document->hasPropertiesErrors())
		{
			foreach ($document->getPropertiesErrors() as $propertyName => $errors) 
			{
				foreach ($errors as $error) 
				{
					$this->rejectValue($propertyName, $error);
				}
			}
		}	
		$this->document = $document;
	}
	
	public function __destruct()
	{
		$this->document == null;
	}
}