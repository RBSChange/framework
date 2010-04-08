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
		$fileName = 'backoffice.css';
		$file = f_util_FileUtils::buildWebeditPath('modules', $this->model->getModuleName(), 'style', $fileName);

		$selector = 'treechildren::-moz-tree-image(modules_'.$this->model->getModuleName().'_'.$this->model->getDocumentName().')';
		$iconName = $this->model->getIcon();
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
			$css = f_web_CSSStylesheet::getInstanceFromFile($file);
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