<?php

/**
 * AfRegexFilter class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.filters
 */

/**
 * This filter handles regular expression patterns.
 * 
 * The filter syntax is entirely determined by the database being queried.
 */
class AfRegexFilter extends AfBaseFilter
{
	/**
	 * @var string the prefix string to identify regex filter expressions.
	 */
	public $prefix = '/';
	
	/**
	 * @var string the suffix string to identify regex filter expressions.
	 */
	public $suffix = '/';
	
	private $pattern;
	
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
		
		$this->getDbHelper()->addRegexCondition($criteria,
				$this->columnExpression, $this->pattern, $this->invertLogic);
		
		return $criteria;
	}
	
	private $_expressionAccepted;
	private function parseExpression()
	{
		// Only need to parse the expression once
		if ($this->_expressionAccepted !== null)
			return $this->_expressionAccepted;
		
		$this->_expressionAccepted = false;
		
		// First strip the suffix and prefix and fail if they aren't found
		$result = self::stripPrefixSuffixString($this->filterExpression,
				$this->prefix, $this->suffix);
		
		if ($result === false)
			return $this->_expressionAccepted = false;
		
		// Fail if the database doesn't recognise the pattern syntax
		if (!$this->getDbHelper()->checkRegex(
				$this->getDbConnection(), $result))
			return $this->_expressionAccepted = false;
		
		// Expression was accepted so save the pattern
		$this->pattern = $result;
		
		return $this->_expressionAccepted = true;
	}
}
