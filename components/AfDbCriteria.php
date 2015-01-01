<?php

/**
 * AfDbCriteria class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.components
 */

/**
 * This class extends CDbCriteria to provide methods to easily add advanced
 * filter conditions.
 * 
 * As long as you aren't using an extended version of CDbCriteria, you can make
 * use of this class. It should be instantiated using the
 * AdvancedFilters::createCriteria() method.
 */
class AfDbCriteria extends CDbCriteria
{
	private $config;
	
	/**
	 * Construct a new criteria object.
	 * 
	 * @param array $data the initial property values to pass to the base
	 * CDbCriteria class.
	 * @param array $config override the application level AdvancedFilters
	 * configuration.
	 */
	public function __construct($data=array(), $config=array())
	{
		parent::__construct($data);
		
		$this->config = $config;
	}
	
	/**
	 * Add an advanced filter condition to the existing criteria.
	 * 
	 * @param string $columnExpression the disambiguated column name (or a
	 * valid SQL expression).
	 * @param string $filterExpression the entered filter expression.
	 * @param string $operator the operator used to concatenate the new
	 * condition with the existing one. Defaults to 'AND'.
	 * @param array $config override the application and instance level
	 * AdvancedFilters configuration.
	 * @return AfDbCriteria the criteria object to allow chaining.
	 */
	public function addAdvancedFilterCondition($columnExpression,
			$filterExpression, $operator='AND', $config=array())
	{
		// Merge the existing config with the provided config
		$config = CMap::mergeArray($this->config, $config);
		
		// Construct a parser object with the merged criteria
		$afParser = new AfParser($columnExpression, $filterExpression, $config);
		
		// Merge in the criteria returned from the filter parser
		$this->mergeWith($afParser->getCriteria(), $operator);
		
		return $this;
	}
}
