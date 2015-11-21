<?php
namespace Hawk\Filter;

/**
 *
 */
class UrlFilter implements FilterInterface
{
	/**
	 * [filter description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function filter($data)
	{
		return filter_var($data, FILTER_SANITIZE_URL);
	}
}