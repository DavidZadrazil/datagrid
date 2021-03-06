<?php

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\DataGrid\Column;

use Ublaboo\DataGrid\DataGridException;
use Ublaboo;
use Ublaboo\DataGrid\Row;

abstract class Column extends Ublaboo\DataGrid\Object
{

	/**
	 * @var string
	 */
	protected $column;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $replacements = [];

	/**
	 * @var Renderer|NULL
	 */
	protected $renderer;

	/**
	 * @var string
	 */
	protected $template;

	/**
	 * @var boolean
	 */
	protected $is_sortable = FALSE;

	/**
	 * @var array
	 */
	protected $sort;

	/**
	 * @var bool
	 */
	protected $template_escaping = TRUE;

	/**
	 * @var string
	 */
	protected $align;

	/**
	 * @var array
	 */
	protected $template_variables;

	/**
	 * @var callable
	 */
	protected $editable_callback;


	/**
	 * @param string $column
	 * @param string $name
	 */
	public function __construct($column, $name)
	{
		$this->column = $column;
		$this->name = $name;
	}


	/**
	 * Render row item into template
	 * @param  Row   $row
	 * @return mixed
	 */
	public function render(Row $row)
	{
		/**
		 * Renderer function may be used
		 */
		if ($renderer = $this->getRenderer()) {
			if (!$renderer->getConditionCallback()) {
				return call_user_func_array($renderer->getCallback(), [$row->getItem()]);
			}

			if (call_user_func_array($renderer->getConditionCallback(), [$row->getItem()])) {
				return call_user_func_array($renderer->getCallback(), [$row->getItem()]);
			}
		}

		/**
		 * Or replacements may be applied
		 */
		list($do_replace, $replaced) = $this->applyReplacements($row);
		if ($do_replace) {
			return $replaced;
		}

		return $this->getColumnValue($row);
	}


	/**
	 * Should be column values escaped in latte?
	 * @param boolean $template_escaping
	 */
	public function setTemplateEscaping($template_escaping = TRUE)
	{
		$this->template_escaping = (bool) $template_escaping;

		return $this;
	}


	public function isTemplateEscaped()
	{
		return $this->template_escaping;
	}


	/**
	 * Set column sortable or not
	 * @param bool $sortable
	 */
	public function setSortable($sortable = TRUE)
	{
		$this->is_sortable = (bool) $sortable;

		return $this;
	}


	/**
	 * Tell whether column is sortable
	 * @return boolean
	 */
	public function isSortable()
	{
		return $this->is_sortable;
	}


	/**
	 * Get column name
	 * @return string
	 */
	public function getColumnName()
	{
		return $this->column;
	}


	/**
	 * Get column value of row item
	 * @param  Row   $row
	 * @return mixed
	 */
	public function getColumnValue(Row $row)
	{
		return $row->getValue($this->column);
	}


	public function getName()
	{
		return $this->name;
	}


	/**
	 * Set column replacements
	 * @param  array $replacements
	 * @return Column
	 */
	public function setReplacement(array $replacements)
	{
		$this->replacements = $replacements;

		return $this;
	}


	/**
	 * Tell whether columns has replacements
	 * @return boolean
	 */
	public function hasReplacements()
	{
		return (bool) $this->replacements;
	}


	/**
	 * Apply replacements
	 * @param  Row   $row
	 * @return array
	 */
	public function applyReplacements(Row $row)
	{
		$value = $row->getValue($this->column);

		if ((is_scalar($value) || is_null($value)) && isset($this->replacements[$value])) {
			return [TRUE, $this->replacements[$value]];
		}

		return [FALSE, NULL];
	}


	/**
	 * Set renderer callback and (it may be optional - the condition callback will decide)
	 * @param callable $renderer
	 */
	public function setRenderer($renderer, $condition_callback = NULL)
	{
		if ($this->hasReplacements()) {
			throw new DataGridException(
				"Use either Column::setReplacement() or Column::setRenderer, not both."
			);
		}

		if (!is_callable($renderer)) {
			throw new DataGridException(
				"Renderer (method Column::setRenderer()) must be callable."
			);
		}

		if (NULL != $condition_callback && !is_callable($condition_callback)) {
			throw new DataGridException(
				"Renderer (method Column::setRenderer()) must be callable."
			);
		}

		$this->renderer = new Renderer($renderer, $condition_callback);

		return $this;
	}


	/**
	 * Set renderer callback just if condition is truthy
	 * @param callable $renderer
	 */
	public function setRendererOnCondition($renderer, $condition_callback)
	{
		return $this->setRenderer($renderer, $condition_callback);
	}


	/**
	 * Return custom renderer callback
	 * @return Renderer|null
	 */
	public function getRenderer()
	{
		return $this->renderer;
	}


	/**
	 * Column may have its own template
	 * @param string $template
	 */
	public function setTemplate($template, array $template_variables = [])
	{
		$this->template = $template;
		$this->template_variables = $template_variables;

		return $this;
	}


	/**
	 * Column can have variables that will be passed to custom template scope
	 * @return array
	 */
	public function getTemplateVariables()
	{
		return $this->template_variables;
	}


	/**
	 * Tell whether column has its owntemplate
	 * @return bool
	 */
	public function hasTemplate()
	{
		return (bool) $this->template;
	}


	/**
	 * Get column template path
	 * @return string
	 */
	public function getTemplate()
	{
		return $this->template;
	}


	/**
	 * Tell whether data source is sorted by this collumn
	 * @return boolean
	 */
	public function isSortedBy()
	{
		return (bool) $this->sort;
	}


	/**
	 * Tell column his sorting options
	 * @param array $sort
	 */
	public function setSort(array $sort)
	{
		$this->sort = $sort[$this->column];

		return $this;
	}


	/**
	 * What sorting will be applied after next click?
	 * @return array
	 */
	public function getSortNext()
	{
		if ($this->sort == 'ASC') {
			return [$this->column => 'DESC'];
		} else if ($this->sort == 'DESC') {
			return [$this->column => NULL];
		}

		return [$this->column => 'ASC'];
	}


	/**
	 * Is sorting ascending?
	 * @return boolean
	 */
	public function isSortAsc()
	{
		return $this->sort == 'ASC';
	}


	/**
	 * Set column alignment
	 * @param string $align
	 */
	public function setAlign($align)
	{
		$this->align = (string) $align;

		return $this;
	}


	/**
	 * Has column some alignment?
	 * @return boolean [description]
	 */
	public function hasAlign()
	{
		return (bool) $this->align;
	}


	/**
	 * Get column alignment
	 * @return string
	 */
	public function getAlign()
	{
		return $this->align;
	}


	/**
	 * Set callback that will be called after inline editing
	 * @param callable $editable_callback
	 */
	public function setEditableCallback(callable $editable_callback)
	{
		$this->editable_callback = $editable_callback;

		return $this;
	}


	/**
	 * Return callback that is used after inline editing
	 * @return callable
	 */
	public function getEditableCallback()
	{
		return $this->editable_callback;
	}


	/**
	 * Is column editable?
	 * @return boolean
	 */
	public function isEditable()
	{
		return (bool) $this->getEditableCallback();
	}

}
