<?php

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\DataGrid;

use Nette;
use LeanMapper;
use DibiRow;
use Ublaboo\DataGrid\Utils\PropertyAccessHelper;

class Row extends Nette\Object
{

	/**
	 * @var DataGrid
	 */
	protected $datagrid;

	/**
	 * @var mixed
	 */
	protected $item;

	/**
	 * @var string
	 */
	protected $primary_key;

	/**
	 * @var mixed
	 */
	protected $id;


	/**
	 * @param mixed  $item
	 * @param string $primary_key
	 */
	public function __construct(DataGrid $datagrid, $item, $primary_key)
	{
		$this->datagrid = $datagrid;
		$this->item = $item;
		$this->primary_key = $primary_key;

		$this->id = is_object($item) ? $item->{$primary_key} : $item[$primary_key];
	}


	/**
	 * Get id value of item
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}


	/**
	 * Get item value of key
	 * @param  mixed $key
	 * @return mixed
	 */
	public function getValue($key)
	{
		if ($this->item instanceof LeanMapper\Entity) {
			return $this->getLeanMapperEntityProperty($this->item, $key);

		} else if ($this->item instanceof DibiRow) {
			return $this->item->{$key};

		} else if ($this->item instanceof Nette\Database\Table\ActiveRow) {
			return $this->item->{$key};

		} else if (is_array($this->item)) {
			return $this->item[$key];

		} else {
			/**
			 * Doctrine entity
			 */
			return $this->getDoctrineEntityProperty($this->item, $key);

		}
	}


	/**
	 * LeanMapper: Access object properties to get a item value
	 * @param  LeanMapper\Entity $item
	 * @param  mixed             $key
	 * @return mixed
	 */
	public function getLeanMapperEntityProperty(LeanMapper\Entity $item, $key)
	{
		$properties = explode('.', $key);
		$value = $item;

		while ($property = array_shift($properties)) {
			if (!isset($value->{$property})) {
				if ($this->datagrid->strict_entity_property) {
					throw new DataGridException(sprintf(
						'Target Property [%s] is not an object or is empty, trying to get [%s]',
						$value, str_replace('.', '->', $key)
					));
				}

				return NULL;
			}

			$value = $value->{$property};
		}

		return $value;
	}


	/**
	 * Doctrine: Access object properties to get a item value
	 * @param  mixed $item
	 * @param  mixed $key
	 * @return mixed
	 */
	public function getDoctrineEntityProperty($item, $key)
	{
		$properties = explode('.', $key);
		$value = $item;
		$accessor = PropertyAccessHelper::getAccessor();

		while ($property = array_shift($properties)) {
			if (!is_object($value) && !$value) {
				if ($this->datagrid->strict_entity_property) {
					throw new DataGridException(sprintf(
						'Target Property [%s] is not an object or is empty, trying to get [%s]',
						$value, str_replace('.', '->', $key)
					));
				}

				return NULL;
			}

			$value = $accessor->getValue($value, $property);
		}

		return $value;
	}


	/**
	 * Get original item
	 * @return mixed
	 */
	public function getItem()
	{
		return $this->item;
	}


	/**
	 * Has particular row group actions allowed?
	 * @return bool
	 */
	public function hasGroupAction()
	{
		$condition = $this->datagrid->getRowCondition('group_action');

		return $condition ? $condition($this->item) : TRUE;
	}


	/**
	 * Has particular row and action allowed?
	 * @param  mixed  $key
	 * @return bool
	 */
	public function hasAction($key)
	{
		$condition = $this->datagrid->getRowCondition('action', $key);

		return $condition ? $condition($this->item) : TRUE;
	}

}
