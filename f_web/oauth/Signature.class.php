<?php
interface f_web_oauth_Signature
{
	public function getName();
	
	public function buildSignatureFromRequest($request);
}

class f_web_oauth_SignatureHmacSha1 implements f_web_oauth_Signature
{
	
	/**
	 * @see f_web_oauth_Signature::buildSignatureFromRequest()
	 *
	 * @param f_web_oauth_Request $request
	 */
	public function buildSignatureFromRequest($request)
	{
		$token = $request->getToken();
		$consumer = $request->getConsumer();
		return base64_encode(hash_hmac("sha1", $request->getBaseSignature(), f_web_oauth_Util::encode($consumer->getSecret()) . '&' . f_web_oauth_Util::encode($token ? $token->getSecret() : ''), true));
	}
	
	/**
	 * @see f_web_oauth_Signature::getName()
	 */
	public function getName()
	{
		return "HMAC-SHA1";
	}
}

class f_web_oauth_SignatureRsaSha1 implements f_web_oauth_Signature
{
	
	/**
	 * @see f_web_oauth_Signature::buildSignatureFromRequest()
	 *
	 * @param f_web_oauth_Request $request
	 */
	public function buildSignatureFromRequest($request)
	{
		throw new Exception("RSA-SHA1 not implemented!");
	}
	
	/**
	 * @see f_web_oauth_Signature::getName()
	 */
	public function getName()
	{
		return "RSA-SHA1";
	}
}


class f_web_oauth_SignaturePlaintext implements f_web_oauth_Signature
{
	/**
	 * @see f_web_oauth_Signature::buildSignatureFromRequest()
	 *
	 * @param f_web_oauth_Request $request
	 */
	public function buildSignatureFromRequest($request)
	{
		return $request->getBaseSignature();
	}
	
	/**
	 * @see f_web_oauth_Signature::getName()
	 */
	public function getName()
	{
		return "PLAINTEXT";
	}
}