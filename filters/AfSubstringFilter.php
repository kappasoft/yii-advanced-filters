<?php

/**
 * AfSubstringFilter class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.filters
 */

/**
 * This filter handles matches of an exact substring.
 */
class AfSubstringFilter extends AfBaseFilter
{
	/**
	 * @var string the prefix string to identify substring expressions.
	 */
	public $prefix = '#';
	
	/**
	 * @var string the suffix string to identify substring expressions.
	 */
	public $suffix = '#';
	
	private $searchString;
	
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
		
		$criteria->addSearchCondition($this->columnExpression,
				$this->searchString, true, 'AND',
				$this->invertLogic ? 'NOT LIKE' : 'LIKE');
		
		return $criteria;
	}
	
	private $_expressionAccepted;
	private function parseExpression()
	{
		// Only need to parse the expression once
		if ($this->_expressionAccepted !== null)
			return $this->_expressionAccepted;
		
		$this->_expressionAccepted = false;
		
		$result = self::stripPrefixSuffixString($this->filterExpression,
				$this->prefix, $this->suffix);
		
		if ($result !== false)
		{
			$this->_expressionAccepted = true;
			$this->searchString = $result;
		}
		
		return $this->_expressionAccepted;
	}
}
