<?php

/**
 * AfBaseDbHelper class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.dbhelpers
 */

/**
 * The base class for all advanced filter database helpers.
 * 
 * The methods in this class are used by filters to add database specific
 * syntax to conditions.
 * 
 * The base class provides default functionality for database operations where
 * the syntax is common amongst multiple databases, and abstract methods that
 * must be overridden where the syntax is database specific.
 */
abstract class AfBaseDbHelper extends CComponent
{
	/**
	 * Alters a database expression so that null values are converted to an
	 * empty string.
	 * Override this method in child classes if the database uses different
	 * syntax.
	 * 
	 * @param string $dbExpression the expression to update.
	 * @return string the updated expression.
	 */
	public function convertNullToEmptyString($dbExpression)
	{
		return "COALESCE(($dbExpression), '')";
	}
	
	/**
	 * Alters a database expression so that empty strings are converted to null.
	 * Override this method in child classes if the database uses different
	 * syntax.
	 * 
	 * @param string $dbExpression the expression to update.
	 * @return string the updated expression.
	 */
	public function convertEmptyStringToNull($dbExpression)
	{
		return "NULLIF(($dbExpression), '')";
	}
	
	/**
	 * Alters a database expression so that strings are converted to decimals.
	 * 
	 * Values that can't be converted are set to the specific non-numeric
	 * result value, which should be an integer or null.
	 * 
	 * @param string $dbExpression the expression to update.
	 * @param integer $numDigits the maximum number of digits that the decimal
	 * number should contain.
	 * @param integer $decimalPlaces the number of decimal places that the
	 * resulting decimal should have.
	 * @param integer $nonNumericResultValue the integer value to use if an
	 * expression isn't recognised as a number. This can also be null.
	 * @return string the updated expression.
	 */
	abstract public function convertExpressionToDecimal($dbExpression,
			$numDigits, $decimalPlaces, $nonNumericResultValue);
	
	/**
	 * Verifies that the provided pattern is syntactically valid for the
	 * specific database. This may require a test query to be run.
	 * No default implementation is provided as each database uses its own
	 * syntax.
	 * 
	 * @param CDbConnection $dbConnection the database connection object.
	 * @param string $regex the pattern to test.
	 * @return boolean true if the syntax is valid, false if not.
	 */
	abstract public function checkRegex($dbConnection, $regex);
	
	/**
	 * Adds a regular expression condition to the provided criteria.
	 * No default implementation is provided as each database uses its own
	 * syntax.
	 * 
	 * @param CDbCriteria $criteria the criteria to update.
	 * @param string $columnExpression the column to search, or a valid
	 * expression.
	 * @param string $regex the pattern to match against.
	 * @param boolean $invertLogic whether the logic should be inverted to
	 * return the opposite query results.
	 */
	abstract public function addRegexCondition($criteria, $columnExpression,
			$regex, $invertLogic);
}
