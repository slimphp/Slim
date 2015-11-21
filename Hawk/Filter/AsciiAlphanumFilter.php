<?php
namespace Hawk\Filter;

/**
 *
 */
class AsciiAlphanumFilter implements FilterInterface
{
	/**
	 * [filter description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function filter($data)
	{
		return preg_replace('/[^a-zA-Z0-9]/', '', $data);
	}
}