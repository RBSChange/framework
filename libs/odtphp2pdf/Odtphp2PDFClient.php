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
		$postData = $this->buildPostData($template, $model);
		$info = null;
		$result = $this->postRequest($postData, $info);
		if ($info["content_type"] != "application/pdf")
		{
			throw new Exception("Unable to get PDF from server: ".$result);
		}
		return $result;
	}

	/**
	 * @param String $template
	 * @param array $model
	 * @return String the modified ODT content
	 * @throws Exception
	 */
	function getOdt($template, $model)
	{
		$postData = $this->buildPostData($template, $model);
		$postData["_ODT_ONLY_"] = "true";
		$info = null;
		$result = $this->postRequest($postData, $info);
		if ($info["content_type"] != "application/vnd.oasis.opendocument.text")
		{
			//throw new Exception("Unable to get ODT from server: ".$result);
		}
		return $result;
	}

	// private content

	private function postRequest($postData, &$info)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->serviceUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		if (($result = curl_exec($ch)) !== false)
		{
			$info = curl_getinfo($ch);
		}
		else
		{
			if (curl_errno($ch))
			{
				throw new Exception("Unable to get PDF : ".curl_error($ch));
			}
		}
		curl_close($ch);
		if ($info["http_code"] != "200")
		{
			throw new Exception("Unable to get content from server: ".var_export($result, true));
		}
		return $result;
	}

	private function buildPostData($template, $model)
	{
		$postData = array('newTemplate' => '@'.realpath($template));
		foreach ($model as $key => $value)
		{
			$this->toPostData($value, "model[".$key."]", $postData);
		}
		
		return $postData;
	}
	
	private $pictureIndex = 0;
	
	private function isImage($key)
	{
		return substr($key, -5) == "PICT]";
	}

	/**
	 * @param array|String $arrayOrString
	 * @param String $currentKey
	 * @param array $postData
	 */
	private function toPostData($arrayOrString, $currentKey, &$postData)
	{
		if (is_array($arrayOrString))
		{
			if (count($arrayOrString) == 0)
			{
				$postData[$currentKey."[".$key."]"] = '__ODTPHP2PDT_EMPTY_ARRAY__';
			}
			else
			{
				foreach ($arrayOrString as $key => $value)
				{
					$this->toPostData($value, $currentKey."[".$key."]", $postData);
				}
			}
		}
		elseif ($this->isImage($currentKey))
		{
			$pictureName = "__ODTPHP2PDFPICTURE".$this->pictureIndex."__";
			$this->pictureIndex++;
			$postData[$pictureName] = "@".realpath($arrayOrString);
			$postData[$currentKey] = $pictureName;
		}
		else
		{
			$postData[$currentKey] = $arrayOrString;
		}
	}
}