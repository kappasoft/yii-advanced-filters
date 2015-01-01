<?php

/**
 * AfMssqlDbHelper class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.dbhelpers
 */

/**
 * A generic database helper for SQL Server. If you are using an SQL Server
 * version of 2012 or later, you should instead use the more robust
 * AfMssql2012DbHelper class, by overriding the $driverMap property in the
 * configuration of the advanced filters extension.
 */
class AfMssqlDbHelper extends AfBaseDbHelper
{
	/**
	 * Provides conversion to decimal for SQL Server versions earlier than 2012.
	 * 
	 * SQL Server stops processing records as soon as it hits an invalid cast.
	 * To support older versions of SQL Server, this implementation checks the
	 * value length and uses ISNUMERIC rather than the more reliable TRY_CAST
	 * function. If you're using SQL Server 2012 or later, you should use the
	 * AfMssql2012DbHelper class instead.
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
		
		return "CASE WHEN LEN($dbExpression) <= $numDigits "
				. "AND ISNUMERIC(CONCAT(($dbExpression), 'e0')) = 1 "
				. "THEN CAST(($dbExpression) "
				. "AS DECIMAL($numDigits, $decimalPlaces)) "
				. "ELSE $nonNumericResultValue END";
	}
	
	/**
	 * Checks regular expression syntax using the very limited PATINDEX
	 * function. Documentation for this function's syntax can be found on MSDN.
	 * 
	 * It makes use of wildcards, so, assuming the extension is configured to
	 * use the default regex prefix and suffix, patterns might look like this:
	 * 
	 * /%[0-9]%/   The value contains a number.
	 * /[0-9]%/    The value starts with a number.
	 * /%[0-9]/    The value ends with a number.
	 * 
	 * @param CDbConnection $dbConnection the database connection object.
	 * @param string $regex the pattern to test.
	 * @return boolean true if the syntax is valid, false if not.
	 */
	public function checkRegex($dbConnection, $regex)
	{
		$query = "SELECT PATINDEX(:regex, '1')";
		
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
	 * Adds a regex condition using the very limited PATINDEX function.
	 * Documentation for this function's syntax can be found on MSDN.
	 * 
	 * It makes use of wildcards, so, assuming the extension is configured to
	 * use the default regex prefix and suffix, patterns might look like this:
	 * 
	 * /%[0-9]%/   The value contains a number.
	 * /[0-9]%/    The value starts with a number.
	 * /%[0-9]/    The value ends with a number.
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
		$paramName = ':regex' . md5($regex . $columnExpression);
		$operator = $invertLogic ? '=' : '>';
		$criteria->addCondition(
				"PATINDEX($paramName, ($columnExpression)) $operator 0");
		$criteria->params[$paramName] = $regex;
	}
}
