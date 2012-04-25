<?php
/**
 * @package framework.exception
 */
/**
 * @deprecated
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
/**
 * @deprecated
 */
class BadInitializationException extends Exception
{
}

/**
 * ClassException is used in every class that must thorw exception
 */
/**
 * @deprecated
 */
class ClassException extends BaseException
{
}

/**
 * ClassNotFoundException is used when a class are not found in project
 */
/**
 * @deprecated
 */
class ClassNotFoundException extends Exception
{
}

/**
 * @deprecated
 */
class FileNotFoundException extends BaseException
{

	public function __construct($file)
	{
		$key = 'framework.exception.errors.File-not-found';
		$attributes = array('file' => $file);
		parent::__construct('file-not-found', $key, $attributes);
	}
}

/**
 * FrameworkException is used by Framework package classes to throw exception
 */
/**
 * @deprecated
 */
class FrameworkException extends BaseException
{
}

/**
 * @deprecated
 */
class IllegalArgumentException extends Exception
{

	public function __construct ($argumentNameOrMessage, $expectedArgumentType = null)
	{
		if (!is_null($expectedArgumentType))
		{
			parent::__construct("Illegal argument: ".$argumentNameOrMessage." must be a ".$expectedArgumentType);
		}
		else
		{
			parent::__construct($argumentNameOrMessage);
		}
	}
}

/**
 * @deprecated
 */
class IllegalOperationException extends Exception
{
}

/**
 * @deprecated
 */
class IllegalTransitionException extends Exception
{

	public function __construct ($previousStatus, $newStatus)
	{
		parent::__construct("Illegal status transition from '".$previousStatus."' to '".$newStatus."'");
	}
}

/**
 * @deprecated
 */
class IndexException extends Exception 
{	
}

/**
 * @deprecated
 */
class TagException extends Exception 
{
}

/**
 * @deprecated
 */
class InvalidContextualTagException extends TagException
{
	public function __construct($tagName)
	{
		parent::__construct('Invalid contextual tag: '.$tagName);
	}
}

/**
 * @deprecated
 */
class InvalidExclusiveTagException extends TagException
{
	public function __construct($tagName)
	{
		parent::__construct('Invalid exclusive tag: '.$tagName);
	}
}

/**
 * @deprecated
 */
class InvalidFunctionalTagException extends TagException
{
	public function __construct($tagName)
	{
		parent::__construct('Invalid functional tag: '.$tagName);
	}
}

/**
 * @deprecated
 */
class InvalidTagException extends TagException
{
	public function __construct($tagName)
	{
		parent::__construct('Invalid tag: '.$tagName);
	}
}

/**
 * @deprecated
 */
class IOException extends Exception
{
}

/**
 * ListNotFoundException is used when a list is not found
 */
/**
 * @deprecated
 */
class ListNotFoundException extends Exception
{
}


/**
 * NoUserForWorkitemException is used at workitem initialization if trere is not valid user.
 */
/**
 * @deprecated
 */
class NoUserForWorkitemException extends BaseException
{
	public function __construct ($argumentName)
	{
		$key = 'framework.exception.errors.No-valid-user-found-for-this-workitem';
		parent::__construct($argumentName, $key);
	}	
}

/**
 * @deprecated
 */
class ServiceNotFoundException extends Exception 
{
	public function __construct($documentModelName)
	{
		parent::__construct('Could not find DocumentService for Document Model "'.$documentModelName.'".');
	}
}

/**
 * @deprecated
 */
class TemplateNotFoundException extends BaseException
{
	private static $key = 'framework.exception.errors.Template-not-found-exception';
	
	public function __construct($filePath, $moduleName = null)
	{
		$attributes = array('filePath' => $filePath, 'moduleName' => $moduleName);
		parent::__construct("template $filePath not found in module $moduleName", self::$key, $attributes);
	}
}

/**
 * @deprecated
 */
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


/**
 * @deprecated
 */
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
/**
 * @deprecated
 */
class ValidationException extends Exception
{
}

/**
 * @deprecated
 */
class ValidatorConfigurationException extends Exception
{
}