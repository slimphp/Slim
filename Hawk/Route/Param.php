<?php
namespace Hawk\Rest\Route;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Hawk\Exception\InvalidParameterException;

/**
 * \Hawk\Route\Param
 */
abstract class Param
{
	/**
	 * @var int
	 */
	const REQUIRED = true;

	/**
	 * @var int
	 */
	const OPTIONAL = false;

	/**
	 * [$name description]
	 * @var string
	 */
	protected $name;

	/**
	 * [$value description]
	 * @var mixed
	 */
	protected $value;

	/**
	 * [$filter description]
	 * @var array of \Hawk\Filter\Filter
	 */
	protected $filters;

	/**
	 * [$validator description]
	 * @var array of \Hawk\Validator\Validator
	 */
	protected $validators;

	/**
	 * [$value description]
	 * @var int
	 */
	protected $required;

	/**
	 * [$value description]
	 * @var mixed
	 */
	protected $defaultValue;

	/**
	 * [__construct description]
	 * @param string $name         [description]
	 * @param array  $filters      [description]
	 * @param array  $validators   [description]
	 * @param int    $required     [description]
	 * @param mixed  $defaultValue [description]
	 */
	public function __construct($name, array $filters, array $validators, $required, $defaultValue = null)
	{
		$this->name         = $name;
		$this->value        = null;
		$this->filters      = $filters;
		$this->validators   = $validators;
		$this->required     = $required;
		$this->defaultValue = $defaultValue;
	}

	/**
	 * [checkRequest description]
	 * @param  ServerRequestInterface $request [description]
	 * @return [type]                          [description]
	 */
	public abstract function checkRequest(ServerRequestInterface $request);

	/**
	 * [filter description]
	 * @return bool [description]
	 */
	public function filter()
	{
		foreach ($this->filters as $filter)
			$this->value = $filter->filter($this->value);

		if (trim($this->value) === '')
		{
			if ($this->required === self::REQUIRED)
				throw new InvalidParameterException($this->name . '(null after filtering)');
			else
				$this->value = $this->defaultValue;
		}
	}

	/**
	 * [validate description]
	 * @return bool [description]
	 */
	public function validate()
	{
		foreach ($this->validators as $validator)
		{
			try {
				$validator->validate($this->value);
			} catch (Exception $e) {
				if ($this->required === self::REQUIRED)
					throw $e;
				else
					$this->value = $this->defaultValue;
			}
		}
	}

	/**
	 * [setValue description]
	 * @param array $source [description]
	 */
	public function setValue(array $source)
	{
		if (strpos($name, '[') !== false)
		{
			$name = str_replace(['[', ']'], '&', $name);

			$keys = explode('&', $name);

			foreach ($keys as $k => $v)
				if ($v === '')
					unset($keys[$k]);

			foreach ($keys as $v)
				$value = $source[$v];
		}
		else
			$this->value = $source[$name];
	}

	/**
	 * [getValue description]
	 * @return [type] [description]
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * [getName description]
	 * @return [type] [description]
	 */
	public function getName()
	{
		return $this->name;
	}
}