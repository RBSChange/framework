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
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__);
		}
		
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
		$script = 'framework/listener/publishDocumentsBatch.php';
		foreach ($documentsArray as $chunk)
		{
			f_util_System::execHTTPScript($script, $chunk);
		}
	}
	
	private function getDocumentIdsToProcess($start, $end)
	{
		$toProcess = array();
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('publishListenerInfos.ser');
		if (file_exists($compiledFilePath))
		{
			$models = unserialize(file_get_contents($compiledFilePath));
			$rc = RequestContext::getInstance();
			foreach ($models as $modelName => $langs) 
			{
				foreach ($langs as $lang)
				{
					try
					{
						$rc->beginI18nWork($lang);
						$query = f_persistentdocument_PersistentProvider::getInstance()->createQuery($modelName, false);
						$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')))
								->add(Restrictions::orExp(Restrictions::between('startpublicationdate', $start, $end), 
										Restrictions::between('endpublicationdate', $start, $end)))
								->setProjection(Projections::property('id', 'id'));
								
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
		}
		else
		{
			Framework::error(__METHOD__ . ' File not found ' . $compiledFilePath);
		}
		return $toProcess;
	}
}