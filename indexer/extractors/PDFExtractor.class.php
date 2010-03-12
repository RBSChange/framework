<?php
/**
 * Auto-generated doc comment
 * @package framework.indexer.extractors
 */

class indexer_PDFExtractor
{
	/**
	 * @var String
	 */
	private $pdfPath = null;
	
	public function __construct($path)
	{
		$this->pdfPath = $path;
	}
	
	/**
	 * Get the text content of a pdf
	 *
	 * @return String
	 */
	public function getText()
	{
		$processHandle = popen('pdftotext -enc UTF-8 -nopgbrk -q ' . escapeshellarg($this->pdfPath). ' -', 'r');
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