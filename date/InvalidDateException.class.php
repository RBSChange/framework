<?php
class InvalidDateException extends Exception
{
	public function __construct($dateString)
	{
		parent::__construct('Invalid date: "'.$dateString.'".');
	}
}