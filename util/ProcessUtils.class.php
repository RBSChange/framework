<?php
abstract class f_util_ProcessUtils
{
	public static function printBackTrace($html = false, $nbSkip = 1)
	{
		$backTrace = debug_backtrace();

		if ($html)
		{
			echo "<pre>\n";
		}
		$i = 0;
		foreach ($backTrace as $call)
		{
			if ($i-$nbSkip >= 0)
			{
				if (isset($call['file']))
				{
					echo "#".($i-$nbSkip)." Called in ".$call['file'].'/'.$call['function']."(".self::printArgs($call['args']).") line ".$call['line'];
				}
				else
				{
					echo "#".($i-$nbSkip)." Called in ". $call['function']."(".self::printArgs($call['args']).")";
				}
				if ($html)
				{
					echo "<br>";
				}
				echo "\n";
			}
			$i++;
		}
		if ($html)
		{
			echo "</pre>\n";
		}
	}
	
	public static function getBackTrace($html = false, $nbSkip = 2)
	{
		ob_start();
		self::printBackTrace($html, $nbSkip);
		return ob_get_clean();
	}
	
	private static function printArgs($args, $level = 1)
	{
		if (!empty($args))
		{
			$echo = array();
			foreach ($args as $arg)
			{
				if (is_object($arg))
				{
					$echo[] = get_class($arg);
				}
				elseif (is_array($arg))
				{
					if ($level > 0)
					{
						$echo[] = self::printArgs($arg, $level-1);	
					}
					else
					{
						$echo [] = 'Array';
					}
				}
				else
				{
					$echo[] = var_export($arg, true);
				}
			}
			return join(', ', $echo);
		}
	}
}