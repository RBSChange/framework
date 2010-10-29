<?php
if (!defined("PCLZIP_TEMPORARY_DIR"))
{
	$tmpDir = null;
	if (function_exists("sys_get_temp_dir"))
	{
		$tmpDir = sys_get_temp_dir();
	} else {
		$tmpDir = "/tmp";
	}

	define("PCLZIP_TEMPORARY_DIR", $tmpDir."/".uniqid("pclzip"));
}


class cboot_PclZip implements cboot_Zipper
{
	/**
	 * @var PclZip
	 */
	private $zip;
	/**
	 * @var String
	 */
	private $zipPath;
	/**
	 * @var String
	 */
	private $workDir;

	function __construct($zipPath)
	{
		$this->zip = new PclZip($zipPath);
		$this->zipPath = $zipPath;
	}

	function close()
	{
		$this->zip = null;
	}

	/**
	 * @param String $path
	 * @param String[] $entries
	 */
	function extractTo($path, $entries = null)
	{
		if ($entries === null)
		{
			if ($this->zip->extract(PCLZIP_OPT_PATH, $path) == 0)
			{
				throw new Exception("Could not extract ".$this->zipPath." to $path: ".$this->zip->errorInfo(true));
			}
		}
		else
		{
			if ($this->zip->extract(PCLZIP_OPT_PATH, $path, PCLZIP_OPT_BY_NAME, $entries) == 0)
			{
				throw new Exception("Could not extract ".$this->zipPath." to $path: ".$this->zip->errorInfo(true));
			}
		}
	}

	/**
	 * @param cboot_ZipContent $content
	 */
	function add($content)
	{
		foreach ($content->getEntries() as $path => $localPath)
		{
			if ($localPath === null)
			{
				$this->zip->add($path);
			}
			else
			{
				if (basename($path) == basename($localPath))
				{
					if (is_dir($path))
					{
						$this->zip->add($path, PCLZIP_OPT_REMOVE_PATH, $path, PCLZIP_OPT_ADD_PATH, $localPath);
					}
					else
					{
						$this->zip->add($path, PCLZIP_OPT_REMOVE_PATH, dirname($path), PCLZIP_OPT_ADD_PATH, dirname($localPath));
					}
				}
				else
				{
					// TODO: directories ...
					$pathRenamed = $this->getWorkDir()."/".basename($localPath);
					if (!copy($path, $pathRenamed))
					{
						throw new Exception("Could not copy $path to $pathRenamed");
					}
					if (is_dir($path))
					{
						$this->zip->add($pathRenamed, PCLZIP_OPT_REMOVE_PATH, $pathRenamed, PCLZIP_OPT_ADD_PATH, $localPath);
					}
					else
					{
						$this->zip->add($pathRenamed, PCLZIP_OPT_REMOVE_PATH, dirname($pathRenamed), PCLZIP_OPT_ADD_PATH, dirname($localPath));
					}
				}
			}
		}
	}

	function getWorkDir()
	{
		if ($this->workDir === null)
		{
			$this->workDir = PCLZIP_TEMPORARY_DIR;
			if (!is_dir($this->workDir) && !mkdir($this->workDir, 0777, true))
			{
				throw new Exception("Could not create ".$this->workDir);
			}
		}

		return $this->workDir;
	}

	function __destruct()
	{
		if ($this->zip !== null)
		{
			$this->close();
			
		}
		if ($this->workDir !== null)
		{
			// TODO: PHP version
			exec("rm -rf ".$this->workDir);
		}
	}
}


