<?php
class I18nConfig
{
	private $m_id;
	private $m_label;
	private $m_icon;
	
	private static $m_configs;
	
	public function getId()
	{
		return $this->m_id;
	}
	
	public function getLabel()
	{
		return $this->m_label;
	}	
	
	public function getIcon()
	{
		return $this->m_icon;
	}	
	
	private function __construct($id, $label, $icon)
	{
		$this->m_id = $id;
		$this->m_label = $label;
		$this->m_icon = $label;
	}
	
	
	public static function getConfig()
	{
		if (is_null(self::$m_configs))
		{
			$langs = RequestContext::getSupportedLanguages();
			self::$m_configs = array();
			foreach ($langs as $lang)
			{
				switch ($lang)
				{
					case 'fr':
						self::$m_configs[$lang] = new I18nConfig($lang, 'Français', 'flag_france');
						break;
					case 'en':
						self::$m_configs[$lang] = new I18nConfig($lang, 'Anglais', 'flag_great_britain');
						break;
					case 'de':
						self::$m_configs[$lang] = new I18nConfig($lang, 'Allemand', 'flag_germany');
						break;
					case 'bg':
						self::$m_configs[$lang] = new I18nConfig($lang, 'Bulgare', 'flag_bulgaria');
						break;
					case 'es':
						self::$m_configs[$lang] = new I18nConfig($lang, 'Espagnol', 'flag_spain');
						break;
					case 'it':
						self::$m_configs[$lang] = new I18nConfig($lang, 'Italien', 'flag_italy');
						break;
					case 'pt':
						self::$m_configs[$lang] = new I18nConfig($lang, 'Portugais', 'flag_portugal');
						break;
					case 'ar':
						self::$m_configs[$lang] = new I18nConfig($lang, 'Arabe', 'flag_kuwait');
						break;						
					default:
						self::$m_configs[$lang] = new I18nConfig($lang, $lang, 'flag_generic');
						break;
				}				
			}
		}
		return self::$m_configs;
	}
}
