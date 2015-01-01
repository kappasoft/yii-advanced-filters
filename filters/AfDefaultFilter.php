<?php

/**
 * AfDefaultFilter class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.filters
 */

/**
 * This filter acts as a catch-all and matches columns where every word in the
 * expression appears in any order.
 */
class AfDefaultFilter extends AfBaseFilter
{
	/**
	 * @var string the string used to split the expression into separate words.
	 * Any whitespace around the resulting words will be trimmed.
	 */
	public $wordDelimiter = ' ';
	
	/**
	 * The AfDefaultFilter class acts as a catch all, so accepts any expression.
	 * 
	 * @return true
	 */
	public function acceptsFilterExpression()
	{
		return true;
	}
	
	/**
	 * Builds a new CDbCriteria object based on the expression provided.
	 * 
	 * @return CDbCriteria the new criteria object.
	 */
	public function getCriteria()
	{
		$searchTerms = preg_split(
				'/\\s*' . preg_quote($this->wordDelimiter, '/') . '\\s*/i',
				$this->filterExpression, null, PREG_SPLIT_NO_EMPTY);
		
		$operator = $this->invertLogic ? 'OR' : 'AND';
		$like = $this->invertLogic ? 'NOT LIKE' : 'LIKE';
		
		$criteria = new CDbCriteria;
		
		foreach ($searchTerms as $searchTerm)
			$criteria->addSearchCondition($this->columnExpression, $searchTerm,
					true, $operator, $like);
		
		return $criteria;
	}
}
