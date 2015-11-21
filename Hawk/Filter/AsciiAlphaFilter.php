<?php
namespace Hawk\Filter;

/**
 *
 */
class AsciiAlphaFilter implements FilterInterface
{
	/**
	 * [filter description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function filter($data)
	{
		return preg_replace('/[^a-zA-Z]/', '', $data);
	}
}