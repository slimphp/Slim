<?php
namespace Hawk\Validator;

/**
 * \Hawk\Validator\ValidatorInterface
 *
 */
interface ValidatorInterface
{
	/**
	 * [validate description]
	 *
	 * @throws Exception   An exception describing the error.
	 *
	 * @param  mixed $data [description]
	 *
	 * @return void        [description]
	 */
	public function validate($data);
}