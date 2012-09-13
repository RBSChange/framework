<?php
class f_DatabaseException extends Exception
{
	private $errorCode;

	public function __construct($errorCode, $msg)
	{
		$this->errorCode = (int) $errorCode;
		parent::__construct($msg);
	}

	public function getErrorCode()
	{
		return $this->errorCode;
	}
}