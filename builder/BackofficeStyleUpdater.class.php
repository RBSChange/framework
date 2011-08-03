<?php
/**
 * @package framework.builder
 */
class builder_BackofficeStyleUpdater
{
	/**
	 * Document model object
	 * @var f_persistentdocument_PersistentDocumentModel
	 */
	private $model = null;

	/**
	 * Load the document model
	 *
	 * @param string $model (ex: modules_users/users)
	 */
	public function __construct($modelName)
	{
		$this->model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
	}

	/**
	 * Generate or update document locale file
	 * @return void
	 */
	public function updateXmlDocument()
	{
		self::updateCssFile($this->model->getModuleName(), $this->model->getDocumentName(), $this->model->getIcon());
	}
	
	/**
	 * @param generator_PersistentModel $model
	 */
	public static function updateCssByDocument($model)
	{
		self::updateCssFile($model->getModuleName(), $model->getDocumentName(), $model->getIcon());
	}
	
	private static function updateCssFile($moduleName, $documentName, $iconName)
	{
		$fileName = 'backoffice.css';
		$file = f_util_FileUtils::buildModulesPath($moduleName, 'style', $fileName);

		$selector = 'treechildren::-moz-tree-image(modules_'.$moduleName.'_'.$documentName.')';
		if (f_util_StringUtils::isNotEmpty($iconName))
		{
			$iconName = 'small/' . $iconName;
		}
		else
		{
			$iconName = 'small/document';
		}

		if (file_exists($file))
		{
			$css = website_CSSStylesheet::getInstanceFromFile($file);
			foreach ($css->getCSSRules() as $rule)
			{
				if ($rule->getSelectorText() == $selector)
				{
					// selector already in file
					return;
				}
			}
			$content = file_get_contents($file) . "\n";
		}
		else
		{
			$content = "";
		}

		if (!is_writeable(dirname($file)))
		{
			echo "$file management canceled: file is not writable\n";
			return;
		}

		$content .= $selector . "{\n\tlist-style-image: url(/changeicons/$iconName.png);\n}";
		echo "Updating $file\n";
		f_util_FileUtils::write($file, $content, f_util_FileUtils::OVERRIDE);		
	}
}