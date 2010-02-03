<?php
/**
 * @package framework.loader
 */
class TemplateLoader extends FileLoader implements ResourceLoader
{

	/**
	 * The singleton instance
	 * @var TemplateLoader
	 */
	private static $instance;

	/**
	 * Construct of TemplateLoader where the resolver class instance is setted
	 */
	protected function __construct()
	{
		$this->resolver = TemplateResolver::getInstance();
	}

	/**
	 * Return the current TemplateLoader after reseting the TemplateResolver instance.
	 * You can override the TemplateLoader using the Injection mechanism
	 * @see Injection
	 * @see TemplateResolver
	 * @return TemplateLoader
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			$finalClassName = Injection::getFinalClassName(get_class());
			self::$instance = new $finalClassName();
		}
		self::$instance->resolver->reset();
		return self::$instance;
	}

	/**
	 * Get an object Template with the $filename
	 *
	 * @param string $filename Name of template file that you want to load
	 * @return TemplateObject
	 *
	 * @throws TemplateNotFoundException
	 */
	public function load($filename)
	{
		$this->resolver->addCurrentWebsiteToPotentialDirectories();
				
		$path = $this->resolver->getPath($filename);
		if ($path === null)
		{
			throw new TemplateNotFoundException($this->getDirectory()."/".$filename, $this->getPackageName());
		}
		$template = new TemplateObject($this->templateLocalize($path), $this->resolver->getMimeContentType());
		if (Framework::inDevelopmentMode() && $this->resolver->getMimeContentType() === K::HTML)
		{
			$template->setOriginalPath($path); 
		}
		return $template;
	}

	/**
	 * Set the mime content type for the researched template
	 *
	 * @param string $type Mime type (html, xml , xul, ...)
	 * @return TemplateLoader object $this
	 */
	public function setMimeContentType($type)
	{
		$this->resolver->setMimeContentType($type);
		return $this;
	}

	private function loadTemplatesArray()
	{

	}

	private function appendTemplatesArray($templateName, $path)
	{

	}

	/**
	 * @param String $filePath
	 * @return String
	 */
	protected final function templateLocalize($filePath)
	{
		$lang = RequestContext::getInstance()->getLang();

		$precompiledFilePath = f_util_FileUtils::buildAbsolutePath(PHPTAL_PHP_CODE_DESTINATION, $lang, $this->getPackageName(), basename($filePath).'_'.md5($filePath).f_util_StringUtils::getFileExtension($filePath, true));

		if (!file_exists($precompiledFilePath) || filectime($precompiledFilePath) < filectime($filePath))
		{
			$originalContent = str_replace(array('${lang}', '{lang}'), $lang, f_util_FileUtils::read($filePath));
			$content = preg_replace(array('/i18n:translate/', '/i18n:attributes/'), array('change:translate', 'change:i18nattr') , $originalContent);

			f_util_FileUtils::writeAndCreateContainer($precompiledFilePath, $content, f_util_FileUtils::OVERRIDE);
		}
		return $precompiledFilePath;
	}
}
