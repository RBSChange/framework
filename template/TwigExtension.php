<?php

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class f_template_TwigExtension extends AbstractExtension
{
	public function getFilters()
	{
		return [
			new TwigFilter('trans', [$this, 'transFilter']),
		];
	}

	public function getFunctions()
	{
		return [
			new Twig_Function('path', [$this, 'pathFunction'], ['is_safe' => ['html'], 'needs_context' => true, 'needs_environment' => true]),
		];
	}

	public function transFilter($key, $formatters = [], $replacements = [])
	{
		return LocaleService::getInstance()->transFO($key, $formatters, $replacements);
	}

	public function pathFunction(Twig_Environment $env, $context, ...$vars)
	{
		if (!isset($vars[0])) {
			throw new BaseException('"path" Twig function requires at least 1 parameter');
		}

		$documentId = 0;
		$lang = null;

		$config = $vars[0];
		if (is_array($config)) {
			foreach ($config as $key => $value) {
				switch ($key) {
					case 'document':
						$documentId = $value->getId();
						break;

					case 'lang':
						$lang = $value;
						break;
				}
			}
		} else {
			// TODO
		}

		$websiteId = website_WebsiteModuleService::getInstance()->getCurrentWebsite()->getId();

		if ($documentId) {
			return PHPTAL_Php_Attribute_CHANGE_link::getUrl($documentId, $lang, [], '', $websiteId);
		}

		return '';
	}
}
