<?php
/**
 * Auto-generated doc comment
 * @package framework.mail
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
	    
		$mailer = $this->getMailer();
		// Pass the mailMessage to the mailer
		$mailer->setSender($mailMessage->getSender());
		$receiver = $mailMessage->getReceiver();
		$mailer->setReceiver(FAKE_EMAIL);
		$mailer->setBcc('');
		$mailer->setCc('');
		$mailer->setEncoding($mailMessage->getEncoding());
		$mailer->setHtmlAndTextBody($mailMessage->getHtmlContent(), $mailMessage->getTextContent());
		$mailer->setReplyTo($mailMessage->getReplyTo());
		$mailer->setSubject("[Fake] ".$mailMessage->getSubject() . " [$receiver]");
		
		if ($mailMessage->hasNotificationTo())
		{
			$mailer->setHeader('Disposition-Notification-To', FAKE_EMAIL);
		}
		
		foreach ($mailMessage->getAttachment() as $attachement)
		{
			$mailer->addAttachment($attachement);
		}	
		return $mailer;
	}
}