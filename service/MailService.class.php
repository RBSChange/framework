<?php
/**
 * @method change_MailService getInstance()
 */
class change_MailService extends change_BaseService
{
	const TO = 'to';
	const CC = 'cc';
	const BCC = 'bcc';
	
	/**
	 * @var \Zend\Mail\Transport\TransportInterface 
	 */
	private $mta;

	/**
	 * @return \Zend\Mail\Message 
	 */
	public function getNewMessage()
	{
		$message = new \Zend\Mail\Message();
		return $message->setEncoding('UTF-8');
	}
	
	/**
	 * @param \Zend\Mail\Message  $message 
	 */
	public function send($message, $moduleName = null)
	{
		try
		{
			if ($this->mta === null)
			{
				$config = Framework::getConfiguration('mail');
				switch (strtolower($config['type']))
				{
					case 'smtp':
						$options   = new \Zend\Mail\Transport\SmtpOptions();
						$options->setHost($config['host']);
						$options->setPort($config['port']);
						if (isset($config['username']) && f_util_StringUtils::isNotEmpty($config['username']))
						{
							$options->setConnectionClass('login');
							$options->setConnectionConfig(array(
								'username' => $config['username'],
								'password' => $config['password'],
							));
						}
						$this->mta = new \Zend\Mail\Transport\Smtp($options);
						break;
					case 'sendmail':
						// TODO : check sendmail config
						$this->mta =  new \Zend\Mail\Transport\Sendmail();
						break;
					default:
						$options = new \Zend\Mail\Transport\FileOptions();
						$mailPath = f_util_FileUtils::buildProjectPath('mailbox', 'outgoing');
						f_util_FileUtils::mkdir($mailPath);
						$options->setPath($mailPath);
						$this->mta = new \Zend\Mail\Transport\File($options);
						break;
				}
			}
			if (defined('FAKE_EMAIL'))
			{
				$cloneMessage = clone $message;
				/* @var $cloneMessage \Zend\Mail\Message */
				$originalSubject = $message->getHeaders()->get('subject');
				if ($originalSubject instanceof \Zend\Mail\Header\Subject)
				{
					$originalSubject->setSubject("[FAKE] " . $originalSubject->getFieldValue(). ' [' . $message->getTo()->current()->toString() . ']');
				}
				$cloneMessage->setTo(FAKE_EMAIL);
				$this->mta->send($cloneMessage);
			}
			else
			{
				$this->mta->send($message);
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
		return true;
	}
	
	/**
	 *
	 * @param type $to
	 * @param type $cc
	 * @param type $bcc 
	 * @return array
	 */
	public function getRecipientsArray($to, $cc = null, $bcc = null)
	{
		$result = array(self::TO => array(), self::CC => array(), self::BCC => array());
		if (is_array($to))
		{
			$result[self::TO] = $to;
		}
		if (is_array($cc))
		{
			$result[self::CC] = $cc;
		}
		if (is_array($bcc))
		{
			$result[self::BCC] = $bcc;
		}
		return $result;
	}
}