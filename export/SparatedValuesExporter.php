<?php
class export_SparatedValuesExporter
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
	 * @var array<String> For example: array("article.label", "countryCode", "", ..., "price.value")
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
	 * @param String
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
	private $toEncoding = 'UTF-8';
	
	/**
	 * @param String $toEncoding
	 */
	public function setToEncoding($toEncoding)
	{
		$this->toEncoding = $toEncoding;
	}
	
	/**
	 * @return String
	 */
	public function getToEncoding()
	{
		return $this->toEncoding;
	}
	
	/**
	 * @param array<array<String, Object>> $data For example: array(
	 * 			array("article" => $article1, "price" => $price1), 
	 * 			array("article" => $article2, "price" => $price2)
	 * 		)
	 * @return String
	 */
	public function write($data)
	{
		$compiledDefinition = $this->getCompiledDefinition();
		
		$contentArray = array();
		foreach ($data as $dataRow)
		{
			$contentArray[] = $this->writeLine($dataRow, $compiledDefinition);
		}

		return implode($this->getLineSeparator(), $contentArray);
	}
		
	/**
	 * @param array<array<String, Object>> $data For example: array(
	 * 			array("article" => $article1, "price" => $price1), 
	 * 			array("article" => $article2, "price" => $price2)
	 * 		)
	 * @param String $filePath
	 * @throws IOException
	 */
	public function export($data, $filePath)
	{
		$fileContent = $this->write($data);
		$fileContent = f_util_StringUtils::convertEncoding($fileContent, 'UTF-8', $this->getToEncoding());
		f_util_FileUtils::write($filePath, $fileContent, f_util_FileUtils::OVERRIDE);
	}
	
	/**
	 * @param array<String, Object> $dataRow
	 * @return String
	 */
	private function writeLine($dataRow, $compiledDefinition)
	{
		// Get the different values.
		$values = array();
		foreach ($compiledDefinition as $column)
		{
			switch ($column['type'])
			{
				case 'object' :
					if (isset($dataRow[$column['key']]))
					{
						$object = $dataRow[$column['key']];
						$getter = $column['getter'];
						if (f_util_ClassUtils::methodExists($object, $getter))
						{
							$value = f_util_ClassUtils::callMethodOn($object, $getter);
						}
					}
					break;
				
				case 'basic' :
					if (isset($dataRow[$column['key']]))
					{
						$value = $dataRow[$column['key']];
					}
					break;
					
				default :
					$value = '';
					break;
			}
			
			$values[] = $value;
		}
		
		// Construct the line.
		return join($this->getSeparator(), $values);
	}
	
	/**
	 * @return array<array<String => String>>
	 */
	private function getCompiledDefinition()
	{
		$compiledDefinition = array();
		foreach ($this->getDefinition() as $index => $value)
		{
			$keys = explode('.', $value);
			if (count($keys) == 2 && $keys[0] && $keys[1])
			{
				$objectKey = $keys[0];
				$propertyKey = $keys[1];				
				$getter = 'get' . ucfirst($propertyKey);
				$compiledDefinition[$index] = array('type' => 'object', 'key' => $objectKey, 'getter' => $getter);
			}
			else if (count($keys) == 1 && $keys[0])
			{
				$objectKey = $keys[0];
				$compiledDefinition[$index] = array('type' => 'basic', 'key' => $objectKey);
			}
			else
			{
				$compiledDefinition[$index] = array('type' => 'empty');
			}
		}
		return $compiledDefinition;
	}
}
?>