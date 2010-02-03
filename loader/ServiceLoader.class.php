<?php
/**
 * @package framework.loader
 */
class ServiceLoader implements ResourceLoader
{

	/**
	 * The singleton instance
	 * @var ServiceLoader
	 */
	private static $instance = null;

	/**
	 * Class resolver to find the path of class
	 * @var ClassResolver
	 */
	private $resolver = null;

	/**
	 * Construct of ClassLoader where the resolver class instance is setted
	 */
	protected function __construct()
	{
		$this->resolver = Resolver::getInstance('class');
	}

	/**
	 * Return the current ClassLoader
	 *
	 * @return ClassLoader
	 */
	public static function getInstance()
	{

		if( is_null(self::$instance) )
		{
			$className = __CLASS__;
			self::$instance = new $className();
		}

		return self::$instance;

	}

	/**
	 * Get an instance of the Service associated to documents of type $documentModelName.
	 *
	 * @param string $documentModelName Name of the document model
	 * @return f_persistentdocument_DocumentService
	 *
	 * @throws ServiceNotFoundException
	 */
	public function load($documentModelName)
	{
		$typeObject = ComponentTypeObject::getInstance($documentModelName);
		$docType = $typeObject->getComponentType();
		$serviceClassName = sprintf(
			'%s_%sService',
			strtolower($typeObject->getPackageName()), ucfirst($docType)
			);

		$path = null;
		try
		{
			$path = $this->resolver->getPath($serviceClassName);
		}
		catch (ClassNotFoundException $e)
		{
			// TODO see f_persistentdocument_PersistentDocumentModel->getInstance($moduleName, $documentName)
			if ($docType != 'folder')
			{
				throw new ServiceNotFoundException($documentModelName);
			}
		}

		if ( $docType == 'folder' && is_null($path) )
		{
			$serviceClassName = 'generic_FolderService';
			try
			{
				$path = $this->resolver->getPath($serviceClassName);
			}
			catch (ClassNotFoundException $e)
			{
				throw new ServiceNotFoundException($documentModelName);
			}
		}

		require_once($path);

		$serviceInstance = f_util_ClassUtils::callMethod($serviceClassName, 'getInstance');
		if ( $serviceInstance instanceof f_persistentdocument_DocumentService )
		{
			return $serviceInstance;
		}

		throw new ServiceNotFoundException($documentModelName);
	}

	/**
	 * Get an instance of the Service associated to documents of type $documentModelName.
	 *
	 * @param string $documentModelName Name of the document model
	 * @return f_persistentdocument_DocumentService
	 *
	 * @throws ServiceNotFoundException
	 */
	public static function getServiceByDocumentModelName($documentModelName)
	{
		return self::getInstance()->load($documentModelName);
	}
}
