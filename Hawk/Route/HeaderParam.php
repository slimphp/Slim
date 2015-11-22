<?php
namespace Hawk\Rest\Route;

use Psr\Http\Message\ServerRequestInterface;
use Hawk\Exception\InvalidArgumentException;

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
			if ($headerLine !== '')
				$this->value = $headerLine;
			else
				throw new InvalidArgumentException($this->name . '(not present)');
		}
		else // $this->required === Param::OPTIONAL
		{
			if ($headerLine !== '')
				$this->value = $headerLine;
			else
				$this->value = $this->defaultValue;
		}
	}
}
