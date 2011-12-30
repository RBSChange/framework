<?php
require_once dirname(__FILE__) .'/PclZip.php';
require_once dirname(__FILE__) .'/pclzip.lib.php';
class cboot_Zip
{
	/**
	 * @var String
	 */
	private static $driverClassName;

	/**
	 * @return cboot_Zipper
	 */
	private static function getInstance($zipPath)
	{
		if (self::$driverClassName === null)
		{
			self::$driverClassName = "cboot_PclZip";
		}
		return new self::$driverClassName($zipPath);
	}

	/**
	 * @param String $zipPath
	 * @param String $dest
	 * @param String[] $entries
	 */
	static function unzip($zipPath, $dest, $entries = null)
	{
		$zip = self::getInstance($zipPath);
		$zip->extractTo($dest, $entries);
		$zip->close();
	}

	/**
	 * @param String $zipPath
	 * @param cboot_Zipcontent $content
	 */
	function zip($zipPath, $content)
	{
		if (file_exists($zipPath))
		{
			// TODO: handle directories, ... etc.
			unlink($zipPath);
		}
		$zip = self::getInstance($zipPath);
		$zip->add($content);
		$zip->close();
	}
}

class cboot_ZipContent
{
	private $entries;

	/**
	 * For example: new cboot_ZipContent('afile', array('file2', 'file3'), array('file4' => 'localFile4Path')) 
	 */
	function __construct()
	{
		foreach (func_get_args() as $arg)
		{
			if (is_array($arg))
			{
				if (empty($arg))
				{
					continue;
				}
				if (isset($arg[0]))
				{
					// numeric indexed
					foreach ($arg as $path)
					{
						$this->entries[$path] = null;
					}
				}
				else
				{
					// String indexed : means rewrited paths
					foreach ($arg as $path => $localPath)
					{
						$this->entries[$path] = $localPath;
					}
				}
			}
			else
			{
				$this->entries[$arg] = null;
			}
		}
	}

	/**
	 * @param String $fileOrDirectory
	 * @param String $localPath
	 * @return cboot_ZipContent
	 */
	function add($fileOrDirectory, $localPath = null)
	{
		$this->entries[$fileOrDirectory] = $localPath;
		return $this;
	}

	/**
	 * @param String $fileOrDirectory
	 * @param String $localPath
	 * @return cboot_ZipContent
	 */
	function addMultiple($filesOrDirectories)
	{
		foreach ($filesOrDirectories as $file)
		{
			$this->entries[$file] = $null;
		}
		return $this;
	}

	/**
	 * @return array<String, String|null>
	 */
	function getEntries()
	{
		return $this->entries;
	}
}

interface cboot_Zipper
{
	/**
	 * @param String $zipPath
	 */
	function __construct($zipPath);

	function close();

	/**
	 * @param String $path
	 * @param String[] $entries
	 */
	function extractTo($path, $entries = null);

	/**
	 * @param cboot_ZipContent $content
	 */
	function add($content);
}