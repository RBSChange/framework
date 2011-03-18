<?php
abstract class f_util_CSVUtils
{
	/**
	 * Exports $data as CSV.
	 *
	 * $fields is an associative array:
	 * $fields = array (
	 *    "field1" => "Label of field 1",
	 *    "field2" => "Label of field 2"
	 *    );
	 *
	 * $data is an array of associative arrays:
	 * $data = array (
	 *    array ("field1" => "value1", "field2" => "value2" ...), // line 1
	 *    array ("field1" => "value1", "field2" => "value2" ...), // line 2
	 *    );
	 *
	 * @param array<string,string> $fields
	 * @param array $data
	 *
	 * @return string
	 */
	public static function export($fields, $data, $options = null)
	{
		if ( ! $options instanceof f_util_CSVUtils_export_options )
		{
			$options = new f_util_CSVUtils_export_options();
		}

		$replace = array();
		$replace["\r\n"] = chr(10);
		if($options->quote)
		{
			$replace['"'] = '""';
		}

		$csv = array();
		$line = array();
		$replaceKeys = array_keys($replace);
		$replaceValues = array_values($replace);
		if ($options->outputHeaders)
		{
			foreach ($fields as $name => $label)
			{
				if ($options->quote)
				{
					$line[] = '"' . str_replace($replaceKeys, $replaceValues, $label) . '"';
				}
				else
				{
					$line[] = str_replace($replaceKeys, $replaceValues, $label);
				}
			}
			$csv[] = join($options->separator, $line);
		}

		foreach ($data as &$entry)
		{
			$line = array();
			foreach ($fields as $name => $label)
			{
				if ($options->quote)
				{
					$line[] = '"' . str_replace($replaceKeys, $replaceValues, strval($entry[$name])) . '"';
				}
				else
				{
					$line[] = str_replace($replaceKeys, $replaceValues, strval($entry[$name]));

				}
			}
			$csv[] = join($options->separator, $line);
		}
		if (count($csv) > 0)
		{
			return utf8_decode(join($options->crlf, $csv)).$options->crlf;	
		}
		else
		{
			return "";
		}
	}
}


class f_util_CSVUtils_export_options
{
	public $separator     = "\t";
	public $quote         = true;
	public $crlf          = "\r\n";
	public $outputHeaders = true;
}