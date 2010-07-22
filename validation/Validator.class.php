<?php
/**
 * @package framework.validation
 */
interface validation_Validator
{
	/**
	 * Validate $data and append error message in $errors.
	 *
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 * 
	 * @return void
	 */
	public function validate($field, $errors);
}