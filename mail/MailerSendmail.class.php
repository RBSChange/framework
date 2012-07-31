<?php
/**
 * Auto-generated doc comment
 * @package framework.mail
 */
/**
 * @deprecated
 */
class MailerSendmail extends Mailer
{
	
	public function __construct($params)
	{
		// Set mail driver
		$this->mailDriver = strtolower($params['type']);
		if (isset($params['sendmail_path']))
		{
			$this->factoryParams['sendmail_path'] = $params['sendmail_path'];
		}
		if (isset($params['sendmail_args']))
		{
			$this->factoryParams['sendmail_args'] = $params['sendmail_args'];
		}		
	}

	public function getFactoryParams()
	{
		return $this->factoryParams;
	}
		
	
	/**
	 * Send a mail with smtp driver
	 * @return mixed boolean or PearError
	 */
	public function sendMail()
	{
		Framework::info(__METHOD__." to : ".$this->getReceiver());

		$body = $this->getMimeObject()->get();
		$hdrs = $this->getMimeObject()->headers($this->getHeaders());

		$mailObject = Mail::factory('sendmail', $this->getFactoryParams());

		return $mailObject->send($this->getAllRecipientEmail(), $hdrs, $body);
	}	
}