<?php
/**
 * @package framework.mail
 */
/**
 * @deprecated
 */
class MailMessage
{

	/**
	 * Email(s) of sender
	 * @var String
	 */
	private $sender = null;
	/**
	 * Email(s) for the reply to
	 * @var String
	 */
	private $replyTo = null;
	/**
	 * Email(s) of blind carbon copy
	 * @var String
	 */
	private $bcc = null;
	/**
	 * Email(s) of carbon copy
	 * @var String
	 */
	private $cc = null;
	/**
	 * Subject of message
	 * @var String
	 */
	private $subject = null;
	/**
	 * Email(s) of receiver
	 * @var String
	 */
	private $receiver = null;
	/**
	 * Encoding of content
	 * @var String
	 */
	private $encoding = 'utf-8';
	
	/**
	 * Html content
	 * @var String
	 */
	private $html = null;
	
	/**
	 * Text content
	 * @var String
	 */
	private $text = null;
	
	/**
	 * Module name of the module that construct the message
	 * @var String
	 */
	private $moduleName = null;
	/**
	 * Array of path for file attachment
	 * @var array
	 */
	private $attachment = array();

	private $source;
	
	private $notificationTo;

	/**
	 * @param string $sender
	 * @return MailMessage
	 */
	public function setSender($sender)
	{
		$this->sender = $sender;
		return $this;
	}


	/**
	 * @return String
	 */
	public function getSender()
	{
		return $this->sender;
	}


	/**
	 * @param string $replyTo
	 * @return MailMessage
	 */
	public function setReplyTo($replyTo)
	{
		$this->replyTo = $replyTo;
		return $this;
	}


	/**
	 * @return String
	 */
	public function getReplyTo()
	{
		return $this->replyTo;
	}


	/**
	 * @param string $bcc
	 * @return MailMessage
	 */
	public function setBcc($bcc)
	{
		$this->bcc = $bcc;
		return $this;
	}


	/**
	 * @return String
	 */
	public function getBcc()
	{
		return $this->bcc;
	}


	/**
	 * @param string $cc
	 * @return MailMessage
	 */
	public function setCc($cc)
	{
		$this->cc = $cc;
		return $this;
	}


	/**
	 * @return String
	 */
	public function getCc()
	{
		return $this->cc;
	}


	/**
	 * @param string $subject
	 * @return MailMessage
	 */
	public function setSubject($subject)
	{
		$this->subject = $subject;
		return $this;
	}


	/**
	 * @return String
	 */
	public function getSubject()
	{
		return $this->subject;
	}


	/**
	 * @param string $receiver
	 * @return MailMessage
	 */
	public function setReceiver($receiver)
	{
		$this->receiver = $receiver;
		return $this;
	}


	/**
	 * @return String
	 */
	public function getReceiver()
	{
		return $this->receiver;
	}


	/**
	 * @param string $attachment
	 * @return MailMessage
	 */
	public function addAttachment($attachment)
	{
		$this->attachment[] = $attachment;
		return $this;
	}


	/**
	 * @return array
	 */
	public function getAttachment()
	{
		return $this->attachment;
	}


	/**
	 * @param string $encoding
	 * @return MailMessage
	 */
	public function setEncoding($encoding)
	{
		$this->encoding = $encoding;
		return $this;
	}


	/**
	 * @return String
	 */
	public function getEncoding()
	{
		return $this->encoding;
	}


	/**
	 * @param string $receiver
	 * @return MailMessage
	 */
	public function setModuleName($moduleName)
	{
		$this->moduleName = $moduleName;
		return $this;
	}


	/**
	 * @return String
	 */
	public function getModuleName()
	{
		return $this->moduleName;
	}


	/**
	 * @param string $htmlBody
	 * @param string $textBody
	 * @return MailMessage
	 */
	public function setHtmlAndTextBody($htmlBody, $textBody = null)
	{
		$this->html = $htmlBody;
		$this->text = $textBody;
		return $this;
	}


	/**
	 * @return String
	 */
	public function getHtmlContent()
	{
		return f_util_StringUtils::addCrLfToHtml($this->html);
	}


	/**
	 * @return String
	 */
	public function getTextContent()
	{
		return $this->text;
	}
	
	private $bounceBackAddress;
	
	public function setBounceBackAddress($address)
	{
		$this->bounceBackAddress = $address;
	}
	
	public function getBounceBackAddress()
	{
		return $this->bounceBackAddress;
	}

	/**
	 * Send the mail and return true if ok or and PearError
	 * @return mixed boolean or PearError
	 */
	public function send()
	{
		return MailService::getInstance()->send($this);
	}

	/**
	 * @param mail_MessageRecipients $recipients
	 *
	 * @throws IllegalArgumentException If $recipients is not a mail_MessageRecipients instance.
	 */
	public function setRecipients($recipients)
	{
		if ($recipients->hasTo())
		{
			$this->setReceiver(implode(',', $recipients->getTo()));
		}	
		if ($recipients->hasCC())
		{
			$this->setCc(implode(',', $recipients->getCC()));
		}
		if ($recipients->hasBCC())
		{
			$this->setBcc(implode(',', $recipients->getBCC()));
		}
	}
	
	/**
	 * @return MailSource
	 */
	public function getSource()
	{
		return $this->source;
	}
	
	/**
	 * @param MailSource $source
	 */
	public function setSource($source)
	{
		$this->source = $source;
	}
	
	/**
	 * @return Boolean
	 */
	public function hasSource()
	{
		return $this->source !== null;
	}
	
	/**
	 * @return String
	 */
	public function getNotificationTo()
	{
		return $this->notificationTo;
	}
	
	/**
	 * @param String $notificationTo
	 */
	public function setNotificationTo($notificationTo)
	{
		$this->notificationTo = $notificationTo;
	}
	
	public function hasNotificationTo()
	{
		return $this->notificationTo !== null;
	}

}
