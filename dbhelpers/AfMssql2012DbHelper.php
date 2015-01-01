<?php

/**
 * AfMssql2012DbHelper class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.dbhelpers
 */

/**
 * This database helper class can be used for SQL Server versions 2012 and
 * later. It offers more robust numeric conversion than the generic SQL Server
 * helper.
 * 
 * To use this class, override the extension's $driverMap property in your
 * application config.
 */
class AfMssql2012DbHelper extends AfMssqlDbHelper
{
	/**
	 * Overrides the generic SQL Server implementation to make use of the
	 * TRY_CAST function added in 2012. This is a more robust solution and
	 * should be used if you only need to deal with SQL Server versions from
	 * 2012 onwards.
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
		
		// Result for non-numeric expressions will be null
		$dbExpression = "TRY_CAST(($dbExpression) "
				. "AS DECIMAL($numDigits, $decimalPlaces))";
		
		// If non-numeric values should have an integer result, coalesce to the
		// correct value.
		if ($nonNumericResultValue !== null)
		{
			$nonNumericResultValue = (int)$nonNumericResultValue;
			$dbExpression = "COALESCE($dbExpression, $nonNumericResultValue)";
		}
		
		return $dbExpression;
	}
}
