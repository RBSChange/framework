<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument.criteria
 */
class Restrictions
{
	private function __construct()
	{
		// empty
	}

	/**
	 * Apply an "equals" constraint to each property in the key set of a Array
	 * @param array $propertyNameValues
	 * For example: allEq(array("author" => "me", "contributor" => "you"))
	 */
	static function allEq($propertyNameValues)
	{
		$conjunction = new f_persistentdocument_criteria_Conjunction;
		foreach ($propertyNameValues as $propertyName => $value)
		{
			$conjunction->add(self::eq($propertyName, $value));
		}
		return $conjunction;
	}

	/**
	 * Return the conjunction of 1..n expressions
	 * For example: andExp(Restrictions::eq("author", "me"), Restrictions::between("creationdate", "2007-01-31 00:00:00", "2007-02-28 00:00:00"))
	 */
	static function andExp()
	{
		$junction = new f_persistentdocument_criteria_Conjunction;
		foreach (func_get_args() as $criterion)
		{
			if (is_array($criterion))
			{
				foreach ($criterion as $c)
				{
					$junction->add($c);
				}
			}
			else
			{
				$junction->add($criterion);
			}
		}
		return $junction;
	}

	/**
   	 * Apply a "between" constraint to the named property
   	 * For example: between("creationdate", "2007-01-31 00:00:00", "2007-02-28 00:00:00")
   	 */
	static function between($propertyName, $min, $max)
	{
		return new f_persistentdocument_criteria_BetweenExpression($propertyName, $min, $max);
	}

	/**
	 * Apply an "equal" constraint to the named property
	 * @param string $propertyName
	 * @param mixed $value
	 * @param boolean $ignoreCase deprecated, use ieq($propertyName, $value) instead of eq($propertyName, $value, true)
	 * For example: eq("author", "me")
	 * @return SimpleExpression
	 */
	static function eq($propertyName, $value, $ignoreCase = false)
	{
		return new f_persistentdocument_criteria_SimpleExpression($propertyName, $value, '=', $ignoreCase);
	}

	/**
	 * Apply an "equal" constraint to two properties
	 */
	static function eqProperty($propertyName, $otherPropertyName)
	{
		return new f_persistentdocument_criteria_PropertyExpression($propertyName, $otherPropertyName, '=');
	}

	/**
	  * Apply a "greater than or equal" constraint to the named property
	  * For example: ge("creationdate", $today)
	  */
	static function ge($propertyName, $value)
	{
		return new f_persistentdocument_criteria_SimpleExpression($propertyName, $value, '>=');
	}

	/**
	 * Apply a "greater than or equal" constraint to two properties
	 *
	 */
	static function geProperty($propertyName, $otherPropertyName)
	{
		return new f_persistentdocument_criteria_PropertyExpression($propertyName, $otherPropertyName, '>=');
	}

	/**
	 * Apply a "greater than" constraint to the named property
	 * For example: gt("creationdate", $today)
	 */
	static function gt($propertyName, $value)
	{
		return new f_persistentdocument_criteria_SimpleExpression($propertyName, $value, '>');
	}

	/**
	 * Apply a "greater than" constraint to two properties
	 *
	 */
	static function gtProperty($propertyName, $otherPropertyName)
	{
		return new f_persistentdocument_criteria_PropertyExpression($propertyName, $otherPropertyName, '>');
	}

	/**
	 * Apply an "equal" constraint to the identifier property
	 * For example: idEq($documentId)
	 */
	static function idEq($value)
	{
		return new f_persistentdocument_criteria_SimpleExpression('id', $value, '=');
	}

	/**
	 * Apply an "equal ignore case" constraint to the named property,
	 * ignoring case
	 * For example: ieq("author", "mE")
	 */
	static function ieq($propertyName, $value)
	{
		return new f_persistentdocument_criteria_SimpleExpression($propertyName, $value, '=', true);
	}

	/**
	 * A case-insensitive "like", similar to Postgres ilike operator
	 * @param string $propertyName
	 * @param string $value
	 * @param MatchMode $matchMode
	 * For example: ilike("email", "@rBbS.fR", MatchMode::END)
	 * @see MatchMode
	 */
	static function ilike($propertyName, $value, $matchMode = null)
	{
		return new f_persistentdocument_criteria_LikeExpression($propertyName, $value, $matchMode, true);
	}

	/**
	 * @param string $propertyName
	 * @param string $value
	 */
	static function beginsWith($propertyName, $value)
	{
		return new f_persistentdocument_criteria_LikeExpression($propertyName, $value, MatchMode::START(), false);
	}
	
	/**
	 * @param string $propertyName
	 * @param string $value
	 */
	static function ibeginsWith($propertyName, $value)
	{
		return new f_persistentdocument_criteria_LikeExpression($propertyName, $value, MatchMode::START(), true);
	}
	
	/**
	 * @param string $propertyName
	 * @param string $value
	 */
	static function endsWith($propertyName, $value)
	{
		return new f_persistentdocument_criteria_LikeExpression($propertyName, $value, MatchMode::END(), false);
	}
	
	/**
	 * @param string $propertyName
	 * @param string $value
	 */
	static function iendsWith($propertyName, $value)
	{
		return new f_persistentdocument_criteria_LikeExpression($propertyName, $value, MatchMode::END(), true);
	}
	
	/**
	 * Apply an "in" constraint to the named property
	 */
	static function in($propertyName, $values)
	{
		return new f_persistentdocument_criteria_InExpression($propertyName, $values, false);
	}
	
	/**
	 * Apply an "notin" constraint to the named property
	 */
	static function notin($propertyName, $values)
	{
		return new f_persistentdocument_criteria_InExpression($propertyName, $values, true);
	}

	/**
	 * Constrain a collection valued property to be empty
	 *
	 * @param unknown_type $propertyName
	 */
	static function isEmpty($propertyName)
	{
		return new f_persistentdocument_criteria_EmptyExpression($propertyName);
	}

	/**
	 * Constrain a collection valued property to be non-empty
	 *
	 * @param unknown_type $propertyName
	 */
	static function isNotEmpty($propertyName)
	{
		return new f_persistentdocument_criteria_NotEmptyExpression($propertyName);
	}

	/**
	 * Apply an "is not null" constraint to the named property
	 *
	 * @param string $propertyName
	 */
	static function isNotNull($propertyName)
	{
		return new f_persistentdocument_criteria_NotNullExpression($propertyName);
	}

	/**
	 * Apply an "is null" constraint to the named property
	 *
	 * @param string $propertyName
	 */
	static function isNull($propertyName)
	{
		return new f_persistentdocument_criteria_NullExpression($propertyName);
	}

	/**
	 * Apply a "less than or equal" constraint to the named property
	 *
	 * @param string $propertyName
	 * @param mixed $value
	 */
	static function le($propertyName, $value)
	{
		return new f_persistentdocument_criteria_SimpleExpression($propertyName, $value, '<=');
	}

	/**
	 * Apply a "less than or equal" constraint to two properties
	 *
	 * @param string $propertyName
	 * @param string $otherPropertyName
	 */
	static function leProperty($propertyName, $otherPropertyName)
	{
		return new f_persistentdocument_criteria_PropertyExpression($propertyName, $otherPropertyName, '<=');
	}

	/**
	 *  Apply a "like" constraint to the named property
	 *
	 * @param string $propertyName
	 * @param mixed $value
	 */
	static function like($propertyName, $value, $matchMode = null, $ignoreCase = false)
	{
		return new f_persistentdocument_criteria_LikeExpression($propertyName, $value, $matchMode, $ignoreCase);
	}

	/**
	 *  Apply a "not like" constraint to the named property
	 *
	 * @param string $propertyName
	 * @param mixed $value
	 */
	static function notLike($propertyName, $value, $matchMode = null, $ignoreCase = false)
	{
		return new f_persistentdocument_criteria_LikeExpression($propertyName, $value, $matchMode, $ignoreCase, true);
	}
	
	/**
	 * Apply a "less than" constraint to the named property
	 *
	 * @param string $propertyName
	 * @param mixed $value
	 */
	static function lt($propertyName, $value)
	{
		return new f_persistentdocument_criteria_SimpleExpression($propertyName, $value, '<');
	}

	/**
	 *  Apply a "less than" constraint to two properties
	 *
	 * @param string $propertyName
	 * @param string $otherPropertyName
	 */
	static function ltProperty($propertyName, $otherPropertyName)
	{
		return new f_persistentdocument_criteria_PropertyExpression($propertyName, $otherPropertyName, '<');
	}

	/**
	 * Apply a "not equal" constraint to the named property
	 *
	 * @param string $propertyName
	 */
	static function ne($propertyName, $value)
	{
		return new f_persistentdocument_criteria_SimpleExpression($propertyName, $value, '!=');
	}

	/**
	 * Apply a "not equal" constraint to two properties
	 *
	 * @param string $propertyName
	 * @param string $otherPropertyName
	 */
	static function neProperty($propertyName, $otherPropertyName)
	{
		return new f_persistentdocument_criteria_PropertyExpression($propertyName, $otherPropertyName, '!=');
	}

	/**
	 * Return the disjuction of 1..n expressions
	 */
	static function orExp()
	{
		$junction = new f_persistentdocument_criteria_Disjunction;
		foreach (func_get_args() as $criterion)
		{
			if (is_array($criterion))
			{
				foreach ($criterion as $c)
				{
					$junction->add($c);
				}
			}
			else
			{
				$junction->add($criterion);
			}
		}
		return $junction;
	}

	/**
	 * Apply a "parentOf" constraint (in a tree).
	 * @param string $documentId
	 */
	static function parentOf($documentId)
	{
		return self::ancestorOf($documentId, 1);
	}

	/**
	 * Apply a "childOf" constraint (in a tree).
	 * @param string $documentId
	 */
	static function childOf($documentId)
	{
		return self::descendentOf($documentId, 1);
	}

	/**
	 * Apply a "siblingOf" constraint (in a tree).
	 * @param string $documentId
	 */
	static function siblingOf($documentId)
	{
		return new f_persistentdocument_criteria_SiblingOfExpression($documentId);
	}

	/**
	 * Apply a "ancestorOf" constraint (in a tree).
	 * @param string $documentId
	 * @param integer level
	 */
	static function ancestorOf($documentId, $level = -1)
	{
		return new f_persistentdocument_criteria_AncestorOfExpression($documentId, $level);
	}
	/**
	 * Apply a "previousSiblingOf" constraint (in a tree).
	 * @param string $documentId
	 */
	static function previousSiblingOf($documentId)
	{
		return new f_persistentdocument_criteria_PreviousSiblingOfExpression($documentId);
	}
	/**
	 * Apply a "nextSiblingOf" constraint (in a tree).
	 * @param string $documentId
	 */
	static function nextSiblingOf($documentId)
	{
		return new f_persistentdocument_criteria_NextSiblingOfExpression($documentId);
	}
	/**
	 * Apply a "descendentOf" constraint (in a tree).
	 * @param string $documentId
	 * @param integer $level
	 */
	static function descendentOf($documentId, $level = -1)
	{
		return new f_persistentdocument_criteria_DescendentOfExpression($documentId, $level);
	}

	/**
	 * Apply a "referenceOf" constraint.
	 * @param string $documentId
	 */
	static function referenceOf($documentId)
	{
		return new f_persistentdocument_criteria_ReferenceOfExpression($documentId);
	}

	/**
	 * Apply a "referenceBy" constraint.
	 * @param string $referenceDocumentId
	 */
	static function referencedBy($referenceDocumentId)
	{
		return new f_persistentdocument_criteria_ReferenceOfExpression($referenceDocumentId, true);
	}

	/**
	 * Apply an "hasTag" constraint
	 * @param string $tagName
	 */
	static function hasTag($tagName)
	{
		return new f_persistentdocument_criteria_HasTagExpression($tagName);
	}
	
	/**
	 * Apply an "isTagged" constraint
	 */
	static function isTagged()
	{
		return new f_persistentdocument_criteria_IsTaggedExpression();
	}

	//
	// Shortcuts section
	//

	/**
	 * Shorcut for <code>eq("publicationstatus", "PUBLISHED")</code>
	 * @return SimpleExpression
	 */
	static function published()
	{
		return self::eq("publicationstatus", "PUBLISHED");
	}
}