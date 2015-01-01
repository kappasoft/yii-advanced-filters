<?php

/**
 * AfRangeFilter class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.filters
 */

/**
 * This filter handles numeric ranges.
 * 
 * If a prefix, infix or suffix string is set to an empty string, it will not
 * be used when identifying the expression type.
 */
class AfRangeFilter extends AfBaseFilter
{
	/**
	 * @var string the prefix string for 'between' expressions.
	 */
	public $betweenPrefix = '';
	
	/**
	 * @var string the infix string for 'between' expressions.
	 */
	public $betweenInfix = ' to ';
	
	/**
	 * @var string the suffix string for 'between' expressions.
	 */
	public $betweenSuffix = '';
	
	/**
	 * @var string the prefix string for 'less than' expressions.
	 */
	public $lessThanPrefix = '<';
	
	/**
	 * @var string the suffix string for 'less than' expressions.
	 */
	public $lessThanSuffix = '';
	
	
	/**
	 * @var string the prefix string for 'less than or equal to' expressions.
	 */
	public $lessThanEqualPrefix = '<=';
	
	/**
	 * @var string the suffix string for 'less than or equal to' expressions.
	 */
	public $lessThanEqualSuffix = '';
	
	
	/**
	 * @var string the prefix string for 'greater than' expressions.
	 */
	public $greaterThanPrefix = '>';
	
	/**
	 * @var string the suffix string for 'greater than' expressions.
	 */
	public $greaterThanSuffix = '';
	
	
	/**
	 * @var string the prefix string for 'greater than or equal to' expressions.
	 */
	public $greaterThanEqualPrefix = '>=';
	
	/**
	 * @var string the suffix string for 'greater than or equal to' expressions.
	 */
	public $greaterThanEqualSuffix = '';
	
	
	/**
	 * @var string the prefix string for 'equal to' expressions.
	 */
	public $equalPrefix = '=';
	
	/**
	 * @var string the suffix string for 'equal to' expressions.
	 */
	public $equalSuffix = '';
	
	/**
	 * @var string the regular expression pattern used to identify numbers in
	 * the provided filter expression.
	 */
	public $numberPattern = '-?\\d*\\.?\\d+';
	
	/**
	 * @var integer the number of digits that will be used to represent
	 * database values once they are converted to a decimal format. Change this
	 * if your data contains values that are too large to fit in a decimal of
	 * this size.
	 */
	public $conversionNumDigits = 20;
	
	/**
	 * @var integer the number of decimal places that will be used when
	 * converting database values to a decimal format. Change this if your data
	 * contains values with more decimal places. You may also need to override
	 * the $conversionNumDigits property.
	 */
	public $conversionDecimalPlaces = 4;
	
	/**
	 * @var boolean whether columns which can't be converted to a number should
	 * be treated as having a zero value in numeric comparisons. If this is
	 * false, rows where the column is non-numeric will never be returned.
	 */
	public $treatNonNumericValuesAsZero = false;
	
	private $startOfRange;
	private $endOfRange;
	private $rangeIsInclusive = true;
	
	/**
	 * Overrides the parent implementation to include a cast to decimal.
	 * 
	 * If $this->treatNonNumericTextAsZero is false, non-numeric column values
	 * will be converted to null.
	 * 
	 * @return string the updated column expression.
	 */
	protected function getColumnExpression()
	{
		$columnExpression = parent::getColumnExpression();
		
		$nonNumericResultValue = $this->treatNonNumericValuesAsZero ? 0 : null;
		
		return $this->getDbHelper()->convertExpressionToDecimal(
				$columnExpression, $this->conversionNumDigits,
				$this->conversionDecimalPlaces, $nonNumericResultValue);
	}
	
	/**
	 * Determines whether the provided expression can be processed by this
	 * filter class.
	 * 
	 * @return boolean whether this class can process the expression.
	 */
	public function acceptsFilterExpression()
	{
		return $this->parseExpression();
	}
	
	/**
	 * Builds a new CDbCriteria object based on the expression provided.
	 * 
	 * @return CDbCriteria the new criteria object.
	 */
	public function getCriteria()
	{
		$this->parseExpression();
		
		$criteria = new CDbCriteria;
		
		$column = $this->columnExpression;
		
		$hash = md5($this->filterExpression . $this->columnExpression);
		$startParam = ':rangeStart' . $hash;
		$endParam = ':rangeEnd' . $hash;
		
		if ($this->startOfRange !== null)
		{
			$operator = $this->rangeIsInclusive
					? ($this->invertLogic ? '<' : '>=')
					: ($this->invertLogic ? '<=' : '>');
			
			$criteria->addCondition("$column $operator $startParam");
			$criteria->params[$startParam] = $this->startOfRange;
		}
		
		if ($this->endOfRange !== null)
		{
			$operator = $this->rangeIsInclusive
					? ($this->invertLogic ? '>' : '<=')
					: ($this->invertLogic ? '>=' : '<');
			
			// Merge type only applies for between and equal ranges
			$mergeType = $this->invertLogic ? 'OR' : 'AND';
			
			$criteria->addCondition("$column $operator $endParam",
					$mergeType);
			$criteria->params[$endParam] = $this->endOfRange;
		}
		
		return $criteria;
	}
	
	private $_expressionAccepted;
	private function parseExpression()
	{
		// Only need to parse the expression once
		if ($this->_expressionAccepted !== null)
			return $this->_expressionAccepted;
		
		$this->_expressionAccepted = true;
		
		$expression = $this->filterExpression;
		
		// First check for between matches and return early if found
		$betweenMatches = array();
		
		if (preg_match($this->getBetweenPattern(), $expression,
				$betweenMatches))
		{
			$this->startOfRange = $betweenMatches[1];
			$this->endOfRange = $betweenMatches[2];
			return $this->_expressionAccepted;
		}
		
		// Check for any other pattern matches
		$matches = array();
		
		if (preg_match($this->getLessThanPattern(), $expression, $matches))
		{
			$this->endOfRange = $matches[1];
			$this->rangeIsInclusive = false;
		}
		else if (preg_match($this->getLessThanEqualPattern(), $expression,
				$matches))
		{
			$this->endOfRange = $matches[1];
		}
		else if (preg_match($this->getGreaterThanPattern(), $expression,
				$matches))
		{
			$this->startOfRange = $matches[1];
			$this->rangeIsInclusive = false;
		}
		else if (preg_match($this->getGreaterThanEqualPattern(), $expression,
				$matches))
		{
			$this->startOfRange = $matches[1];
		}
		else if (preg_match($this->getEqualPattern(), $expression, $matches))
		{
			$this->startOfRange = $matches[1];
			$this->endOfRange = $matches[1];
		}
		else
		{
			$this->_expressionAccepted = false;
		}
		
		return $this->_expressionAccepted;
	}
	
	private function getBetweenPattern()
	{
		return '/^'
				. preg_quote($this->betweenPrefix, '/')
				. '\\s*(' . $this->numberPattern . ')\\s*'
				. preg_quote($this->betweenInfix, '/')
				. '\\s*(' . $this->numberPattern . ')\\s*'
				. preg_quote($this->betweenSuffix, '/')
				. '$/i';
	}
	
	private function getLessThanPattern()
	{
		return '/^'
				. preg_quote($this->lessThanPrefix, '/')
				. '\\s*(' . $this->numberPattern . ')\\s*'
				. preg_quote($this->lessThanSuffix, '/')
				. '$/i';
	}
	
	private function getLessThanEqualPattern()
	{
		return '/^'
				. preg_quote($this->lessThanEqualPrefix, '/')
				. '\\s*(' . $this->numberPattern . ')\\s*'
				. preg_quote($this->lessThanEqualSuffix, '/')
				. '$/i';
	}
	
	private function getGreaterThanPattern()
	{
		return '/^'
				. preg_quote($this->greaterThanPrefix, '/')
				. '\\s*(' . $this->numberPattern . ')\\s*'
				. preg_quote($this->greaterThanSuffix, '/')
				. '$/i';
	}
	
	private function getGreaterThanEqualPattern()
	{
		return '/^'
				. preg_quote($this->greaterThanEqualPrefix, '/')
				. '\\s*(' . $this->numberPattern . ')\\s*'
				. preg_quote($this->greaterThanEqualSuffix, '/')
				. '$/i';
	}
	
	private function getEqualPattern()
	{
		return '/^'
				. preg_quote($this->equalPrefix, '/')
				. '\\s*(' . $this->numberPattern . ')\\s*'
				. preg_quote($this->equalSuffix, '/')
				. '$/i';
	}
}
