<?php
/**
 * Auto-generated doc comment
 * @package framework.mail
 */
class MailerNull extends Mailer
{
	public function __construct($params)
	{
		
	}
	
	public function getFactoryParams()
	{
		return array();
	}

	/**
	 * Send a mail with smtp driver
	 * @return mixed boolean or PearError
	 */
	public function sendMail()
	{
		return true;
	}

}

class NullMailService extends MailService
{
    
	/**
	 * @param MailMessage $mailMessage
	 * @return Mailer
	 */
	protected function buildMailer($mailMessage)
	{
		if (!Framework::isDebugEnabled())
		{
			Framework::warn("You are currently using NullMailService - this should not be the case outside of DEBUG mode");
			return;
		}
		Framework::debug(__METHOD__);
		Framework::debug("Mail sender : " . $mailMessage->getSender());
		Framework::debug("Mail receiver : " . $mailMessage->getReceiver());
		Framework::debug("Mail reply to : " . $mailMessage->getReplyTo());
		Framework::debug("Mail subject : " . $mailMessage->getSubject());
		Framework::debug("Mail Html : " . $mailMessage->getHtmlContent());
		Framework::debug("Mail Text : " . $mailMessage->getTextContent());
		return new MailerNull(array());
	}
}