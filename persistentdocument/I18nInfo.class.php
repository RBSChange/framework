<?php
class I18nInfo
{
	/**
	 * @example "fr"
	 * @var String
	 */
	private $m_vo;

	/**
	 * @example array("fr" => "fr label", ...)
	 * @var array
	 */
	private $m_labels = array();

	public function setVo($lang)
	{
		$this->m_vo = $lang;
	}

	public function getVo()
	{
		return $this->m_vo;
	}

	public function setLabel($lang, $label)
	{
		$this->m_labels[$lang] = $label;
	}

	public function removeLabel($lang)
	{
		if ($this->hasLabel($lang))
		{
			unset($this->m_labels[$lang]);
		}
	}

	public function getVoLabel()
	{
		if (is_null($this->m_vo) || !$this->hasLabel($this->m_vo))
		{
			return null;
		}
		return $this->m_labels[$this->m_vo];
	}

	public function setVoLabel($label)
	{
		$this->setLabel($this->m_vo, $label);
	}

	public function getLabel()
	{
		return $this->getLabelByLang(RequestContext::getInstance()->getLang());
	}

	public function getLabels()
	{
		return $this->m_labels;
	}

	public function isContextLangAvailable()
	{
		$contextLang = RequestContext::getInstance()->getLang();
		return ($contextLang == $this->m_vo) || ($this->hasLabel($contextLang));
	}

	public function isLangAvailable($lang)
	{
		return ($lang == $this->m_vo) || ($this->hasLabel($lang));
	}

	private function getLabelByLang($lang)
	{
		if ($this->hasLabel($lang))
		{
			return $this->m_labels[$lang];
		}
		// intcours - 2007-04-12 - return VO label if there's no label for the given lang :
		// return null;
		return $this->getVoLabel();
	}

	private function hasLabel($lang)
	{
		if (is_null($this->m_labels) || !array_key_exists($lang, $this->m_labels))
		{
			return false;
		}
		return true;
	}

	public function getLangs()
	{
		$langs = array($this->m_vo);
		if (count($this->m_labels) == 0)
		{
			return $langs;
		}
		else
		{
			return array_unique(array_merge($langs, array_keys($this->m_labels)));
		}
	}
	
	/**
	 * @return array
	 */
	public function toPersistentProviderArray()
	{
		$array = array('lang_vo' => $this->m_vo);
		foreach ($this->m_labels as $lang => $value) 
		{
			if ($value !== null)
			{
				$array['label_' . $lang] = $value;
			}
		}
		return $array;
	}
	
	/**
	 * Return NULL si $i18nInfos ne contient pas d'information de localisation
	 * @param array $i18nInfos
	 * @return I18nInfo
	 */
	public static function getInstanceFromArray($i18nInfos)
	{
		$instance = new I18nInfo();		
		foreach ($i18nInfos as $key => $value) 
		{
			if ($key === 'lang_vo')
			{
				$instance->m_vo = $value;
			} 
			elseif (strpos($key, 'label_') === 0 && strlen($key) === 8 && !is_null($value))
			{
				$instance->m_labels[substr($key, 6)] = $value;
			}	
		}
		
		if ($instance->m_vo === null)
		{
			return null; 	
		}
		return $instance;
	}
}