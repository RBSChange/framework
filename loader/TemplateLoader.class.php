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
			self::$instance = new self();
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
		$currentPageId = website_PageService::getInstance()->getCurrentPageId();
		if ($currentPageId)
		{
			$currentPage = DocumentHelper::getDocumentInstance($currentPageId, "modules_website/page");
			list($theme, ) = explode('/', $currentPage->getTemplate());
			
			$themeDir = f_util_FileUtils::buildProjectPath('themes', $theme);
			$this->resolver->addPotentialDirectory($themeDir);
			$overrideThemeDir = f_util_FileUtils::buildOverridePath('themes', $theme);
			$this->resolver->addPotentialDirectory($overrideThemeDir);
		}
		$path = $this->resolver->getPath($filename);
		if ($path === null)
		{
			throw new TemplateNotFoundException($this->getDirectory()."/".$filename, $this->getPackageName());
		}
		
		$template = new TemplateObject($path, $this->resolver->getMimeContentType());
		
		if (Framework::inDevelopmentMode() && $this->resolver->getMimeContentType() === 'html')
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
}
