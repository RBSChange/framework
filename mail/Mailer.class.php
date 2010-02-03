<?php
/**
 * @package framework.mail
 */
abstract class Mailer
{

	protected $mailDriver = null;
	protected $mimeObject = null;

	protected $factoryParams = array();
	protected $mailerParams = array();
	
	
	/**
	 * @var Array
	 */
	private $mailHeaders = array();
	
	/**
	 * @var String
	 */
	private $sender;

	/**
	 * @var String
	 */
	private $replyTo;
	
	/**
	 * @var String
	 */
	private $bcc;
	
	/**
	 * @var String
	 */
	private $cc;
	
	/**
	 * @var String
	 */
	private $subject;
	
	/**
	 * @var String
	 */
	private $receiver;
	
	/**
	 * @var String
	 */
	private $bounceBackAddress;
	
	public abstract function __construct($params);
	public abstract function sendMail();

	/**
	 *
	 * getMimeObject returns the PEAR Mail_Mime object
	 * needed for HTML mail formating.
	 *
	 * @return Object Mail_Mime object
	 *
	 */
	public function getMimeObject()
	{
		if (is_null($this->mimeObject))
		{
		    $this->mimeObject = new Mail_mime("\n");
		}
		return $this->mimeObject;
	}

	/**
	 *
	 * setEncoding set the encoding charset for the mail.
	 *
	 * @param String encoding The encoding charset
	 *
	 */
	public function setEncoding($encoding)
	{
	    $this->getMimeObject()->_build_params['html_charset'] = $encoding;
        $this->getMimeObject()->_build_params['text_charset'] = $encoding;
        $this->getMimeObject()->_build_params['head_charset'] = $encoding;
	}

	/**
	 *
	 * setHtmlAndTextBody set the HTML and Text body for the mail.
	 *
	 * THIS FEATURE REQUIRES THE USE OF THE MAIL_MIME OBJECT.
	 *
	 * THE BODY AND HEADERS GIVEN TO THE sendMail() METHOD WILL BE OVERRIDDEN.
	 *
	 * @param String htmlBody The HTML body
	 * @param String textBody The Text body (if null, the text content
	 * is built from the HTML content)
	 *
	 */
	public function setHtmlAndTextBody($htmlBody, $textBody = null)
	{
		$this->getMimeObject()->setHtmlBody($htmlBody);
		if (is_null($textBody))
		{
		    $textBody = f_util_StringUtils::htmlToText($htmlBody);
		}
		$this->getMimeObject()->setTxtBody($textBody);
	}

	/**
	 * @return String
	 */
	public function getMessage()
	{
		return $this->getMimeObject()->getMessage();
	}
	
	/**
	 *
	 * addAttachment adds an attachment to the mail.
	 *
	 * @param String attachment File path
	 *
	 */
	public function addAttachment($attachment)
	{
		$this->getMimeObject()->addAttachment($attachment);
	}

	/**
	 *
	 * GetHeaders is a function to get a header of an email
	 *
	 * @return Array A header who will send to sendMail function
	 *
	 */
	public function getHeaders()
	{
		return $this->mailHeaders;
	}
	
	/**
	 * @return String
	 */
	public function getBcc()
	{
		return $this->bcc;
	}
	
	/**
	 * @param String $bcc
	 */
	public function setBcc($bcc)
	{
		$this->setHeader('Bcc', $bcc);
		$this->bcc = $bcc;
	}
	
	/**
	 * @return String
	 */
	public function getBounceBackAddress()
	{
		return $this->bounceBackAddress;
	}
	
	/**
	 * @param String $bounceBackAddress
	 */
	public function setBounceBackAddress($bounceBackAddress)
	{
		$this->setHeader('Return-Path', $bounceBackAddress);
		$this->bounceBackAddress = $bounceBackAddress;
	}
	
	/**
	 * @return String
	 */
	public function getCc()
	{
		return $this->cc;
	}
	
	/**
	 * @param String $cc
	 */
	public function setCc($cc)
	{
		$this->setHeader('Cc', $cc);
		$this->cc = $cc;
	}
	
	/**
	 * @return String
	 */
	public function getReceiver()
	{
		return $this->receiver;
	}
	
	/**
	 * @param String $receiver
	 */
	public function setReceiver($receiver)
	{
		$this->setHeader('To', $receiver);
		$this->receiver = $receiver;
	}
	
	/**
	 * @return String
	 */
	public function getReplyTo()
	{
		return $this->replyTo;
	}
	
	/**
	 * @param String $replyTo
	 */
	public function setReplyTo($replyTo)
	{
		$this->setHeader('Reply-To', $replyTo);
		$this->replyTo = $replyTo;
	}
	
	/**
	 * @return String
	 */
	public function getSender()
	{
		return $this->sender;
	}
	
	/**
	 * @param String $sender
	 * @example $myMail->setSender($string) where $string = "myemail1@rbs.fr" or "myemail1@rbs.fr,myemail2@rbs.fr" or "my name <myemail1@rbs.fr>"
	 */
	public function setSender($sender)
	{
		$this->setHeader('From', $sender);
		$this->sender = $sender;
	}
	
	/**
	 * @return String
	 */
	public function getSubject()
	{
		return $this->subject;
	}
	
	/**
	 * @param String $subject
	 */
	public function setSubject($subject)
	{
		$this->setHeader('Subject', $subject);
		$this->subject = $subject;
	}
	
	/**
	 * Get all emails address that are used to send the email, primary receiver and cc and bcc receiver.
	 * The header is only used to display informations in mail reader
	 * @return string
	 */
	protected function getAllRecipientEmail()
	{
		$emails = $this->getReceiver();

		// Get cc emails
		if ( $this->getCc() )
		{
			$emails .= ',' . $this->getCc();
		}

		// Get bcc emails
		if ( $this->getBcc() )
		{
			$emails .= ',' . $this->getBcc();
		}

		return $emails;
	}
	
	public function setHeader($name, $value)
	{
		if ($value === null)
		{
			unset($this->mailHeaders[$name]);
		}
		else
		{
			$this->mailHeaders[$name] = $value;
		}
	}
}