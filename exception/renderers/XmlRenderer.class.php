<?php
class exception_XmlRenderer extends exception_Renderer
{
	
	public function __construct()
	{
		$this->contentType = 'text/xml';
	}
	
	public final function getStackTraceContents(Exception $exception)
	{
		$traceData = $exception->getTrace();
		
		$code = ($exception->getCode() > 0) ? $exception->getCode() : 'N/A';
		$file = ($exception->getFile() != null) ? $exception->getFile() : 'N/A';
		$line = ($exception->getLine() != null) ? $exception->getLine() : 'N/A';
		$message = ($exception->getMessage() != null) ? $exception->getMessage() : 'N/A';
		$class = (isset($traceData[0]["class"])) ? $traceData[0]["class"] : 'N/A';
		
		$trace = array();
		
		if (count($traceData) > 0)
		{
			// format the stack trace
			for($i = 0, $z = count($traceData); $i < $z; $i ++)
			{
				if (! isset($traceData[$i]['file']))
				{
					// no file key exists, skip this index
					continue;
				}
				

				
				$tFile = $traceData[$i]['file'];
				
				$filename = basename($tFile);
				$pattern = '/(.*?)\.(class|interface)\.php/i';
				$match = null;
				if (preg_match($pattern, $filename, $match))
				{
					$tClass = $match[1];
				}
				else
				{
					$tClass = null;
				}
  
				$tFunction = $traceData[$i]['function'];
				$tLine = $traceData[$i]['line'];
				
				if ($tClass != null)
				{
					$tFunction = $tClass . '::' . $tFunction . '()';
				}
				else
				{
					$tFunction = $tFunction . '()';
				}
				
				$data = 'at %s in [%s:%s]';
				$data = sprintf($data, $tFunction, $tFile, $tLine);
				
				$trace[] = $data;
			}
		}
		
		$doc = new DOMDocument('1.0', 'UTF-8');
		$exceptionxml = $doc->createElement('exception');
		$doc->appendChild($exceptionxml);
		
		$elem = $doc->createElement('message');
		$elem->appendChild($doc->createTextNode($message));
		$exceptionxml->appendChild($elem);
		
		$elem = $doc->createElement('type');
		$elem->appendChild($doc->createTextNode(get_class($exception)));
		$exceptionxml->appendChild($elem);
		
		$elem = $doc->createElement('code');
		$elem->appendChild($doc->createTextNode($code));
		$exceptionxml->appendChild($elem);
		
		$elem = $doc->createElement('class');
		$elem->appendChild($doc->createTextNode($class));
		$exceptionxml->appendChild($elem);
		
		$elem = $doc->createElement('file');
		$elem->appendChild($doc->createTextNode($file));
		$exceptionxml->appendChild($elem);
		
		$elem = $doc->createElement('line');
		$elem->appendChild($doc->createTextNode($line));
		$exceptionxml->appendChild($elem);
		
		if ($exception instanceof BaseException) 
		{
			$key = $exception->getKey();
			if (!empty($key))
			{
				$elem = $doc->createElement('key');
				$elem->appendChild($doc->createTextNode($key));
				$exceptionxml->appendChild($elem);
				$attributesArray = $exception->getAttributes();
				if (f_util_ArrayUtils::isNotEmpty($attributesArray))
				{
					$attributes = $doc->createElement('attributes');
					$exceptionxml->appendChild($attributes);
					foreach ($attributesArray as $key => $value)
					{
						$attribute = $doc->createElement('attribute');
						$attribute->setAttribute('name', strval($key));
						$attribute->appendChild($doc->createTextNode(strval($value)));
						$attributes->appendChild($attribute);
					}
				}
			}
		}
		
		if (f_util_ArrayUtils::isNotEmpty($trace))
		{
			$tracexml = $doc->createElement('trace');
			$exceptionxml->appendChild($tracexml);
			foreach ($trace as $value)
			{
				$line = $doc->createElement('line');
				$line->appendChild($doc->createTextNode(strval($value)));
				$tracexml->appendChild($line);
			}
		}
		return $doc->saveXML($exceptionxml);
	}
}