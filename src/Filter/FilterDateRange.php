<?php

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\DataGrid\Filter;

use Nette;

class FilterDateRange extends Filter
{

	/**
	 * @var array
	 */
	private $placeholder;

	/**
	 * @var string
	 */
	private $name_second;

	/**
	 * @var string
	 */
	protected $template = 'datagrid_filter_daterange.latte';

	/**
	 * @var array
	 */
	protected $format = ['j. n. Y', 'd. m. yyyy'];


	/**
	 * @param string $key
	 * @param string $name
	 * @param string $column
	 * @param string $name_second
	 */
	public function __construct($key, $name, $column, $name_second)
	{
		parent::__construct($key, $name, $column);

		$this->name_second = $name_second;
	}


	/**
	 * Adds select box to filter form
	 * @param Nette\Application\UI\Form $form
	 */
	public function addToFormContainer($form)
	{
		$container = $form->addContainer($this->key);

		$container->addText('from', $this->name)
			->setAttribute('data-provide', 'datepicker')
			->setAttribute('data-date-orientation', 'bottom')
			->setAttribute('data-date-format', $this->getJsFormat())
			->setAttribute('data-date-today-highlight', 'true')
			->setAttribute('data-date-autoclose', 'true');

		$container->addText('to', $this->name_second)
			->setAttribute('data-provide', 'datepicker')
			->setAttribute('data-date-orientation', 'bottom')
			->setAttribute('data-date-format', $this->getJsFormat())
			->setAttribute('data-date-today-highlight', 'true')
			->setAttribute('data-date-autoclose', 'true');

		if ($placeholder = $this->getPlaceholder()) {
			if ($text_from = reset($placeholder)) {
				$form[$this->key]['from']->setAttribute('placeholder', $text_from);
			}

			if ($text_to = end($placeholder) && $text_to != $text_from) {
				$form[$this->key]['to']->setAttribute('placeholder', $text_to);
			}
		}
	}


	/**
	 * Set html attr placeholder of both fields
	 * @param string $placeholder
	 */
	public function setPlaceholder($placeholder)
	{
		if (!is_array($placeholder)) {
			throw new FilterDateRangeException(
				'FilterDateRange::setPlaceholder can only accept array of placeholders'
			);
		}

		$this->placeholder = $placeholder;

		return $this;
	}


	/**
	 * Get filter condition
	 * @return array
	 */
	public function getCondition()
	{
		$value = $this->getValue();

		return [$this->column => [
			'from' => isset($value['from']) ? $value['from'] : '',
			'to' => isset($value['to']) ? $value['to'] : ''
		]];
	}


	/**
	 * Set format for datepicker etc
	 * @param string $php_format
	 * @param string $js_format
	 */
	public function setFormat($php_format, $js_format)
	{
		$this->format = [$php_format, $js_format];
	}


	/**
	 * Get php format for datapicker
	 * @return string
	 */
	public function getPhpFormat()
	{
		return $this->format[0];
	}


	/**
	 * Get js format for datepicker
	 * @return string
	 */
	public function getJsFormat()
	{
		return $this->format[1];
	}

}


class FilterDateRangeException extends \Exception
{
}
