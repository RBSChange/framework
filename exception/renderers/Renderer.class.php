<?php
/**
 * @package framework.exception.renderers
 */
abstract class exception_Renderer
{
	protected $contentType = 'text/plain';

	abstract public function getStackTraceContents(Exception $exception);

	public final function printStackTrace(Exception $exception)
	{
		header('Content-type: '.$this->contentType);
		echo $this->getStackTraceContents($exception);
	}
}