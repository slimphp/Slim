<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use FastRoute\Dispatcher;
use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use FastRoute\DataGenerator;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Interfaces\RouteInterface;

/**
 * CorsRouter
 */
class CorsRouter extends Router
{
    /**
     * @var array
     */
    protected $patternList;

    /**
     * CorsRouter constructor.
     * @param RouteParser|null $parser
     */
    public function __construct(RouteParser $parser = null)
    {
        $this->patternList = [];

        parent::__construct($parser);
    }

    /**
     * @param string[] $methods
     * @param string $pattern
     * @param callable $handler
     *
     * @return RouteInterface
     */
    public function map($methods, $pattern, $handler)
    {
        if (!isset($this->patternList[$pattern])) {
            $this->patternList[$pattern] = [];
        }
        $this->patternList[$pattern] = array_merge($this->patternList[$pattern], $methods);
        
        return parent::map($methods, $pattern, $handler);
    }

    /**
     * @param $pattern
     * @return mixed
     */
    public function getMethods($pattern) {
        return $this->patternList[$pattern];
    }
}
