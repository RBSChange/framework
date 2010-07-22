<?php
/**
 * @package framework.tree.parser
 */
class tree_parser_XmlDateTreeParser extends tree_parser_XmlTreeParser
{

	/**
     * Create a DOM node based on the given document.
     *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return DOMElement
	 */
	protected function createNodeFromDocument($document)
	{
		$lang = RequestContext::getInstance()->getLang();
		$isContextLangAvailable = $document->isContextLangAvailable();
		$langs = $document->getI18nInfo()->getLangs();

		$label = $isContextLangAvailable ? $document->getI18nInfo()->getLabel() : $document->getI18nInfo()->getVoLabel();

		$correction = ($document->getPersistentModel()->useCorrection() && $document->getCorrectionofid());
		$publicated = $this->filterPublicatedTreeNodes($document);
		$publishable = $this->filterPublishableTreeNodes($document);

		if ($document instanceof generic_persistentdocument_folder)
		{
			if (preg_match('/^\d{4}\-(\d{2})(\-(\d{2}))?$/', $label, $matches))
			{
				switch (count($matches))
				{
					case 2 :
						$label = date_DateFormat::format($label.'-01 00:00:00', 'F');
						break;

					case 4 :
						$label = $matches[3];
						break;
				}
			}
		}

		return $this->createNode(
			$document->getId(),
			$label,
			$document->getDocumentModelName(),
			$lang,
			join(' ', $langs),
			$isContextLangAvailable,
			$document->getPublicationstatus(),
			$document->getModificationdate(),
			$publicated,
			$publishable,
			$correction
			);
	}
}