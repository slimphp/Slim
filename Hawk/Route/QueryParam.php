<?php
namespace Hawk\Rest\Route;

use UnexpectedValueException;
use Psr\Http\Message\ServerRequestInterface;
use Hawk\Exception\InvalidArgumentException;

/**
 *
 */
class QueryParam extends Param
{
	/**
	 * [checkRequest description]
	 * @param  ServerRequestInterface $request [description]
	 * @return [type]                          [description]
	 */
	public function checkRequest(ServerRequestInterface $request)
	{
		$queryParams = $request->getQueryParams();

		if ($this->required === Param::REQUIRED)
		{
			if (array_key_exists($this->name, $queryParams) || empty($queryParams[$this->name]) )
				$this->value = $queryParams[$this->name];
			else
				throw new InvalidArgumentException($this->name . '(not present)');
		}
		else // $this->required === Param::OPTIONAL
		{
			if (array_key_exists($this->name, $queryParams) || empty($queryParams[$this->name]) )
				$this->value = $queryParams[$this->name];
			else
				$this->value = $this->defaultValue;
		}
	}
}
