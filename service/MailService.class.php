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
	 * @var Zend_Mail_Transport_Abstract 
	 */
	private $mta;

	/**
	 * @return Zend_Mail 
	 */
	public function getNewMessage()
	{
		return new Zend_Mail('UTF-8');
	}
	
	/**
	 * @param Zend_Mail $message 
	 */
	public function send($message, $moduleName = null)
	{
		try
		{
			if ($this->mta === null)
			{
				$config = Framework::getConfiguration('mail');
				if (defined('FAKE_EMAIL') && f_util_StringUtils::isEmpty(FAKE_EMAIL))
				{
					$config['type'] = "File";
				}
				switch (strtolower($config['type']))
				{
					case 'smtp':
						$this->mta = new Zend_Mail_Transport_Smtp($config['host'], $config);
						break;
					default:
						$mailPath = f_util_FileUtils::buildProjectPath('mailbox', 'outgoing');
						f_util_FileUtils::mkdir($mailPath);
						$this->mta = new Zend_Mail_Transport_File(array('path' => $mailPath));
						break;
				}
			}
			if (defined('FAKE_EMAIL'))
			{
				$cloneMessage = clone $message;
				/* @var $cloneMessage Zend_Mail */
				$cloneMessage->clearRecipients();
				$cloneMessage->clearSubject();
				$cloneMessage->setSubject("[FAKE] " . $message->getSubject() . ' [' . f_util_ArrayUtils::firstElement($message->getRecipients()) . ']');
				$cloneMessage->addTo(FAKE_EMAIL);
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