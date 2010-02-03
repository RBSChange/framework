<?php
class MassMailer
{
	const FILE_LOCK_EXTENSION = '.lock';
	
	/**
	 * @var MassMailer
	 */
	private static $instance;
	
	/**
	 * @return MassMailer
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new MassMailer();
		}
		return self::$instance;
	}
	
	/**
	 * @param MailMessage $mailMessage
	 */
	public function send($mailMessage)
	{
		try
		{
			if (!($mailMessage instanceof MailMessage))
			{
				throw new Exception('$mailMessage must be an instance of mail message');
			}
			$mailMessage->setBounceBackAddress('testemailing.change@rbs.fr');
			$compressedContent = gzcompress(serialize($mailMessage));
			if ($mailMessage->hasSource())
			{
				$outboxPath = $this->getOutboxPathForSource($mailMessage->getSource());
			}
			else
			{
				$outboxPath = $this->getOutboxPath();
			}
			$basePath = $this->getTempFileOrDirName($outboxPath . DIRECTORY_SEPARATOR . $this->getSubdirectoriesForContent($compressedContent), 'mail');
			f_util_FileUtils::writeAndCreateContainer($basePath, $compressedContent);
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}
	
	/**
	 * @param Mixed $source
	 */
	public function cancelForSource($source)
	{
		$this->cleanForSource($source);
	}
	
	public function cleanForSource($source)
	{
		if (!is_dir($this->getOutboxPathForSource($source)))
		{
			return;
		}
		@rename($this->getOutboxPathForSource($source), $this->getTrashPathForSource($source));
	}
	
	/**
	 * @param array $messagePaths
	 * @param string $batchPath
	 */
	public function batchSend($messagePaths, $batchPath)
	{
		if (f_util_ArrayUtils::isEmpty($messagePaths) == 1)
		{
			$pathsToProcess = self::getInstance()->getMessagesToSend();
			foreach (array_chunk($pathsToProcess, 500) as $messagePathArray)
			{
				$processHandle = popen('php ' . $batchPath . ' ' . implode(' ', $messagePathArray), 'r');
				while ($string = fread($processHandle, 1024))
				{
					echo $string;
				}
				pclose($processHandle);
			}
		}
		else
		{
			foreach ($messagePaths as $mailMessagePath)
			{
				try
				{
					$mailMessage = unserialize(gzuncompress(file_get_contents($mailMessagePath)));
					$sourceId = $mailMessage->getSource();
					if ($mailMessage instanceof MailMessage)
					{
						$returnValue = MailService::getInstance()->send($mailMessage);
						if ($returnValue === true)
						{
							$this->successLog($mailMessage->getSender(), $mailMessage->getReceiver(), 'send', $sourceId);
							f_event_EventManager::dispatchEvent('sendMailSuccess', $this, array('message' => $mailMessage));
						}
						else
						{
							$this->errorLog($mailMessage->getSender(), $mailMessage->getReceiver(), 'send', $sourceId);
							f_event_EventManager::dispatchEvent('sendMailFailed', $this, array('message' => $mailMessage));
						}
					}
					@unlink($mailMessagePath);
				}
				catch (Exception $e)
				{
					Framework::exception($e);
				}
			}
		}
	}
		
	protected function getMessagesToSend()
	{
		$pathsToProcess = array();
		$directoryIterator = new RecursiveDirectoryIterator($this->getOutboxPath());
		foreach (new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST) as $mailMessageFile)
		{
			if ($mailMessageFile->isFile())
			{
				$fileName = $mailMessageFile->getFilename();
				$filePath = $mailMessageFile->getPath() . DIRECTORY_SEPARATOR . $fileName;
				if (stripos($fileName, self::FILE_LOCK_EXTENSION) === false)
				{
					@rename($filePath, $filePath . self::FILE_LOCK_EXTENSION);
					$pathsToProcess[] = $filePath . self::FILE_LOCK_EXTENSION;
				}
			}
		}
		return $pathsToProcess;
	}
	
	protected function successLog($sender, $receiver, $sourceId = null, $action = null)
	{
		if ($action === null)
		{
			$action = "not-specified";
		}
		
		if ($sourceId === null)
		{
			$sourceId = "not-specified";
		}
		$messageString = date_Calendar::now()->toString() . "\t" . $action . "\t" . $sender . "\t" . $receiver . "\t" . $sourceId;
		LoggerManager::getLogger('mailerSuccess')->log(new Message($messageString));
	}
	
	protected function errorLog($sender, $receiver, $sourceId = null, $action = null)
	{
		if ($action === null)
		{
			$action = "not-specified";
		}
		
		if ($sourceId === null)
		{
			$sourceId = "not-specified";
		}
		$messageString = date_Calendar::now()->toString() . "\t" . $action . "\t" . $sender . "\t" . $receiver . "\t" . $sourceId;
		LoggerManager::getLogger('mailerError')->log(new Message($messageString));
	}
	
	/**
	 * @param String $baseDir
	 * @param String $prefix
	 * @return String
	 */
	private function getTempFileOrDirName($baseDir, $prefix)
	{
		$name = f_util_StringUtils::randomString();
		while (file_exists($baseDir . DIRECTORY_SEPARATOR . $prefix . $name))
		{
			$name = f_util_StringUtils::randomString();
		}
		return $baseDir . DIRECTORY_SEPARATOR . $prefix . $name;
	}
	
	/**
	 * @return String
	 */
	private function getMailboxPath()
	{
		return f_util_FileUtils::buildWebeditPath('mailbox');
	}
	
	/**
	 * @return String
	 */
	private function getLogsPath()
	{
		return f_util_FileUtils::buildWebeditPath('mailbox', 'logs');
	}
	
	/**
	 * @return String
	 */
	private function getOutboxPath()
	{
		return f_util_FileUtils::buildWebeditPath('mailbox', 'outbox');
	}
	
	/**
	 * @param Mixed $source
	 * @return String
	 */
	private function getOutboxPathForSource($source)
	{
		return $this->getOutboxPath() . DIRECTORY_SEPARATOR . strval($source);
	}
	
	/**
	 * @return String
	 */
	private function getTrashPath()
	{
		return f_util_FileUtils::buildWebeditPath('mailbox', 'trash');
	}
	
	/**
	 * @param Mixed $source
	 * @return String
	 */
	private function getTrashPathForSource($source)
	{
		return $this->getTrashPath() . DIRECTORY_SEPARATOR . strval($source);
	}
	
	protected function __construct()
	{
		// TODO: this is temporary, those dirs should be created "elsewhere"
		$mailboxPath = $this->getMailboxPath();
		if (!is_dir($mailboxPath))
		{
			f_util_FileUtils::mkdir($mailboxPath);
		}
		
		$outboxPath = $this->getOutboxPath();
		if (!is_dir($outboxPath))
		{
			f_util_FileUtils::mkdir($outboxPath);
		}
			
		$trashPath = $this->getTrashPath();
		if (!is_dir($trashPath))
		{
			f_util_FileUtils::mkdir($trashPath);
		}
		
		$logsPath = $this->getLogsPath();
		if (!is_dir($logsPath))
		{
			f_util_FileUtils::mkdir($logsPath);
		}
	}
	
	/**
	 * @return Boolean
	 */
	private function isOutboxEmpty()
	{
		$iterator = new DirectoryIterator($this->getOutboxPath());
		foreach ($iterator as $file)
		{
			if ($file->isFile())
			{
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Subdirectory tree of outbox in which the specific mail wil be contained eg 1a/05/77/ae/
	 *
	 * @param unknown_type $content
	 * @return unknown
	 */
	private function getSubdirectoriesForContent($content)
	{
		return chunk_split(sprintf("%08x", crc32($content)), 2, DIRECTORY_SEPARATOR);
	}
}
