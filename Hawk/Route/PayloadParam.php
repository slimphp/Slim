<?php
namespace Hawk\Rest\Route;

use UnexpectedValueException;
use Psr\Http\Message\ServerRequestInterface;
use Hawk\Exception\InvalidArgumentException;

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
			if (array_key_exists($this->name, $parsedBody) || trim($parsedBody[$this->name]) === '')
				$this->value = $parsedBody[$this->name];
			else
				throw new InvalidArgumentException($this->name . '(not present)');
		}
		else // $this->required === Param::OPTIONAL
		{
			if (array_key_exists($this->name, $parsedBody) || trim($parsedBody[$this->name]) === '')
				$this->value = $parsedBody[$this->name];
			else
				$this->value = $this->defaultValue;
		}
	}
}
