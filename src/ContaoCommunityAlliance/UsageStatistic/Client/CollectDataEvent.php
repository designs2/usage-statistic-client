<?php

namespace ContaoCommunityAlliance\UsageStatistic\Client;

use Symfony\Component\EventDispatcher\Event;

class CollectDataEvent
	extends Event
{

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * Return the collected data.
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Determine if a data value for the given name is set.
	 *
	 * @param string $name The data name.
	 *
	 * @return bool
	 */
	public function has($name)
	{
		if (empty($name)) {
			throw new \InvalidArgumentException('The data name must not be empty');
		}
		if (!is_string($name) || is_numeric($name)) {
			throw new \InvalidArgumentException('The data name must be a non-numeric string');
		}

		return isset($this->data[$name]);
	}

	/**
	 * Return the data value for the given name, or null if no value was set.
	 *
	 * @param string $name The data name.
	 *
	 * @return integer|float|string|boolean|null
	 */
	public function get($name)
	{
		if (empty($name)) {
			throw new \InvalidArgumentException('The data name must not be empty');
		}
		if (!is_string($name) || is_numeric($name)) {
			throw new \InvalidArgumentException('The data name must be a non-numeric string');
		}

		return isset($this->data[$name])
			? $this->data[$name]
			: null;
	}

	/**
	 * Set the data value for the given name.
	 *
	 * @param string                       $name  The data name.
	 * @param integer|float|string|boolean $value The data value.
	 *
	 * @return $this
	 */
	public function set($name, $value)
	{
		if (empty($name)) {
			throw new \InvalidArgumentException('The data name must not be empty');
		}
		if (!is_string($name) || is_numeric($name)) {
			throw new \InvalidArgumentException('The data name must be a non-numeric string');
		}
		if (empty($value)) {
			throw new \InvalidArgumentException('The data value must not be empty');
		}
		if (!is_scalar($value)) {
			throw new \InvalidArgumentException('The data value must be a scalar value');
		}

		$this->data[$name] = $value;

		return $this;
	}
}
