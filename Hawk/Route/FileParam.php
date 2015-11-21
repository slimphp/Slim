<?php
namespace Hawk\Rest\Route;

use Psr\Http\Message\ServerRequestInterface;
use Hawk\Exception\InvalidParameterException;

/**
 *
 */
class FileParam extends Param
{
	/**
	 * [checkRequest description]
	 * @param  ServerRequestInterface $request [description]
	 * @return [type]                          [description]
	 */
	public function checkRequest(ServerRequestInterface $request)
	{
		$uploadedFiles = $request->getUploadedFiles();

		if ($this->required === Param::REQUIRED)
		{
			if (!array_key_exists($this->name, $uploadedFiles))
				throw new InvalidParameterException($this->name . '(not present)');
			else
				$this->value = $uploadedFiles[$this->name];
		}
		else // $this->required === Param::OPTIONAL
		{
			if (!array_key_exists($this->name, $uploadedFiles))
				$this->value = $this->defaultValue;
			else
				$this->value = $uploadedFiles[$this->name];
		}
	}
}
