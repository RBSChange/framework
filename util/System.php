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
	 */
	public static function execHTTPScript($relativeScriptPath, $arguments = array())
	{
		$url = Framework::getBaseUrl() .'/changescriptexec.php';
		$rc = curl_init();
		curl_setopt($rc, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($rc, CURLOPT_USERAGENT, 'RBSChange/3.0');
		curl_setopt($rc, CURLOPT_REFERER, $url);
		$postData = http_build_query(array('phpscript' => $relativeScriptPath, 'argv' => $arguments), '', '&');
		curl_setopt($rc, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($rc, CURLOPT_POST, true);
		curl_setopt($rc, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($rc, CURLOPT_URL, $url);
		$data = curl_exec($rc);
		curl_close($rc);
		return $data;		
	}
}