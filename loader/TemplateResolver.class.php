<?php
/**
 * @package framework.loader
 */
class TemplateResolver extends FileResolver implements ResourceResolver
{

	/**
	 * The singleton instance
	 * @var TemplateResolver
	 */
	private static $instance = null;

	/**
	 * Engine used by browser (all, gecko, trident, ...)
	 * @var string
	 */
	private $engine = 'all';
	/**
	 * Engine version used by browser (all, 1.8, 4, ...)
	 * @var string
	 */
	private $engineVersion = 'all';

	/**
	 * Content type of template (html, xml, xul, ...)
	 * @var string
	 */
	private $mimeContentType = K::HTML ;

	/**
	 * Return the current TemplateResolver
	 *
	 * @return TemplateResolver
	 */
	public static function getInstance()
	{

		if ( is_null(self::$instance) )
		{
			self::$instance = new TemplateResolver();

		}
		self::$instance->reset();
		return self::$instance;

	}

	/**
	 * Constructor of TemplateResolver set the engine type
	 */
	protected function __construct()
	{
		// Set engine informations
		$this->resolveBrowserEngine();
		$this->setDirectory('templates');
		if (!is_dir(PHPTAL_PHP_CODE_DESTINATION))
		{
			@mkdir(PHPTAL_PHP_CODE_DESTINATION, 0777, true);
		}
	}

	/**
	 * Reset the resolver to use its default values (no directory and no packageName set).
	 *
	 * @return object $this
	 */
	public function reset()
	{
		parent::reset();
		$this->setDirectory('templates');
		$this->setMimeContentType(K::HTML);
		return $this;
	}

	/**
	 * Return the path of the researched resource
	 *
	 * @param string $templateName Name of researched template
	 * @return string Path of resource
	 */
	public function getPath($templateName)
	{

		// If not found in cache file search with FileResolver
		// Test multi name case
		// Engine + Engine Version
		$path = parent::getPath($this->getFullFileName($templateName));

		// Engine + all
		if (NULL === $path)
		{
			$path = parent::getPath($this->getFullFileName($templateName, false));
		}

		// all + all
		if (NULL === $path)
		{
			$path = parent::getPath($this->getFullFileName($templateName, false, false));
		}
		
		if (NULL === $path)
		{
			$path = parent::getPath($templateName . '.' . $this->mimeContentType);
		}

		return $path;

	}

	/**
	 * Set the mime content type for the researched template
	 *
	 * @param string $type Mime type (html, xml , xul, ...)
	 * @return object $this
	 */
	public function setMimeContentType($type)
	{

		$this->mimeContentType = $type;
		return $this;

	}

	/**
	 * Get the mime content type for the researched template
	 *
	 * @return string
	 */
	public function getMimeContentType()
	{

		return $this->mimeContentType;

	}

	/**
	 * Return the full name of the template file with engine type, engine version and content type.
	 *
	 * @param string $templateName Name of th file template (News-Folder-Title, ...)
	 * @param boolean $useEngine If true you use the particular engine
	 * @param boolean $useVersion If true you use the particular engine version
	 * @return string Full name of template file
	 */
	private function getFullFileName($templateName, $useVersion = true, $useEngine = true)
	{

		$engine = $this->engine;
		$engineVersion = $this->engineVersion;

		// Test if the name must use the engine version
		if ($useVersion === false)
		{
			$engineVersion = 'all';
		}

		// Test if the name must use the engine name
		if ($useEngine === false)
		{
			$engine = 'all';
		}

		return $templateName . "." . $engine . "." . $engineVersion . "." . $this->mimeContentType;

	}

	/**
	 * Get the engine type and version and set the class variable
	 */
	private function resolveBrowserEngine()
	{
        $requestContext = RequestContext::getInstance();
        $this->engine = $requestContext->getUserAgentType();
        $this->engineVersion = $requestContext->getUserAgentTypeVersion();
	}

}
