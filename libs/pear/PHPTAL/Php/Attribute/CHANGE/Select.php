<?php
// change:select
//
// <span change:select="min 1; max 50;" />
// <span change:select="listId 'modules_blabla/listid';" />

/**
 * @package phptal.php.attribute
 * @author intbonjf
 * 2007-11-30
 */
class PHPTAL_Php_Attribute_CHANGE_select extends PHPTAL_Php_Attribute
{
	public function start()
	{
		$this->expression = $this->extractEchoType($this->expression);

		$expressions = $this->tag->generator->splitExpression($this->expression);
		$min    = 'null';
		$max    = 'null';
		$listId = 'null';
		$name   = 'null';
		$defaultValue = 'null';
		$id = 'null';
		$firstLabel = 'null';
		$firstValue = 'null';
		$class = 'null';
		foreach ($expressions as $exp)
		{
			list($attribute, $value) = $this->parseSetExpression($exp);
			switch ($attribute)
			{
				case 'min' :
					$min = $this->evaluate($value);
					break;
				case 'max' :
					$max = $this->evaluate($value);
					break;
				case 'listId' :
					$listId = $this->evaluate($value);
					break;
				case 'name' :
					$name = $this->evaluate($value);
					break;
				case 'defaultValue' :
					$defaultValue = $this->evaluate($value);
					break;
				case 'id' :
					$id = $this->evaluate($value);
					break;
				case 'firstLabel' :
					if(f_util_StringUtils::beginsWith($value, "'") && f_util_StringUtils::endsWith($value, "'"))
					{
						$firstLabel = $value;
					}
					elseif(f_util_StringUtils::beginsWith($value, "&"))
					{
						$firstLabel = "'".$value."'";
					}
					else
					{
						$firstLabel = $this->evaluate($value);
					}
					break;
				case 'firstValue' :
					if(f_util_StringUtils::beginsWith($value, "'") && f_util_StringUtils::endsWith($value, "'"))
					{
						$firstValue = $value;
					}
					elseif(f_util_StringUtils::beginsWith($value, "&"))
					{
						$firstValue = "'".$value."'";
					}
					else
					{
						$firstValue = $this->evaluate($value);
					}
					break;
				case 'class' :
					$class = $this->evaluate($value);
					break;

			}
		}
		$code = $this->_getCode($name, $min, $max, $listId, $defaultValue, $id, $firstLabel, $firstValue, $class);
		$this->doEcho($code);
	}

	protected function _getCode($name, $min, $max, $listId, $defaultValue, $id, $firstLabel, $firstValue, $class)
	{
		$code = 'PHPTAL_Php_Attribute_CHANGE_select::buildSelect(' . $name . ', ' . $min . ', ' . $max . ', ' . $listId . ', ' . $defaultValue . ', ' . $id . ', ' . $firstLabel . ', ' . $firstValue . ', ' . $class . ')';
		return $code;
	}

	public static function buildSelect($name, $min, $max, $listId, $defaultValue, $id, $firstLabel, $firstValue, $class)
	{
		$html = '<select name="'.$name.'"';
		if(!is_null($id))
		{
			$html .= ' id="'.$id.'"';
		}
		if(!is_null($class))
		{
			$html .= ' class="'.$class.'"';
		}
		$html .= '>';

		if(!is_null($firstLabel))
		{
			$lang = RequestContext::getInstance()->getLang();
			$firstLabel = str_replace('&amp;', '&', $firstLabel);
			if(f_Locale::isLocaleKey($firstLabel.";"))
			{
				$firstLabel = f_Locale::translate($firstLabel.";", null, $lang);
			}
			$html .= '<option value="';
			if(!is_null($firstValue))
			{
				$firstValue = str_replace('&amp;', '&', $firstValue);
				if(f_Locale::isLocaleKey($firstValue.";"))
				{
					$firstValue = f_Locale::translate($firstValue.";", null, $lang);
				}
				$html .= $firstValue;
			}
			$html .= '">'. f_util_HtmlUtils::textToHtml($firstLabel) .'</option>';
		}
		if (!is_null($min) && !is_null($max))
		{
			for ($i=min($min, $max) ; $i<=max($min, $max) ; $i++)
			{
				$html .= '<option value="'.$i.'"';
				if ($i == $defaultValue)
				{
					$html .= ' selected="selected"';
				}
				$html .= ">".$i."</option>";
			}
		}
		else if (!is_null($listId))
		{
			$list = list_ListService::getInstance()->getDocumentInstanceByListId($listId);
			foreach ($list->getItems() as $item)
			{
				$html .= '<option value="'.$item->getValue().'"';
				if ($item->getValue() == $defaultValue)
				{
					$html .= ' selected="selected"';
				}
				$html .= ">". f_util_HtmlUtils::textToHtml($item->getLabel()) ."</option>";
			}
		}

		$html .= "</select>";
		return $html;
	}

	public function end()
	{
	}
}