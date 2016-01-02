<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Stack\Mocks;

class BadHttpException extends \Exception
{
    public function getRequest()
    {
        return 'request';
    }

    public function getResponse()
    {
        return 'false';
    }
}
