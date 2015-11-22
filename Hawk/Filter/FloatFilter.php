<?php
namespace Hawk\Filter;

/**
 *
 */
class FloatFilter implements FilterInterface
{
	/**
	 * [filter description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function filter($data)
	{
		return preg_replace('/[^eE\d.+-]/', '', $data);
	}
}
