<?php

namespace Mike;

class Config implements \ArrayAccess, \Countable {


	public function __construct(array $config = null) {

		foreach($config as $key => $value) {

			$this->offsetSet($key, $value);
		}
	}

	/**
	 * Check if offset exists
	 *
	 * @param int $index
	 *
	 * @return bool
	 */

	public function offsetExists($index) {

		$index = strval($index);

		return isset($this->{$index});
	}

	/**
	 * Get config parameter
	 *
	 * @param string $index
	 * @param null $defaultValue
	 *
	 * @return mixed|Config
	 */

	public function get($index, $defaultValue = null) {

		$index = strval($index);

		if(isset($this->{$index}))
			return $this->{$index};

		return $defaultValue;
	}

	/**
	 * Get attribute
	 *
	 * @param mixed $index
	 */

	public function offsetGet($index) {

		$index = strval($index);

		return $this->{$index};
	}

	/**
	 * Set attribute
	 *
	 * @param mixed $index
	 * @param mixed $value
	 */

	public function offsetSet($index, $value) {

		$index = strval($index);

		if(is_array($value)) {

			$this->{$index} = new self($value);

		} else {

			$this->{$index} = $value;

		}
	}

	/**
	 * Unset array attribute
	 *
	 * @param mixed $index
	 */

	public function offsetUnset($index) {

		$index = strval($index);

		unset($this->{$index});

	}



	public function merge(Config $config) {

		return $this->_merge($config);
	}

	/**
	 * Convert object to array
	 *
	 * @return array
	 */

	public function toArray() {

		$arrayConfig = [];

		foreach(get_object_vars($this) as $key => $value) {

			if(is_object($value)) {

				if(method_exists($value, 'toArray')) {

					$arrayConfig[$key] = $value->toArray();
				} else {

					$arrayConfig[$key] = $value;
				}


			} else {

				$arrayConfig[$key] = $value;
			}

		}


		return $arrayConfig;
	}

	/**
	 * Return count of attributes
	 *
	 * @return int
	 */

	public function count() {

		return count(get_object_vars($this));
	}

	/**
	 * Restores the state of a Mike\Config object
	 $
	 * @return Config
	 */

	public static function __set_state(array $data)	{

		return new self($data);
	}

	/**
	 * Merge config instances
	 *
	 * @param Config $config
	 * @param Config $instance = null
	 *
	 * @return Config
	 */

	protected final function _merge(Config $config, $instance = null) {

		if(!is_object($instance))
			$instance = $this;

		$number = $instance->count();

		foreach(get_object_vars($config) as $key => $value) {

			$property = strval($key);

			if(isset($instance->{$property})) {

				$localObject = $instance->{$property};

				if(is_object($localObject) && is_object($value)) {

					if($localObject instanceof Config && $value instanceof Config) {

						$this->_merge($value, $localObject);
						continue;

					}

				}
			}

			if(is_numeric($key)) {

				$key = strval($number);
				$number++;
			}

			$instance->{$key} = $value;

		}


		return $instance;
	}

}