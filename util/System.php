<?php
class f_util_System
{
	public static function escapeCmd($cmd)
	{
		$cmd = mb_ereg_replace(", ", "\\, ", $cmd);
		$cmd = mb_ereg_replace(" ", "\\ ", $cmd);
		return $cmd;
	}

	/**
	 * @param String $cmd
	 * @param String $msg
	 * @param Boolean $captureStdout
	 * @param String input
	 * @return String the output result of execution
	 */
	public static function exec($cmd, $msg = null, $captureStdout = true, $input = null)
	{
		if ($msg !== null)
		{
			echo $msg."...";
		}

		$cmd .= " 2>&1";
		
		$descriptorspec = array(
		0 => array('pipe', 'r'), // stdin
		1 => array('pipe', 'w'), // stdout
		2 => array('pipe', 'w') // stderr
		);
		$proc = proc_open($cmd, $descriptorspec, $pipes);
		if (!is_resource($proc))
		{
			throw new Exception("Can not execute $cmd");
		}
		stream_set_blocking($pipes[2], 0);
		if ($input !== null)
		{
			fwrite($pipes[0], $input);
		}
		fclose($pipes[0]);
		$output = "";
		while (!feof($pipes[1]))
		{
			$s = fread($pipes[1], 512);
			if ($s === false)
			{
				throw new Exception("Error while executing $cmd: could not read further execution result");
			}
			$output .= $s;
			if (!$captureStdout)
			{
				echo $s;
			}
		}

		$retVal = proc_close($proc);
		if (0 != $retVal)
		{
			throw new Exception("Could not execute $cmd (exit code $retVal):\n".$output);
		}
		if ($msg !== null)
		{
			echo " done\n";
		}
		return trim($output);
	}

	/**
	 * @param String $cmd
	 * @param String $msg
	 * @param Boolean $captureStdout
	 * @param String input
	 * @return array the output result of execution
	 */
	public static function execArray($cmd, $msg = null, $captureStdout = true, $input = null)
	{
		$out = self::exec($cmd, $msg, $captureStdout, $input);
		if (empty($out))
		{
			return array();
		}
		return explode("\n", $out);
	}
	
	/**
	 * @param string $relativeScriptPath to WEBEDIT_HOME
	 * @param array $arguments
	 * @param boolean $noFramework
	 */
	public static function execHTTPScript($relativeScriptPath, $arguments = array(), $noFramework = false)
	{
		list($name, $secret) = explode('#', file_get_contents(WEBEDIT_HOME . '/build/config/oauth/script/consumer.txt'));
		$consumer = new f_web_oauth_Consumer($name, $secret);

		list($name, $secret) = explode('#', file_get_contents(WEBEDIT_HOME . '/build/config/oauth/script/token.txt'));	
		$token = new f_web_oauth_Token($name, $secret);
		
		$request = new f_web_oauth_Request('http://'.Framework::getUIDefaultHost() .'/changescriptexec.php', $consumer, f_web_oauth_Request::METHOD_POST);
		$request->setParameter('phpscript', $relativeScriptPath);
		if ($noFramework)
		{
			$request->setParameter('noframework', 'true');
		}
		if (count($arguments) > 0)
		{
			$request->setParameter('argv', $arguments);
		}
		$request->setToken($token);
		$client = new f_web_oauth_HTTPClient($request);
		$client->getBackendClientInstance()->setTimeOut(0);
		return $client->execute();		
	}
	
	/**
	 * @param string $relativeScriptPath to WEBEDIT_HOME
	 * @param array $arguments
	 */
	public static function execChangeHTTPCommand($commandName, $arguments = array())
	{
		return self::execHTTPScript("framework/bin/changeHTTP.php", array_merge(array($commandName), $arguments), true);
	}
	
	/**
	 * @param string $relativeScriptPath to WEBEDIT_HOME
	 * @param array $arguments
	 */
	public static function execChangeConsoleCommand($commandName, $arguments = array())
	{
		return self::exec("change.php $commandName " . implode(" ", $arguments)); 
	}
	
	/**
	 * @param string $relativeScriptPath to WEBEDIT_HOME
	 * @param array $arguments
	 */
	public static function execChangeCommand($commandName, $arguments = array())
	{
		if (isset($_SERVER['REMOTE_ADDR']))
		{
			// Mode web
			return self::execChangeHTTPCommand($commandName, $arguments);
		}
		return self::execChangeConsoleCommand($commandName, $arguments);
	}
	
}