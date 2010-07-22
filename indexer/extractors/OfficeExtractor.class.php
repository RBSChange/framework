<?php
/**
 * Auto-generated doc comment
 * @package framework.indexer.extractors
 */

class indexer_OfficeExtractor
{
	/**
	 * @var String
	 */
	private $officeFilePath = null;
	
	public function __construct($path)
	{
		$this->officeFilePath = $path;
	}
	
	/**
	 * Get the text content of a pdf
	 *
	 * @return String
	 */
	public function getText()
	{
		$processHandle = popen('office2text.sh ' . escapeshellarg($this->officeFilePath) . ' 2>&1', 'r');
		if ($processHandle === false)
		{
			throw new Exception(__METHOD__ . ": could not get a valid process handle");
		}
		ob_start();
		while (($string = fread($processHandle, 1024)))
		{
			echo $string;
		}
		$result = ob_get_clean();
		$exitCode = pclose($processHandle);
		if ($exitCode !== 0)
		{
			throw new Exception(__METHOD__ . ": extractor task ended with exit code $exitCode");
		}
		return $result;
	}
}