<?php
namespace Hawk\Validator;

use Hawk\Exception\InvalidArgumentException;

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
		{
			$allowedValues = implode(', ', $enum);
			throw new InvalidArgumentException($this->paramName . "(not present in white-list: [$allowedValues])");
		}
	}
}
