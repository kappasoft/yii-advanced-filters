<?php

/**
 * AfMysqlDbHelper class file
 * 
 * @author Keith Burton
 * @package advancedfilters.dbhelpers
 */

/**
 * A generic database helper for MySQL.
 */
class AfMysqlDbHelper extends AfBaseDbHelper
{
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
	public function convertExpressionToDecimal($dbExpression, $numDigits,
			$decimalPlaces, $nonNumericResultValue)
	{
		$numDigits = (int)$numDigits;
		$decimalPlaces = (int)$decimalPlaces;
		$nonNumericResultValue = $nonNumericResultValue === null
				? 'NULL' : (int)$nonNumericResultValue;
		
		// If the required result for non-numeric expressions is zero, we can
		// use MySQL's default behavior
		if ($nonNumericResultValue === 0)
		{
			return "CAST(($dbExpression) "
					. "AS DECIMAL($numDigits, $decimalPlaces))";
		}
		
		// Otherwise we should check the value looks numeric and cast only if
		// it does, otherwise using the non numeric result value
		return "IF(($dbExpression) REGEXP '^-?[0-9]*.?[0-9]+$', "
				. "CAST(($dbExpression) AS DECIMAL($numDigits, $decimalPlaces))"
				. ", $nonNumericResultValue)";
	}
	
	/**
	 * Checks regular expression syntax using the REGEXP keyword. Documentation
	 * for this syntax can be found on the MySQL website.
	 * 
	 * This is checked against the database, as invalid syntax could otherwise
	 * cause an exception to be thrown when the data is fetched.
	 * 
	 * @param CDbConnection $dbConnection the database connection object.
	 * @param string $regex the pattern to test.
	 * @return boolean true if the syntax is valid, false if not.
	 */
	public function checkRegex($dbConnection, $regex)
	{
		$query = 'SELECT "1" REGEXP :regex';
		
		try
		{
			$dbConnection->createCommand($query)
					->query(array(':regex'=>$regex));
		}
		catch (Exception $ex)
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Adds a regex condition using the REGEXP keyword. Documentation for this
	 * syntax can be found on the MySQL website.
	 * 
	 * @param CDbCriteria $criteria the criteria to update.
	 * @param string $columnExpression the column to search, or a valid
	 * expression.
	 * @param string $regex the pattern to match against.
	 * @param boolean $invertLogic whether the logic should be inverted to
	 * return the opposite query results.
	 */
	public function addRegexCondition($criteria, $columnExpression,
			$regex, $invertLogic)
	{
		$criteria->addSearchCondition($columnExpression,
				$regex, false, 'AND', ($invertLogic ? 'NOT REGEXP' : 'REGEXP'));
	}
}
