<?php
namespace Hawk\Filter;

/**
 *
 */
interface FilterInterface
{
	/**
	 * [filter description]
	 * @param  string $data data to be filtered
	 * @return string       filtered data
	 */
	public function filter($data);
}