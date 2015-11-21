<?php
namespace Hawk\Filter;

/**
 *
 */
class UnicodeAlphaFilter implements FilterInterface
{
	/**
	 * [filter description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function filter($data)
	{
		return preg_replace('/[^\p{L}]/u', '', $data);
	}
}