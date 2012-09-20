<?php
class change_ConstraintsTranslator extends \Zend\I18n\Translator\Translator
{
	/**
	 * Get a translated message.
	 *
	 * @param  string $message
	 * @param  string $locale
	 * @param  string $textDomain
	 * @return string|null
	 */
	protected function getTranslatedMessage($message, $locale = null, $textDomain = 'default')
	{
		if (strpos($message, ' ') === false)
		{
			$ls = LocaleService::getInstance();
			$key = $textDomain . '.' . $message;
			$msg = $ls->isKey($key) ? $ls->trans($key) : null;
			Framework::fatal(__METHOD__ . ": $message, $locale, $textDomain = $msg");
			if ($msg === null)
			{
				f_util_ProcessUtils::printBackTrace();
			}
			return $msg;
		}
		return null;
	}
}