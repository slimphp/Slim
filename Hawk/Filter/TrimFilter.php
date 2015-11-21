<?php
namespace Hawk\Filter;

/**
 *
 */
class TrimFilter implements FilterInterface
{
	/**
	 * [filter description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function filter($data)
	{
		return trim($data);
	}
}