<?php

/**
 * AfParser class file.
 * 
 * @author Keith Burton
 * @package advancedfilters.components
 */

/**
 * This class is responsible for parsing the entered expression into segments
 * split by the "and" and "or" delimiters, and determining whether logic should
 * be inverted.
 * 
 * The resulting segments are passed to AfBaseFilter to construct an
 * appropriate filter object.
 */
class AfParser extends CComponent
{
	/**
	 * @var string the string used to 'or' filter expressions together.
	 * Set this to an empty string to remove this functionality.
	 */
	public $orDelimiter = '|';
	
	/**
	 * @var string the string used to 'and' filter expressions together.
	 * Set this to an empty string to remove this functionality.
	 */
	public $andDelimiter = '&';
	
	/**
	 * @var string the string which can be prepended to a delimiter string to
	 * allow its use within a filter expression.
	 * Set this to an empty string to disallow escaping.
	 */
	public $escapeSequence = '\\';
	
	/**
	 * @var string the string which can be prepended to a filter expression in
	 * order to invert its logic and return the opposite results.
	 * You can specify values for both $invertLogicPrefix and
	 * $invertLogicSuffix to require that the expression be enclosed
	 * between two specific strings in order to invert the logic.
	 * Set both to an empty string to prevent logic inversion.
	 */
	public $invertLogicPrefix = '!';
	
	/**
	 * @var string the string which can be appended to a filter expression in
	 * order to invert its logic and return the opposite results.
	 * You can specify values for both $invertLogicPrefix and
	 * $invertLogicSuffix to require that the expression be enclosed
	 * between two specific strings in order to invert the logic.
	 * Set both to an empty string to prevent logic inversion.
	 */
	public $invertLogicSuffix = '';
	
	/**
	 * @var CDbConnection|string either a CDbConnection object or the string
	 * name of an application component representing a CDbConnection.
	 * Defaults to 'db'.
	 */
	public $dbConnection = 'db';
	
	/**
	 * @var array mapping between PDO driver and database helper class path.
	 * Each database helper must extend AfBaseDbHelper.
	 * If the $dbConnection has a driver name that is not specified in this
	 * array, or it maps to null, an AfException will be thrown.
	 */
	public $driverMap = array(
		'pgsql'=>null,               // PostgreSQL
		'mysqli'=>'AfMysqlDbHelper', // MySQL
		'mysql'=>'AfMysqlDbHelper',  // MySQL
		'sqlite'=>null,              // sqlite 3
		'sqlite2'=>null,             // sqlite 2
		'mssql'=>'AfMssqlDbHelper',  // Mssql driver on windows
		'dblib'=>'AfMssqlDbHelper',  // dblib drivers on linux
		'sqlsrv'=>'AfMssqlDbHelper', // Mssql
		'oci'=>null,                 // Oracle driver
	);
	
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
	public $filterConfig = array(
		'range'=>array(
			'class'=>'AfRangeFilter',
			'priority'=>10,
		),
		'exact'=>array(
			'class'=>'AfExactFilter',
			'priority'=>20,
		),
		'substring'=>array(
			'class'=>'AfSubstringFilter',
			'priority'=>30,
		),
		'regex'=>array(
			'class'=>'AfRegexFilter',
			'priority'=>40,
		),
		'default'=>array(
			'class'=>'AfDefaultFilter',
			'priority'=>50,
		),
	);
	
	/**
	 * @var array Holds a two level array of "anded" filters grouped inside
	 * "ored" filters.
	 */
	private $filters = array();
	
	/**
	 * Parses the provided filter expression into segments and creates an array
	 * of filters to process the full expression.
	 * 
	 * This class should not be instantiated directly, but by using methods
	 * of the AdvancedFilters application component.
	 * 
	 * @param string $columnExpression the disambiguated column name (or a
	 * valid SQL expression).
	 * @param string $filterExpression the entered filter expression.
	 * @param array $config the configuration array.
	 */
	public function __construct($columnExpression, $filterExpression, $config)
	{
		$this->applyConfig($config);
		
		// Get concrete database connection and helper objects
		$dbConnection = $this->getDbConnectionObject();
		$dbHelper = $this->getDbHelper();
		
		$filterExpressionParts = $this->tokenize(trim($filterExpression));
		
		foreach ($filterExpressionParts as $filterExpressionPartOr)
		{
			$andFilters = array();
			
			foreach ($filterExpressionPartOr as $filterExpressionPartAnd)
			{
				$invertLogic = $this->processLogicInversion(
						$filterExpressionPartAnd);
				
				$andFilters[] = AfBaseFilter::createFilter(
						$columnExpression, $filterExpressionPartAnd,
						$invertLogic, $dbConnection, $dbHelper,
						$this->filterConfig);
			}
			
			$this->filters[] = $andFilters;
		}
	}
	
	/**
	 * Returns a CDbCriteria object created by merging the criteria returned
	 * from all filters.
	 * 
	 * @return CDbCriteria the criteria object.
	 */
	public function getCriteria()
	{
		$orCriteria = new CDbCriteria;
		
		foreach ($this->filters as $andFilters)
		{
			$andCriteria = new CDbCriteria;
			
			foreach ($andFilters as $andFilter)
				$andCriteria->mergeWith($andFilter->getCriteria(), 'AND');
			
			$orCriteria->mergeWith($andCriteria, 'OR');
		}
		
		return $orCriteria;
	}
	
	/**
	 * Updates this object's properties from a configuration array.
	 * 
	 * @param array $config the configuration array.
	 */
	private function applyConfig($config)
	{
		if (isset($config['orDelimiter']))
			$this->orDelimiter = $config['orDelimiter'];
		
		if (isset($config['andDelimiter']))
			$this->andDelimiter = $config['andDelimiter'];
		
		if (isset($config['invertLogicPrefix']))
			$this->invertLogicPrefix = $config['invertLogicPrefix'];
		
		if (isset($config['invertLogicSuffix']))
			$this->invertLogicSuffix = $config['invertLogicSuffix'];
		
		if (isset($config['escapeSequence']))
			$this->escapeSequence = $config['escapeSequence'];
		
		if (isset($config['dbConnection']))
			$this->dbConnection = $config['dbConnection'];
		
		if (isset($config['driverMap']))
			$this->driverMap = CMap::mergeArray($this->driverMap,
					$config['driverMap']);
		
		if (isset($config['filterConfig']))
			$this->filterConfig = CMap::mergeArray($this->filterConfig,
					$config['filterConfig']);
	}
	
	/**
	 * Split the provided string on the $orDelimiter and $andDelimiter
	 * characters, removing whitespace on either side of the delimiters.
	 * $escapeSequence, $orDelimiter and $andDelimiter strings can be
	 * escaped with a preceding $escapeSequence string.
	 * 
	 * @param string $filterExpression the expression to split.
	 * @return array a two level array of "and" patterns grouped inside "or"
	 * patterns.
	 */
	private function tokenize($filterExpression)
	{
		// A two level array of "anded" expressions grouped inside "ored"
		// expressions
		$tokens = array();
		
		// First split on unescaped $orDelimiter characters as "or" operations
		// have lower precedence than "and" operations
		$orSegments = $this->splitOnDelimiter($filterExpression,
				$this->orDelimiter, $this->escapeSequence);
		
		foreach ($orSegments as $orSegment)
		{
			// Now split each "or" segment on unescaped $andDelimiter characters
			$andSegments = $this->splitOnDelimiter($orSegment,
					$this->andDelimiter, $this->escapeSequence, true);
			
			// Only update the tokens array if non-empty segments are found
			if (count($andSegments))
				$tokens[] = $andSegments;
		}
		
		return $tokens;
	}
	
	/**
	 * Split a string by unescaped delimiters, trimming each segment.
	 * 
	 * @param string $string the string to split.
	 * @param string $delimiter the delimiter string. If this is an empty
	 * string, a single element array will be returned containing the original
	 * string trimmed.
	 * @param string $escapeSequence the string which can be prepended to the
	 * delimiter to prevent splitting. The escape sequence should be doubled up
	 * to prevent escaping of the delimiter. If this is an empty string, no
	 * escaping will be performed.
	 * @param boolean $removeEscapeSequences whether to remove escape sequences
	 * from the string. Escape sequences before a delimiter will always be
	 * removed. This should only be set to true when splitting a string segment
	 * for the final time and will transform doubled escape sequences into a
	 * single escape sequence, and remove the escape sequence in scenarios where
	 * it is not valid.
	 * @return array the delimiter separated string segments.
	 */
	private function splitOnDelimiter($string, $delimiter, $escapeSequence,
			$removeEscapeSequences = false)
	{
		// If the delimiter is empty, trim and return the original string
		if ($delimiter === '')
			return array(trim($string));
		
		$delimiter = strtolower($delimiter);
		$escapeSequence = strtolower($escapeSequence);
		
		$delimLength = strlen($delimiter);
		$escLength = strlen($escapeSequence);
		$stringLength = strlen($string);
		
		$segments = array();
		$currentPos = 0;
		$extracted = '';
		
		while ($currentPos < $stringLength)
		{
			// Only deal with escapes if the escape character is not empty
			if ($escapeSequence !== '')
			{
				// Check for escape character first
				$substr = substr($string, $currentPos, $escLength);

				if (strtolower($substr) === $escapeSequence)
				{
					// Check for an escaped escape character
					$substr2 = substr($string, $currentPos + $escLength,
							$escLength);

					if (strtolower($substr2) === $escapeSequence)
					{
						// Only add the first escape if we're not removing escape
						// characters
						if (!$removeEscapeSequences)
							$extracted .= $substr;

						// Add the escaped character and update position
						$extracted .= $substr2;
						$currentPos += $escLength * 2;
						continue;
					}

					// If we have an escaped delimiter, just add the delimiter
					$substr = substr($string, $currentPos + $escLength,
							$delimLength);

					if (strtolower($substr) === $delimiter)
					{
						$extracted .= $substr;
						$currentPos += $escLength + $delimLength;
						continue;
					}

					// If it's an unknown escape and we're removing escape
					// characters, skip this escape sequence
					if ($removeEscapeSequences)
					{
						$currentPos += $escLength;
						continue;
					}
				}
			}
			
			// Now check for the delimiter
			$substr = substr($string, $currentPos, $delimLength);
			
			// If we're at a delimiter, add the extracted string to the array
			if (strtolower($substr) === $delimiter)
			{
				$extracted = trim($extracted);
				
				// Only add non-empty segments
				if ($extracted !== '')
					$segments[] = $extracted;
				
				// Update positions and clear extracted string
				$extracted = '';
				$currentPos += $delimLength;
				continue;
			}
			
			// If there are no escapes or delimiters to deal with, append the
			// next character to the extracted string and update positions.
			$extracted .= substr($string, $currentPos, 1);
			$currentPos++;
		}
		
		$extracted = trim($extracted);
		
		// Add final extracted string to array if it is not empty
		if ($extracted !== '')
			$segments[] = $extracted;
		
		return $segments;
	}
	
	/**
	 * Analyses a filter expression segment to see if it is surrounded by a
	 * logic inversion prefix and suffix. If it is, the prefix and suffix and
	 * any whitespace are trimmed from the string and true is returned.
	 * Otherwise, false is returned.
	 * 
	 * @param string $filterExpression a segment which may or may not be
	 * surrounded by a logic inversion prefix and suffix. It is received by
	 * reference and will be stripped and trimmed in place if the prefix and
	 * suffix are found.
	 * @return boolean whether the condition logic should be inverted.
	 */
	private function processLogicInversion(&$filterExpression)
	{
		// If logic inversion is disabled, return early
		if ($this->invertLogicPrefix === '' && $this->invertLogicSuffix === '')
			return false;
		
		$prefix = strtolower($this->invertLogicPrefix);
		$suffix = strtolower($this->invertLogicSuffix);
		$string = $filterExpression;
		
		$prefixLength = strlen($prefix);
		$suffixLength = strlen($suffix);
		$stringLength = strlen($filterExpression);
		
		// No inversion if the string is shorter than the prefix and suffix
		if ($stringLength < $prefixLength + $suffixLength)
			return false;
		
		// No inversion if the prefix doesn't match
		if (strtolower(substr($string, 0, $prefixLength)) !== $prefix)
			return false;
		
		// No inversion if the suffix doesn't match
		if (strtolower(substr($string, -$suffixLength, $suffixLength))
				!== $suffix)
			return false;
		
		// Inversion has been requested, now strip the filter expression
		$filterExpression = $stringLength === $prefixLength + $suffixLength ? ''
				: trim(substr($string, $prefixLength,
						$stringLength - $prefixLength - $suffixLength));
		
		return true;
	}
	
	/**
	 * @var CDbConnection caches the connection object for the life of this
	 * AfParser object.
	 */
	private $_dbConnectionObjectCache;
	
	/**
	 * Returns an instance of CDbConnection determined by the $dbConnection
	 * property.
	 * 
	 * @return CDbConnection the connection object.
	 */
	private function getDbConnectionObject()
	{
		if ($this->_dbConnectionObjectCache === null)
		{
			$dbConnection = $this->dbConnection;

			if (is_string($dbConnection))
				$dbConnection = Yii::app()->$dbConnection;
			
			$this->_dbConnectionObjectCache = $dbConnection;
		}

		return $this->_dbConnectionObjectCache;
	}
	
	/**
	 * @var array maps a class path to a concrete instance of a subclass of
	 * AfBaseDbHelper. The classes contain no state and are shared amongst all
	 * instances of AfParser.
	 */
	private static $_dbHelperCache = array();
	
	/**
	 * Gets a database helper object to allow database specific syntax to be
	 * used in criteria conditions.
	 * Database helpers are cached and shared amongst all instances of AfParser.
	 * 
	 * @return AfBaseDbHelper the database helper object.
	 * @throws AfException if no database helper is available for the database
	 * connection driver.
	 */
	private function getDbHelper()
	{
		$dbConnection = $this->getDbConnectionObject();
		$driverName = $dbConnection->getDriverName();
		
		// Throw an exception if no helper is available for this driver name
		if (!isset($this->driverMap[$driverName]))
		{
			throw new AfException(
					"No database helper available for driver '$driverName'.");
		}
		
		// Get the required helper class path
		$dbHelperClassPath = $this->driverMap[$driverName];
		
		// If the cache doesn't yet contain this database helper, add it
		if (!isset(self::$_dbHelperCache[$dbHelperClassPath]))
		{
			self::$_dbHelperCache[$dbHelperClassPath]
					= Yii::createComponent($dbHelperClassPath);
		}
		
		// Return the cached helper object
		return self::$_dbHelperCache[$dbHelperClassPath];
	}
}
