<?php
/**
 * framework_patch_0312
 * @package modules.framework
 */
class framework_patch_0312 extends patch_BasePatch
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
		$error = false;
		// Implement your patch here.
		foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModels() as $model)
		{
			$serviceClass = $model->getDocumentService();
			$reflectionClass = new ReflectionClass(get_class($serviceClass));
			$reflectionMethod = $reflectionClass->getMethod('getNewDocumentInstance');
			if (strpos($this->getReflectionObjCode($reflectionMethod), 'getNewDocumentInstance(') !== false)
			{
				$error = true;
				echo $reflectionMethod->getFileName() . "\n\t"  . 'please update getNewDocumentInstance method to call $this->getNewDocumentInstanceByModelName("' . $model->getName() .'")' . "\n\n";
			}
		}
		if ($error)
		{
			exit(1);
		}
	}
	
	/**
	 * @param $obj
	 * @return String
	 */
	private function getReflectionObjCode($reflectionObj)
	{
		return $this->getCode($reflectionObj->getFileName(), $reflectionObj->getStartLine()+1, $reflectionObj->getEndLine());
	}

	/**
	 * @param String $fileName
	 * @param Integer $start
	 * @param Integer $end
	 * @return String
	 */
	private function getCode($fileName, $startLine, $endLine)
	{
		$lines = file($fileName, FILE_IGNORE_NEW_LINES);
		list($start, $length) = $this->computeStartLength($fileName, $startLine, $endLine);
		return join("\n", array_slice($lines, $start, $length));
	}
		
	private function computeStartLength($fileName, $startLine, $endLine)
	{
		$start = $startLine-1;
		$length = $endLine-$startLine+1;
		return array($start, $length);
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'framework';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0312';
	}
}