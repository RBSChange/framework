<?php
class framework_patch_0305 extends patch_BasePatch
{
	
	/**
	 * Returns true if the patch modify code that is versionned.
	 * If your patch modify code that is versionned AND database structure or content,
	 * you must split it into two different patches.
	 * @return Boolean true if the patch modify code that is versionned.
	 */
	public function isCodePatch()
	{
		return true;
	}
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Remove the following line and implement the patch here.
		parent::execute();
		
		$modules = ModuleService::getInstance()->getModulesObj();
		foreach ($modules as $mod)
		{
			$stylePath = f_util_FileUtils::buildWebeditPath('modules', $mod->getName(), 'style');
			$this->migratePath($mod, $stylePath);

			$stylePath = f_util_FileUtils::buildWebappPath('modules', $mod->getName(), 'style');
			$this->migratePath($mod, $stylePath);
		}
	}
	
	/**
	 * @param c_Module $mod
	 * @param string $stylePath
	 */
	private function migratePath($mod, $stylePath)
	{
		if (is_dir($stylePath))
		{
			$files = scandir($stylePath);
			foreach ($files as $fileName)
			{
				if (preg_match('/\.xml$/', $fileName))
				{
					$filePath = f_util_FileUtils::buildAbsolutePath($stylePath, $fileName);
					if (is_writable($filePath))
					{
						$cssxml = new DOMDocument();
						$cssxml->load($filePath);
						if ($cssxml->documentElement)
						{
							$cssrule = $cssxml->getElementsByTagName('style');
							if ($cssrule->length > 0)
							{
								
								$css = f_web_CSSStylesheet::getInstanceFromFile($filePath);
								$engines = $css->getAllEngine();
								$cssContent = $css->getCSSForEngine('all.all');
								foreach ($engines as $eng)
								{
									if ($eng === 'all.all')
									{
										continue;
									}
									$cssContent .= "\n@import url(/modules/" . $mod->getName() . "/style/" . str_replace('.xml', '.' . $eng . '.css', $fileName) . ');';
								
								}
								$cssPath = str_replace('.xml', '.css', $filePath);
								echo "\nWriting CSS: $cssPath";
								unlink($cssPath);
								file_put_contents($cssPath, $cssContent);
								
								foreach ($engines as $eng)
								{
									if ($eng === 'all.all')
									{
										continue;
									}
									$cssPath = str_replace('.xml', '.' . $eng . '.css', $filePath);
									echo "\nWriting Sub CSS: $cssPath";
									$cssContent = $css->getCSSForEngine($eng);
									unlink($cssPath);
									file_put_contents($cssPath, $cssContent);
								}
							
							}
							echo "\nDelete: $filePath";
							unlink($filePath);
						}
					}
					else
					{
						echo "\nLocked : $filePath";
					}
				}
			}
		}
	}
	
	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'framework';
	}
	
	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0305';
	}

}