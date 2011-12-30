<?php
class import_SparatedValuesImporter
{
	/**
	 * @param array<String> $definition
	 * @param String $separator
	 */
	public function __construct($definition, $separator, $lineSeparator = "\n")
	{
		$this->definition = $definition;
		$this->separator = $separator;
		$this->lineSeparator = $lineSeparator;
	}

	/**
	 * @var array<String> Example: array("article.label", ..., "price.value")
	 */
	private $definition;	

	/**
	 * @param array<String> $definition
	 */
	private function setDefinition($definition)
	{
		$this->definition = $definition;
	}

	/**
	 * @return array<String>
	 */
	private function getDefinition()
	{
		return $this->definition;
	}
	
	/**
	 * @var String
	 */
	private $separator;
	
	/**
	 * @param String $separator
	 */
	private function setSeparator($separator)
	{
		$this->separator = $separator;
	}
	
	/**
	 * @return String
	 */
	private function getSeparator()
	{
		return $this->separator;
	}
	
	/**
	 * @var String
	 */
	private $lineSeparator;
	
	/**
	 * @param String $lineSeparator
	 */
	private function setLineSeparator($lineSeparator)
	{
		$this->lineSeparator = $lineSeparator;
	}
	
	/**
	 * @return String
	 */
	private function getLineSeparator()
	{
		return $this->lineSeparator;
	}
	
	/**
	 * @var String
	 */
	private $fromEncoding = 'UTF-8';
	
	/**
	 * @param String $fromEncoding
	 */
	public function setFromEncoding($fromEncoding)
	{
		$this->fromEncoding = $fromEncoding;
	}
	
	/**
	 * @return String
	 */
	public function getFromEncoding()
	{
		return $this->fromEncoding;
	}
	
	/**
	 * @param String $fileContent
	 * @return array<array<String, String>> 
	 * For example: 
	 * 		array(
	 * 			array("article.label" => "Shampooing", ..., "price.value" => "50"), 
	 * 			array("article.label" => "Sèche-cheveux", ..., "price.value" => "15.20")
	 * 		)
	 */
	public function read($fileContent)
	{
		$lines = explode($this->getLineSeparator(), $fileContent);
		
		$data = array();
		foreach ($lines as $line)
		{
			// Ignore empty lines.
			if ($line)
			{
				$data[] = $this->readLine($line);
			}
		}
		
		return $data;
	}
	
	/**
	 * @param String $filePath
	 * @return array<array<String, String>> 
	 * For example: 
	 * 		array(
	 * 			array("article.label" => "Shampooing", ..., "price.value" => "50"), 
	 * 			array("article.label" => "Sèche-cheveux", ..., "price.value" => "15.20")
	 * 		)
	 */
	public function import($filePath)
	{
		$fileContent = f_util_FileUtils::read($filePath);
		$fileContent = f_util_StringUtils::convertEncoding($fileContent, $this->getFromEncoding());
		return $this->read($fileContent);
	}
	
	/**
	 * @param String $line
	 * @return array<String, Object>
	 */
	private function readLine($line)
	{
		$lineValues = explode($this->getSeparator(), $line);
		
		// Get the different values.
		$values = array();
		foreach ($this->getDefinition() as $index => $column)
		{
			if ($column != '')
			{
				$values[$column] = $lineValues[$index];
			}
		}
		return $values;
	}
}
?>