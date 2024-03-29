<?php
abstract class TpDataApiObject implements ArrayAccess {

	/**
	 * TpAbstractModel constructor.
	 *
	 * @param array $data
	 */
	public function __construct(array $data = array()) {
		$keys = static::keys();
		$filtered = TpUtils::filterKeys($data, $keys);

		foreach($filtered as $key => $value) {
			$this[$key] = $value;
		}
	}

	/**
	 * @return array
	 */
	public function toArray() {
		$data = array();
		$keys = self::keys();
		foreach($keys as $name) {
			$data[$name] = static::demodelizeRecursive($this->$name);
		}
		return $data;
	}

	/**
	 * @return string[]
	 */
	public static function keys() {
		$calledClass = get_called_class();
		$reflection = new ReflectionClass($calledClass);

		// Filter out static properties and those beginning with an underscore.
		$allProperties = $reflection->getProperties();
		$dataProperties = array_filter(
			$allProperties, array('self', 'filterDataProperties')
		);
		$sortedDataProperties = static::sortDataProperties($dataProperties);

		$propertyNames = array();
		foreach($sortedDataProperties as $property) {
			$propertyNames[] = $property->getName();
		}

		return $propertyNames;
	}

	/**
	 * Callback to be used by the array_filter function.
	 *
	 * @param ReflectionProperty $property
	 * @return bool
	 */
	private static function filterDataProperties(ReflectionProperty $property) {
		$underscored = strpos($property->getName(), '_') === 0;
		$static      = $property->isStatic();
		return !$underscored && !$static;
	}
	/**
	 * Prepend inherited properties.
	 *
	 * @param ReflectionProperty[] $dataProperties
	 * @return ReflectionProperty[]
	 */
	private static function sortDataProperties(array $dataProperties) {
		$inherited = array();
		$own = array();

		$calledClassName = get_called_class();

		foreach($dataProperties as $property) {
			$propertyClass = $property->getDeclaringClass();
			$propertyClassName = $propertyClass->getName();

			if($propertyClassName == $calledClassName) {
				$own[] = $property;
			} else {
				$inherited[] = $property;
			}
		}

		$combined = array_merge($inherited, $own);
		return $combined;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected static function demodelizeRecursive($value) {
		if($value instanceof TpDataApiObject) {
			$demodelized = $value->toArray();
		} else {
			$isArray = is_array($value);
			if($isArray) {
				$demodelized = array();
				foreach($value as $k => $v) {
					$demodelized[$k] = static::demodelizeRecursive($v);
				}
			} else {
				$demodelized = $value;
			}
		}

		return $demodelized;
	}

	/* *** ArrayAccess *** */

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		$keys = static::keys();
		$offsetExists = in_array($offset, $keys);
		return $offsetExists;
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset): mixed
	{
		$getterName = 'get' . ucfirst($offset);
		$value = $this->$getterName();
		return $value;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value): void
	{
		$setterName = 'set' . ucfirst($offset);
		$this->$setterName($value);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset): void
	{
		$this->offsetSet($offset, null);
	}

}
