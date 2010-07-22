<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2005  Sean Kerr.                                       |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

/**
 * LoggerManager provides accessibility and management of all loggers.
 *
 * @package    agavi
 * @subpackage logging
 *
 * @author     Sean Kerr (skerr@mojavi.org)
 * @copyright  (c) Sean Kerr, {@link http://www.mojavi.org}
 * @since      0.9.0
 * @version    $Id: LoggerManager.class.php 87 2005-06-03 21:19:23Z bob $
 */
class LoggerManager extends AgaviObject
{

	private static $loggers = array();
	private static $loggerGroups = array();
	/**
	 * Initialize this LoggingManager.
	 *
	 * @return bool true, if initialization completes successfully, otherwise
	 *              false.
	 *
	 * @throws <b>InitializationException</b> If an error occurs while
	 *                                        initializing this LoggingManager.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public static function initialize ()
	{
		// load database configuration
		require_once(ConfigCache::checkConfig(AG_CONFIG_DIR . '/logging.ini'));
	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve a logger.
	 *
	 * @param string A logger name.
	 *
	 * @return Logger A Logger, if a logger with the name exists, otherwise
	 *                null.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public static function getLogger ($name = 'default')
	{
		if (isset(self::$loggers[$name]))
		{
			return self::$loggers[$name];
		}
		return null;
	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve a list of logger names.
	 *
	 * @return array An indexed array of logger names.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public static function getLoggerNames ()
	{
		return array_keys(self::$loggers);
	}

	// -------------------------------------------------------------------------

	/**
	 * Indicates that a logger exists.
	 *
	 * @param string A logger name.
	 *
	 * @return bool true, if the logger exists, otherwise false.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public static function hasLogger ($name)
	{
		return isset(self::$loggers[$name]);
	}

	// -------------------------------------------------------------------------

	/**
	 * Remove a logger.
	 *
	 * @param string A logger name.
	 *
	 * @return Logger A Logger, if the logger has been removed, otherwise null.
	 *
	 * @throws <b>LoggingException</b> If the logger name is 'default', which
	 *                                 cannot be removed.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public static function & removeLogger ($name)
	{

		$retval = null;

		if (isset(self::$loggers[$name]))
		{
			if ($name != 'default')
			{
				$retval = self::$loggers[$name];
				unset(self::$loggers[$name]);
			} else
			{
				// cannot remove the default logger
				$error = 'Cannot remove the default logger';
				throw new LoggingException($error);
			}
		}

		return $retval;
	}

	// -------------------------------------------------------------------------

	/**
	 * Set a new logger instance.
	 *
	 * If a logger with the name already exists, an exception will be thrown.
	 *
	 * @param string A logger name.
	 * @param Logger A Logger instance.
	 *
	 * @return void
	 *
	 * @throws <b>LoggingException</b> If a logger with the name already exists.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public static function setLogger ($name, $logger)
	{
		if (!isset(self::$loggers[$name]))
		{
			self::$loggers[$name] = $logger;
			return;
		}

		// logger already exists
		$error = 'A logger with the name "%s" is already registered';
		$error = sprintf($error, $name);

		throw new LoggingException($error);
	}

	// -------------------------------------------------------------------------

	/**
	 * Log a Message.
	 *
	 * @param Message The Message to log.
	 *
	 * @return void
	 *
	 * @author Bob Zoller (bob@agavi.org)
	 * @since 0.9.1
	 */
	public static function log(Message $message)
	{
		foreach (self::$loggers as $logger) {
			$logger->log($message);
		}
	}
	public static function groupLog(Message $message,$groupName)
	{
		if (isset(self::$loggerGroups[$groupName]))
		{
			foreach (self::$loggerGroups[$groupName] as $loggerName)
			{
				$logger=LoggerManager::getLogger($loggerName);
				if ($logger==null)
				{
					//logger $loggerName in loggerGroup $groupName is desactivated"
					continue;
				}
				$logger->log($message);
			}
		}
	}
	
	public static function addToLoggerGroup($loggerName,$groupName)
	{
		$result = false;
		if (isset(self::$loggerGroups[$groupName])==false) 
		{
			self::$loggerGroups[$groupName]=array();
		}
		else
		{
			$result = array_search($loggerName,self::$loggerGroups[$groupName]);
		}
		
			
		if ($result==false)
			{
				array_push(self::$loggerGroups[$groupName],$loggerName);
				return true;
			}
		
		return false;
	}
	
	public static function setLoggerGroup($groupName,$loggerNameArray)
	{
		self::$loggerGroups[$groupName]=$loggerNameArray;
	}
	
	public static function removeLoggerGroup($groupName)
	{
		$retval = null;

		if (isset($loggerGroups[$groupName]))
		{
			if ($name != 'default')
			{
				$retval = self::$loggerGroups[$groupName];
				unset(self::$loggerGroups[$groupName]);
			} else
			{
				// cannot remove the default logger
				$error = 'Cannot remove the default logger';
				throw new LoggingException($error);
			}
		}

		return $retval;
	}
	
	// -------------------------------------------------------------------------

	/**
	 * Execute the shutdown procedure.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public static function shutdown ()
	{
		// loop through our loggers and shut them all down
		foreach (self::$loggers as $name => $logger)
		{
			$logger->shutdown();
			unset(self::$loggers[$name]);
		}
	}

}

?>
