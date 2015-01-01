<?php

/**
 * AdvancedFilters class file.
 * 
 * @author Keith Burton
 * @package advancedfilters
 */

// Define a different path alias for this extension in the config file to
// override this default.
defined('ADVANCED_FILTERS_BASE_PATH_ALIAS') or
		define('ADVANCED_FILTERS_BASE_PATH_ALIAS', 'advancedFiltersBasePath');

// Set path alias and import extension sub-directories
Yii::setPathOfAlias(ADVANCED_FILTERS_BASE_PATH_ALIAS, dirname(__FILE__));
Yii::import(ADVANCED_FILTERS_BASE_PATH_ALIAS . '.components.*');
Yii::import(ADVANCED_FILTERS_BASE_PATH_ALIAS . '.dbhelpers.*');
Yii::import(ADVANCED_FILTERS_BASE_PATH_ALIAS . '.filters.*');

/**
 * This class is the application component for the advanced filters extension.
 * All interaction with the extension should be initiated through this class.
 * 
 * If you're not using an extended version of CDbCriteria, the easiest way to
 * use this extension is to create an AfDbCriteria object. You can use this
 * in the same way as CDbCriteria, with an additional method to add advanced
 * filter conditions:
 * 
 * $criteria = Yii::app()->advancedFilters->createCriteria();
 * $criteria->addInCondition('id', array(1, 2, 3, 4));
 * $criteria->addAdvancedFilterCondition('code', '/^[A-C]/');
 * $items = Item::model()->findAll($criteria);
 * 
 * If you have extended CDbCriteria, you can instead use the following method to
 * update your criteria:
 * 
 * $criteria = new YourCriteriaClass;
 * $criteria->addInCondition('id', array(1, 2, 3, 4));
 * Yii::app()->advancedFilters
 *		->addAdvancedFilterCondition($criteria, 'code', '/^[A-C]/');
 * $items = Item::model()->findAll($criteria);
 * 
 * All configuration is passed through to the AfParser class. The
 * filterConfig array is used to override the default config for the inbuilt
 * filter classes and to add new classes.
 * 
 * To override the properties of an existing filter class, use one of the
 * predefined array keys: 'range', 'exact', 'substring', 'regex', 'default'.
 * 
 * To add a new filter, add an array with an unused key in the following format:
 * 
 * 'advancedFilters'=>array(
 *     'example'=>array(
 *         'class'=>'path.to.ExampleFilterClass',
 *         'priority'=>25,
 *     ),
 * ),
 * 
 * The filter class should extend AfBaseFilter.
 * 
 * The priority defines when the filter is processed in relation to the other
 * filters. Filters with lower numbers are processed first, and the first filter
 * that accepts a pattern segment will process that segment. You can change the
 * order of default filters by altering their priority in the same way.
 * 
 * You can deactivate any filter by setting its 'active' property to false.
 * 
 * Each filter class only receives a segment of the entered filter expression
 * produced by splitting the expression on the "and" and "or" delimiters and
 * removing any invert prefix and suffix. Whitespace around the segment is
 * also removed.
 */
class AdvancedFilters extends CApplicationComponent
{
	/**
	 * @var string the string used to 'or' filter expressions together.
	 * Set this to an empty string to remove this functionality.
	 */
	public $orDelimiter;
	
	/**
	 * @var string the string used to 'and' filter expressions together.
	 * Set this to an empty string to remove this functionality.
	 */
	public $andDelimiter;
	
	/**
	 * @var string the string which can be prepended to a delimiter string to
	 * allow its use within a filter expression.
	 * Set this to an empty string to disallow escaping.
	 */
	public $escapeSequence;
	
	/**
	 * @var string the string which can be prepended to a filter expression in
	 * order to invert its logic and return the opposite results.
	 * You can specify values for both $invertLogicPrefix and
	 * $invertLogicSuffix to require that the expression be enclosed
	 * between two specific strings in order to invert the logic.
	 * Set both to an empty string to prevent logic inversion.
	 */
	public $invertLogicPrefix;
	
	/**
	 * @var string the string which can be appended to a filter expression in
	 * order to invert its logic and return the opposite results.
	 * You can specify values for both $invertLogicPrefix and
	 * $invertLogicSuffix to require that the expression be enclosed
	 * between two specific strings in order to invert the logic.
	 * Set both to an empty string to prevent logic inversion.
	 */
	public $invertLogicSuffix;
	
	/**
	 * @var CDbConnection|string either a CDbConnection object or the string
	 * name of an application component representing a CDbConnection.
	 * Defaults to 'db'.
	 */
	public $dbConnection;
	
	/**
	 * @var array mapping between PDO driver and database helper class name.
	 * Each database helper must extend AfBaseDbHelper.
	 * If the $dbConnection has a driver name that is not specified in this
	 * array, or it maps to null, an AfException will be thrown.
	 */
	public $driverMap;
	
	/**
	 * @var array the default filters to load, and the priorities of each.
	 * Lower priority values mean that the pattern will be tested against the
	 * filter earlier, so more specific filters should be given a lower number
	 * than more general filters.
	 * Override the 'active' property to specify whether each filter should be
	 * used when processing filter expressions. The default filter cannot be
	 * deactivated.
	 * Any additional configuration will be applied to the specific filter class
	 * when it is instantiated.
	 */
	public $filterConfig;
	
	/**
	 * Instantiates and returns a new AfDbCriteria object.
	 * Applies any application level configuration defined in your config file,
	 * which can be overridden using the $config parameter.
	 * 
	 * Use this method if you have not extended CDbCriteria with a customised
	 * class.
	 * 
	 * @param array $data the initial property values to pass to the base
	 * CDbCriteria class.
	 * @param array $config override the application level AdvancedFilters
	 * configuration.
	 * @return AfDbCriteria the configured criteria object.
	 */
	public function createCriteria($data=array(), $config=array())
	{
		// Merge the default properties with the provided config
		$config = CMap::mergeArray(get_object_vars($this), $config);
		
		return new AfDbCriteria($data, $config);
	}
	
	/**
	 * Add an advanced filter condition to an existing instance of CDbCriteria
	 * or a class extending CDbCriteria.
	 * 
	 * Use this method if you have extended CDbCriteria with a customised class.
	 * 
	 * @param CDbCriteria $criteria the criteria to update.
	 * @param string $columnExpression the disambiguated column name (or a
	 * valid SQL expression).
	 * @param string $filterExpression the entered filter expression.
	 * @param string $operator the operator used to concatenate the new
	 * condition with the existing one. Defaults to 'AND'.
	 * @param array $config override the application level AdvancedFilters
	 * configuration.
	 */
	public function addAdvancedFilterCondition($criteria, $columnExpression,
			$filterExpression, $operator='AND', $config=array())
	{
		$newCriteria = $this->createCriteria();
		
		$newCriteria->addAdvancedFilterCondition($columnExpression,
				$filterExpression, $operator, $config);
		
		$criteria->mergeWith($newCriteria);
	}
	
	/**
	 * Gets the version number of this extension.
	 * 
	 * @return string the version number.
	 */
	public function getVersion()
	{
		return '1.0.0';
	}
}

