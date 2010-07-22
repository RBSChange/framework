<?php
/**
 * @package framework.validation
 */
class validation_DateValidator extends validation_ValidatorImpl implements validation_Validator
{
		private $formatToRegexp = array(
		'd' => '\d{2}',
		'm' => '\d{2}',
		'Y' => '\d{4}',
		'H' => '\d{2}',
		'i' => '\d{2}',
		's' => '\d{2}'
		);

		private $specialRegexpChars = array('\\', ':', '?', '-', '^', '$', '(', ')', '[', ']', '{', '}', '.');

		private $orderedParts = array();

		private $humanReadableFormat = null;
		
		private $originalFormat = null;
	
		private $minDate = null;
		
		private $maxDate = null;
		
		private $errorType = 'format';

		/**
		 * Validate $data and append error message in $errors.
		 *
		 * @param validation_Property $Field
		 * @param validation_Errors $errors
		 *
		 * @return void
		 */
		protected function doValidate(validation_Property $field, validation_Errors $errors)
		{
			$value = strval($field->getValue());
			$matches = array();
			$rejected = false;
			if (preg_match('#'.$this->getParameter().'#', $value, $matches))
			{
				$parts = array();
				for ($i=1 ; $i<count($matches) && ! $rejected ; $i++)
				{
					$name  = $this->orderedParts[$i-1];
					$value = intval($matches[$i]);
					$parts[$name] = $value;
				}

				try
				{
					$originalFormat = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $parts['Y'], $parts['m'], $parts['d'], $parts['H'], $parts['i'], $parts['s']);
					$dateTime = date_Calendar::getInstance($originalFormat);
					$calculated = date_DateFormat::format($dateTime, 'Y-m-d H:i:s');
					$rejected = $originalFormat != $calculated;
					
					$dateCalculated = substr($calculated, 0, 10);
					
					if (!$rejected && $this->minDate != null && ($dateCalculated < $this->minDate))
					{
						$this->errorType = "min";
						$rejected = true;
					}
					
					if (!$rejected && $this->maxDate != null && ($dateCalculated > $this->maxDate))
					{
						$this->errorType = "max";
						$rejected = true;
					}
					
				}
				catch (InvalidDateException $e)
				{
					$rejected = true;
				}
			}
			else
			{
				$rejected = true;
			}

			if ($rejected)
			{
				$this->reject($field->getName(), $errors);
			}
		}


		/**
		 * Sets the value of the unique validator's parameter.
		 *
		 * @param mixed $value
		 */
		public function setParameter($value)
		{
			$values = explode("|", validation_StringValueParser::getValue($value));
			$value = $values[0];
			$this->originalFormat = $value;
			if (count($values) > 1)
			{
				$this->minDate = $values[1];
			}
			if (count($values) > 2)
			{
				$this->maxDate = $values[2];
			}
			$this->humanReadableFormat = '';

			$this->orderedParts = array();
			$regexp = '';
			$escaped = false;
			for ($i=0 ; $i<strlen($value) ; $i++)
			{
				$c = $value{$i};
				if ($c == '\\')
				{
					if ($escaped)
					{
						$regexp .= '\\';
					}
					$escaped = ! $escaped;
				}
				else
				{
					if ( isset($this->formatToRegexp[$c]) && ! $escaped )
					{
						$regexp .= '('.$this->formatToRegexp[$c].')';
						$this->orderedParts[] = $c;
						$this->humanReadableFormat .= f_Locale::translate('&framework.validation.validator.date.'.$c.';');
					}
					else if ($c == ' ')
					{
						$regexp .= '\s+';
						$this->humanReadableFormat .= ' ';
					}
					else
					{
						if ( in_array($c, $this->specialRegexpChars) )
						{
							$regexp .= '\\';
						}
						$regexp .= $c;
						$this->humanReadableFormat .= $c;
					}
					$escaped = false;
				}
			}

			parent::setParameter('^'.$regexp.'$');
		}


		protected function getMessage()
		{
			switch ($this->errorType)
			{
				case 'min' :
					$date = date_Calendar::getInstance($this->minDate);
					$dateformated = date_DateFormat::format($date, $this->originalFormat);
					return f_Locale::translate('&framework.validation.validator.Date.Min.message;', array('mindate' => $dateformated));
				case 'max' :
					$date = date_Calendar::getInstance($this->maxDate);
					$dateformated = date_DateFormat::format($date, $this->originalFormat);
					return f_Locale::translate('&framework.validation.validator.Date.Max.message;', array('maxdate' => $dateformated));
				default :
					return f_Locale::translate($this->getMessageCode(), array('format' => $this->humanReadableFormat));
					
			}
		}
}