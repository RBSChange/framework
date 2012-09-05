<?php
/**
 * @package framework.exception
 */
class BadArgumentException extends BaseException
{
	public function __construct ($argumentName, $expectedArgumentType)
	{
		$key = 'framework.exception.errors.Bad-argument';
		$attributes = array('argument' => $argumentName, 'type' => $expectedArgumentType);
		parent::__construct("bad-argument", $key, $attributes);
	}
}

/**
 * BadInitializationException is used when a class isn't correctly initialized
 */
class BadInitializationException extends Exception
{
}

/**
 * ClassNotFoundException is used when a class are not found in project
 */
class ClassNotFoundException extends Exception
{
}

class FileNotFoundException extends BaseException
{

	public function __construct($file)
	{
		$key = 'framework.exception.errors.File-not-found';
		$attributes = array('file' => $file);
		parent::__construct('file-not-found', $key, $attributes);
	}
}

class IllegalOperationException extends Exception
{
}

class IllegalTransitionException extends Exception
{

	public function __construct ($previousStatus, $newStatus)
	{
		parent::__construct("Illegal status transition from '".$previousStatus."' to '".$newStatus."'");
	}
}

class IndexException extends Exception 
{	
}

class TagException extends Exception 
{
}

class InvalidContextualTagException extends TagException
{
	public function __construct($tagName)
	{
		parent::__construct('Invalid contextual tag: '.$tagName);
	}
}

class InvalidExclusiveTagException extends TagException
{
	public function __construct($tagName)
	{
		parent::__construct('Invalid exclusive tag: '.$tagName);
	}
}

class InvalidFunctionalTagException extends TagException
{
	public function __construct($tagName)
	{
		parent::__construct('Invalid functional tag: '.$tagName);
	}
}
class InvalidTagException extends TagException
{
	public function __construct($tagName)
	{
		parent::__construct('Invalid tag: '.$tagName);
	}
}

class IOException extends Exception
{
}

class ServiceNotFoundException extends Exception 
{
	public function __construct($documentModelName)
	{
		parent::__construct('Could not find DocumentService for Document Model "'.$documentModelName.'".');
	}
}

class TemplateNotFoundException extends BaseException
{
	private static $key = 'framework.exception.errors.Template-not-found-exception';
	
	public function __construct($filePath, $moduleName = null)
	{
		$attributes = array('filePath' => $filePath, 'moduleName' => $moduleName);
		parent::__construct("template $filePath not found in module $moduleName", self::$key, $attributes);
	}
}

class TransactionCancelledException extends Exception
{
	/**
	 * @var Exception
	 */
	private $sourceException = null;

	/**
	 * @param Exception $sourceException
	 */
	public function __construct($sourceException)
	{
		if ($sourceException !== null)
		{
			parent::__construct("Transaction cancelled: ".$sourceException->getMessage());
		}
		else
		{
			parent::__construct("Transaction cancelled (unknown cause)");
		}
		$this->sourceException = $sourceException;
	}

	/**
	 * @return Exception
	 */
	public function getSourceException()
	{
		return $this->sourceException;
	}
}

class UnavailableModuleException extends Exception
{

	public function __construct ($moduleName)
	{
		parent::__construct("Module \"".$moduleName."\" is not installed.");
	}
}

/**
 * ValidationException is used by Validator
 */
class ValidationException extends Exception
{
}

class ValidatorConfigurationException extends Exception
{
}