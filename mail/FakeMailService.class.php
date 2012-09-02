<?php
/**
 * Auto-generated doc comment
 * @package framework.mail
 */
/**
 * @deprecated
 */
class FakeMailService extends MailService
{
    
	/**
	 * @param MailMessage $mailMessage
	 * @return Mailer
	 */
	protected function buildMailer($mailMessage)
	{
	    if (!defined('FAKE_EMAIL'))
	    {
	        throw new Exception('The constant FAKE_EMAIL is not defined');
	    }
	    
		$mailer = parent::buildMailer($mailMessage);
		$receiver = $mailer->getReceiver();
		$mailer->setReceiver(FAKE_EMAIL);
		$mailer->setBcc('');
		$mailer->setCc('');
		$mailer->setSubject("[Fake] ".$mailMessage->getSubject() . " [$receiver]");
		if ($mailMessage->hasNotificationTo())
		{
			$mailer->setHeader('Disposition-Notification-To', FAKE_EMAIL);
		}
		return $mailer;
	}
}