<?php

/**
 * AfBaseFilter class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.filters
 */

/**
 * All filter classes should extend AfBaseFilter. It contains abstract functions
 * that must be implemented by child classes, as well as some helper functions
 * for common filter operations.
 */
abstract class AfBaseFilter extends CComponent
{
	/**
	 * @var boolean whether this filter should be included when processing
	 * filter expressions.
	 */
	public $active = true;
	
	/**
	 * @var integer the priority with which this should be processed. Smaller
	 * numbers are processed first.
	 */
	public $priority = 0;
	
	/**
	 * @var boolean whether null results from column expressions should be
	 * coalesced to empty strings. This allows the columns to be included in
	 * filter results.
	 */
	public $treatNullAsEmptyString = true;
	
	private $columnExpression;
	private $filterExpression;
	private $invertLogic = false;
	private $dbHelper;
	private $dbConnection;
	
	/**
	 * Constructor. Subclasses of AfBaseFilter should not be instantiated
	 * directly, but by using methods of the application component.
	 * 
	 * @param string $columnExpression the disambiguated column name (or a
	 * valid SQL expression).
	 * @param string $filterExpression the entered filter expression.
	 * @param boolean $invertLogic whether the condition logic should be
	 * inverted to return the opposite results.
	 * @param CDbConnection $dbConnection the database connection object.
	 * @param AfBaseDbHelper $dbHelper the database helper object.
	 */
	public function __construct($columnExpression, $filterExpression,
			$invertLogic, $dbConnection, $dbHelper)
	{
		$this->columnExpression = $columnExpression;
		$this->filterExpression = $filterExpression;
		$this->invertLogic = $invertLogic;
		$this->dbConnection = $dbConnection;
		$this->dbHelper = $dbHelper;
	}
	
	/**
	 * Gets the column expression, with the result coalesced to an empty string
	 * if $this->treatNullAsEmptyString is true.
	 * 
	 * @return string the column expression.
	 */
	protected function getColumnExpression()
	{
		return $this->treatNullAsEmptyString
				? $this->getDbHelper()
						->convertNullToEmptyString($this->columnExpression)
				: $this->columnExpression;
	}
	
	/**
	 * Gets the filter expression segment entered by the user.
	 * 
	 * @return string the entered filter expression segment.
	 */
	protected function getFilterExpression()
	{
		return $this->filterExpression;
	}
	
	/**
	 * Specifies whether the filter logic should be inverted in the resulting
	 * database criteria.
	 * 
	 * @return boolean whether the filter logic should be inverted.
	 */
	protected function getInvertLogic()
	{
		return $this->invertLogic;
	}
	
	/**
	 * Gets a database helper class to provide database specific syntax for
	 * filter criteria.
	 * 
	 * @return AfBaseDbHelper the database helper object.
	 */
	protected function getDbHelper()
	{
		return $this->dbHelper;
	}
	
	/**
	 * Gets a connection to the relevant database so that filters can perform
	 * queries directly, in order to validate syntax.
	 * 
	 * @return CDbConnection the connection object.
	 */
	protected function getDbConnection()
	{
		return $this->dbConnection;
	}
	
	/**
	 * Instantiate and return the first filter that can process the provided
	 * filter expression.
	 * 
	 * This is guaranteed to return a valid class as the AfDefaultFilter
	 * responds to any expression and is returned if no other filter can process
	 * the expression.
	 * 
	 * @param string $columnExpression the column or expression to which to
	 * apply this filter.
	 * @param string $filterExpression the string containing the filter pattern.
	 * @param boolean $invertLogic whether the condition logic should be
	 * inverted.
	 * @param CDbConnection $dbConnection the database connection object.
	 * @param AfBaseDbHelper $dbHelper the helper to use when dealing with
	 * database specific syntax.
	 * @param array $filterConfig an array of configuration for all available
	 * filter types.
	 * @return AfBaseFilter an instance of a subclass of AfBaseFilter.
	 */
	public static function createFilter($columnExpression, $filterExpression,
			$invertLogic, $dbConnection, $dbHelper, $filterConfig)
	{
		// Sort the available filters by priority value
		usort($filterConfig, function($a, $b){
			$aPriority = isset($a['priority']) ? $a['priority'] : 0;
			$bPriority = isset($b['priority']) ? $b['priority'] : 0;
			
			return $aPriority == $bPriority ? 0
					: ($aPriority < $bPriority ? -1 : 1);
		});
		
		foreach ($filterConfig as $classConfig)
		{
			// Ignore filters that have been marked as not active
			if (isset($classConfig['active']) && !$classConfig['active'])
				continue;
			
			$filter = Yii::createComponent($classConfig, $columnExpression,
					$filterExpression, $invertLogic, $dbConnection,
					$dbHelper);
			
			if ($filter->acceptsFilterExpression())
				return $filter;
		}
		
		// If no matching filter has been found, return the default filter
		return Yii::createComponent('AfDefaultFilter', $columnExpression,
				$filterExpression, $invertLogic, $dbConnection, $dbHelper);
	}
	
	/**
	 * A generic helper function to strip a specified prefix and suffix from
	 * a string if both are found.
	 * The prefix and/or suffix can be an empty string, to allow matching at
	 * only one side of the string.
	 * Comparison of the prefix and suffix is case insensitive.
	 * 
	 * If the string is surrounded by the specified prefix and suffix, the
	 * stripped string will be returned with any whitespace intact. Otherwise,
	 * boolean false is returned.
	 * 
	 * @param string $string the string to test and strip.
	 * @param string $prefix the prefix to compare. Use an empty string to
	 * ignore the prefix.
	 * @param string $suffix the suffix to compare. Use an empty string to
	 * ignore the suffix.
	 * @return boolean|string the stripped string if the specified prefix and
	 * suffix are matched. Boolean false otherwise.
	 */
	protected static function stripPrefixSuffixString($string, $prefix, $suffix)
	{
		$prefix = strtolower($prefix);
		$suffix = strtolower($suffix);
		
		$prefixLength = strlen($prefix);
		$suffixLength = strlen($suffix);
		$stringLength = strlen($string);
		
		// Not accepted if the string is shorter than the prefix and suffix
		if ($stringLength < $prefixLength + $suffixLength)
			return false;
		
		// Not accepted if the prefix doesn't match
		if (strtolower(substr($string, 0, $prefixLength)) !== $prefix)
			return false;
		
		// Not accepted if the suffix doesn't match
		if (strtolower(substr($string, -$suffixLength, $suffixLength))
				!== $suffix)
			return false;
		
		// Return empty string if only the prefix and suffix are found
		if ($stringLength === $prefixLength + $suffixLength)
			return '';
		
		// Strip the prefix and suffix and return the resulting string
		return substr($string, $prefixLength,
				$stringLength - $prefixLength - $suffixLength);
	}
	
	/**
	 * The implementation of this method should analyse the filter expression
	 * to determine whether this filter accepts the expression.
	 * 
	 * @return boolean whether the filter accepts the provided expression.
	 */
	abstract public function acceptsFilterExpression();
	
	/**
	 * Gets a CDbCriteria object with the filter conditions applied. The
	 * criteria can be merged with an existing criteria object.
	 * 
	 * @return CDbCriteria the new criteria object.
	 */
	abstract public function getCriteria();
}
