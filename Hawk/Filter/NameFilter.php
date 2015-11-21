<?php
namespace Hawk\Filter;

/**
 *
 */
class NameFilter implements FilterInterface
{
	/**
	 * [filter description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function filter($data)
	{
		return preg_replace('/[^\p{L} ]/u', '', $data); // /u allows unicode characters, e.g. á, ẽ, õ, etc.
	}
}