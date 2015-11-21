<?php
namespace Hawk\Validator;

use Hawk\Exception\InvalidParameterException;

class EnumValidator implements ValidatorInterface
{
	private $paramName;
	private $enum;

	public function __construct($paramName, array $enum)
	{
		$this->paramName = $paramName;
		$this->enum = $enum;
	}

	public function validate($data)
	{
		if (!in_array($data, $this->enum, true))
			throw new InvalidParameterException($this->paramName);
	}
}