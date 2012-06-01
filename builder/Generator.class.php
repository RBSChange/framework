<?php
$smartyClassPath = f_util_FileUtils::buildWebeditPath('libs', 'smarty', 'libs', 'Smarty.class.php');
require_once($smartyClassPath);

/**
 * @package framework.builder
 */
class builder_Generator extends Smarty 
{

     function __construct($relativePath = null) 
     {
	     // Constructeur de la classe. 
	     // Appelé automatiquement à l'instanciation de la classe.
	     $this->Smarty();
	     if ($relativePath !== null)
	     {
	     	$this->template_dir = f_util_FileUtils::buildFrameworkPath('builder', 'templates', $relativePath, '');
	     }
	     $this->compile_dir = f_util_FileUtils::buildChangeCachePath('smarty', 'templates_c', '');
	     $this->config_dir = f_util_FileUtils::buildChangeCachePath('smarty', 'configs', '');
	     $this->cache_dir = f_util_FileUtils::buildChangeCachePath('smarty', 'cache', '');
	     $this->caching = false;
	     $this->left_delimiter = "<{";
	     $this->right_delimiter = "}>";
	     
	     f_util_FileUtils::mkdir($this->compile_dir);
	     f_util_FileUtils::mkdir($this->config_dir);
	     f_util_FileUtils::mkdir($this->cache_dir);
     }
     
     /**
      * @param String $templateDir
      */
     function setTemplateDir($templateDir)
     {
     	$this->template_dir = $templateDir;
     }
     
    /**
     * get a concrete filename for automagically created content
     *
     * @param string $auto_base
     * @param string $auto_source
     * @param string $auto_id
     * @return string
     * @staticvar string|null
     * @staticvar string|null
     */
    function _get_auto_filename($auto_base, $auto_source = null, $auto_id = null)
    {
    	return $auto_base . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, '_', sha1($this->template_dir) . '_' . $auto_source);
    }
}
