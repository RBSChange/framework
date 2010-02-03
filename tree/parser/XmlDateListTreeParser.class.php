<?php
/**
 * @package framework.tree.parser
 */
class tree_parser_XmlDateListTreeParser extends tree_parser_XmlListTreeParser
{
	const LEVEL_ROOT  = 0;
	const LEVEL_YEAR  = 1;
	const LEVEL_MONTH = 2;
	const LEVEL_DAY   = 3;

	private $listLevel = self::LEVEL_DAY;


	/**
	 * @param Integer $level Constant value among LEVEL_YEAR, LEVEL_MONTH, LEVEL_DAY or self::LEVEL_ROOT.
	 */
	protected function setListLevel($level)
	{
		if ($level != self::LEVEL_DAY && $level != self::LEVEL_MONTH && $level != self::LEVEL_YEAR && $level != self::LEVEL_ROOT)
		{
			throw new Exception('Bad level value: must be LEVEL_YEAR, LEVEL_MONTH, LEVEL_DAY or LEVEL_ROOT');
		}
		$this->listLevel = $level;
	}


	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $level
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
	protected function getTreeChildren($document, $level)
	{
		$dateLabel = $document->getLabel();
		$matches = array();
		switch ($this->listLevel)
		{
			case self::LEVEL_DAY :
				if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $dateLabel, $matches))
				{
					return array();
				}
				$startdate = date_Converter::convertDateToGMT($matches[0] . ' 00:00:00');
				$endate = date_Calendar::getInstance($startdate)->add(date_Calendar::DAY, 1)->toString();
				break;

			case self::LEVEL_MONTH :
				if (!preg_match('/^\d{4}\-\d{2}/', $dateLabel, $matches))
				{
					return array();
				}
				$startdate = date_Converter::convertDateToGMT($matches[0] . '-01 00:00:00');
				$endate = date_Calendar::getInstance($startdate)->add(date_Calendar::MONTH, 1)->toString();
				break;
			case self::LEVEL_YEAR :
				if (!preg_match('/^\d{4}/', $dateLabel, $matches))
				{
					return array();
				}
				$startdate = date_Converter::convertDateToGMT($matches[0] . '-01-01 00:00:00');
				$endate = date_Calendar::getInstance($startdate)->add(date_Calendar::YEAR, 1)->toString();
				break;
		}

		$componentArray = array();
		foreach ($this->childrenTypes as $childType)
		{
			$query = $this->getPersitentProvider()->createQuery($childType);
			if ($this->listLevel != self::LEVEL_ROOT || ! $document instanceof generic_persistentdocument_rootfolder)
			{
				$query->add(Restrictions::ge('creationdate', $startdate));
				$query->add(Restrictions::lt('creationdate', $endate));
			}
			$componentArray = array_merge($componentArray, $query->find());
		}
		return $componentArray;
	}
}