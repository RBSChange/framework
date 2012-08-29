<?php
/**
 * Usage :<br/>
 * <code>
 * class AListener
 * {
 *	public onAnEvent($source, $params);
 *	public onAnEvent2($source, $params);
 * }
 * $listener = new AListener();
 * f_event_EventManager::register($listener);
 * // this call onAnEvent($aSource, $params) on $listener an all other listener registered for this event
 * f_event_EventManager::dispatchEvent('anEvent', $aSource, $params);
 * // this call onAnEvent2($aSource, $params) on $listener an all other listener registered for this event
 * f_event_EventManager::dispatchEvent('anEvent2', $aSource, $params);
 * </code>
 */
class f_event_EventManager
{
	/**
	 * value = null
	 */
	const EVENT_DOCUMENT_LOADED = 'document_loaded';

	/**
	 * value = null
	 *
	 */
	const EVENT_DOCUMENT_INSERTED = 'document_inserted';

	/**
	 * value = null
	 */
	const EVENT_DOCUMENT_UPDATED = 'document_updated';

	/**
	 * value = null
	 */
	const EVENT_DOCUMENT_DELETED = 'document_deleted';


	/**
	 * value = the new state
	 */
	const EVENT_DOCUMENT_STATE_CHANGED = 'document_state_changed';

	/**
	 * value = the name of property which changed
	 */
	const EVENT_DOCUMENT_PROPERTY_CHANGED = 'document_property_changed';

	/**
	 * @var array
	 */
	private static $m_eventHandlers = array();
	/**
	 * @var boolean
	 */
	private static $isCacheLoaded = false;

	/**
	 * @param string $methodName
	 * @return string eventName or null
	 */
	private static function getEventNameFromMethodName($methodName)
	{
		if (!f_util_StringUtils::beginsWith($methodName, 'on'))
		{
			return null;
		}
		return strtolower($methodName[2]).substr($methodName, 3);
	}

	/**
	 * @param Object $listener the listener instance
	 * @param string $eventName
	 * @param string $methodName
	 */
	public static function register($listener, $eventName = null, $methodName = null)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug('EventManager : Register '.get_class($listener).'->'.$methodName.' for '.$eventName);
		}

		if (is_null($eventName) || empty($eventName))
		{
			// register listener for all "onXXX" methods
			foreach (f_util_ClassUtils::getMethods($listener) as $method)
			{
				if ($method->isPublic())
				{
					$methodEventName = self::getEventNameFromMethodName($method->getName());
					if (!is_null($methodEventName))
					{
						self::register($listener, $methodEventName, $method->getName());
					}
				}
			}
		}
		else
		{
			if (is_null($methodName) || empty($methodName))
			{
				$methodName = "on".ucfirst($eventName);
			}
			if (!f_util_ClassUtils::methodExists($listener, $methodName))
			{
				// FIXME intsimoa : BadArgumentException signature semantic ?
				throw new BadArgumentException($listener.'->'.$methodName, 'method');
			}
			if (!array_key_exists($eventName, self::$m_eventHandlers))
			{
				self::$m_eventHandlers[$eventName] = array();
			}
			self::$m_eventHandlers[$eventName][] = array($listener, $methodName);
		}
	}

	/**
	 * Only used to test EventManager ...
	 */
	public static function unregisterAll()
	{
		self::$isCacheLoaded = true;
		self::$m_eventHandlers = array();
	}
	
	/**
	 * Remove registered listener
	 *
	 * @param string $listenerClassName
	 * @param string $methodName
	 */
	public static function unregister($listenerClassName, $methodName = null)
	{
		self::loadListenerConfigCache();
		$newEventHandlers = array();
		foreach (self::$m_eventHandlers as $eventName => $listeners)
		{
			foreach ($listeners as $listenerInfo) 
			{
				if (get_class($listenerInfo[0]) === $listenerClassName 
					&& ($methodName === null || $methodName === $listenerInfo[1]))
				{
					continue;
				}
				
				if (!isset($newEventHandlers[$eventName]))
				{
					$newEventHandlers[$eventName] = array();	
				}
				$newEventHandlers[$eventName][] = $listenerInfo;
			}
		}
		self::$m_eventHandlers = $newEventHandlers;
	}

	/**
	 * @param string $eventName
	 * @param Object $sender the source of event
	 * @param array<String, mixed> $params
	 */
	public static function dispatchEvent ($eventName, $sender, $params = null)
	{
		self::loadListenerConfigCache();

		if (is_null(self::$m_eventHandlers) || !array_key_exists($eventName, self::$m_eventHandlers))
		{
			return;
		}
		foreach (self::$m_eventHandlers[$eventName] as $listenerInfo)
		{
			try
			{
				$listenerInfo[0]->{$listenerInfo[1]}($sender, $params);
			}
			catch (Exception $e)
			{
				Framework::exception($e);
				if (f_util_ClassUtils::propertyExists($listenerInfo[0], $listenerInfo[1]."Required"))
				{
					throw $e;
				}
			}
		}
	}

	/**
	 * Load a "listeners.xml" file
	 * @param string $path absolute path of the file to load
	 */
	public static function loadListenerConfig($path)
	{
		$listeners = new SimpleXMLElement(f_util_FileUtils::read($path));
		foreach ($listeners->listener as $listenerNode)
		{
			$listenerClass = (string) $listenerNode['listenerClass'];
			$eventName = (string) $listenerNode['eventName'];
			$method = (string) $listenerNode['listenerMethod'];
			self::register(new $listenerClass(), $eventName, $method);
		}
	}

	private static function loadListenerConfigCache()
	{
		if (!self::$isCacheLoaded)
		{
			$listenersFile = f_util_FileUtils::buildChangeBuildPath('listeners.php');
			if (is_readable($listenersFile))
			{
				require_once $listenersFile;
			}
			self::$isCacheLoaded = true;
		}
	}
}
