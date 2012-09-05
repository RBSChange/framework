<?php
/**
 * @package framework.mail
 */
class MailService extends BaseService
{

	const CHANGE_SOURCE_ID_HEADER = 'X-Change-Source-Id';
	/**
	 * the singleton instance
	 * @var MailService
	 */
	private static $instance = null;

	/**
	 * @return MailService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			$finalClass = Injection::getFinalClassName(get_class());
			if (defined("FAKE_EMAIL") && $finalClass == "MailService")
			{
				$finalClass = "FakeMailService"; 
			}
			self::$instance = new $finalClass();
		}
		return self::$instance;
	}

	/**
	 * Get a new mail message.
	 * @return MailMessage
	 */
	public function getNewMailMessage()
	{
		return new MailMessage();
	}

	/**
	 * Send the mail and return true if ok or and PearError
	 *
	 * @param MailMessage $mailMessage
	 * @return mixed boolean or PearError
	 */
	public function send($mailMessage)
	{
		$this->mailer = null;
		return $this->buildMailer($mailMessage)->sendMail();
	}

	/**
	 * Send the mail to the given receiver and return true if ok or and PearError
	 *
	 * @param MailMessage $mailMessage
	 * @param String $receiver
	 * @return mixed boolean or PearError
	 */
	public function sendTo($mailMessage, $receiver)
	{
		$mailMessage->setReceiver($receiver);
		return $this->send($mailMessage);
	}
	/**
	 * @var Mailer
	 */
	private $mailer;

	/**
	 * @return Mailer
	 */
	public function getMailer()
	{
		if ($this->mailer !== null)
		{
			return $this->mailer;
		}
		// Load configuration of mailer
		$mailConfiguration = Framework::getConfiguration('mail');

		// Instance the mailer
		$className = "Mailer" . ucfirst( strtolower( $mailConfiguration['type'] ) );
		$class = new ReflectionClass($className);
		$mailer = $class->newInstance($mailConfiguration);
		$this->mailer = $mailer;
		
		return $mailer;
	}
	
	/**
	 * @param MailMessage $mailMessage
	 * @return Mailer
	 */
	protected function buildMailer($mailMessage)
	{
		$mailer = $this->getMailer();
		
		// Pass the mailMessage to the mailer
		$mailer->setSender($mailMessage->getSender());
		$mailer->setReceiver($mailMessage->getReceiver());
		$mailer->setBcc($mailMessage->getBcc());
		$mailer->setCc($mailMessage->getCc());
		$mailer->setEncoding($mailMessage->getEncoding());
		$mailer->setHtmlAndTextBody($mailMessage->getHtmlContent(), $mailMessage->getTextContent());
		$mailer->setReplyTo($mailMessage->getReplyTo());
		$mailer->setSubject($mailMessage->getSubject());
		$mailer->setBounceBackAddress($mailMessage->getBounceBackAddress());
		
		// Accusé de reception
		if ($mailMessage->hasNotificationTo())
		{
			$mailer->setHeader('Disposition-Notification-To', $mailMessage->getNotificationTo());
		}
		$attachmentInfos = $mailMessage->getAttachmentInfos();
		foreach ($mailMessage->getAttachment() as $idx => $attachment)
		{
			$mailer->addAttachment($attachment, $attachmentInfos[$idx][0], $attachmentInfos[$idx][1]);
		}
		return $mailer;
	}
	
}