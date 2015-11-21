<?php
namespace Hawk\Rest\Route;

use UnexpectedValueException;
use Psr\Http\Message\ServerRequestInterface;
use Hawk\Exception\InvalidParameterException;

/**
 *
 */
class PayloadParam extends Param
{
	/**
	 * [checkRequest description]
	 * @param  ServerRequestInterface $request [description]
	 * @return [type]                          [description]
	 */
	public function checkRequest(ServerRequestInterface $request)
	{
		$parsedBody = (array) $request->getParsedBody();

		if ($this->required === Param::REQUIRED )
		{
			if (!array_key_exists($this->name, $parsedBody) || trim($parsedBody[$this->name]) === '')
				throw new InvalidParameterException($this->name . '(not present)');
			else
				$this->value = $parsedBody[$this->name];
		}
		else // $this->required === Param::OPTIONAL
		{
			if (!array_key_exists($this->name, $parsedBody) || trim($parsedBody[$this->name]) === '')
				$this->value = $this->defaultValue;
			else
				$this->value = $parsedBody[$this->name];
		}
	}
}