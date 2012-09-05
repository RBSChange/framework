<?php
class f_mail_MIMEObject
{
	
	private $headerArray = array();
	private $content;
	
	public function __construct($contentString)
	{
		$this->headerArray = $this->extractHeaders(trim($contentString));
	}
	
	/**
	 * @param String $headerName
	 * @return Boolean
	 */
	public function hasHeader($headerName)
	{
		return isset($this->headerArray[strtolower($headerName)]);
	}
	
	/**
	 * @param String $headerName
	 * @return String
	 */
	public function getHeader($headerName)
	{
		if (!$this->hasHeader($headerName))
		{
			return null;
		}
		return $this->headerArray[strtolower($headerName)];
	}
	
	private function getHeaderEndOffset($textContent)
	{
		return strlen($textContent);
	}
	
	/**
	 * @param String $textContent
	 * @return Array
	 */
	private function extractHeaders($textContent)
	{
		$headers = array();
		$parsedHeaders = array();
		preg_match('/^[\s]*$/', $textContent, $matches, PREG_OFFSET_CAPTURE);
		preg_match_all('/[a-zA-Z\-_]+:[ ].*[\n]/', $textContent, $headers, PREG_OFFSET_CAPTURE );
		$lastHeaderEndOffset = 0;
		$headerEndOffset = $this->getHeaderEndOffset($textContent);
		foreach ($headers[0] as $rawHeader)
		{
			$headerName = trim(substr($rawHeader[0], 0, strpos($rawHeader[0], ':')));
			$headerValue = trim(substr($rawHeader[0], strpos($rawHeader[0], ':') + 1));
			if ($rawHeader[1] < $headerEndOffset)
			{
				$parsedHeaders[strtolower($headerName)] = $headerValue;
				$lastHeaderEndOffset = $rawHeader[1] + strlen($rawHeader[0]);
			}
		}		
		$this->content = trim(substr($textContent, $lastHeaderEndOffset));
		return $parsedHeaders;
	}
	
	/**
	 * @return String
	 */
	public function getContent()
	{
		switch ($this->getHeader('content-transfer-encoding'))
		{
			case 'base64':
				return base64_decode($this->content);
			case 'quoted-printable':
				return quoted_printable_decode($this->content);
			default:
				return $this->content;
		}
	}
}
/**
 * @deprecated
 */
class PopMailMessage extends f_mail_MIMEObject
{
	/**
	 * @var String
	 */
	private $error;
	
	/**
	 * @var String
	 */
	private $rawBody;
	
	/**
	 * @var String
	 */
	private $rawHeader;
	
	/**
	 * @var f_mail_MIMEObject
	 */
	private $header;
	
	/**
	 * @var f_mail_MIMEObject[]
	 */
	private $messageParts = array();
	
	private $errorRegularExpression;
	
	private $errorMatches = array();
	
	
	/**
	 * @param String $body
	 * @param String $header
	 */
	public function __construct($body, $header)
	{
		$this->rawHeader = $header;
		$this->header = new f_mail_MIMEObject($this->rawHeader);
		$this->rawBody = $body;
		$this->extractParts();
	}
	
	/**
	 * @return Boolean
	 */
	private function isMultipartMessage()
	{
		return strpos($this->header->getHeader('Content-Type'), 'multipart') === 0;
	}
	
	/**
	 */
	private function extractParts()
	{
		if (!$this->isMultipartMessage())
		{
			$this->messageParts[] = new f_mail_MIMEObject($this->rawBody);
			return;
		}
		
		$matches = array();
		preg_match('/boundary="([^"]+)"/', $this->rawHeader, $matches);
		if (count($matches) < 2)
		{
			throw new Exception("Multipart message has no part");
		}
		
		$mimePartSeparator = '--' . $matches[1];
		$mimePartSeparatorLength = strlen($mimePartSeparator);
		$mimePartsOffsets = array();
		$mimeParts = array();
		$partIndex = -1;
		while (($partIndex = strpos($this->rawBody, $mimePartSeparator, $partIndex + 1)) !== false)
		{
			$mimePartsOffsets[] = $partIndex + strlen($mimePartSeparator);
		}
		
		$partCount = count($mimePartsOffsets);
		for ($i = 0; $i < $partCount; $i++)
		{
			if (isset($mimePartsOffsets[$i + 1]))
			{
				$mimePartContent = substr($this->rawBody, $mimePartsOffsets[$i], $mimePartsOffsets[$i + 1] - $mimePartsOffsets[$i] - $mimePartSeparatorLength);
			}
			else
			{
				$mimePartContent = substr($this->rawBody, $mimePartsOffsets[$i]);
			}
			$this->messageParts[] = new f_mail_MIMEObject($mimePartContent);
		}
	}
	
	/**
	 * @return unknown
	 */
	public function getErrorMatches()
	{
		return $this->errorMatches;
	}
	
	/**
	 * @return unknown
	 */
	public function getErrorRegularExpression()
	{
		return $this->errorRegularExpression;
	}
	
	/**
	 * @param String $errorRegularExpression
	 */
	public function setErrorRegularExpression($errorRegularExpression)
	{
		$this->errorRegularExpression = $errorRegularExpression;
	}
	
	/**
	 * @param f_mail_MIMEObject $mimeObject
	 * @return String
	 */
	private function hasErrorUrlInContent($mimeObject)
	{
		if ($this->errorRegularExpression === null)
		{
			return false;
		}
		
		$contentType = $mimeObject->getHeader('content-type');
		if ($contentType == 'message/rfc822')
		{
			$subMessage = new PopMailMessage($mimeObject->getContent());
			$subMessage->setErrorRegularExpression($this->errorRegularExpression);
			return $subMessage->isError();
		}
		else if ($contentType == "text/plain" || $contentType == "text/html")
		{
			return preg_match($this->errorRegularExpression, $mimeObject->getContent(), $this->errorMatches) !== 0;
		}
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public function isError()
	{
		if ($this->error === null)
		{
			$this->error = false;
			foreach ($this->messageParts as $part)
			{
				if ($this->hasErrorUrlInContent($part))
				{
					$this->error = true;
					break;
				}
			}
		}
		return $this->error;
	}
}
