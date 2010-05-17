<?php
class f_web_oauth_HTTPClient
{
	/**
	 * @var HTTPClient
	 */
	private $mBackend;
	
	/**
	 * 	@var f_web_oauth_Request
	 */
	private $mOauthRequest;
	
	/**
	 */
	public function __construct(f_web_oauth_Request $request)
	{
		$this->mOauthRequest = $request;
		$this->mBackend = HTTPClientService::getNewHTTPClient();
		$this->mBackend->setTimeOut(5);
	}
	
	/**
	 * @return String
	 */
	public function execute($headers = true)
	{
		$this->mOauthRequest->sign();
		if ($headers)
		{
			$this->mBackend->setOption(CURLOPT_HTTPHEADER, array($this->mOauthRequest->getAuthorizationHeader()));
		}
		if ($this->mOauthRequest->getMethod() == f_web_oauth_Request::METHOD_POST)
		{
			return $this->mBackend->post($this->mOauthRequest->getUrl(), $this->mOauthRequest->getPostParameters());
		}
		
		return $this->mBackend->get($this->mOauthRequest->getUrl());
	}
	
	/**
	 * @return Integer
	 */
	public function getHTTPReturnCode()
	{
		return $this->mBackend->getHTTPReturnCode();
	}
	
	
	/**
	 * @return HTTPClient
	 */
	public function getBackendClientInstance()
	{
		return $this->mBackend;
	}
}