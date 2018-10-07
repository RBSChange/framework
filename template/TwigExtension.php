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
			throw new BaseException('path Twig function requires at least 1 parameter');
		}

		$documentId = null;
		$lang = null;
		$anchor = null;
		$tag = null;
		$parameters = [];
		$home = false;
		$back = false;
		$module = null;
		$action = null;

		$expressions = $vars[0];
		if (is_array($expressions)) {
			foreach ($expressions as $attribute => $value) {
				switch ($attribute) {
					case 'back':
						$back = true;
						break 2;

					case 'module':
						$module = $value;
						break;

					case 'action':
						$action = $value;
						break;

					case 'document':
						$documentId = $value->getId();
						break;

					case 'documentId':
						$documentId = $value;
						break;

					case 'lang':
						$lang = $value;
						break;

					case 'anchor':
						$anchor = $value;
						break;

					case 'tag':
						$tag = $value;
						break;

					case 'params':
						$parameters = array_merge($parameters, $value);
						break;

					case 'home':
						$home = true;
						break;

					default:
						$parameters[$attribute] = $value;
						break;
				}
			}
		} else {
			if ($expressions instanceof f_persistentdocument_PersistentDocument) {
				$documentId = $expressions->getId();
			}
		}

		$websiteId = website_WebsiteModuleService::getInstance()->getCurrentWebsite()->getId();

		if ($module !== null) {
			if ($action === null)
			{
				$action = AG_DEFAULT_ACTION;
			}
			return PHPTAL_Php_Attribute_CHANGE_link::getRedirectionUrl($module, $action, $lang, $parameters, $anchor, $websiteId);
		}

		if ($back) {
			return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		}

		if ($home) {
			$documentId = $websiteId;
		}

		if ($documentId) {
			return PHPTAL_Php_Attribute_CHANGE_link::getUrl($documentId, $lang, $parameters, $anchor, $websiteId);
		}

		if ($tag !== null) {
			return PHPTAL_Php_Attribute_CHANGE_link::getTaggedPage($tag, $lang, $parameters, $anchor, $websiteId);
		}

		throw new BaseException('Missing expression for Twig path function');
	}
}
