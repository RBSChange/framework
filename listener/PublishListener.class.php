<?php
/**
 * @package framework.listener
 */
class f_listener_PublishListener
{

	
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onHourChange($sender, $params)
	{
		$date = $params['date'];
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . "($date,".var_export($params['previousRunTime'], true).")");
		}
		if (isset($params['previousRunTime']))
		{
			$start = date_Calendar::getInstanceFromTimestamp($params['previousRunTime'])->toString();
		}
		else
		{
			$start = date_Calendar::getInstance($date)->add(date_Calendar::HOUR, -1)->toString();
		}
		$end = date_Calendar::getInstance($date)->add(date_Calendar::HOUR, 1)->toString();	
		$documentsArray = array_chunk($this->getDocumentIdsToProcess($start, $end), 500);
		foreach ($documentsArray as $chunk)
		{
			$processHandle = popen("php " . dirname(__FILE__) . DIRECTORY_SEPARATOR . "publishDocumentsBatch.php " . implode(" ", $chunk), "r");
			while ( ($string = fread($processHandle, 1000)) != false)
			{
				// do nothing
			}
			pclose($processHandle);
		}
	}
	
	private function getDocumentIdsToProcess($start, $end)
	{
		$toProcess = array();
		foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModels() as $model)
		{
			if (strpos($model->getName(), 'modules_test/') === 0 || $model->publishOnDayChange() === false)
			{
				continue;
			}
			
			$pubproperty = $model->getProperty('publicationstatus');
			if (is_null($pubproperty))
			{
				return array();
			}
			
			$rc = RequestContext::getInstance();
			
			if ($model->isLocalized() && $pubproperty->isLocalized())
			{
				$langs = $rc->getSupportedLanguages();
			}
			else
			{
				$langs = array($rc->getDefaultLang());
			}
			
			foreach ($langs as $lang)
			{
				try
				{
					$rc->beginI18nWork($lang);
					$query = f_persistentdocument_PersistentProvider::getInstance()->createQuery($model->getName());
					$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')))->add(Restrictions::eq('model', $model->getName()))->add(Restrictions::orExp(Restrictions::between('startpublicationdate', $start, $end), Restrictions::between('endpublicationdate', $start, $end)))->setProjection(Projections::property('id', 'id'));
					$results = $query->find();
					foreach ($results as $resultArray)
					{
						$toProcess[] = $resultArray['id'] . '/' . $lang;
					}
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
		return $toProcess;
	}
}