<?php
/**
 * Auto-generated doc comment
 * @package framework.mail
 */
class MailerMail extends Mailer
{

	/**
	 * @param array $params
	 */
	public function __construct($params)
	{
	
		
	}

	public function sendMail($body = null, $hdrs = null)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("Mailer to : ".$this->getParam('receiver'));
		}

		if ($this->requiresMime())
		{
            $body = $this->getMimeObject()->get();
            $hdrs = $this->getMimeObject()->headers($this->getHeaders());
		}

		$mailObject =& Mail::factory($this->mailDriver);

		if (empty($hdrs))
		{
			return $mailObject->send($this->getParam('receiver'), $this->getHeaders(), $body);
		}
		else
		{
			return $mailObject->send($this->getParam('receiver'), $hdrs, $body);
		}
	}

}