<?php
namespace Hawk\Rest\Route;

use Psr\Http\Message\ServerRequestInterface;
use Hawk\Exception\InvalidParameterException;

/**
 *
 */
class HeaderParam extends Param
{
	/**
	 * [checkRequest description]
	 * @param  ServerRequestInterface $request [description]
	 * @return [type]                          [description]
	 */
	public function checkRequest(ServerRequestInterface $request)
	{
		$headerLine = trim($request->getHeaderLine($this->name));

		if ($this->required === Param::REQUIRED)
		{
			if ($headerLine === '')
				throw new InvalidParameterException($this->name . '(not present)');
			else
				$this->value = $headerLine;
		}
		else // $this->required === Param::OPTIONAL
		{
			if ($headerLine === '')
				$this->value = $this->defaultValue;
			else
				$this->value = $headerLine;
		}
	}
}