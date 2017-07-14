<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Array and variable filtration.
 * Based on Kohana Validation helper class
 * Derivative work by Dariusz Rorat
 *
 * @package    Security
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Filtration implements ArrayAccess {

	/**
	 * Creates a new Filtration instance.
	 *
	 * @param   array   $array  array to use for filtration
	 * @return  Filtration
	 */
	public static function factory(array $array)
	{
		return new Filtration($array);
	}

	// Bound values
	protected $_bound = array();

	// Field rules
	protected $_rules = array();

	// Field labels
	protected $_labels = array();

	// Array to validate
	protected $_data = array();

	/**
	 * Sets the unique "any field" key and creates an ArrayObject from the
	 * passed array.
	 *
	 * @param   array   $array  array to validate
	 * @return  void
	 */
	public function __construct(array $array)
	{
		$this->_data = $array;
	}

	/**
	 * Throws an exception because Filtration is read-only.
	 * Implements ArrayAccess method.
	 *
	 * @throws  Kohana_Exception
	 * @param   string   $offset    key to set
	 * @param   mixed    $value     value to set
	 * @return  void
	 */
	public function offsetSet($offset, $value)
	{
		throw new Kohana_Exception('Filtration objects are read-only.');
	}

	/**
	 * Checks if key is set in array data.
	 * Implements ArrayAccess method.
	 *
	 * @param   string  $offset key to check
	 * @return  bool    whether the key is set
	 */
	public function offsetExists($offset)
	{
		return isset($this->_data[$offset]);
	}

	/**
	 * Throws an exception because Filtration is read-only.
	 * Implements ArrayAccess method.
	 *
	 * @throws  Kohana_Exception
	 * @param   string  $offset key to unset
	 * @return  void
	 */
	public function offsetUnset($offset)
	{
		throw new Kohana_Exception('Filtration objects are read-only.');
	}

	/**
	 * Gets a value from the array data.
	 * Implements ArrayAccess method.
	 *
	 * @param   string  $offset key to return
	 * @return  mixed   value from array
	 */
	public function offsetGet($offset)
	{
		return $this->_data[$offset];
	}

	/**
	 * Copies the current rules to a new array.
	 *
	 *     $copy = $array->copy($new_data);
	 *
	 * @param   array   $array  new data set
	 * @return  Filtration
	 * @since   3.0.5
	 */
	public function copy(array $array)
	{
		// Create a copy of the current filtration set
		$copy = clone $this;

		// Replace the data set
		$copy->_data = $array;

		return $copy;
	}

	/**
	 * Returns the array representation of the current object.
	 *
	 * @deprecated
	 * @return  array
	 */
	public function as_array()
	{
		return $this->_data;
	}

	/**
	 * Returns the array of data to be filtered.
	 *
	 * @return  array
	 */
	public function data()
	{
		return $this->_data;
	}

	/**
	 * Sets or overwrites the label name for a field.
	 *
	 * @param   string  $field  field name
	 * @param   string  $label  label
	 * @return  $this
	 */
	public function label($field, $label)
	{
		// Set the label for this field
		$this->_labels[$field] = $label;

		return $this;
	}

	/**
	 * Sets labels using an array.
	 *
	 * @param   array   $labels list of field => label names
	 * @return  $this
	 */
	public function labels(array $labels)
	{
		$this->_labels = $labels + $this->_labels;

		return $this;
	}

	/**
	 * Overwrites or appends rules to a field. Each rule will be executed once.
	 * All rules must be string names of functions method names. Parameters must
	 * match the parameters of the callback function exactly
	 *
	 * Aliases you can use in callback parameters:
	 * - :filter - the filter object
	 * - :field - the field name
	 * - :value - the value of the field
	 *
	 *     // The "username" must not be empty and have a minimum length of 4
	 *     $filtration->rule('contents', 'Text::censor_vulgar')
	 *
	 * [!!] Errors must be added manually when using closures!
	 *
	 * @param   string      $field  field name
	 * @param   callback    $rule   valid PHP callback or closure
	 * @param   array       $params extra parameters for the rule
	 * @return  $this
	 */
	public function rule($field, $rule, array $params = NULL)
	{
		if ($params === NULL)
		{
			// Default to array(':value')
			$params = array(':value');
		}

		if ($field !== TRUE AND ! isset($this->_labels[$field]))
		{
			// Set the field label to the field name
			$this->_labels[$field] = $field;
		}

		// Store the rule and params for this rule
		$this->_rules[$field][] = array($rule, $params);

		return $this;
	}

	/**
	 * Add rules using an array.
	 *
	 * @param   string  $field  field name
	 * @param   array   $rules  list of callbacks
	 * @return  $this
	 */
	public function rules($field, array $rules)
	{
		foreach ($rules as $rule)
		{
			$this->rule($field, $rule[0], Arr::get($rule, 1));
		}

		return $this;
	}

	/**
	 * Bind a value to a parameter definition.
	 *
	 *
	 * @param   string  $key    variable name or an array of variables
	 * @param   mixed   $value  value
	 * @return  $this
	 */
	public function bind($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $name => $value)
			{
				$this->_bound[$name] = $value;
			}
		}
		else
		{
			$this->_bound[$key] = $value;
		}

		return $this;
	}

	/**
	 * Executes all filtration rules. This should
	 * typically be called within an if/else block.
	 *
	 *
	 * @return  boolean
	 */
	public function execute()
	{
		if (Kohana::$profiling === TRUE)
		{
			// Start a new benchmark
			$benchmark = Profiler::start('filtration', __FUNCTION__);
		}

		// New data set
		$data = array();

		// Import the rules locally
		$rules = $this->_rules;

		// Bind the filtration object to :filter
		$this->bind(':filter', $this);
		// Bind the data to :data
		$this->bind(':data', $this->_data);

		// Execute the rules
		foreach ($rules as $field => $set)
		{
			// Get the field value
			$value = $this[$field];

			// Bind the field name and value to :field and :value respectively
			$this->bind(array
			(
				':field' => $field,
				':value' => $value,
			));

			foreach ($set as $array)
			{
				// Rules are defined as array($rule, $params)
				list($rule, $params) = $array;

				foreach ($params as $key => $param)
				{
					if (is_string($param) AND array_key_exists($param, $this->_bound))
					{
						// Replace with bound value
						$params[$key] = $this->_bound[$param];
					}
				}

				if (is_array($rule))
				{
					// Allows rule('field', array(':model', 'some_rule'));
					if (is_string($rule[0]) AND array_key_exists($rule[0], $this->_bound))
					{
						// Replace with bound value
						$rule[0] = $this->_bound[$rule[0]];
					}

					// This is an array callback, the method name is the error name
					$data[$field] = call_user_func_array($rule, $params);
				}
				elseif ( ! is_string($rule))
				{
					$data[$field] = call_user_func_array($rule, $params);
				}
				elseif (method_exists('Filter', $rule))
				{
					// Use a method in this object
					$method = new ReflectionMethod('Filter', $rule);

					// Call static::$rule($this[$field], $param, ...) with Reflection
					$data[$field] = $method->invokeArgs(NULL, $params);
				}
				elseif (strpos($rule, '::') === FALSE)
				{
					// Use a function call
					$function = new ReflectionFunction($rule);

					// Call $function($this[$field], $param, ...) with Reflection
					$data[$field] = $function->invokeArgs($params);                    
				}
				else
				{
					// Split the class and method of the rule
					list($class, $method) = explode('::', $rule, 2);

					// Use a static method call
					$method = new ReflectionMethod($class, $method);

					// Call $Class::$method($this[$field], $param, ...) with Reflection
					$data[$field] = $method->invokeArgs(NULL, $params);
				}

			}
		}

		// Unbind all the automatic bindings to avoid memory leaks.
		unset($this->_bound[':filter']);
		unset($this->_bound[':data']);
		unset($this->_bound[':field']);
		unset($this->_bound[':value']);


		if (isset($benchmark))
		{
			// Stop benchmarking
			Profiler::stop($benchmark);
		}
        
		return $data;
	}
    
}
