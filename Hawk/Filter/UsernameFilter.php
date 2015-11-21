<?php
namespace Hawk\Filter;

/**
 *
 */
class UsernameFilter implements FilterInterface
{
	/**
	 * [filter description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function filter($data)
	{
		return preg_replace('/[^\w]/', '', $data);
	}
}