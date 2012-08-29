<?php
class Order
{
	/**
	 * @var String
	 */
	private $propertyName;
	/**
	 * @var Boolean
	 */
	private $ascending;
	/**
	 * @var Boolean
	 */
	private $ignoreCase = false;

	/**
	 * @param string $propertyName
	 * @param boolean $ascending
	 */
	private function __construct($propertyName, $ascending)
	{
		$this->propertyName = $propertyName;
		$this->ascending = $ascending;
	}

	/**
	 * Default order (ascendent, by document_label)
	 * @return Order
	 */
	public static function std()
	{
		return self::asc("document_label");
	}

	/**
	 * @param string $propertyName
	 * @return Order
	 */
	public static function asc($propertyName)
	{
		return new Order($propertyName, true);
	}

	/**
	 * Same as asc, ignoring case
	 * @param string $propertyName
	 * @return Order
	 */
	public static function iasc($propertyName)
	{
		$order = new Order($propertyName, true);
		$order->ignoreCase();
		return $order;
	}

	/**
	 * @param string $propertyName
	 * @return Order
	 */
	public static function desc($propertyName)
	{
		return new Order($propertyName, false);
	}

	/**
	 * Same as desc, ignoring case
	 * @param string $propertyName
	 * @return Order
	 */
	public static function idesc($propertyName)
	{
		$order = new Order($propertyName, false);
		return $order->ignoreCase();
	}

	/**
	 * @param string $propertyName
	 * @param string $orderType "asc" | "desc" | "iasc" | "idesc"
	 * @return Order
	 */
	public static function byString($propertyName, $orderType)
	{
		switch ($orderType)
		{
			case "asc":
				return self::asc($propertyName);
			case "iasc":
				return self::iasc($propertyName);
			case "desc":
				return self::desc($propertyName);
			case "idesc":
				return self::idesc($propertyName);
		}
		throw new Exception("Unknown order type $orderType");
	}

	/**
	 * @return Order
	 */
	public function	ignoreCase()
	{
		$this->ignoreCase = true;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}

	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab,1));
			return $tab[0];
		}
		return null;
	}

	/**
	 * @return boolean
	 */
	public function getAscending()
	{
		return $this->ascending;
	}

	/**
	 * @return boolean
	 */
	public function getIgnorecase()
	{
		return $this->ignoreCase;
	}
}