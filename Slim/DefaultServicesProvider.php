<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use Interop\Container\ContainerInterface;
use Slim\Handlers\Error;
use Slim\Handlers\NotFound;
use Slim\Handlers\NotAllowed;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Slim's default Service Provider.
 */
class DefaultServicesProvider
{
    /**
     * Default settings
     *
     * @var array
     */
    protected static $defaultSettings = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => false,
    ];

    /**
     * Get Slim's default services.
     *
     * @param array $userSettings Associative array of application settings
     *
     * @return Callable[]
     */
    public static function getDefaultServices(array $userSettings = [])
    {
        $defaultSettings = self::$defaultSettings;

        return [
            'settings' => function () use ($defaultSettings, $userSettings) {
                return new Collection(array_merge($defaultSettings, $userSettings));
            },

            'environment' => function () {
                return new Environment($_SERVER);
            },

            'request' => function (ContainerInterface $container) {
                return Request::createFromEnvironment($container->get('environment'));
            },

            'response' => function (ContainerInterface $container) {
                $headers = new Headers(['Content-Type' => 'text/html; charset=utf-8']);
                $response = new Response(200, $headers);

                return $response->withProtocolVersion($container->get('settings')['httpVersion']);
            },

            'router' => function () {
                return new Router;
            },

            'foundHandler' => function () {
                return new RequestResponse;
            },

            'errorHandler' => function (ContainerInterface $container) {
                return new Error($container->get('settings')['displayErrorDetails']);
            },

            'notFoundHandler' => function () {
                return new NotFound;
            },

            'notAllowedHandler' => function () {
                return new NotAllowed;
            },

            'callableResolver' => function (ContainerInterface $container) {
                return new CallableResolver($container);
            },
        ];
    }
}
