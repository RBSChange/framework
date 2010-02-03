<?php
/**
 * A basic odtphp2PDFClient, 
 * odtphp2pdf: OpenOffice documents to PDF. https://sourceforge.net/projects/odtphp2pdf/
 */
class Odtphp2PDFClient
{
	/**
	 * @var String
	 */
	private $serviceUrl;

	/**
	 * @param String $serviceUrl URL of an odtphp2pdf installation
	 */
	function __construct($serviceUrl)
	{
		$this->serviceUrl = $serviceUrl;
	}

	/**
	 * @param String $template
	 * @param array $model
	 * @return String the pdf content
	 * @throws Exception
	 */
	function getPdf($template, $model)
	{
		$ch = curl_init();
		$postData = array('newTemplate' => '@'.realpath($template));
		foreach ($model as $key => $value)
		{
			$this->toPostData($value, "model[".$key."]", $postData);
		}
		curl_setopt($ch, CURLOPT_URL, $this->serviceUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		
		if (($result = curl_exec($ch)) === false)
		{
			if(curl_errno($ch))
			{
				throw new Exception("Unable to get PDF : ".curl_error($ch));
			}
			
		}
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($info["content_type"] != "application/pdf" || $info["http_code"] != "200")
		{
			throw new Exception("Unable to get PDF from server: ".$result);
		}
		return $result;
	}
	
	// private content

	/**
	 * @param array|String $arrayOrString
	 * @param String $currentKey
	 * @param array $postData
	 */
	private function toPostData($arrayOrString, $currentKey, &$postData)
	{
		if (is_array($arrayOrString))
		{
			foreach ($arrayOrString as $key => $value)
			{
				$this->toPostData($value, $currentKey."[".$key."]", $postData);
			}
		}
		else
		{
			$postData[$currentKey] = $arrayOrString;
		}
	}
}