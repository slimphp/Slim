<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Exception;

/**
 * Pass Exception
 *
 * This Exception will cause the Router::dispatch method
 * to skip the current matching route and continue to the next
 * matching route. If no subsequent routes are found, a
 * HTTP 404 Not Found response will be sent to the client.
 */
class Pass extends \Exception
{
}
