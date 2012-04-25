<?php
/**
 * @deprecated
 */
class f_web_oauth_Service extends BaseService 
{
	/**
	 * @var f_web_oauth_Service
	 */
	private static $instance;

	/**
	 * @return f_web_oauth_Service
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * This method returns a new Unauthorized token that will be used for the first leg of
	 * the oauth authentification scheme.
	 *
	 * @param f_web_oauth_Consumer $consumer
	 * @return f_web_oauth_Token
	 */
	public function getNewToken($consumer)
	{
		return new f_web_oauth_Token($consumer->getKey(), $consumer->getSecret());
	}
	
	/**
	 * This method authorizes a token obtained by calling getNewToken.
	 *
	 * @param f_web_oauth_Token $token (with verification code set in case of success)
	 * @param f_web_oauth_Consumer $consumer
	 * 
	 * @return Boolean
	 */
	public function authorizeToken($token)
	{
		return false;
	}
	
	/**
	 * @param f_web_oauth_Token $token
	 * @param f_web_oauth_Consumer $consumer
	 * 
	 * @return f_web_oauth_Token or null
	 */
	public function getAccessToken($token, $consumer)
	{
		return null;
	}
	
	/**
	 * @param Integer $timestamp
	 * @param f_web_oauth_Token $token
	 * @param f_web_oauth_Consumer $consumer
	 * @return unknown
	 */
	public function validateTimestamp($timestamp, $token = null, $consumer = null)
	{
		return false;
	}
	
	/**
	 * @param f_web_oauth_Consumer $consumer
	 * @return String
	 */
	public function getConsumerSecret($consumer)
	{
		$consumer->setSecret(PROJECT_ID);
		return $consumer->getSecret();
	}
	
	
	/**
	 * @param f_web_oauth_Consumer $consumer
	 * @return String
	 */
	public function getTokenSecret($tken)
	{
		$tken->setSecret('');
		return $tken->getSecret();
	}
	
}