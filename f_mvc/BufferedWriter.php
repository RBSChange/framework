<?php
class f_mvc_BufferedWriter implements f_mvc_Writer 
{	
	private $isCapturing = false;
	
	/**
	 * @see f_mvc_Writer::flush()
	 *
	 */
	function flush()
	{
		// Empty
	}
	
	/**
	 * @see f_mvc_Writer::write()
	 *
	 * @param String $text
	 */
	function write($text)
	{
		if (!$this->isCapturing)
		{
			$this->startCapturing();
		}
		echo $text;
	}
	
	/**
	 * @return String
	 */
	function getContent()
	{
		if (!$this->isCapturing)
		{
			return "";
		}
		$result = ob_get_clean();
		$this->isCapturing = false;
		return $result;
	}
	
	function peek()
	{
		if (!$this->isCapturing)
		{
			return "";
		}
		return ob_get_contents();
	}
	
	private function startCapturing()
	{
		if (!$this->isCapturing)
		{
			ob_start();
			$this->isCapturing = true;
		}
	}
}