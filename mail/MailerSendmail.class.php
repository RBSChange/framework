<?php
/**
 * Auto-generated doc comment
 * @package framework.mail
 */

class MailerSendmail extends Mailer
{
	/**
	 * TODO intramsj : This class must be recoded in order to match
	 * fully with the agavi thought. On another hand it must integrate
	 * the mime mail concept
	 */

	protected $mailDriver = "sendmail";
	protected $mailerParams = array();
	protected $factoryParams = array();

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	public function initialize($params)
	{
		$this->mailerParams['sender'] = $params['sender'];
		$this->mailerParams['replyTo'] = $params['replyTo'];
		$this->factoryParams['sendmail_path'] = $params['sendmail_path'];
		$this->factoryParams['sendmail_args'] = $params['sendmail_args'];
	}

	public function getParams()
	{
		return $this->factoryParams;
	}

	/*
	public function sendMail($receiver = null, $body= null) {
		$mailObject =& Mail::factory($this->mailDriver, $this->getParams());
		return $mailObject->send($receiver, $this->getHeaders(), $body);
	}
	*/

    public function sendMail($body = null, $hdrs = null)
    {
		$mailObject =& Mail::factory($this->mailDriver, $this->getParams());

		if ($this->requiresMime())
		{
            $body = $this->getMimeObject()->get();
            $hdrs = $this->getMimeObject()->headers($this->getHeaders());
		}

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

?>